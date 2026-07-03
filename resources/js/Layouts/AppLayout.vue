<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from'vue';
import { Link, router, usePage } from'@inertiajs/vue3';
import axios from 'axios';

const isSidebarOpen = ref(typeof window !=='undefined' ? window.innerWidth >= 768 : true);
const isDark = ref(localStorage.getItem('theme') !=='light');
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
const isLoading = ref(false);
const isUserDropdownOpen = ref(false);
const userDropdownRef = ref(null);
const isNotificationsOpen = ref(false);
const notificationsRef = ref(null);

// Push Notification
const pushSupported = ref(false);
const pushPermission = ref('default'); //'default' |'granted' |'denied'
const isPushSubscribed = ref(false);

const handleClickOutside = (event) => {
  if (userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
    isUserDropdownOpen.value = false;
  }
  if (notificationsRef.value && !notificationsRef.value.contains(event.target)) {
    isNotificationsOpen.value = false;
  }
};

const formatTimeAgo = (dateStr) => {
  if (!dateStr) return'';
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMins / 60);
  const diffDays = Math.floor(diffHours / 24);

  if (diffMins < 1) return'Baru saja';
  if (diffMins < 60) return `${diffMins} m lalu`;
  if (diffHours < 24) return `${diffHours} j lalu`;
  return `${diffDays} hari lalu`;
};

const handleNotificationClick = (notification) => {
  isNotificationsOpen.value = false;
  router.post(route('notifications.read', notification.id), {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      if (notification.data.url) {
        router.visit(notification.data.url);
      }
    }
  });
};

const markAllNotificationsRead = () => {
  const page = usePage();
  
  // Optimistic UI update
  if (page.props.auth && page.props.auth.notifications) {
    page.props.auth.unread_notifications_count = 0;
    page.props.auth.notifications.forEach(n => {
      if (!n.read_at) n.read_at = new Date().toISOString();
    });
  }

  // Silent background request
  axios.post(route('notifications.read-all')).catch(err => {
    console.error('Gagal menandai notifikasi dibaca', err);
  });
};

let loadingTimeout = null;
router.on('start', (event) => {
  if (event.detail.visit.url.pathname.startsWith('/notifications')) return;
  
  // Seamless requests (like pagination or filtering) shouldn't trigger the global spinner
  const isGet = event.detail.visit.method === 'get';
  const preservesState = event.detail.visit.preserveState === true || event.detail.visit.preserveScroll === true;
  
  if (isGet && preservesState) return;
  
  loadingTimeout = setTimeout(() => {
    isLoading.value = true;
  }, 250);
});
router.on('finish', () => {
  if (loadingTimeout) clearTimeout(loadingTimeout);
  isLoading.value = false;
});
router.on('error', () => {
  if (loadingTimeout) clearTimeout(loadingTimeout);
  isLoading.value = false;
});

let toastTimeout = null;

const page = usePage();
const flash = computed(() => page.props.flash);

watch(flash, (newVal) => {
  const message = newVal?.message || newVal?.success || newVal?.error;
  if (message) {
    toastMessage.value = message;
    toastType.value = newVal?.error ? 'error' : 'success';
    showToast.value = true;
    
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
      showToast.value = false;
    }, 3000);
  }
}, { deep: true, immediate: true });
watch(() => page.url, () => {
  if (window.innerWidth < 768) {
    isSidebarOpen.value = false;
  }
  isUserDropdownOpen.value = false;
  isNotificationsOpen.value = false;
  showToast.value = false;
  if (toastTimeout) {
    clearTimeout(toastTimeout);
    toastTimeout = null;
  }
});

const toggleTheme = () => {
  isDark.value = !isDark.value;
  localStorage.setItem('theme', isDark.value ?'dark' :'light');
};

