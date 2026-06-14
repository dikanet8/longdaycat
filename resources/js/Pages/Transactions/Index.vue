<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, Link, router } from'@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import FrontendPagination from '@/Components/FrontendPagination.vue';

const props = defineProps({
  transactions: Array,
  users: Array,
  filters: Object
});

const perPage = ref(props.filters?.per_page || 10);
const currentPage = ref(1);

const searchQuery = ref('');
const dateFilter = ref('');
const statusFilter = ref('');
const methodFilter = ref('');
const userFilter = ref('');

const showMobileFilters = ref(false);

const filteredTransactionsAll = computed(() => {
  const data = Array.isArray(props.transactions) ? props.transactions : (props.transactions?.data || []);
  return data.filter(t => {
    const matchesSearch = (t.kode_transaksi?.toLowerCase() || '').includes(searchQuery.value.toLowerCase()) ||
      (t.id?.toString() || '').includes(searchQuery.value);
    
    const matchesDate = dateFilter.value ? (t.created_at || '').startsWith(dateFilter.value) : true;
    const matchesStatus = statusFilter.value ? (t.status || 'Selesai').toLowerCase() === statusFilter.value.toLowerCase() : true;
    const matchesMethod = methodFilter.value ? (t.metode_bayar || '').toLowerCase() === methodFilter.value.toLowerCase() : true;
    const matchesUser = userFilter.value ? (t.user_id?.toString() || '') === userFilter.value.toString() : true;
    
    return matchesSearch && matchesDate && matchesStatus && matchesMethod && matchesUser;
  });
});

const paginatedTransactions = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return filteredTransactionsAll.value.slice(start, end);
});

watch([searchQuery, dateFilter, statusFilter, methodFilter, userFilter, perPage], () => {
  currentPage.value = 1;
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits: 0 }).format(price);
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('id-ID', {
    day:'numeric',
    month:'short',
    year:'numeric',
    hour:'2-digit',
    minute:'2-digit'
  });
};

const getStatusBadge = (status) => {
  switch (status?.toLowerCase()) {
    case 'selesai':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 shadow-sm';
    case 'pending':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 shadow-sm';
    case 'dibatalkan':
      return 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20 shadow-sm';
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 shadow-sm';
  }
};

const resetFilters = () => {
  searchQuery.value = '';
  dateFilter.value = '';
  statusFilter.value = '';
  methodFilter.value = '';
  userFilter.value = '';
  perPage.value = 10;
  currentPage.value = 1;
};

const showCancelModal = ref(false);
const isCancelling = ref(false);
const transactionToCancel = ref({ id: null, kode: '' });

const confirmCancel = (id, kode) => {
  transactionToCancel.value = { id, kode };
  showCancelModal.value = true;
};

const executeCancel = () => {
  if (transactionToCancel.value.id) {
    isCancelling.value = true;
    router.post(route('payments.cancel', transactionToCancel.value.id), {}, { 
      preserveScroll: true,
      onSuccess: () => {
        showCancelModal.value = false;
      },
      onFinish: () => {
        isCancelling.value = false;
      }
    });
  }
};

const printIframe = ref(null);
const printTransaction = (id) => {
  if (printIframe.value) {
    printIframe.value.src = route('payments.show', id) + '?print=true';
  }
};
</script>

