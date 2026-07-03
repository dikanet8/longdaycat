<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, useForm, router } from'@inertiajs/vue3';
import { ref, computed, watch } from'vue';
import Pagination from'@/Components/Pagination.vue';

const props = defineProps({
  products: Object,
  history: Object,
  users: Array,
  filters: Object
});

const perPage = ref(props.filters?.per_page || 10);

watch(perPage, (value) => {
  router.get(route('stock.index'), { per_page: value }, { preserveState: true, replace: true });
});

const search = ref(new URLSearchParams(window.location.search).get('search') ||'');
const showUpdateModal = ref(false);
const selectedProduct = ref(null);
const activeTab = ref('products'); //'products' or'history'

// History Filters
const historySearch = ref('');
const historyDate = ref('');
const historyActivity = ref('');
const historyUser = ref('');

const form = useForm({
  kode_produk:'',
  jumlah: 1,
  jenis_aktivitas:'masuk',
  keterangan:''
});

const filteredProducts = computed(() => {
  return props.products.data.filter(p => 
    p.nama_produk.toLowerCase().includes(search.value.toLowerCase()) ||
    p.kode_produk.toLowerCase().includes(search.value.toLowerCase())
  );
});

const filteredHistory = computed(() => {
  return props.history.data.filter(log => {
    const matchesSearch = log.produk?.nama_produk?.toLowerCase().includes(historySearch.value.toLowerCase()) ||
              log.kode_produk.toLowerCase().includes(historySearch.value.toLowerCase());
    const matchesDate = historyDate.value ? log.created_at.startsWith(historyDate.value) : true;
    const matchesActivity = historyActivity.value ? log.jenis_aktivitas === historyActivity.value : true;
    const matchesUser = historyUser.value ? log.user_id?.toString() === historyUser.value.toString() : true;

    return matchesSearch && matchesDate && matchesActivity && matchesUser;
  });
});

const openUpdateModal = (product) => {
  selectedProduct.value = product;
  form.kode_produk = product.kode_produk;
  form.jumlah = 1;
  form.jenis_aktivitas ='masuk';
  form.keterangan ='';
  showUpdateModal.value = true;
};

const submitUpdate = () => {
  form.post(route('stock.update'), {
    onSuccess: () => {
      showUpdateModal.value = false;
    }
  });
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day:'numeric',
    month:'short',
    year:'numeric',
    hour:'2-digit',
    minute:'2-digit'
  });
};

const resetProductsFilter = () => {
  search.value ='';
};

const resetHistoryFilters = () => {
  historySearch.value = '';
  historyDate.value = '';
  historyActivity.value = '';
  historyUser.value = '';
};

const showMobileFilters = ref(false);
</script>

