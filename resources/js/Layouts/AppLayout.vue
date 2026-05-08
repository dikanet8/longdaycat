<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const isSidebarOpen = ref(true);
const isDark = ref(localStorage.getItem('theme') !== 'light');
const showToast = ref(false);
const toastMessage = ref('');
const isLoading = ref(false);
const isUserDropdownOpen = ref(false);

let loadingTimeout = null;
router.on('start', () => {
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

const page = usePage();
const flash = computed(() => page.props.flash);

watch(flash, (newVal) => {
    const message = newVal?.message || newVal?.success || newVal?.error;
    if (message) {
        toastMessage.value = message;
        showToast.value = true;
        setTimeout(() => {
            showToast.value = false;
        }, 3000);
    }
}, { deep: true, immediate: true });

const toggleTheme = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
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
});

watch(isDark, () => {
    updateTheme();
});

const logout = () => {
    router.post('/logout');
};

const menuGroups = [
    {
        title: 'Utama',
        items: [
            { name: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', href: '/dashboard' },
            { name: 'POS (Kasir)', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', href: '/pos' },
            { name: 'Transaksi', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', href: '/transactions' },
        ]
    },
    {
        title: 'Inventori',
        items: [
            { name: 'Produk', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', href: '/products' },
            { name: 'Manajemen Stok', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', href: '/stock' },
        ]
    },
    {
        title: 'Laporan',
        items: [
            { name: 'Laporan Penjualan', icon: 'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z', href: '/reports/sales' },
            { name: 'Riwayat Bayar', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', href: '/payments' },
            { name: 'Rekomendasi Stok', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', href: '/reports/stock-recommendations' },
        ]
    },
    {
        title: 'Pengaturan',
        items: [
            { name: 'Manajemen Akun', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', href: '/users' },
            { name: 'Log Aktivitas', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', href: '/settings/activity-log' },
        ]
    }
];

const navItems = [
    { name: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', href: '/dashboard' },
    { name: 'Stok', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', href: '/stock' },
    { name: 'Kasir', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', href: '/pos', isFab: true },
    { name: 'Produk', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', href: '/products' },
    { name: 'Laporan', icon: 'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z', href: '/reports/sales' },
];
</script>

<template>
    <div class="h-screen bg-slate-50 dark:bg-[#0f172a] text-slate-700 dark:text-slate-200 font-sans flex overflow-hidden transition-colors duration-200">
        
        <!-- SIDEBAR (Desktop) -->
        <aside 
            :class="[
                'hidden md:flex flex-col bg-white dark:bg-slate-900 transition-all duration-200 ease-in-out z-30 shadow-xl dark:shadow-none h-screen flex-shrink-0 border-r border-slate-200 dark:border-white/5',
                isSidebarOpen ? 'w-72' : 'w-20'
            ]"
        >
            <div class="p-6 flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <span class="text-white font-bold italic text-xl">L</span>
                </div>
                <span v-if="isSidebarOpen" class="font-bold text-xl tracking-tight text-slate-800 dark:text-white">Longdaycat.Co</span>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto custom-scrollbar">
                <div v-for="group in menuGroups" :key="group.title" class="space-y-2">
                    <!-- Group Title -->
                    <p v-if="isSidebarOpen" class="px-4 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400 mb-2">
                        {{ group.title }}
                    </p>
                    <div v-else class="h-4 border-b border-slate-100 dark:border-white/5 mx-4 mb-2"></div>

                    <!-- Group Items -->
                    <div class="space-y-1">
                        <Link 
                            v-for="item in group.items" 
                            :key="item.name"
                            :href="item.href"
                            class="flex items-center gap-4 px-4 py-2 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 hover:text-slate-900 dark:hover:text-white transition-all group w-full relative overflow-hidden"
                            :class="{ 'bg-blue-600/10 text-blue-600 dark:text-blue-400 shadow-sm': $page.url === item.href || (item.href !== '/dashboard' && item.href !== '#' && $page.url.startsWith(item.href)) }"
                        >
                            <!-- Active Indicator (Filament Style) -->
                            <div v-if="$page.url === item.href || (item.href !== '/dashboard' && item.href !== '#' && $page.url.startsWith(item.href))" class="absolute left-0 top-2 bottom-2 w-1 bg-blue-600 rounded-r-full"></div>
                            
                            <svg class="w-6 h-6 flex-shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span v-if="isSidebarOpen" class="font-bold text-sm tracking-wide">{{ item.name }}</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="p-4 border-t border-slate-100 dark:border-white/5">
                <button 
                    @click="logout"
                    class="w-full flex items-center gap-4 px-4 py-2 rounded-xl hover:bg-red-500/10 hover:text-red-500 transition-all text-slate-500"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span v-if="isSidebarOpen" class="font-medium">Logout</span>
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col relative overflow-hidden">
            
            <!-- NAVBAR -->
            <header class="h-16 bg-white/90 dark:bg-slate-900/70 backdrop-blur-lg flex items-center justify-between px-6 z-20 border-b border-slate-100 dark:border-white/5">
                <div class="flex items-center gap-4">
                    <button @click="isSidebarOpen = !isSidebarOpen" class="hidden md:block text-slate-400 hover:text-slate-900 dark:hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h2 class="text-lg font-semibold text-slate-800 dark:text-white md:block hidden">Dashboard</h2>
                    <!-- Logo for mobile -->
                    <div class="md:hidden flex items-center gap-2">
                        <div class="w-7 h-7 bg-blue-600 rounded flex items-center justify-center">
                            <span class="text-white font-bold italic text-sm">L</span>
                        </div>
                        <span class="font-bold text-lg text-slate-800 dark:text-white">LongDay</span>
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

                    <button class="p-2 text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-full hover:bg-slate-100 dark:hover:bg-white/5 transition-all relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="relative">
                        <button 
                            @click="isUserDropdownOpen = !isUserDropdownOpen"
                            class="flex items-center gap-3 pl-4 border-l border-slate-200 dark:border-white/5 hover:opacity-80 transition-all"
                        >
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-semibold text-slate-800 dark:text-white leading-none mb-1">{{ $page.props.auth.user.name }}</p>
                                <p class="text-xs text-slate-500 leading-none">Administrator</p>
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
                                    <p class="text-xs text-slate-500">Administrator</p>
                                </div>
                                <Link href="/profile" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-blue-600 dark:hover:text-blue-400 transition-all font-medium">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Profil Saya
                                </Link>
                                <Link href="/profile" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-blue-600 dark:hover:text-blue-400 transition-all font-medium">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Pengaturan Akun
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
            <main class="flex-1 overflow-y-auto p-6 pb-24 md:pb-6 relative">
                <!-- Content slot -->
                <slot />
                
                <!-- Background glow -->
                <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/[0.03] rounded-full blur-[120px] pointer-events-none z-0"></div>
            </main>

            <!-- BOTTOM NAVBAR (Mobile Only) -->
            <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200 dark:border-white/5 flex items-center justify-around px-2 z-40">
                <template v-for="item in navItems" :key="'mobile-'+item.name">
                    <!-- FAB Item (Kasir) -->
                    <Link 
                        v-if="item.isFab"
                        :href="item.href"
                        class="flex flex-col items-center justify-center -translate-y-6 w-16 h-16 bg-blue-600 rounded-full shadow-2xl shadow-blue-600/40 border-4 border-slate-50 dark:border-[#0f172a] transition-all active:scale-95"
                    >
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                    </Link>

                    <!-- Normal Items -->
                    <Link 
                        v-else
                        :href="item.href"
                        class="flex flex-col items-center justify-center flex-1 h-full transition-all"
                        :class="($page.url === item.href || (item.href !== '/dashboard' && $page.url.startsWith(item.href))) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500'"
                    >
                        <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        <span class="text-[11px] font-bold capitalize tracking-wider">{{ item.name }}</span>
                    </Link>
                </template>
            </nav>

            <!-- TOAST NOTIFICATION -->
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showToast" class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white dark:bg-slate-800 shadow-2xl rounded-2xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 w-0 flex-1 pt-0.5">
                                <p class="text-sm font-bold text-slate-900 dark:text-white">Notifikasi</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ toastMessage }}</p>
                            </div>
                            <div class="ml-4 flex-shrink-0 flex">
                                <button @click="showToast = false" class="bg-transparent rounded-md inline-flex text-slate-400 hover:text-slate-500 focus:outline-none">
                                    <span class="sr-only">Close</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

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
