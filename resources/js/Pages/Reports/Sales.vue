<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';

import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    stats: Object,
    chart: Object,
    filters: Object,
    recent_transactions: Object
});

const formFilters = ref({
    date: (props.filters && props.filters.date) ? props.filters.date : '',
    month: props.filters ? props.filters.month : new Date().getMonth() + 1,
    year: props.filters ? props.filters.year : new Date().getFullYear(),
    per_page: (props.filters && props.filters.per_page) ? props.filters.per_page : 10
});

import { watch } from 'vue';
watch(() => formFilters.value.per_page, (value) => {
    applyFilter();
});

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price || 0);
};

const stats = computed(() => [
    { name: 'Total Pendapatan', value: formatPrice(props.stats?.total_pendapatan || 0), change: '+0%', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-emerald-400' },
    { name: 'Total Transaksi', value: props.stats?.total_transaksi || 0, change: '+0%', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', color: 'text-blue-400' },
    { name: 'Produk Terlaris', value: props.stats?.produk_terlaris || '-', change: 'Top #1', icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', color: 'text-amber-400' },
    { name: 'Total Produk Terjual', value: (props.stats?.total_produk_terjual || 0) + ' pcs', change: '+0%', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', color: 'text-purple-400' },
]);

const chartData = computed(() => ({
    labels: props.chart?.labels || [],
    datasets: [
        {
            label: 'Penjualan',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderColor: '#3b82f6',
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#3b82f6',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            data: props.chart?.data || []
        }
    ]
}));

const applyFilter = () => {
    router.get('/reports/sales', formFilters.value, { preserveState: true });
};

const printReport = () => {
    window.print();
};

const getFilterPeriod = () => {
    try {
        if (formFilters.value.date) {
            const d = new Date(formFilters.value.date);
            if (!isNaN(d.getTime())) {
                return d.toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
            }
        }
    } catch (e) {
        console.error(e);
    }
    
    const monthIndex = parseInt(formFilters.value.month);
    if (!isNaN(monthIndex) && monthIndex >= 1 && monthIndex <= 12 && formFilters.value.year) {
        return `${months[monthIndex - 1]} ${formFilters.value.year}`;
    }
    return `Tahun ${formFilters.value.year || new Date().getFullYear()}`;
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleFont: { size: 14, weight: 'bold' },
            bodyFont: { size: 13 },
            padding: 12,
            cornerRadius: 12,
            displayColors: false,
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            grid: { display: true, color: 'rgba(203, 213, 225, 0.1)', drawBorder: false },
            ticks: { 
                display: true, 
                color: '#64748b', 
                font: { size: 11 }, 
                beginAtZero: true,
                callback: function(value) {
                    if (value >= 1000000000) {
                        return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
                    } else if (value >= 1000000) {
                        return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                    } else if (value >= 1000) {
                        return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                    }
                    return 'Rp ' + value;
                }
            }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 11 } }
        }
    }
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

const printDate = computed(() => {
    return new Date().toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
});
</script>

<template>
    <Head title="Laporan Penjualan" />

    <AppLayout>
        <div class="space-y-8">
            <!-- Print Only Header -->
            <div class="hidden print:block mb-6 border-b-2 border-slate-900 pb-4">
                <div class="flex justify-between items-end">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Longdaycat.Co</h1>
                        <p class="text-xs text-slate-600 mt-1">Laporan Penjualan Terperinci</p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p class="font-semibold">Periode: {{ getFilterPeriod() }}</p>
                        <p class="text-[10px] mt-1 text-slate-500">Dicetak pada: {{ printDate }}</p>
                    </div>
                </div>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 no-print">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Laporan Penjualan</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Analisis performa penjualan produk Anda.</p>
                </div>
            </div>

            <!-- Filters Bar (Above Cards, Borderless) -->
            <div class="flex flex-wrap items-end gap-3 no-print">
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold ml-1 mb-1.5">Tanggal</label>
                    <input type="date" v-model="formFilters.date" @change="applyFilter" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-36 py-2.5 px-3.5 shadow-sm" />
                </div>
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold ml-1 mb-1.5">Bulan</label>
                    <select v-model="formFilters.month" @change="applyFilter" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-32 py-2.5 px-3.5 shadow-sm cursor-pointer">
                        <option v-for="(m, i) in months" :key="m" :value="i+1">{{ m }}</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 font-bold ml-1 mb-1.5">Tahun</label>
                    <select v-model="formFilters.year" @change="applyFilter" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-24 py-2.5 px-3.5 shadow-sm cursor-pointer">
                        <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
                <div class="flex flex-col justify-end">
                    <button 
                        @click="printReport" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-sm transition-all active:scale-95 h-[38px]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cetak
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.name" 
                    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-slate-900 dark:ring-white/10 flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ stat.name }}</span>
                        <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-lg" :class="stat.color">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white mt-2 truncate">{{ stat.value }}</div>
                    <div class="mt-2 flex items-center gap-1.5 text-xs font-semibold">
                        <span class="text-emerald-600 dark:text-emerald-400">
                            {{ stat.change }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-md overflow-hidden shadow-sm border-t-4 border-t-blue-600 no-print">
                <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-slate-900 dark:text-white">Trend Penjualan</h3>
                        <p class="text-xs text-slate-500 font-medium">Visualisasi pendapatan dalam periode terpilih</p>
                    </div>
                </div>
                <div class="p-6 h-[400px]">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Detailed Table -->
            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-100 dark:border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h3 class="font-bold text-lg text-slate-900 dark:text-white">Daftar Transaksi Terperinci</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                            <tr>
                                <th class="px-8 py-5">Waktu</th>
                                <th class="px-8 py-5">Kode</th>
                                <th class="px-8 py-5">Item</th>
                                <th class="px-8 py-5">Total</th>
                                <th class="px-8 py-5">Metode</th>
                                <th class="px-8 py-5">Kasir</th>
                                <th class="px-8 py-5 text-right no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr v-for="trx in recent_transactions?.data || []" :key="trx.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                <td class="px-8 py-5 text-xs text-slate-500 font-medium">{{ formatDate(trx.created_at) }}</td>
                                <td class="px-8 py-5">
                                    <span class="font-black text-slate-900 dark:text-white text-sm uppercase tracking-tighter">{{ trx.kode_transaksi }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <p class="text-xs text-slate-600 dark:text-slate-300 font-bold capitalize truncate max-w-xs">
                                        {{ trx.details ? trx.details.map(d => d.produk?.nama_produk).filter(Boolean).join(', ') : '' }}
                                    </p>
                                </td>
                                <td class="px-8 py-5 font-black text-blue-600 dark:text-blue-400 text-sm tracking-tighter">{{ formatPrice(trx.total_harga) }}</td>
                                <td class="px-8 py-5">
                                    <span class="px-2 py-1 bg-slate-100 dark:bg-white/5 text-[10px] font-black uppercase rounded">{{ trx.metode_bayar }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 capitalize">{{ trx.user?.name || 'Sistem' }}</span>
                                </td>
                                <td class="px-8 py-5 text-right no-print">
                                    <Link :href="`/payments/${trx.id}`" class="text-blue-600 font-black text-[10px] uppercase hover:underline">Detail</Link>
                                </td>
                            </tr>
                            <tr v-if="!recent_transactions?.data || recent_transactions.data.length === 0">
                                <td colspan="7" class="px-8 py-12 text-center text-xs text-slate-400 font-bold uppercase tracking-wider bg-slate-50/10 dark:bg-white/[0.01]">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-8 h-8 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span>Tidak ada transaksi pada periode ini</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="px-8 py-5 border-t border-slate-100 dark:border-white/5 flex items-center justify-end gap-6 bg-slate-50/50 dark:bg-white/[0.02] no-print">
                    <!-- Per Page Selector -->
                    <div class="flex items-center gap-2 mr-auto">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
                        <select v-model="formFilters.per_page" class="bg-white dark:bg-slate-800 border-none rounded-md text-[11px] font-bold text-slate-700 dark:text-slate-300 ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 py-1.5 pl-3 pr-8 w-16 cursor-pointer">
                            <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </div>

                    <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">
                        Menampilkan {{ recent_transactions?.from || 0 }}-{{ recent_transactions?.to || 0 }} dari {{ recent_transactions?.total || 0 }}
                    </p>
                    <Pagination :links="recent_transactions?.links || []" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Reset heights and layout scrolling for proper multi-page printing */
    html, body, #app, main, .h-\[100dvh\], .flex-1, .space-y-8 {
        height: auto !important;
        overflow: visible !important;
        position: static !important;
        background-color: white !important;
        color: black !important;
        background: none !important;
    }

    /* Hide layout chrome and non-print items */
    aside, 
    header, 
    nav, 
    .no-print, 
    button, 
    a, 
    select {
        display: none !important;
    }

    /* Full-width print space */
    main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    /* Print friendly grid/containers */
    .grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 1rem !important;
    }
    
    .grid > div {
        border: 1px solid #e2e8f0 !important;
        padding: 1rem !important;
        border-radius: 0.5rem !important;
        background: white !important;
        box-shadow: none !important;
        color: black !important;
    }

    /* Table printing optimizations */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 1.5rem !important;
    }
    
    th, td {
        border-bottom: 1px solid #cbd5e1 !important;
        padding: 10px 12px !important;
        font-size: 11px !important;
        color: black !important;
    }

    th {
        font-weight: bold !important;
        background-color: #f1f5f9 !important;
    }

    tr {
        page-break-inside: avoid !important;
    }
}
</style>
