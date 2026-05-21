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
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Rekomendasi Stok</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Analisis cerdas untuk kebutuhan restok produk Anda.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="px-3 md:px-4 py-2 bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-xl border border-amber-200 dark:border-amber-500/20 flex items-center gap-2 shadow-sm">
                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                        <span class="text-[10px] md:text-xs font-black uppercase tracking-widest">{{ lowStock.length }} Produk Kritis</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Low Stock Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-rose-100 dark:bg-rose-500/10 text-rose-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Stok Kritis</h2>
                    </div>

                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-3xl md:rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                                    <tr>
                                        <th class="px-6 py-4">Produk</th>
                                        <th class="px-6 py-4 text-center">Sisa</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                                    <tr v-for="p in lowStock" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-white/5">
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
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card List -->
                        <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
                            <div v-for="p in lowStock" :key="'low-'+p.id" class="p-5 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-white/5 overflow-hidden shrink-0 border border-slate-200 dark:border-white/10">
                                        <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 dark:text-white text-base capitalize truncate leading-tight">{{ p.nama_produk }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest">Sisa {{ p.stok }}</span>
                                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Min. {{ p.stok_minimal }}</span>
                                        </div>
                                    </div>
                                </div>
                                <Link :href="route('stock.index')" class="p-3 bg-blue-600 text-white rounded-xl active:scale-90 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <div v-if="lowStock.length === 0" class="px-6 py-12 text-center">
                            <p class="text-slate-400 text-sm italic font-medium">Semua stok dalam kondisi aman.</p>
                        </div>
                    </div>
                </div>

                <!-- Fast Moving Section -->
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-blue-100 dark:bg-blue-500/10 text-blue-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Produk Terlaris (30 Hari)</h2>
                    </div>

                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-3xl md:rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                                    <tr>
                                        <th class="px-6 py-4">Produk</th>
                                        <th class="px-6 py-4 text-center">Stok Saat Ini</th>
                                        <th class="px-6 py-4 text-right">Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                                    <tr v-for="p in fastMoving" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-white/5">
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
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card List -->
                        <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
                            <div v-for="p in fastMoving" :key="'fast-'+p.id" class="p-5 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-white/5 overflow-hidden shrink-0 border border-slate-200 dark:border-white/10">
                                        <img v-if="p.gambar" :src="p.gambar" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 dark:text-white text-base capitalize truncate leading-tight">{{ p.nama_produk }}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Stok Saat Ini: {{ p.stok }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 rounded-lg text-[9px] font-black uppercase tracking-wider text-center shrink-0">
                                    Restok
                                </span>
                            </div>
                        </div>

                        <div v-if="fastMoving.length === 0" class="px-6 py-12 text-center">
                            <p class="text-slate-400 text-sm italic font-medium">Belum ada data penjualan yang cukup.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analysis Card -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-6 md:p-8 text-white relative overflow-hidden shadow-2xl">
                <div class="absolute top-0 right-0 p-8 opacity-10">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div class="relative z-10 max-w-2xl">
                    <h3 class="text-xl md:text-2xl font-black mb-4 tracking-tight uppercase">Tips Manajemen Stok</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="font-medium text-sm md:text-base leading-relaxed">Prioritaskan restok untuk produk yang masuk dalam kategori <span class="font-black underline decoration-blue-300">Fast Moving</span> meskipun stoknya belum mencapai batas minimal.</p>
                        </li>
                        <li class="flex items-start gap-3">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="font-medium text-sm md:text-base leading-relaxed">Segera restok produk dengan status <span class="font-black underline decoration-rose-400">Habis</span> untuk menghindari hilangnya potensi penjualan harian.</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