<template>
  <Head title="Riwayat Transaksi" />

  <AppLayout>
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-1">Riwayat Transaksi</h1>
          <p class="text-slate-500 dark:text-slate-400 font-medium text-xs md:text-sm">Lihat dan pantau semua transaksi penjualan yang telah dilakukan.</p>
        </div>
      </div>

      <!-- Filter & Search Section -->
      <div class="bg-white dark:bg-slate-900 p-3 md:p-2.5 rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-2.5">
          <!-- Search Field & Mobile Filter Toggle -->
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
                placeholder="Cari Kode..." 
                class="block w-full pl-10 pr-3 py-2.5 md:py-2 border-none bg-slate-50 dark:bg-white/5 rounded-md text-xs ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
              >
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
              v-model="dateFilter"
              type="date" 
              class="block w-full md:w-40 px-3 py-2.5 md:py-2 border-none bg-slate-50 dark:bg-white/5 rounded-md text-[11px] md:text-xs ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
            >

            <!-- Status Filter -->
            <select v-model="statusFilter" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
              <option value="">Semua Status</option>
              <option value="selesai">Selesai</option>
              <option value="pending">Pending</option>
            </select>

            <!-- Method Filter -->
            <select v-model="methodFilter" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
              <option value="">Semua Metode</option>
              <option value="cash">Cash</option>
              <option value="qris">QRIS</option>
            </select>

            <!-- User Filter -->
            <select v-model="userFilter" class="w-full md:w-48 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
              <option value="">Semua Kasir</option>
              <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
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

      <!-- Transactions Area -->
      <div class="bg-white dark:bg-slate-900 rounded-md border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
              <tr>
                <th class="px-8 py-5">Tanggal & Waktu</th>
                <th class="px-8 py-5">Kode Transaksi</th>
                <th class="px-8 py-5">Kasir</th>
                <th class="px-8 py-5 text-center">Tanggal Pembayaran</th>
                <th class="px-8 py-5">Produk</th>
                <th class="px-8 py-5 text-center">Total Belanja</th>
                <th class="px-8 py-5 text-center">Status</th>
                <th class="px-8 py-5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y-2 divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
              <tr v-for="t in paginatedTransactions" :key="t.id" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.02] transition-colors group">
                <td class="px-8 py-5">
                  <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">{{ formatDate(t.created_at) }}</span>
                </td>
                <td class="px-8 py-5">
                  <div class="flex flex-col">
                    <span class="font-black text-slate-900 dark:text-white text-sm tracking-tight uppercase">{{ t.kode_transaksi }}</span>
                    <span class="text-[10px] text-slate-400 font-mono uppercase">{{ t.id }}</span>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-md bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-[10px] uppercase shadow-sm">
                      {{ t.user?.name ? t.user.name.substring(0, 2) : 'SI' }}
                    </div>
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize">{{ t.user?.name ||'Sistem' }}</span>
                  </div>
                </td>
                <td class="px-8 py-5 text-center">
                  <span v-if="t.pembayaran?.tanggal_pembayaran" class="text-xs text-slate-600 dark:text-slate-400 font-medium whitespace-nowrap">{{ formatDate(t.pembayaran.tanggal_pembayaran) }}</span>
                  <span v-else class="text-[10px] px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-sm font-bold uppercase tracking-wider">Belum Ada</span>
                </td>
                <td class="px-8 py-5">
                  <div class="flex flex-col gap-0.5">
                    <p class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-tighter">
                      {{ t.details?.length || 0 }} ITEMS
                    </p>
                    <p class="text-xs text-slate-500 truncate max-w-[200px] capitalize">
                      {{ t.details?.map(d => d.produk?.nama_produk).join(',') }}
                    </p>
                  </div>
                </td>
                <td class="px-8 py-5 text-center">
                  <span class="text-sm font-black text-slate-900 dark:text-white tracking-tighter">{{ formatPrice(t.total_harga) }}</span>
                </td>
                <td class="px-8 py-5 text-center">
                  <span :class="['px-3 py-1 rounded-md text-[10px] font-black uppercase tracking-wider border', getStatusBadge(t.status ||'Selesai')]">
                    {{ t.status ||'Selesai' }}
                  </span>
                </td>
                <td class="px-8 py-5 text-right">
                  <div v-if="(t.status || 'selesai').toLowerCase() !== 'dibatalkan'" class="flex items-center justify-end gap-2">
                    <button @click="confirmCancel(t.id, t.kode_transaksi)" class="p-2 text-slate-400 hover:text-red-500 transition-colors" title="Batalkan Transaksi">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                    <button @click="printTransaction(t.id)" class="p-2 text-slate-400 hover:text-emerald-500 transition-colors" title="Cetak Struk">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                      </svg>
                    </button>
                    <Link :href="route('payments.show', t.id)" class="p-2 text-slate-400 hover:text-blue-500 transition-colors" title="Lihat Struk">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card List -->
        <div class="md:hidden divide-y-2 divide-slate-100 dark:divide-white/5">
          <div v-for="t in paginatedTransactions" :key="'mobile-'+t.id" class="p-5 space-y-4">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-black text-slate-900 dark:text-white text-base tracking-tight uppercase">{{ t.kode_transaksi }}</span>
                  <span :class="['px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider border whitespace-nowrap', getStatusBadge(t.status ||'Selesai')]">
                    {{ t.status ||'Selesai' }}
                  </span>
                </div>
                <p class="text-[10px] text-slate-500 font-medium mt-1">{{ formatDate(t.created_at) }}</p>
              </div>
              <span class="text-base font-black text-blue-600 dark:text-blue-400 whitespace-nowrap shrink-0">{{ formatPrice(t.total_harga) }}</span>
            </div>

            <div class="flex items-center justify-between gap-4 py-3 px-4 bg-slate-50 dark:bg-white/5 rounded-md border border-slate-100 dark:border-white/5">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-blue-600/10 text-blue-600 flex items-center justify-center font-bold text-xs">
                  {{ t.user?.name.charAt(0) ||'S' }}
                </div>
                <div class="flex flex-col">
                  <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400">Kasir: {{ t.user?.name ||'Sistem' }}</span>
                  <span class="text-[9px] text-slate-500 uppercase font-black tracking-widest">{{ t.metode_bayar ||'Cash' }}</span>
                </div>
              </div>
              <div class="text-right">
                <p v-if="t.pembayaran?.tanggal_pembayaran" class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mb-1">Bayar: {{ formatDate(t.pembayaran.tanggal_pembayaran) }}</p>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">{{ t.details?.length || 0 }} ITEMS</p>
              </div>
            </div>

            <div v-if="(t.status || 'selesai').toLowerCase() !== 'dibatalkan'" class="flex items-center gap-2 border-t border-slate-50 dark:border-white/5 pt-3 -mt-1">
              <button @click="confirmCancel(t.id, t.kode_transaksi)" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 rounded-md transition-colors active:scale-95" title="Batalkan">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Batal
              </button>
              <button @click="printTransaction(t.id)" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 rounded-md transition-colors active:scale-95" title="Cetak">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak
              </button>
              <Link :href="route('payments.show', t.id)" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md transition-colors active:scale-95" title="Detail">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Detail
              </Link>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredTransactionsAll.length === 0" class="px-8 py-32 text-center">
          <div class="flex flex-col items-center justify-center animate-in fade-in zoom-in duration-500">
            <div class="relative mb-6">
              <div class="absolute inset-0 bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-xl animate-pulse"></div>
              <div class="w-24 h-24 bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-800/50 rounded-full flex items-center justify-center relative shadow-lg border border-white dark:border-white/5">
                <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
              </div>
            </div>
            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-2">Belum Ada Transaksi</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium max-w-sm mx-auto mb-6">
              Tidak ada data transaksi yang ditemukan berdasarkan filter yang Anda terapkan.
            </p>
            <button @click="resetFilters" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 rounded-full text-xs font-black uppercase tracking-wider transition-all hover:scale-105 hover:shadow-lg shadow-slate-900/20">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Reset Filter
            </button>
          </div>
        </div>
        <!-- Pagination & Per Page -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
          <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
            <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
              Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ filteredTransactionsAll.length > 0 ? (currentPage - 1) * perPage + 1 : 0 }}-{{ Math.min(currentPage * perPage, filteredTransactionsAll.length) }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ filteredTransactionsAll.length }}</span> transaksi
            </p>
            <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
              <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
            <FrontendPagination :total="filteredTransactionsAll.length" :per-page="perPage" v-model="currentPage" />
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showCancelModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50">
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div v-if="showCancelModal" class="relative bg-white dark:bg-slate-900 rounded-md shadow-xl w-full max-w-sm overflow-hidden flex flex-col">
              <!-- Header -->
              <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-600 dark:text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Batalkan Transaksi</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Konfirmasi tindakan</p>
                  </div>
                </div>
                <button type="button" @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6">
                <div class="p-4 rounded-xl border border-red-100 dark:border-red-900/50 bg-red-50/50 dark:bg-red-900/20 text-center">
                  <p class="text-sm text-red-900 dark:text-red-200">
                    Apakah Anda yakin ingin membatalkan transaksi <span class="font-bold uppercase">{{ transactionToCancel?.kode }}</span>? Tindakan ini permanen.
                  </p>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                <button @click="showCancelModal = false" :disabled="isCancelling" class="px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                  Batal
                </button>
                <button @click="executeCancel" :disabled="isCancelling" class="flex items-center justify-center min-w-[100px] px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm shadow-red-500/20 transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                  <svg v-if="isCancelling" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ isCancelling ? 'Memproses...' : 'Batalkan' }}</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- Hidden Iframe for Direct Printing -->
    <iframe ref="printIframe" class="w-0 h-0 absolute border-none invisible opacity-0"></iframe>
  </AppLayout>
</template>
