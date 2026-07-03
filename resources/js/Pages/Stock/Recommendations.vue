<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, Link } from'@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
  lowStock: Array,
  recommendations: Array,
  sma_periode: {
    type: Number,
    default: 7
  }
});

const searchQuery = ref('');
const activeFilter = ref('all'); //'all','restock','critical','safe'
const currentPage = ref(1);
const perPage = ref(10);

const filteredRecommendations = computed(() => {
  let items = props.recommendations || [];
  
  if (searchQuery.value.trim() !== '') {
    const query = searchQuery.value.toLowerCase();
    items = items.filter(item => 
      item.nama_produk.toLowerCase().includes(query) || 
      item.kode_produk.toLowerCase().includes(query)
    );
  }
  
  if (activeFilter.value === 'restock') {
    items = items.filter(item => item.status === 'Kritis' || item.status === 'Perlu Restok');
  } else if (activeFilter.value === 'critical') {
    items = items.filter(item => item.status === 'Kritis');
  } else if (activeFilter.value === 'safe') {
    items = items.filter(item => item.status === 'Aman');
  }
  
  return items;
});

const paginationInfo = computed(() => {
  const total = filteredRecommendations.value.length;
  const from = total === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1;
  const to = Math.min(total, currentPage.value * perPage.value);
  const lastPage = Math.ceil(total / perPage.value) || 1;
  
  let pages = [];
  for (let i = 1; i <= lastPage; i++) {
    pages.push(i);
  }

  return { total, from, to, lastPage, pages };
});

const paginatedRecommendations = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return filteredRecommendations.value.slice(start, end);
});

const changePage = (page) => {
  if (page >= 1 && page <= paginationInfo.value.lastPage) {
    currentPage.value = page;
  }
};

const resetFilters = () => {
  searchQuery.value = '';
  activeFilter.value = 'all';
  currentPage.value = 1;
  perPage.value = 10;
};

watch([searchQuery, activeFilter, perPage], () => {
  currentPage.value = 1;
});

// Stats computations
const stats = computed(() => {
  const total = props.recommendations?.length || 0;
  const critical = props.recommendations?.filter(item => item.status ==='Kritis').length || 0;
  const restock = props.recommendations?.filter(item => item.status ==='Perlu Restok').length || 0;
  const safe = props.recommendations?.filter(item => item.status ==='Aman').length || 0;
  const hasRecommendation = props.recommendations?.filter(item => item.rekomendasi_tambah > 0).length || 0;
  
  return { total, critical, restock, safe, hasRecommendation };
});
</script>

