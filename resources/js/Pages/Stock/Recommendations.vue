<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, Link } from'@inertiajs/vue3';
import { ref, computed } from'vue';

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

const filteredRecommendations = computed(() => {
  let items = props.recommendations || [];
  
  // Apply search filter
  if (searchQuery.value.trim() !=='') {
    const query = searchQuery.value.toLowerCase();
    items = items.filter(item => 
      item.nama_produk.toLowerCase().includes(query) || 
      item.kode_produk.toLowerCase().includes(query)
    );
  }
  
  // Apply status filter
  if (activeFilter.value ==='restock') {
    items = items.filter(item => item.status ==='Kritis' || item.status ==='Perlu Restok');
  } else if (activeFilter.value ==='critical') {
    items = items.filter(item => item.status ==='Kritis');
  } else if (activeFilter.value ==='safe') {
    items = items.filter(item => item.status ==='Aman');
  }
  
  return items;
});

// Stats computations
const stats = computed(() => {
  const total = props.recommendations?.length || 0;
  const critical = props.recommendations?.filter(item => item.status ==='Kritis').length || 0;
  const restock = props.recommendations?.filter(item => item.status ==='Perlu Restok').length || 0;
  const safe = props.recommendations?.filter(item => item.status ==='Aman').length || 0;
  
  return { total, critical, restock, safe };
});
</script>

