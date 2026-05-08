<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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
    recent_transactions: Array
});

const formFilters = ref({
    date: props.filters.date || '',
    month: props.filters.month,
    year: props.filters.year,
});

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
};

const stats = computed(() => [
    { name: 'Total Pendapatan', value: formatPrice(props.stats.total_pendapatan), change: '+0%', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-emerald-400' },
    { name: 'Total Transaksi', value: props.stats.total_transaksi, change: '+0%', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', color: 'text-blue-400' },
    { name: 'Produk Terlaris', value: props.stats.produk_terlaris, change: 'Top #1', icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', color: 'text-amber-400' },
    { name: 'Total Produk Terjual', value: props.stats.total_produk_terjual + ' pcs', change: '+0%', icon: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', color: 'text-purple-400' },
]);

const chartData = computed(() => ({
    labels: props.chart.labels,
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
            data: props.chart.data
        }
    ]
}));

import { router } from '@inertiajs/vue3';

const applyFilter = () => {
    router.get('/dashboard', formFilters.value, { preserveState: true });
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            titleFont: { size: 14, weight: 'bold' },
            bodyFont: { size: 13 },
            padding: 12,
            cornerRadius: 12,
            displayColors: false
        }
    },
    scales: {
        y: {
            grid: {
                display: true,
                color: 'rgba(203, 213, 225, 0.1)',
                drawBorder: false
            },
            ticks: {
                display: true,
                color: '#64748b',
                font: { size: 11 },
                beginAtZero: true
            },
            min: 0,
            suggestedMin: 0
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: '#64748b',
                font: { size: 11 }
            }
        }
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="space-y-8">
            <!-- Header & Filters -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Dashboard</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Ringkasan performa bisnis Anda secara real-time.</p>
                </div>
            </div>

            <!-- Filter Section (Separated) -->
            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md p-2.5 rounded-md border border-slate-200 dark:border-white/5 shadow-sm inline-flex flex-wrap items-center gap-3">
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase tracking-wider text-slate-500 font-bold ml-2 mb-1">Tanggal</label>
                    <input type="date" v-model="formFilters.date" @change="applyFilter" class="bg-slate-50 dark:bg-white/5 border-none rounded-md text-xs text-slate-800 dark:text-white focus:ring-1 focus:ring-blue-500 w-36 py-2 px-3" />
                </div>
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase tracking-wider text-slate-500 font-bold ml-2 mb-1">Bulan</label>
                    <select v-model="formFilters.month" @change="applyFilter" class="bg-slate-50 dark:bg-white/5 border-none rounded-md text-xs text-slate-800 dark:text-white focus:ring-1 focus:ring-blue-500 w-32 py-2 px-3">
                        <option v-for="(m, i) in months" :key="m" :value="i+1">{{ m }}</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-[10px] uppercase tracking-wider text-slate-500 font-bold ml-2 mb-1">Tahun</label>
                    <select v-model="formFilters.year" @change="applyFilter" class="bg-slate-50 dark:bg-white/5 border-none rounded-md text-xs text-slate-800 dark:text-white focus:ring-1 focus:ring-blue-500 w-24 py-2 px-3">
                        <option v-for="y in [2024, 2025, 2026]" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="stat in stats" :key="stat.name" 
                    class="bg-white dark:bg-slate-900/40 backdrop-blur-md p-6 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all group relative overflow-hidden shadow-sm border border-slate-200 dark:border-white/5 border-t-4"
                    :class="{
                        'border-t-emerald-500': stat.color.includes('emerald'),
                        'border-t-blue-500': stat.color.includes('blue'),
                        'border-t-amber-500': stat.color.includes('amber'),
                        'border-t-purple-500': stat.color.includes('purple'),
                    }"
                >
                    <!-- Glass shine effect -->
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-slate-100 dark:bg-white/5 rounded-full blur-2xl group-hover:bg-blue-500/5 transition-all"></div>
                    
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl group-hover:scale-110 transition-transform duration-500 shadow-inner" :class="stat.color">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
                            </svg>
                        </div>
                        <span :class="[stat.change.startsWith('+') || stat.change.includes('#') ? 'text-emerald-500 bg-emerald-500/10' : 'text-rose-500 bg-rose-400/10', 'text-[10px] font-black px-2 py-1 rounded-lg uppercase tracking-wider']">
                            {{ stat.change }}
                        </span>
                    </div>

                    <p class="text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-[0.2em]">{{ stat.name }}</p>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-2 truncate tracking-tighter capitalize">{{ stat.value }}</h3>
                    
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Sales Chart -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900/40 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden shadow-sm relative border-t-4 border-t-blue-600">
                    <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-lg text-slate-900 dark:text-white">Grafik Penjualan</h3>
                            <p class="text-xs text-slate-500 font-medium">Data penjualan 7 hari terakhir</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-blue-600 rounded-full"></span>
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-tighter">Revenue</span>
                        </div>
                    </div>
                    <div class="p-6 h-[300px] relative">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md border border-slate-200 dark:border-white/5 rounded-2xl overflow-hidden shadow-sm flex flex-col relative border-t-4 border-t-purple-600">
                    <div class="p-6 border-b border-slate-100 dark:border-white/5">
                        <h3 class="font-bold text-lg text-slate-900 dark:text-white">Transaksi Terakhir</h3>
                        <p class="text-xs text-slate-500 font-medium">5 Transaksi terbaru hari ini</p>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div v-if="recent_transactions.length > 0" class="divide-y divide-slate-100 dark:divide-white/5">
                            <div v-for="trx in recent_transactions" :key="trx.id" class="p-4 hover:bg-slate-50 dark:hover:bg-white/5 transition-all group">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-sm flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-tighter">{{ trx.kode_transaksi }}</p>
                                            <p class="text-[10px] text-slate-500 font-medium">{{ new Date(trx.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }} • Tunai</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-slate-900 dark:text-white">{{ formatPrice(trx.total_harga) }}</p>
                                        <span class="text-[9px] font-bold text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded-sm uppercase tracking-wider">Berhasil</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="h-full flex flex-col items-center justify-center p-8 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Belum ada transaksi</p>
                        </div>
                    </div>
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-white/5">
                        <Link href="/payments" class="flex items-center justify-center gap-2 text-xs font-black text-blue-600 dark:text-blue-400 hover:text-blue-700 uppercase tracking-widest transition-all">
                            Lihat Semua Laporan
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
