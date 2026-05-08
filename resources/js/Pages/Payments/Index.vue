<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    transactions: Object,
    users: Array,
    filters: Object
});

const perPage = ref(props.filters?.per_page || 10);

const search = ref('');
const filterStatus = ref('');
const filterMetode = ref('');
const filterUser = ref('');

const filteredTransactions = computed(() => {
    return props.transactions.data.filter(t => {
        const matchesSearch = t.kode_transaksi?.toLowerCase().includes(search.value.toLowerCase()) ||
                            t.id.toString().includes(search.value);
        const matchesStatus = !filterStatus.value || (t.status || 'Selesai').toLowerCase() === filterStatus.value.toLowerCase();
        const matchesMetode = !filterMetode.value || t.metode_bayar?.toLowerCase() === filterMetode.value.toLowerCase();
        const matchesUser = !filterUser.value || t.user_id?.toString() === filterUser.value.toString();

        return matchesSearch && matchesStatus && matchesMetode && matchesUser;
    });
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusBadge = (status) => {
    switch (status?.toLowerCase()) {
        case 'selesai':
            return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        case 'pending':
            return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
        case 'dibatalkan':
            return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
        default:
            return 'bg-slate-500/10 text-slate-500 border-slate-500/20';
    }
};

const resetFilters = () => {
    search.value = '';
    filterStatus.value = '';
    filterMetode.value = '';
    filterUser.value = '';
    perPage.value = 10;
};

import { watch } from 'vue';
watch(perPage, (value) => {
    router.get(route('payments.index'), { per_page: value }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Riwayat Pembayaran" />

    <AppLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Riwayat Pembayaran</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Kelola seluruh transaksi dan pantau status pembayaran secara real-time.</p>
                </div>
            </div>

            <!-- Filter Bar (Synced) -->
            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md p-2.5 rounded-md border border-slate-200 dark:border-white/5 shadow-sm space-y-2.5">
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Search Field -->
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari kode..." 
                            class="block w-full pl-10 pr-3 py-2 border-none bg-slate-50 dark:bg-white/5 rounded-md text-xs ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
                        >
                    </div>

                    <!-- Status Filter -->
                    <select v-model="filterStatus" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                        <option value="">Semua Status</option>
                        <option value="selesai">Selesai</option>
                        <option value="pending">Pending</option>
                        <option value="dibatalkan">Dibatalkan</option>
                    </select>

                    <!-- Method Filter -->
                    <select v-model="filterMetode" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                        <option value="">Semua Metode</option>
                        <option value="cash">Cash</option>
                        <option value="qris">QRIS</option>
                    </select>

                    <!-- User Filter -->
                    <select v-model="filterUser" class="w-full md:w-48 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                        <option value="">Semua Kasir</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                    </select>

                    <div class="h-6 w-[1px] bg-slate-200 dark:bg-white/10 mx-1 hidden lg:block"></div>

                    <!-- Reset Button -->
                    <button 
                        @click="resetFilters"
                        class="p-2.5 bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-blue-500 rounded-md transition-all shadow-inner flex-shrink-0 group"
                        title="Reset Filter"
                    >
                        <svg class="w-4 h-4 transition-transform duration-500 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Table Area -->
            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md shadow-sm border border-slate-200 dark:border-white/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-[0.2em] font-black">
                            <tr>
                                <th class="px-8 py-5">Waktu</th>
                                <th class="px-8 py-5">Transaksi</th>
                                <th class="px-8 py-5">Kasir</th>
                                <th class="px-8 py-5">Item</th>
                                <th class="px-8 py-5 text-center">Total</th>
                                <th class="px-8 py-5 text-center">Metode</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr v-for="t in filteredTransactions" :key="t.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                <td class="px-8 py-5">
                                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">{{ formatDate(t.created_at) }}</p>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-900 dark:text-white text-sm tracking-tight uppercase">{{ t.kode_transaksi }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono uppercase">{{ t.id }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize">{{ t.user?.name || 'Sistem' }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex flex-col gap-0.5">
                                        <p class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-tighter">
                                            {{ t.details?.length || 0 }} ITEMS
                                        </p>
                                        <p class="text-xs text-slate-500 truncate max-w-[200px] capitalize">
                                            {{ t.details?.map(d => d.produk?.nama_produk).join(', ') }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span class="text-sm font-black text-slate-900 dark:text-white tracking-tighter">{{ formatPrice(t.total_harga) }}</span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-white/5 rounded-full text-[10px] font-black text-slate-600 dark:text-slate-400 uppercase tracking-widest border border-slate-200 dark:border-white/10">
                                        {{ t.metode_bayar || 'Cash' }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border', getStatusBadge(t.status || 'Selesai')]">
                                        {{ t.status || 'Selesai' }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <Link :href="route('payments.show', t.id)" class="inline-flex p-2.5 bg-slate-50 dark:bg-white/5 rounded-md text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all hover:scale-110 shadow-sm border border-slate-200 dark:border-white/5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="filteredTransactions.length === 0">
                                <td colspan="8" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-slate-50 dark:bg-white/5 rounded-md flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium italic">Tidak ada pembayaran yang cocok dengan filter.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="px-8 py-5 border-t border-slate-100 dark:border-white/5 flex items-center justify-end gap-6 bg-slate-50/50 dark:bg-white/[0.02]">
                    <!-- Per Page Selector -->
                    <div class="flex items-center gap-2 mr-auto">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
                        <select v-model="perPage" class="bg-white dark:bg-slate-800 border-none rounded-md text-[11px] font-bold text-slate-700 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 py-1 px-3">
                            <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </div>

                    <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">
                        Menampilkan {{ transactions.from }}-{{ transactions.to }} dari {{ transactions.total }}
                    </p>
                    <Pagination :links="transactions.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