<template>
  <Head title="Rekomendasi Stok" />

  <AppLayout>
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header -->
      <div>
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Rekomendasi Stok</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Analisis cerdas kebutuhan pengadaan produk dengan metode Single Moving Average (SMA).</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Products -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex items-center gap-4">
          <div class="p-2 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Produk</span>
            <div class="text-xl md:text-2xl font-semibold tracking-tight text-slate-950 dark:text-white mt-0.5">{{ stats.total }}</div>
          </div>
        </div>

        <!-- Critical -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex items-center gap-4">
          <div class="p-2 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-lg relative">
            <div v-if="stats.critical > 0" class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full animate-ping"></div>
            <div v-if="stats.critical > 0" class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full"></div>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Stok Kritis</span>
            <div class="text-xl md:text-2xl font-semibold tracking-tight text-rose-600 dark:text-rose-400 mt-0.5">{{ stats.critical }}</div>
          </div>
        </div>

        <!-- Needs Restock -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex items-center gap-4">
          <div class="p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Perlu Restok</span>
            <div class="text-xl md:text-2xl font-semibold tracking-tight text-amber-600 dark:text-amber-400 mt-0.5">{{ stats.restock }}</div>
          </div>
        </div>

        <!-- Safe -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex items-center gap-4">
          <div class="p-2 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Stok Aman</span>
            <div class="text-xl md:text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400 mt-0.5">{{ stats.safe }}</div>
          </div>
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
            Sistem memperkirakan kebutuhan stok periode berikutnya dengan menghitung rata-rata penjualan riil dari 3 periode ({{ sma_periode }} harian) terakhir secara terus menerus: 
            <span class="font-mono bg-white/10 px-2 py-0.5 rounded text-[11px] font-black text-white ml-1">SMA = (P1 + P2 + P3) / 3</span>.
            Jika stok sekarang lebih rendah dari prediksi permintaan SMA, sistem menyarankan jumlah pengadaan tambahan yang ideal.
          </p>
        </div>
        <div class="text-[9px] text-blue-200 font-bold uppercase tracking-widest mt-4">
          *P1 = {{ sma_periode }} hari terakhir | P2 = hari {{ sma_periode + 1 }}-{{ sma_periode * 2 }} lalu | P3 = hari {{ sma_periode * 2 + 1 }}-{{ sma_periode * 3 }} lalu
        </div>
      </div>

      <!-- Filter & Table Section -->
      <div class="space-y-4">
        <!-- Filters and Search Toolbar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <!-- Tabs -->
          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
            <button 
              @click="activeFilter ='all'"
              :class="activeFilter ==='all' 
                ?'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-sm' 
                :'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-white/5 dark:hover:bg-slate-800/60'"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition-all duration-300 flex items-center gap-2 whitespace-nowrap"
            >
              Semua
              <span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-[10px] font-bold rounded-lg text-slate-500 dark:text-slate-400">
                {{ stats.total }}
              </span>
            </button>
            
            <button 
              @click="activeFilter ='restock'"
              :class="activeFilter ==='restock' 
                ?'bg-amber-500 text-white shadow-sm' 
                :'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-white/5 dark:hover:bg-slate-800/60'"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition-all duration-300 flex items-center gap-2 whitespace-nowrap"
            >
              Butuh Restok
              <span class="px-1.5 py-0.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 text-[10px] font-bold rounded-lg">
                {{ stats.critical + stats.restock }}
              </span>
            </button>

            <button 
              @click="activeFilter ='critical'"
              :class="activeFilter ==='critical' 
                ?'bg-rose-500 text-white shadow-sm' 
                :'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-white/5 dark:hover:bg-slate-800/60'"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition-all duration-300 flex items-center gap-2 whitespace-nowrap"
            >
              Stok Kritis
              <span class="px-1.5 py-0.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 text-[10px] font-bold rounded-lg">
                {{ stats.critical }}
              </span>
            </button>

            <button 
              @click="activeFilter ='safe'"
              :class="activeFilter ==='safe' 
                ?'bg-emerald-500 text-white shadow-sm' 
                :'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 dark:bg-slate-900 dark:text-slate-400 dark:border-white/5 dark:hover:bg-slate-800/60'"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition-all duration-300 flex items-center gap-2 whitespace-nowrap"
            >
              Stok Aman
              <span class="px-1.5 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold rounded-lg">
                {{ stats.safe }}
              </span>
            </button>
          </div>

          <!-- Search Input -->
          <div class="relative w-full md:w-72">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Cari nama atau kode produk..." 
              class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-md text-xs font-bold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300"
            />
          </div>
        </div>

        <!-- Table Card Container -->
        <div class="bg-white dark:bg-slate-900 rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
          <!-- Empty State -->
          <div v-if="filteredRecommendations.length === 0" class="px-6 py-16 text-center">
            <svg class="w-12 h-12 text-slate-300 dark:text-slate-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <p class="text-slate-400 text-sm font-bold">Tidak ada produk yang cocok dengan filter atau kata kunci Anda.</p>
          </div>

          <!-- Desktop Table -->
          <div v-else class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
              <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                <tr>
                  <th class="px-6 py-4">Produk</th>
                  <th class="px-6 py-4 text-center">Stok / Min</th>
                  <th class="px-6 py-4 text-center">Histori Penjualan (P3 - P2 - P1)</th>
                  <th class="px-6 py-4 text-center">Prediksi (SMA)</th>
                  <th class="px-6 py-4 text-center">Status</th>
                  <th class="px-6 py-4 text-center">Rekomendasi Restok</th>
                  <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                <tr v-for="p in filteredRecommendations" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors duration-200">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-white/5 shadow-sm">
                        <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                      </div>
                      <div class="min-w-0">
                        <p class="font-black text-slate-900 dark:text-white text-sm capitalize truncate">{{ p.nama_produk }}</p>
                        <p class="text-[9px] text-slate-500 uppercase font-mono tracking-tighter">{{ p.kode_produk }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <span :class="p.stok <= p.stok_minimal ?'text-rose-500 font-black' :'text-slate-700 dark:text-slate-300 font-bold'" class="text-sm">
                      {{ p.stok }}
                    </span>
                    <span class="text-[10px] text-slate-400">/{{ p.stok_minimal }}</span>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <div class="inline-flex items-center gap-1.5 font-bold text-xs bg-slate-50 dark:bg-white/[0.02] p-1.5 rounded-lg border border-slate-100 dark:border-white/5 text-slate-500">
                      <span class="px-1.5 py-0.5 bg-slate-200/50 dark:bg-white/5 rounded text-slate-400">{{ p.sales_w3 }}</span>
                      <span>→</span>
                      <span class="px-1.5 py-0.5 bg-slate-200/50 dark:bg-white/5 rounded text-slate-400">{{ p.sales_w2 }}</span>
                      <span>→</span>
                      <span class="px-1.5 py-0.5 bg-slate-200/50 dark:bg-white/5 rounded text-slate-600 dark:text-slate-300 font-black">{{ p.sales_w1 }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-center font-black text-slate-900 dark:text-white text-sm bg-slate-50/50 dark:bg-white/[0.02]">{{ p.sma }}</td>
                  <td class="px-6 py-4 text-center">
                    <span v-if="p.status ==='Kritis'" class="px-2 py-0.5 bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 rounded text-[10px] font-black uppercase tracking-wider">
                      {{ p.status }}
                    </span>
                    <span v-else-if="p.status ==='Perlu Restok'" class="px-2 py-0.5 bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 rounded text-[10px] font-black uppercase tracking-wider">
                      {{ p.status }}
                    </span>
                    <span v-else class="px-2 py-0.5 bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 rounded text-[10px] font-black uppercase tracking-wider">
                      {{ p.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center font-black text-sm" :class="p.rekomendasi_tambah > 0 ?'text-blue-600 dark:text-blue-400' :'text-slate-400'">
                    <span v-if="p.rekomendasi_tambah > 0" class="px-2 py-0.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded text-xs font-black">
                      +{{ p.rekomendasi_tambah }} unit
                    </span>
                    <span v-else>-</span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <Link 
                      :href="route('stock.index', { search: p.kode_produk })" 
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-xl active:scale-95 hover:bg-blue-700 text-[10px] font-black uppercase tracking-wider transition-all shadow-sm"
                    >
                      Tambah Stok
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Card List -->
          <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
            <div v-for="p in filteredRecommendations" :key="'rec-'+p.id" class="p-5 space-y-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-md bg-slate-100 dark:bg-white/5 overflow-hidden shrink-0 border border-slate-200 dark:border-white/10 shadow-sm">
                  <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                </div>
                <div class="min-w-0 flex-1">
                  <p class="font-black text-slate-900 dark:text-white text-base capitalize truncate leading-tight">{{ p.nama_produk }}</p>
                  <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-1">{{ p.kode_produk }}</p>
                </div>
                <span v-if="p.status ==='Kritis'" class="px-2 py-1 bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0">
                  Kritis
                </span>
                <span v-else-if="p.status ==='Perlu Restok'" class="px-2 py-1 bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0">
                  Restok
                </span>
                <span v-else class="px-2 py-1 bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0">
                  Aman
                </span>
              </div>
              
              <div class="grid grid-cols-3 gap-2 bg-slate-50 dark:bg-white/[0.02] p-3 rounded-md border border-slate-100 dark:border-white/5 text-center">
                <div>
                  <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Stok / Min</p>
                  <p class="text-xs font-black text-slate-800 dark:text-slate-200 mt-0.5">{{ p.stok }} / {{ p.stok_minimal }}</p>
                </div>
                <div>
                  <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Histori (P3-P1)</p>
                  <p class="text-xs font-bold text-slate-600 dark:text-slate-400 mt-0.5">{{ p.sales_w3 }}, {{ p.sales_w2 }}, {{ p.sales_w1 }}</p>
                </div>
                <div>
                  <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Prediksi SMA</p>
                  <p class="text-xs font-black text-blue-600 dark:text-blue-400 mt-0.5">{{ p.sma }}</p>
                </div>
              </div>
              
              <div class="flex items-center justify-between gap-4 pt-1">
                <div v-if="p.rekomendasi_tambah > 0" class="flex-1 flex items-center justify-between bg-blue-50 dark:bg-blue-950/20 px-3.5 py-2.5 rounded-xl border border-blue-100 dark:border-blue-900/10">
                  <span class="text-[10px] font-bold text-blue-800 dark:text-blue-300">Rekomendasi Restok:</span>
                  <span class="text-xs font-black text-blue-600 dark:text-blue-400">+{{ p.rekomendasi_tambah }} unit</span>
                </div>
                <Link 
                  :href="route('stock.index', { search: p.kode_produk })" 
                  class="px-4 py-2.5 bg-blue-600 text-white rounded-xl active:scale-95 text-xs font-black uppercase tracking-wider transition-all text-center shadow-sm"
                  :class="p.rekomendasi_tambah > 0 ?'' :'w-full'"
                >
                  Tambah Stok
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
