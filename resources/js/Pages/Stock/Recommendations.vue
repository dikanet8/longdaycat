<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    lowStock: Array,
    fastMoving: Array
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
};
</script>

<template>
    <Head title="Rekomendasi Stok" />

    <AppLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Rekomendasi Stok</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Analisis cerdas untuk kebutuhan restok produk Anda.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-md border border-amber-200 dark:border-amber-500/20 flex items-center gap-2 shadow-sm">
                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                        <span class="text-xs font-black uppercase tracking-widest">{{ lowStock.length }} Produk Kritis</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Low Stock Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-rose-100 dark:bg-rose-500/10 text-rose-600 rounded-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Stok Kritis</h2>
                    </div>

                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                                    <tr>
                                        <th class="px-6 py-4">Produk</th>
                                        <th class="px-6 py-4 text-center">Sisa</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <tr v-for="p in lowStock" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-md bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-white/5">
                                                    <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-900 dark:text-white text-sm capitalize truncate">{{ p.nama_produk }}</p>
                                                    <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter">{{ p.kode_produk }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-rose-500 font-black text-sm">{{ p.stok }}</span>
                                            <span class="text-[10px] text-slate-400 ml-1">/{{ p.stok_minimal }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2 py-0.5 bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 rounded text-[10px] font-black uppercase tracking-wider">
                                                {{ p.stok === 0 ? 'Habis' : 'Sangat Rendah' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <Link :href="route('stock.index')" class="p-2 text-slate-400 hover:text-blue-500 transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="lowStock.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <p class="text-slate-400 text-sm italic">Semua stok dalam kondisi aman.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fast Moving Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-blue-100 dark:bg-blue-500/10 text-blue-600 rounded-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Produk Terlaris (30 Hari)</h2>
                    </div>

                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                                    <tr>
                                        <th class="px-6 py-4">Produk</th>
                                        <th class="px-6 py-4 text-center">Stok Saat Ini</th>
                                        <th class="px-6 py-4 text-right">Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <tr v-for="p in fastMoving" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-md bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-white/5">
                                                    <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-900 dark:text-white text-sm capitalize truncate">{{ p.nama_produk }}</p>
                                                    <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter">{{ p.kode_produk }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span :class="p.stok <= p.stok_minimal ? 'text-rose-500' : 'text-slate-900 dark:text-white'" class="font-black text-sm">{{ p.stok }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 rounded text-[10px] font-black uppercase tracking-wider">
                                                Restok Segera
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="fastMoving.length === 0">
                                        <td colspan="3" class="px-6 py-12 text-center">
                                            <p class="text-slate-400 text-sm italic">Belum ada data penjualan yang cukup.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analysis Card -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-md p-8 text-white relative overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div class="relative z-10 max-w-2xl">
                    <h3 class="text-2xl font-black mb-4 tracking-tight uppercase">Tips Manajemen Stok</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="font-medium">Prioritaskan restok untuk produk yang masuk dalam kategori **Fast Moving** meskipun stoknya belum mencapai batas minimal.</p>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="font-medium">Segera restok produk dengan status **Habis** untuk menghindari hilangnya potensi penjualan harian.</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