const updateTheme = () => {
  if (isDark.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

onMounted(() => {
  updateTheme();
  document.addEventListener('click', handleClickOutside);
  initPushNotification();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

watch(isDark, () => {
  updateTheme();
});

const logout = () => {
  router.post('/logout');
};

// ─── Web Push Notification ────────────────────────────────────────────────

function urlBase64ToUint8Array(base64String) {
  const padding ='='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g,'+').replace(/_/g,'/');
  const rawData = window.atob(base64);
  return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}

async function initPushNotification() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    pushSupported.value = false;
    return;
  }
  pushSupported.value = true;
  pushPermission.value = Notification.permission;

  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    isPushSubscribed.value = !!subscription;
  } catch (e) {
    console.warn('Push init error:', e);
  }
}

async function subscribePush() {
  try {
    pushPermission.value = await Notification.requestPermission();
    if (pushPermission.value !=='granted') return;

    const registration = await navigator.serviceWorker.ready;

    // Ambil VAPID public key dari server
    const res = await fetch('/push/vapid-public-key', {
      headers: {'X-Requested-With':'XMLHttpRequest' }
    });
    const { publicKey } = await res.json();

    const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(publicKey),
    });

    const subJson = subscription.toJSON();

    // Kirim subscription ke server menggunakan axios (otomatis menyertakan CSRF token)
    await axios.post('/push/subscribe', {
      endpoint: subJson.endpoint,
      p256dh: subJson.keys.p256dh,
      auth: subJson.keys.auth,
    });

    isPushSubscribed.value = true;
    showToast.value = true;
    toastType.value = 'success';
    toastMessage.value = 'Notifikasi diaktifkan!';
  } catch (e) {
    console.error('Push subscribe error:', e);
    showToast.value = true;
    toastType.value = 'error';
    toastMessage.value = 'Gagal mengaktifkan notif: ' + (e.response?.data?.message || e.message || String(e));
  }
}

async function unsubscribePush() {
  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    if (!subscription) return;

    const endpoint = subscription.endpoint;
    await subscription.unsubscribe();

    await axios.delete('/push/unsubscribe', {
      data: { endpoint }
    });

    isPushSubscribed.value = false;
  } catch (e) {
    console.error('Push unsubscribe error:', e);
  }
}

// ─── Local Desktop Notification ───────────────────────────────────────────

const tampilkanNotifikasiLokal = (judul, pesan, url = null) => {
  if (!("Notification" in window)) {
    console.warn("Browser Anda tidak mendukung notifikasi desktop.");
    return;
  }

  if (Notification.permission === "granted") {
    eksekusiNotifikasi(judul, pesan, url);
  } else if (Notification.permission !== "denied") {
    Notification.requestPermission().then(function (permission) {
      if (permission === "granted") {
        eksekusiNotifikasi(judul, pesan, url);
      }
    });
  }
};

const eksekusiNotifikasi = (judul, pesan, url) => {
  const options = {
    body: pesan,
    icon: '/logo.png',
    vibrate: [200, 100, 200],
  };

  const infoNotif = new Notification(judul, options);
  infoNotif.onclick = function() {
    window.focus();
    if (url) {
      router.visit(url);
    }
    infoNotif.close();
  };
};

// Automatically show local notification when a new unread notification arrives via Inertia props
watch(() => page.props.auth?.unread_notifications_count, (newCount, oldCount) => {
  if (newCount > (oldCount || 0)) {
    const latestNotif = page.props.auth.notifications?.[0];
    if (latestNotif && !latestNotif.read_at) {
      tampilkanNotifikasiLokal(latestNotif.data.title, latestNotif.data.message, latestNotif.data.url);
    }
  }
});