<template>
  <Head title="Rekomendasi Restok" />

  <AppLayout>
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header -->
      <div>
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Rekomendasi Restok</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Analisis cerdas kebutuhan pengadaan produk dengan metode Single Moving Average (SMA).</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Needs Restock -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Perlu Restock</span>
            <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-lg text-rose-500 dark:text-rose-400 relative">
              <div v-if="(stats.critical + stats.restock) > 0" class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full animate-ping"></div>
              <div v-if="(stats.critical + stats.restock) > 0" class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full"></div>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white mt-2 truncate">{{ stats.restock + stats.critical }}</div>
        </div>

        <!-- Total Item Rekomendasi -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Item Rekomendasi</span>
            <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-lg text-blue-500 dark:text-blue-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white mt-2 truncate">{{ stats.hasRecommendation }}</div>
        </div>

        <!-- Periode Perhitungan -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex flex-col justify-between">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Periode Perhitungan</span>
            <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-lg text-purple-500 dark:text-purple-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <div class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white mt-2 truncate">{{ sma_periode }} Hari</div>
        </div>
      </div>

      <!-- Bento Row: SMA formula explanation -->
      <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-md p-6 md:p-8 text-white relative overflow-hidden shadow-md flex flex-col justify-between min-h-[160px]">
        <div class="absolute top-0 right-0 p-8 opacity-10">
          <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
            <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
        </div>
        <div class="relative z-10 max-w-4xl">
          <h3 class="text-base md:text-lg font-black mb-2 tracking-tight uppercase">Metode Prediksi Single Moving Average (SMA)</h3>
          <p class="text-xs md:text-sm text-blue-100 leading-relaxed">
            Sistem memperkirakan kebutuhan stok harian dengan menghitung rata-rata penjualan riil dari {{ sma_periode }} hari terakhir secara terus menerus: 
            <span class="font-mono bg-white/10 px-2 py-0.5 rounded text-[11px] font-black text-white ml-1">SMA = (H1 + H2 + ... + H{{ sma_periode }}) / {{ sma_periode }}</span>.
            Jika stok sekarang lebih rendah dari prediksi rata-rata penjualan harian (SMA), sistem menyarankan jumlah pengadaan tambahan yang ideal.
          </p>
        </div>
        <div class="text-[9px] text-blue-200 font-bold uppercase tracking-widest mt-4">
          *H = Penjualan harian | Dihitung berdasarkan {{ sma_periode }} hari terakhir
        </div>
      </div>

      <!-- Filter & Table Section -->
      <div class="space-y-4">
        <!-- Search & Filters (Synced Layout) -->
        <div class="bg-white dark:bg-slate-900 p-3 md:p-2 rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
          <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-2.5">
            <!-- Search Field -->
            <div class="flex items-center gap-2.5 w-full md:w-64">
              <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                  <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input 
                  v-model="searchQuery"
                  type="text" 
                  placeholder="Cari nama atau kode produk..." 
                  class="block w-full pl-10 pr-3 py-2.5 md:py-2 border-none bg-slate-50 dark:bg-white/5 rounded-md text-xs ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
                >
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:flex md:items-center gap-2 w-full">
              <!-- Status Filter -->
              <select v-model="activeFilter" class="w-full md:w-48 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                <option value="all">Semua Status ({{ stats.total }})</option>
                <option value="restock">Butuh Restok ({{ stats.critical + stats.restock }})</option>
                <option value="critical">Stok Kritis ({{ stats.critical }})</option>
                <option value="safe">Stok Aman ({{ stats.safe }})</option>
              </select>

              <!-- Reset Button -->
              <button 
                @click="resetFilters"
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

        <!-- Table Card Container -->
        <div class="bg-white dark:bg-slate-900 rounded-md overflow-hidden shadow-sm dark:shadow-2xl border border-slate-200 dark:border-white/5">
          <!-- Desktop Table -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
              <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-[0.2em] font-black">
                <tr>
                  <th class="pl-8 pr-4 py-5">Info Produk & SKU</th>
                  <th class="px-6 py-5 text-center">Sisa Produk</th>
                  <th class="px-6 py-5 text-center">Prediksi Jual</th>
                  <th class="pl-6 pr-8 py-5 text-center">Rekomendasi Restok</th>
                </tr>
              </thead>
              <tbody class="divide-y-2 divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                <template v-if="paginatedRecommendations.length > 0">
                  <tr v-for="p in paginatedRecommendations" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                    <td class="pl-8 pr-4 py-5">
                      <div class="flex items-center gap-5">
                        <div class="w-24 h-24 rounded-xl bg-slate-100 dark:bg-gradient-to-br dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden shadow-inner flex-shrink-0 border border-slate-200/50 dark:border-white/5">
                          <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                          <svg v-else class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </div>
                        <div class="min-w-0">
                          <h4 class="font-black text-slate-900 dark:text-white text-base md:text-lg transition-colors capitalize leading-tight mb-2">{{ p.nama_produk }}</h4>
                          <div class="flex flex-col gap-1.5">
                            <div>
                              <span class="text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded font-mono font-black tracking-wider border border-slate-200 dark:border-slate-700 shadow-sm">{{ p.kode_produk }}</span>
                            </div>
                            <div class="flex items-center gap-2" v-if="p.warna || p.ukuran">
                              <span v-if="p.warna" class="text-[11px] bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400 px-2 py-0.5 rounded font-bold border border-indigo-100 dark:border-indigo-500/20 shadow-sm">{{ p.warna }}</span>
                              <span v-if="p.ukuran" class="text-[11px] bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 px-2 py-0.5 rounded font-bold border border-emerald-100 dark:border-emerald-500/20 shadow-sm">{{ p.ukuran }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-5 text-center">
                      <span :class="p.stok <= p.stok_minimal ?'text-rose-500 font-black' :'text-slate-700 dark:text-slate-300 font-bold'" class="text-sm">
                        {{ p.stok }} Item
                      </span>
                    </td>
                    <td class="px-6 py-5 text-center font-black text-slate-900 dark:text-white text-sm">
                      {{ p.sma }} Item / Hari
                    </td>
                    <td class="px-6 py-5 text-center font-black text-sm" :class="p.rekomendasi_tambah > 0 ?'text-blue-600 dark:text-blue-400' :'text-emerald-600 dark:text-emerald-400'">
                      <span v-if="p.rekomendasi_tambah > 0" class="px-3 py-1.5 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-md text-xs font-black border border-rose-200 dark:border-rose-500/20 shadow-sm">
                        +{{ p.rekomendasi_tambah }} Unit
                      </span>
                      <span v-else class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-md text-xs font-black border border-emerald-200 dark:border-emerald-500/20 shadow-sm">
                        Aman
                      </span>
                    </td>
                  </tr>
                </template>
                <tr v-else>
                  <td colspan="4" class="px-8 py-20 text-center">
                    <div class="flex flex-col items-center justify-center">
                      <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                      </div>
                      <h3 class="text-base font-black text-slate-800 dark:text-white mb-1">Tidak Ada Produk Ditemukan</h3>
                      <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Kata kunci atau filter yang Anda gunakan tidak cocok dengan produk manapun.</p>
                      <button @click="resetFilters" class="mt-5 px-5 py-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md text-xs font-bold transition-colors">Reset Filter Pencarian</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Card List -->
          <div class="md:hidden divide-y-2 divide-slate-100 dark:divide-white/5">
            <template v-if="paginatedRecommendations.length > 0">
              <div v-for="p in paginatedRecommendations" :key="'rec-'+p.id" class="p-5 flex flex-col gap-4">
                <div class="flex items-start gap-4">
                  <div class="w-24 h-24 rounded-xl bg-slate-100 dark:bg-gradient-to-br dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden shadow-inner flex-shrink-0 border border-slate-200/50 dark:border-white/5">
                    <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                    <svg v-else class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex flex-col gap-2">
                      <h4 class="font-black text-slate-900 dark:text-white text-base md:text-lg truncate capitalize leading-tight">{{ p.nama_produk }}</h4>
                      <div class="flex flex-col gap-1.5">
                        <div>
                          <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-1.5 py-0.5 rounded font-mono font-black tracking-wider border border-slate-200 dark:border-slate-700 shadow-sm">{{ p.kode_produk }}</span>
                        </div>
                        <div class="flex items-center gap-1.5" v-if="p.warna || p.ukuran">
                          <span v-if="p.warna" class="text-[10px] bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400 px-1.5 py-0.5 rounded font-bold border border-indigo-100 dark:border-indigo-500/20 shadow-sm">{{ p.warna }}</span>
                          <span v-if="p.ukuran" class="text-[10px] bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 px-1.5 py-0.5 rounded font-bold border border-emerald-100 dark:border-emerald-500/20 shadow-sm">{{ p.ukuran }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 mt-3 text-center">
                      <div class="bg-slate-50 dark:bg-white/[0.02] p-2 rounded-md border border-slate-100 dark:border-white/5">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Sisa Produk</p>
                        <p class="text-xs font-black text-slate-800 dark:text-slate-200 mt-0.5">{{ p.stok }} Item</p>
                      </div>
                      <div class="bg-slate-50 dark:bg-white/[0.02] p-2 rounded-md border border-slate-100 dark:border-white/5">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Prediksi Jual</p>
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400 mt-0.5">{{ p.sma }} Item/Hr</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 border-t border-slate-50 dark:border-white/5 pt-3 -mt-1">
                  <div v-if="p.rekomendasi_tambah > 0" class="flex-1 flex items-center justify-between bg-rose-50 dark:bg-rose-950/20 px-3.5 py-2 rounded-md border border-rose-100 dark:border-rose-900/20">
                    <span class="text-[10px] font-bold text-rose-800 dark:text-rose-300">Rekomendasi Restok:</span>
                    <span class="text-xs font-black text-rose-600 dark:text-rose-400">+{{ p.rekomendasi_tambah }} Unit</span>
                  </div>
                  <div v-else class="flex-1 flex items-center justify-between bg-emerald-50 dark:bg-emerald-950/20 px-3.5 py-2 rounded-md border border-emerald-100 dark:border-emerald-900/20">
                    <span class="text-[10px] font-bold text-emerald-800 dark:text-emerald-300">Rekomendasi Restok:</span>
                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">Aman</span>
                  </div>
                </div>
              </div>
            </template>
            <div v-else class="py-16 px-4 flex flex-col items-center justify-center text-center">
              <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
              <h3 class="text-base font-black text-slate-800 dark:text-white mb-1">Tidak Ada Produk</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">Coba gunakan kata kunci pencarian yang lain.</p>
              <button @click="resetFilters" class="mt-5 px-5 py-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md text-xs font-bold transition-colors">Reset Pencarian</button>
            </div>
          </div>
          
          <!-- Pagination -->
          <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
            <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
              <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
                Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ paginationInfo.from }}-{{ paginationInfo.to }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ paginationInfo.total }}</span> produk
              </p>
              <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
                <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                  <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
                </select>
              </div>
            </div>
            
            <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
              <div v-if="paginationInfo.lastPage > 1" class="w-full sm:w-auto">
                <div class="flex sm:hidden items-center justify-between w-full gap-3">
                  <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                    class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase border border-slate-200 dark:border-white/5 rounded-xl transition-all disabled:text-slate-400 disabled:bg-slate-50/50 dark:disabled:bg-slate-800/10 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 active:scale-[0.98]"
                  >&laquo; Previous</button>
                  <button @click="changePage(currentPage + 1)" :disabled="currentPage === paginationInfo.lastPage"
                    class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase border border-slate-200 dark:border-white/5 rounded-xl transition-all disabled:text-slate-400 disabled:bg-slate-50/50 dark:disabled:bg-slate-800/10 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 active:scale-[0.98]"
                  >Next &raquo;</button>
                </div>
                <div class="hidden sm:flex flex-wrap gap-1">
                  <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                    class="px-3.5 py-2 text-xs leading-4 border border-slate-200 dark:border-white/5 rounded-lg transition-all font-semibold disabled:text-slate-400 disabled:bg-transparent hover:bg-slate-50 dark:hover:bg-white/5 text-slate-700 dark:text-slate-300"
                  >&laquo; Previous</button>
                  <button v-for="page in paginationInfo.pages" :key="page" @click="changePage(page)"
                    class="px-3.5 py-2 text-xs leading-4 border border-slate-200 dark:border-white/5 rounded-lg transition-all font-semibold cursor-pointer"
                    :class="page === currentPage ? 'bg-blue-600 border-blue-600 text-white hover:bg-blue-700' : 'hover:bg-slate-50 dark:hover:bg-white/5 text-slate-700 dark:text-slate-300'"
                  >{{ page }}</button>
                  <button @click="changePage(currentPage + 1)" :disabled="currentPage === paginationInfo.lastPage"
                    class="px-3.5 py-2 text-xs leading-4 border border-slate-200 dark:border-white/5 rounded-lg transition-all font-semibold disabled:text-slate-400 disabled:bg-transparent hover:bg-slate-50 dark:hover:bg-white/5 text-slate-700 dark:text-slate-300"
                  >Next &raquo;</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </AppLayout>
</template>