<template>
  <Head title="Manajemen Stok" />

  <AppLayout>
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Manajemen Stok</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Pantau dan kelola persediaan produk Anda.</p>
        </div>
      </div>

      <div>
        <!-- Tab Navigation (Styled like Barcode toggle buttons) -->
        <div class="flex justify-end w-full mb-3">
          <div class="bg-slate-50 dark:bg-slate-800 p-1.5 rounded-xl border border-slate-200 dark:border-white/5 flex items-center gap-1.5 shadow-sm">
            <button 
              @click="activeTab ='products'"
              :class="[activeTab ==='products' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-white dark:hover:bg-slate-700']"
              class="w-32 sm:w-40 py-2 px-3 text-[10px] sm:text-xs font-black uppercase tracking-widest rounded-lg transition-all cursor-pointer text-center"
            >
              Kelola Produk
            </button>
            <button 
              @click="activeTab ='history'"
              :class="[activeTab ==='history' ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-white dark:hover:bg-slate-700']"
              class="w-32 sm:w-40 py-2 px-3 text-[10px] sm:text-xs font-black uppercase tracking-widest rounded-lg transition-all cursor-pointer text-center"
            >
              Riwayat
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div class="transition-all duration-300">
        <!-- Tab: Kelola Produk -->
        <div v-if="activeTab ==='products'" class="animate-in fade-in slide-in-from-bottom-4 duration-500 space-y-6">
          <!-- Filter Section (Synced) -->
          <div class="bg-white dark:bg-slate-900 p-3 md:p-2 rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
            <div class="flex items-center gap-2.5 w-full shrink-0">
              <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </span>
                <input 
                  v-model="search"
                  type="text" 
                  placeholder="Cari produk..." 
                  class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 pl-10 pr-3 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ring-1 ring-slate-200 dark:ring-white/10"
                />
              </div>
              <button 
                @click="resetProductsFilter"
                class="p-2.5 bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-blue-500 rounded-md transition-all shadow-inner flex-shrink-0 group"
                title="Reset Search"
              >
                <svg class="w-4 h-4 transition-transform duration-500 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Main Table & Card Container -->
          <div class="bg-white dark:bg-slate-900 rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-[0.2em] font-black">
                  <tr>
                    <th class="px-8 py-5">Produk</th>
                    <th class="px-8 py-5 text-center">Stok</th>
                    <th class="px-8 py-5 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                  <template v-if="filteredProducts.length > 0">
                    <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                    <td class="px-8 py-5">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-md bg-slate-100 dark:bg-slate-800 overflow-hidden shadow-inner flex-shrink-0 border border-slate-200 dark:border-white/5">
                          <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover" />
                        </div>
                        <div class="min-w-0">
                          <p class="font-bold text-slate-900 dark:text-white text-sm capitalize truncate">{{ product.nama_produk }}</p>
                          <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-0.5">{{ product.kode_produk }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-8 py-5">
                      <div class="flex flex-col items-center">
                        <span :class="[product.stok <= product.stok_minimal ?'text-rose-500' :'text-slate-900 dark:text-white','text-sm md:text-base font-black']">
                          {{ product.stok }}
                        </span>
                        <span class="text-[9px] text-slate-400 uppercase font-black tracking-widest mt-1">Min: {{ product.stok_minimal }}</span>
                      </div>
                    </td>
                    <td class="px-8 py-5 text-right">
                      <button 
                        @click="openUpdateModal(product)"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-wider rounded-md shadow-lg shadow-blue-500/20 transition-all active:scale-95"
                      >
                        Update
                      </button>
                    </td>
                    </tr>
                  </template>
                  <tr v-else>
                    <td colspan="3" class="px-8 py-20 text-center">
                      <div class="flex flex-col items-center justify-center">
                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                          <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                          </svg>
                        </div>
                        <h3 class="text-base font-black text-slate-800 dark:text-white mb-1">
                          {{ search ? 'Produk Tidak Ditemukan' : 'Belum Ada Produk' }}
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-5">
                          {{ search ? 'Kata kunci pencarian Anda tidak cocok dengan produk manapun.' : 'Anda belum menambahkan produk apapun ke sistem.' }}
                        </p>
                        <button v-if="search" @click="resetProductsFilter" class="px-5 py-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md text-xs font-bold transition-colors">
                          Reset Pencarian
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mobile Card List -->
            <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
              <template v-if="filteredProducts.length > 0">
                <div v-for="product in filteredProducts" :key="'mobile-'+product.id" class="p-5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-12 h-12 rounded-md bg-slate-100 dark:bg-slate-800 overflow-hidden shadow-inner flex-shrink-0 border border-slate-200 dark:border-white/5">
                    <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover" />
                  </div>
                  <div class="min-w-0">
                    <p class="font-black text-slate-900 dark:text-white text-sm capitalize truncate leading-tight">{{ product.nama_produk }}</p>
                    <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-1">{{ product.kode_produk }}</p>
                  </div>
                </div>
                <div class="flex flex-col items-end gap-2 shrink-0">
                  <div class="flex flex-col items-end">
                    <span :class="[product.stok <= product.stok_minimal ?'text-rose-500' :'text-blue-600 dark:text-blue-400','text-lg font-black leading-none']">
                      {{ product.stok }}
                    </span>
                    <span class="text-[9px] text-slate-400 font-bold uppercase">Min: {{ product.stok_minimal }}</span>
                  </div>
                  <button 
                    @click="openUpdateModal(product)"
                    class="px-4 py-2 bg-blue-600 text-white text-[10px] font-black uppercase tracking-wider rounded-md shadow-lg shadow-blue-500/20 active:scale-95 transition-transform"
                  >
                    Update
                  </button>
                </div>
                </div>
              </template>
              <div v-else class="py-16 px-4 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                  </svg>
                </div>
                <h3 class="text-base font-black text-slate-800 dark:text-white mb-1">
                  {{ search ? 'Produk Tidak Ditemukan' : 'Belum Ada Produk' }}
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-5">
                  {{ search ? 'Kata kunci pencarian Anda tidak cocok dengan produk manapun.' : 'Anda belum menambahkan produk apapun ke sistem.' }}
                </p>
                <button v-if="search" @click="resetProductsFilter" class="px-5 py-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md text-xs font-bold transition-colors">
                  Reset Pencarian
                </button>
              </div>
            </div>
            <!-- Pagination & Per Page -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
              <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
                <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
                  Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ products.from }}-{{ products.to }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ products.total }}</span> produk
                </p>
                <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
                  <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
                  <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                    <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
                  </select>
                </div>
              </div>
              <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
                <Pagination :links="products.links" />
              </div>
            </div>
          </div>
        </div>

        <!-- Tab: Riwayat Perubahan -->
        <div v-if="activeTab ==='history'" class="animate-in fade-in slide-in-from-bottom-4 duration-500 space-y-6">
          <!-- Filter Section (Synced) -->
          <div class="bg-white dark:bg-slate-900 p-3 md:p-2 rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-2.5 w-full shrink-0">
              <!-- Search Field & Mobile Filter Toggle -->
              <div class="flex items-center gap-2.5 w-full">
                <div class="relative w-full">
                  <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </span>
                  <input 
                    v-model="historySearch"
                    type="text" 
                    placeholder="Cari produk/kode..." 
                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 pl-10 pr-3 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ring-1 ring-slate-200 dark:ring-white/10"
                  />
                </div>
                
                <!-- Mobile Filter Button -->
                <button 
                  @click="showMobileFilters = !showMobileFilters"
                  :class="[showMobileFilters ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' : 'bg-slate-100 dark:bg-white/5 text-slate-500']"
                  class="md:hidden p-2.5 rounded-md transition-all shadow-inner flex items-center justify-center flex-shrink-0"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                  </svg>
                </button>
              </div>

              <div :class="[showMobileFilters ? 'grid' : 'hidden md:flex']" class="grid-cols-2 md:items-center gap-2.5 w-full">
                <!-- Date Filter -->
                <input 
                  v-model="historyDate"
                  type="date" 
                  class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ring-1 ring-slate-200 dark:ring-white/10"
                />

                <!-- Activity Filter -->
                <select v-model="historyActivity" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                  <option value="">Semua Aktivitas</option>
                  <option value="masuk">Stok Masuk</option>
                  <option value="keluar">Stok Keluar</option>
                </select>

                <!-- User Filter -->
                <select v-model="historyUser" class="w-full md:w-48 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                  <option value="">Semua Kasir</option>
                  <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>

                <!-- Reset Button -->
                <button 
                  @click="resetHistoryFilters"
                  class="w-full md:w-auto p-2.5 bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-blue-500 rounded-md transition-all shadow-inner flex items-center justify-center flex-shrink-0 group"
                  title="Reset Filter"
                >
                  <svg class="w-4 h-4 transition-transform duration-500 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  <span class="md:hidden ml-2 text-[11px] font-bold uppercase">Reset</span>
                </button>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-900 rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
            <!-- Desktop History Table -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                  <tr>
                    <th class="px-8 py-5">Tanggal</th>
                    <th class="px-8 py-5">Produk</th>
                    <th class="px-8 py-5 text-center">Aktivitas</th>
                    <th class="px-8 py-5 text-center">Jumlah</th>
                    <th class="hidden md:table-cell px-8 py-5">Keterangan</th>
                    <th class="hidden lg:table-cell px-8 py-5">User</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                  <tr v-for="log in filteredHistory" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                    <td class="px-8 py-5">
                      <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">{{ formatDate(log.created_at) }}</p>
                    </td>
                    <td class="px-8 py-5">
                      <div class="min-w-[150px]">
                        <p class="font-bold text-slate-900 dark:text-white text-sm capitalize truncate">{{ log.produk?.nama_produk }}</p>
                        <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-0.5">{{ log.kode_produk }}</p>
                      </div>
                    </td>
                    <td class="px-8 py-5 text-center">
                      <span :class="[log.jenis_aktivitas ==='masuk' ?'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' :'bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400','px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider']">
                        {{ log.jenis_aktivitas }}
                      </span>
                    </td>
                    <td class="px-8 py-5 text-center font-black text-sm" :class="log.jenis_aktivitas ==='masuk' ?'text-emerald-500' :'text-rose-500'">
                      {{ log.jenis_aktivitas ==='masuk' ?'+' :'-' }}{{ log.jumlah }}
                    </td>
                    <td class="hidden md:table-cell px-8 py-5 text-xs text-slate-500 dark:text-slate-400 font-medium max-w-xs truncate">
                      {{ log.keterangan ||'-' }}
                    </td>
                    <td class="hidden lg:table-cell px-8 py-5 text-xs font-bold text-slate-600 dark:text-slate-400">
                      {{ log.user?.name ||'Sistem' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mobile History List -->
            <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
              <div v-for="log in filteredHistory" :key="'history-mobile-'+log.id" class="p-5 space-y-4">
                <div class="flex items-start justify-between gap-4">
                  <div class="min-w-0">
                    <p class="font-black text-slate-900 dark:text-white text-sm capitalize truncate leading-tight">{{ log.produk?.nama_produk }}</p>
                    <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-1">{{ log.kode_produk }}</p>
                  </div>
                  <span :class="[log.jenis_aktivitas ==='masuk' ?'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' :'bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400','px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider whitespace-nowrap']">
                    {{ log.jenis_aktivitas }}
                  </span>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-500 text-[10px] font-bold">
                      {{ log.user?.name.charAt(0) ||'S' }}
                    </div>
                    <div class="flex flex-col">
                      <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400">{{ log.user?.name ||'Sistem' }}</span>
                      <span class="text-[9px] text-slate-400 font-medium">{{ formatDate(log.created_at) }}</span>
                    </div>
                  </div>
                  <span class="text-base font-black" :class="log.jenis_aktivitas ==='masuk' ?'text-emerald-500' :'text-rose-500'">
                    {{ log.jenis_aktivitas ==='masuk' ?'+' :'-' }}{{ log.jumlah }}
                  </span>
                </div>
                <p v-if="log.keterangan" class="text-[11px] text-slate-500 dark:text-slate-400 font-medium bg-slate-50 dark:bg-white/5 p-3 rounded-md border border-slate-100 dark:border-white/5 italic">
                 "{{ log.keterangan }}"
                </p>
              </div>
            </div>

            <!-- History Empty State -->
            <div v-if="filteredHistory.length === 0" class="px-8 py-20 text-center">
              <div class="flex flex-col items-center justify-center">
                <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                  </svg>
                </div>
                <h3 class="text-base font-black text-slate-800 dark:text-white mb-1">
                  {{ (historySearch || historyDate || historyActivity || historyUser) ? 'Riwayat Tidak Ditemukan' : 'Belum Ada Riwayat' }}
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mb-5">
                  {{ (historySearch || historyDate || historyActivity || historyUser) ? 'Tidak ada riwayat stok yang cocok dengan kata kunci atau filter Anda.' : 'Anda belum memiliki riwayat stok apapun.' }}
                </p>
                <button v-if="historySearch || historyDate || historyActivity || historyUser" @click="resetHistoryFilters" class="px-5 py-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md text-xs font-bold transition-colors">
                  Reset Filter Pencarian
                </button>
              </div>
            </div>
            <!-- Pagination & Per Page -->
            <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
              <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
                <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
                  Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ history.from }}-{{ history.to }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ history.total }}</span> riwayat
                </p>
                <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
                  <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
                  <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                    <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
                  </select>
                </div>
              </div>
              <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
                <Pagination :links="history.links" />
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>

    <!-- Update Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showUpdateModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 ">
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div v-if="showUpdateModal" class="relative bg-white dark:bg-slate-900 rounded-md shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">
              <!-- Header -->
              <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Update Stok</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ selectedProduct?.nama_produk }}</p>
                  </div>
                </div>
                <button type="button" @click="showUpdateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6 overflow-y-auto space-y-5">
                <form id="stockForm" @submit.prevent="submitUpdate" class="space-y-5">
                  <div class="grid grid-cols-2 gap-4">
                    <button 
                      type="button"
                      @click="form.jenis_aktivitas = 'masuk'"
                      :class="[form.jenis_aktivitas === 'masuk' ? 'bg-emerald-500 text-white shadow-lg' : 'bg-white dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700', 'py-3 rounded-lg font-bold text-sm transition-all']"
                    >
                      Stok Masuk
                    </button>
                    <button 
                      type="button"
                      @click="form.jenis_aktivitas = 'keluar'"
                      :class="[form.jenis_aktivitas === 'keluar' ? 'bg-rose-500 text-white shadow-lg' : 'bg-white dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700', 'py-3 rounded-lg font-bold text-sm transition-all']"
                    >
                      Stok Keluar
                    </button>
                  </div>

                  <div class="p-5 rounded-xl border border-blue-100 dark:border-blue-900/50 bg-blue-50/50 dark:bg-blue-900/20 space-y-4">
                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Jumlah</label>
                      <input 
                        v-model="form.jumlah"
                        type="number" 
                        class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all font-bold"
                        min="1"
                      />
                    </div>

                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Keterangan</label>
                      <textarea 
                        v-model="form.keterangan"
                        rows="3"
                        placeholder="Contoh: Stok dari supplier, dll."
                        class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all text-sm resize-none"
                      ></textarea>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                <button type="button" @click="showUpdateModal = false" class="px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                  Batal
                </button>
                <button 
                  type="submit"
                  form="stockForm"
                  :disabled="form.processing"
                  class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm shadow-blue-500/20 transition-all disabled:opacity-50"
                >
                  {{ form.processing ? 'Memproses...' : 'Simpan Perubahan' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>
