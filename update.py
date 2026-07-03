import sys
import re

with open('d:/web/longdaycat/resources/js/Pages/Stock/Recommendations.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update imports and add new refs
content = content.replace("import { ref, computed } from 'vue';", "import { ref, computed, watch } from 'vue';")

# Add currentPage, perPage, pagination computations, and replace filteredRecommendations
script_start = content.find("const searchQuery = ref('');")
script_end = content.find("// Stats computations")

new_script = """const searchQuery = ref('');
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

"""

if script_start != -1 and script_end != -1:
    content = content[:script_start] + new_script + content[script_end:]

# 2. Replace the Filter & Table Section entirely
filter_section_start = content.find("<!-- Filter & Table Section -->")
filter_section_end = content.find("</AppLayout>")

new_template = """<!-- Filter & Table Section -->
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
                  <th class="pl-8 pr-4 py-5 w-[200px]">Gambar</th>
                  <th class="pl-4 pr-8 py-5">Nama Produk</th>
                  <th class="px-6 py-5 text-center">Stok / Min</th>
                  <th class="px-6 py-5 text-center">Total Penjualan ({{ sma_periode }} Hari)</th>
                  <th class="px-6 py-5 text-center">Prediksi (SMA)</th>
                  <th class="px-6 py-5 text-center">Status</th>
                  <th class="px-6 py-5 text-center">Rekomendasi</th>
                  <th class="pl-4 pr-8 py-5 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y-2 divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                <template v-if="paginatedRecommendations.length > 0">
                  <tr v-for="p in paginatedRecommendations" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                    <td class="pl-8 pr-4 py-5 w-[200px]">
                      <div class="w-20 h-20 rounded-lg bg-slate-100 dark:bg-gradient-to-br dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden shadow-inner flex-shrink-0">
                        <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                        <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                      </div>
                    </td>
                    <td class="pl-4 pr-8 py-5">
                      <div class="min-w-0">
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm transition-colors capitalize">{{ p.nama_produk }}</h4>
                        <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-1">{{ p.kode_produk }}</p>
                      </div>
                    </td>
                    <td class="px-6 py-5 text-center">
                      <span :class="p.stok <= p.stok_minimal ?'text-rose-500 font-black' :'text-slate-700 dark:text-slate-300 font-bold'" class="text-sm">
                        {{ p.stok }}
                      </span>
                      <span class="text-[10px] text-slate-400">/{{ p.stok_minimal }}</span>
                    </td>
                    <td class="px-6 py-5 text-center">
                      <div class="inline-flex items-center justify-center font-black text-sm text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-white/[0.02] px-3 py-1.5 rounded-lg border border-slate-100 dark:border-white/5 min-w-[3rem]">
                        {{ p.total_sales }}
                      </div>
                    </td>
                    <td class="px-6 py-5 text-center font-black text-slate-900 dark:text-white text-sm">{{ p.sma }}</td>
                    <td class="px-6 py-5 text-center">
                      <span v-if="p.status ==='Kritis'" class="px-2 py-1 bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 rounded text-[10px] font-black uppercase tracking-wider">
                        {{ p.status }}
                      </span>
                      <span v-else-if="p.status ==='Perlu Restok'" class="px-2 py-1 bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 rounded text-[10px] font-black uppercase tracking-wider">
                        {{ p.status }}
                      </span>
                      <span v-else class="px-2 py-1 bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 rounded text-[10px] font-black uppercase tracking-wider">
                        {{ p.status }}
                      </span>
                    </td>
                    <td class="px-6 py-5 text-center font-black text-sm" :class="p.rekomendasi_tambah > 0 ?'text-blue-600 dark:text-blue-400' :'text-slate-400'">
                      <span v-if="p.rekomendasi_tambah > 0" class="px-2 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded text-xs font-black">
                        +{{ p.rekomendasi_tambah }} unit
                      </span>
                      <span v-else>-</span>
                    </td>
                    <td class="pl-4 pr-8 py-5 text-right">
                      <Link 
                        :href="route('stock.index', { search: p.kode_produk })" 
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-md active:scale-95 hover:bg-blue-700 text-xs font-bold transition-all shadow-sm shadow-blue-500/20"
                      >
                        Tambah Stok
                      </Link>
                    </td>
                  </tr>
                </template>
                <tr v-else>
                  <td colspan="8" class="px-8 py-20 text-center">
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
                  <div class="w-20 h-20 rounded-md bg-slate-100 dark:bg-gradient-to-br dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden shadow-inner flex-shrink-0">
                    <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                    <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start gap-2">
                      <h4 class="font-black text-slate-900 dark:text-white text-base truncate capitalize leading-tight">{{ p.nama_produk }}</h4>
                      <span v-if="p.status ==='Kritis'" class="px-2 py-1 bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0">Kritis</span>
                      <span v-else-if="p.status ==='Perlu Restok'" class="px-2 py-1 bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0">Restok</span>
                      <span v-else class="px-2 py-1 bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0">Aman</span>
                    </div>
                    <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-1">{{ p.kode_produk }}</p>
                    
                    <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                      <div class="bg-slate-50 dark:bg-white/[0.02] p-2 rounded-md border border-slate-100 dark:border-white/5">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Stok/Min</p>
                        <p class="text-xs font-black text-slate-800 dark:text-slate-200 mt-0.5">{{ p.stok }}/{{ p.stok_minimal }}</p>
                      </div>
                      <div class="bg-slate-50 dark:bg-white/[0.02] p-2 rounded-md border border-slate-100 dark:border-white/5">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Total Sales</p>
                        <p class="text-xs font-black text-slate-800 dark:text-slate-200 mt-0.5">{{ p.total_sales }}</p>
                      </div>
                      <div class="bg-slate-50 dark:bg-white/[0.02] p-2 rounded-md border border-slate-100 dark:border-white/5">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Prediksi SMA</p>
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400 mt-0.5">{{ p.sma }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 border-t border-slate-50 dark:border-white/5 pt-3 -mt-1">
                  <div v-if="p.rekomendasi_tambah > 0" class="flex-1 flex items-center justify-between bg-blue-50 dark:bg-blue-950/20 px-3.5 py-2 rounded-md border border-blue-100 dark:border-blue-900/10">
                    <span class="text-[10px] font-bold text-blue-800 dark:text-blue-300">Rekomendasi Restok:</span>
                    <span class="text-xs font-black text-blue-600 dark:text-blue-400">+{{ p.rekomendasi_tambah }} unit</span>
                  </div>
                  <Link 
                    :href="route('stock.index', { search: p.kode_produk })" 
                    class="flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors active:scale-95 shadow-sm shadow-blue-500/20"
                    :class="p.rekomendasi_tambah > 0 ? 'px-4' : 'flex-1'"
                  >
                    Tambah Stok
                  </Link>
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
"""

if filter_section_start != -1 and filter_section_end != -1:
    content = content[:filter_section_start] + new_template + "\n  </AppLayout>\n</template>\n"

with open('d:/web/longdaycat/resources/js/Pages/Stock/Recommendations.vue', 'w', encoding='utf-8') as f:
    f.write(content)