const menuGroups = [
  {
    title:'Utama',
    items: [
      { name:'Dashboard', icon:'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', href:'/dashboard', roles: ['owner','kasir'] },
      { name: 'POS (Kasir)', icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', href: '/pos', roles: ['owner', 'kasir'] },
      { name:'Transaksi', icon:'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', href:'/transactions', roles: ['owner','kasir'] },
    ]
  },
  {
    title:'Inventori',
    roles: ['owner'],
    items: [
      { name:'Kategori', icon:'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', href:'/categories' },
      { name:'Produk', icon:'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', href:'/products' },
      { name:'Barcode', icon:'M12 4v16m-4-16v16m8-16v16M4 4v16m16-16v16', href:'/barcodes' },
      { name:'Manajemen Stok', icon:'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', href:'/stock' },
    ]
  },
  {
    title:'Laporan',
    items: [
      { name:'Laporan Penjualan', icon:'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6', href:'/reports/sales', roles: ['owner'] },
      { name:'Rekomendasi Restok', icon:'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', href:'/reports/stock-recommendations', roles: ['owner'] },
    ]
  },
  {
    title:'Pengaturan',
    roles: ['owner'],
    items: [
      { name:'Pengaturan Sistem', icon:'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', href:'/settings/system' },
      { name:'Manajemen Akun', icon:'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', href:'/users' },
      { name:'Log Aktivitas', icon:'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', href:'/settings/activity-log' },
    ]
  }
];

const filteredMenuGroups = computed(() => {
  const userRole = page.props.auth.user.role;
  return menuGroups
    .filter(group => !group.roles || group.roles.includes(userRole))
    .map(group => ({
      ...group,
      items: group.items.filter(item => !item.roles || item.roles.includes(userRole))
    }))
    .filter(group => group.items.length > 0);
});

const navItems = [
  { name:'Dashboard', icon:'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', href:'/dashboard', roles: ['owner','kasir'] },
  { name:'Stok', icon:'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', href:'/stock', roles: ['owner'] },
  { name: 'Kasir', icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', href: '/pos', isFab: true, roles: ['owner', 'kasir'] },
  { name:'Produk', icon:'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', href:'/products', roles: ['owner'] },
  { name:'Laporan', icon:'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z', href:'/reports/sales', roles: ['owner'] },
];

const filteredNavItems = computed(() => {
  const userRole = page.props.auth.user.role;
  return navItems.filter(item => !item.roles || item.roles.includes(userRole));
});

const pageTitle = computed(() => {
  const currentPath = page.url.split('?')[0];
  
  if (currentPath.startsWith('/payments/')) {
    return 'Detail Pembayaran';
  }
  
  for (const group of menuGroups) {
    if (group.items) {
      for (const item of group.items) {
        if (currentPath === item.href || (item.href !== '/dashboard' && item.href !== '#' && currentPath.startsWith(item.href))) {
          return item.name;
        }
      }
    }
  }
  return 'Dashboard';
});
</script>

<template>
  <div class="h-[100dvh] bg-slate-50 dark:bg-[#0f172a] text-slate-700 dark:text-slate-200 font-sans flex overflow-hidden transition-colors duration-200">
    
    <!-- Mobile Sidebar Overlay Backdrop -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false" 
      class="md:hidden fixed inset-0 bg-slate-900/60 dark:bg-black/70 z-40 transition-opacity"
    ></div>

    <!-- SIDEBAR -->
    <aside 
      :class="[
       'flex flex-col bg-white dark:bg-slate-900 transition-all duration-300 ease-in-out z-50 dark:shadow-none h-screen flex-shrink-0 border-r border-slate-200 dark:border-white/5',
       'fixed md:relative top-0 left-0 bottom-0',
        isSidebarOpen ?'w-72 translate-x-0' :'w-72 md:w-20 -translate-x-full md:translate-x-0'
      ]"
    >
      <div class="h-16 px-6 flex items-center gap-3 border-b border-slate-100 dark:border-white/5 shrink-0">
        <img src="/logo.png" alt="Logo" class="w-8 h-8 object-contain shrink-0" />
        <span v-if="isSidebarOpen" class="font-bold text-xl tracking-tight text-slate-800 dark:text-white truncate">Longdaycat.Co</span>
      </div>

      <nav :class="['flex-1 px-3 py-4 overflow-y-auto custom-scrollbar pb-20 md:pb-4', isSidebarOpen ? 'space-y-5' : 'space-y-2']">
        <div v-for="group in filteredMenuGroups" :key="group.title" class="space-y-2">
          <!-- Group Title -->
          <p v-if="isSidebarOpen" class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400 mb-2">
            {{ group.title }}
          </p>
          <div v-else class="border-b border-slate-100 dark:border-white/5 mx-3 mb-2"></div>

          <!-- Group Items -->
          <div class="space-y-1">
            <Link 
              v-for="item in group.items" 
              :key="item.name"
              :href="item.href"
              :class="[
                'flex items-center rounded-md hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white transition-all w-full relative group',
                isSidebarOpen ? 'gap-3 px-3 py-2.5 justify-start' : 'justify-center py-2.5',
                {'bg-blue-600/10 text-blue-600 dark:text-blue-400 shadow-sm': $page.url === item.href || (item.href !=='/dashboard' && item.href !=='#' && $page.url.startsWith(item.href)),
                 'text-slate-500 dark:text-slate-400': !($page.url === item.href || (item.href !=='/dashboard' && item.href !=='#' && $page.url.startsWith(item.href))) }
              ]"
              :title="!isSidebarOpen ? item.name : ''"
            >
              <svg class="w-5 h-5 flex-shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
              </svg>
              <span v-if="isSidebarOpen" class="font-bold text-sm tracking-wide">{{ item.name }}</span>
            </Link>
          </div>
        </div>
      </nav>

    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col relative overflow-hidden">
      
      <!-- NAVBAR -->
      <header class="h-16 bg-white dark:bg-slate-900 flex items-center justify-between px-4 sm:px-6 z-20 border-b border-slate-100 dark:border-white/5">
        <div class="flex items-center gap-3 sm:gap-4">
          <button @click="isSidebarOpen = !isSidebarOpen" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <h2 class="text-lg font-semibold text-slate-800 dark:text-white md:block hidden">{{ pageTitle }}</h2>
          <!-- Logo for mobile -->
          <div class="md:hidden flex items-center gap-2">
            <img src="/logo.png" alt="Logo" class="w-8 h-8 object-contain shrink-0" />
            <span class="font-black tracking-tight text-lg text-slate-800 dark:text-white">{{ pageTitle }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-4">
          <!-- THEME TOGGLE -->
          <button 
            @click="toggleTheme" 
            class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 transition-all"
          >
            <svg v-if="isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <!-- Notification Bell Dropdown -->
          <div class="relative" ref="notificationsRef">
            <button 
              @click="isNotificationsOpen = !isNotificationsOpen"
              class="p-2 text-slate-400 hover:text-slate-950 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 transition-all relative flex items-center justify-center"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span v-if="$page.props.auth.unread_notifications_count > 0" class="absolute top-1.5 right-1.5 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[9px] font-black text-white items-center justify-center">
                  {{ $page.props.auth.unread_notifications_count }}
                </span>
              </span>
            </button>

            <!-- Dropdown Panel -->
            <div v-if="isNotificationsOpen" class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-white/5 py-2 z-50 overflow-hidden no-print">
              <!-- Dropdown Header -->
              <div class="px-4 py-3 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">Notifikasi</h4>
                  <button 
                    v-if="pushSupported && pushPermission !=='denied'"
                    @click.stop="isPushSubscribed ? unsubscribePush() : subscribePush()"
                    :title="isPushSubscribed ?'Matikan Notifikasi Chrome' :'Aktifkan Notifikasi Chrome'"
                    :class="[
                     'p-1 rounded-md transition-all flex items-center gap-1 text-[9px] font-bold uppercase tracking-widest',
                      isPushSubscribed ?'text-blue-600 bg-blue-50 dark:bg-blue-500/10' :'text-slate-400 bg-slate-100 dark:bg-white/5 hover:text-slate-800 dark:hover:text-white'
                    ]"
                  >
                    <svg v-if="isPushSubscribed" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6V11c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span>Chrome</span>
                  </button>
                </div>
                <button 
                  v-if="$page.props.auth.unread_notifications_count > 0"
                  @click="markAllNotificationsRead" 
                  class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-bold"
                >
                  Tandai semua dibaca
                </button>
              </div>

              <!-- Notification List -->
              <div class="max-h-[360px] overflow-y-auto divide-y divide-slate-100 dark:divide-white/5" style="scrollbar-width: thin;">
                <div v-if="!$page.props.auth.notifications || $page.props.auth.notifications.length === 0" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                  <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                  <p class="text-xs font-bold">Tidak ada notifikasi baru</p>
                </div>

                <button
                  v-for="item in $page.props.auth.notifications"
                  :key="item.id"
                  @click="handleNotificationClick(item)"
                  :class="[
                   'w-full text-left px-4 py-3 flex items-start gap-3 transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.02]',
                    !item.read_at ?'bg-blue-500/[0.03] dark:bg-blue-500/[0.02]' :''
                  ]"
                >
                  <!-- Icon -->
                  <div 
                    :class="[
                     'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 border',
                      item.data.type ==='transaksi' 
                        ?'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400'
                        :'bg-amber-50 dark:bg-amber-500/10 border-amber-100 dark:border-amber-500/20 text-amber-600 dark:text-amber-400'
                    ]"
                  >
                    <!-- Transaksi Icon -->
                    <svg v-if="item.data.type ==='transaksi'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <!-- Stok Icon -->
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <p class="text-[11px] font-black text-slate-800 dark:text-white capitalize text-left">{{ item.data.title }}</p>
                      <span class="text-[9px] text-slate-400 dark:text-slate-500 text-right">{{ formatTimeAgo(item.created_at) }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-tight text-left">{{ item.data.message }}</p>
                  </div>
                  <!-- Unread Dot -->
                  <div v-if="!item.read_at" class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                </button>
              </div>
            </div>
          </div>
          <div class="relative" ref="userDropdownRef">
            <button 
              @click="isUserDropdownOpen = !isUserDropdownOpen"
              class="flex items-center gap-3 pl-4 border-l border-slate-200 dark:border-white/5 hover:opacity-80 transition-all"
            >
              <div class="text-right hidden sm:block">
                <p class="text-sm font-semibold text-slate-800 dark:text-white leading-none mb-1">{{ $page.props.auth.user.name }}</p>
                <p class="text-xs text-slate-500 leading-none capitalize">{{ $page.props.auth.user.role }}</p>
              </div>
              <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-purple-600 rounded-full flex items-center justify-center font-bold text-white shadow-lg relative">
                {{ $page.props.auth.user.name.charAt(0) }}
                <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></div>
              </div>
            </button>

            <!-- User Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div v-if="isUserDropdownOpen" class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-white/5 py-2 z-50">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-white/5 md:hidden">
                  <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $page.props.auth.user.name }}</p>
                  <p class="text-xs text-slate-500 capitalize">{{ $page.props.auth.user.role }}</p>
                </div>
                <Link href="/profile" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-blue-600 dark:hover:text-blue-400 transition-all font-medium">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Profil Saya
                </Link>
                <div class="border-t border-slate-100 dark:border-white/5 mt-2 pt-2">
                  <button @click="logout" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all font-bold w-full text-left">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <!-- PAGE CONTENT -->
      <main class="flex-1 overflow-y-auto p-6 pb-[calc(5rem+env(safe-area-inset-bottom))] md:pb-6 relative">
        <!-- NOTIFICATION BANNER (Floating Toast) -->
        <Transition
          enter-active-class="transform ease-out duration-300 transition"
          enter-from-class="-translate-y-4 opacity-0 md:translate-y-0 md:translate-x-8"
          enter-to-class="translate-y-0 translate-x-0 opacity-100"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 translate-x-0"
          leave-to-class="-translate-y-4 opacity-0 md:translate-y-0 md:translate-x-8"
        >
          <div v-if="showToast" :class="[
           'fixed z-[100] p-4 rounded-xl flex items-center justify-between border border-l-4 transition-all no-print shadow-2xl',
           'top-20 left-4 right-4 md:left-auto md:right-6 md:w-96',
            toastType ==='error'
              ?'bg-red-50/95 backdrop-blur-sm dark:bg-red-950/90 border-red-200 dark:border-red-900/30 border-l-red-500 text-red-800 dark:text-red-300'
              :'bg-emerald-50/95 backdrop-blur-sm dark:bg-emerald-950/90 border-emerald-200 dark:border-emerald-900/30 border-l-emerald-500 text-emerald-800 dark:text-emerald-300'
          ]">
            <div class="flex items-center gap-3">
              <div :class="[
               'w-8 h-8 rounded-lg flex items-center justify-center shrink-0',
                toastType ==='error' ?'bg-red-500/10 text-red-500' :'bg-emerald-500/10 text-emerald-500'
              ]">
                <svg v-if="toastType !=='error'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
              </div>
              <div>
                <p class="text-[9px] font-black uppercase tracking-widest opacity-60">
                  {{ toastType ==='error' ?'Gagal' :'Sukses' }}
                </p>
                <p class="text-xs font-bold leading-tight">{{ toastMessage }}</p>
              </div>
            </div>
            <button @click="showToast = false" :class="[
             'p-1.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition-all shrink-0',
              toastType ==='error' ?'text-red-500/60 hover:text-red-500' :'text-emerald-500/60 hover:text-emerald-500'
            ]">
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </Transition>

        <!-- Content slot -->
        <slot />
        

      </main>

      <!-- BOTTOM NAVBAR (Mobile Only) -->
      <nav class="md:hidden fixed bottom-0 left-0 right-0 h-[calc(4rem+env(safe-area-inset-bottom))] pb-[env(safe-area-inset-bottom)] bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-white/5 flex items-center justify-around px-2 z-[60]">
        <template v-for="item in filteredNavItems" :key="'mobile-'+item.name">
          <!-- FAB Item (Kasir) -->
          <Link 
            v-if="item.isFab"
            :href="item.href"
            class="flex flex-col items-center justify-center -translate-y-6 w-16 h-16 bg-blue-600 rounded-full shadow-2xl shadow-blue-600/40 border-4 border-slate-50 dark:border-[#0f172a] transition-all active:scale-95"
          >
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
            </svg>
            <span class="text-[9px] font-black text-white uppercase tracking-widest mt-0.5">POS</span>
          </Link>

          <!-- Normal Items -->
          <Link 
            v-else
            :href="item.href"
            class="flex flex-col items-center justify-center flex-1 h-full transition-all"
            :class="($page.url === item.href || (item.href !=='/dashboard' && $page.url.startsWith(item.href))) ?'text-blue-600 dark:text-blue-400' :'text-slate-400 dark:text-slate-500'"
          >
            <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
            </svg>
            <span class="text-[11px] font-bold capitalize tracking-wider">{{ item.name }}</span>
          </Link>
        </template>
      </nav>



      <!-- LOADING SPINNER (Circle) -->
      <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="isLoading" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/20 dark:bg-black/40">
          <div class="relative w-12 h-12">
            <!-- Outer ring -->
            <div class="absolute inset-0 border-4 border-slate-200 dark:border-white/10 rounded-full"></div>
            <!-- Spinning ring -->
            <div class="absolute inset-0 border-4 border-blue-600 rounded-full border-t-transparent animate-spin"></div>
          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style>
/* Custom scrollbar for main area */
main::-webkit-scrollbar {
 width: 6px;
}
main::-webkit-scrollbar-track {
 background: transparent;
}
main::-webkit-scrollbar-thumb {
 background: rgba(0, 0, 0, 0.05);
 border-radius: 10px;
}
.dark main::-webkit-scrollbar-thumb {
 background: rgba(255, 255, 255, 0.05);
}
main::-webkit-scrollbar-thumb:hover {
 background: rgba(0, 0, 0, 0.1);
}
.dark main::-webkit-scrollbar-thumb:hover {
 background: rgba(255, 255, 255, 0.1);
}
</style>
