<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    products: Object,
    filters: Object
});

const perPage = ref(props.filters?.per_page || 10);

const search = ref('');
const filterUkuran = ref('');
const filterWarna = ref('');
const filterStok = ref('');

const uniqueColors = computed(() => {
    return [...new Set(props.products.data.map(p => p.warna))].filter(Boolean);
});

const filteredProducts = computed(() => {
    return props.products.data.filter(product => {
        const matchesSearch = product.nama_produk.toLowerCase().includes(search.value.toLowerCase()) ||
                            product.kode_produk.toLowerCase().includes(search.value.toLowerCase());
        const matchesUkuran = !filterUkuran.value || product.ukuran === filterUkuran.value;
        const matchesWarna = !filterWarna.value || product.warna === filterWarna.value;
        
        let matchesStok = true;
        if (filterStok.value === 'low') {
            matchesStok = product.stok > 0 && product.stok < product.stok_minimal;
        } else if (filterStok.value === 'out') {
            matchesStok = product.stok <= 0;
        }

        return matchesSearch && matchesUkuran && matchesWarna && matchesStok;
    });
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
};

const showDeleteModal = ref(false);
const productToDelete = ref(null);

const confirmDelete = (product) => {
    productToDelete.value = product;
    showDeleteModal.ref = true;
};

const deleteProduct = () => {
    if (productToDelete.value) {
        router.delete(route('products.destroy', productToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                productToDelete.value = null;
            }
        });
    }
};

const resetFilters = () => {
    search.value = '';
    filterUkuran.value = '';
    filterWarna.value = '';
    filterStok.value = '';
    perPage.value = 10;
};

import { watch } from 'vue';
watch(perPage, (value) => {
    router.get(route('products.index'), { per_page: value }, { preserveState: true, replace: true });
});
</script>

<template>
    <Head title="Manajemen Produk" />

    <AppLayout>
        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Manajemen Produk</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Kelola daftar menu dan inventaris produk Anda.</p>
                </div>
                <!-- Desktop Add Button -->
                <Link :href="route('products.create')" class="hidden md:flex px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-md shadow-lg shadow-blue-500/20 transition-all font-bold text-sm items-center gap-2 active:scale-[0.98]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Produk Baru
                </Link>
            </div>

            <!-- Mobile Add Button (Floating) -->
            <Link :href="route('products.create')" class="md:hidden fixed bottom-24 right-6 w-14 h-14 bg-blue-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 active:scale-90 transition-transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </Link>

            <!-- Search & Filters (Synced Layout) -->
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
                            placeholder="Cari produk..." 
                            class="block w-full pl-10 pr-3 py-2 border-none bg-slate-50 dark:bg-white/5 rounded-md text-xs ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
                        >
                    </div>

                    <!-- Ukuran Filter -->
                    <select v-model="filterUkuran" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                        <option value="">Semua Ukuran</option>
                        <option value="S">Size S</option>
                        <option value="M">Size M</option>
                        <option value="L">Size L</option>
                        <option value="XL">Size XL</option>
                        <option value="XXL">Size XXL</option>
                    </select>

                    <!-- Warna Filter -->
                    <select v-model="filterWarna" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                        <option value="">Semua Warna</option>
                        <option v-for="warna in uniqueColors" :key="warna" :value="warna">{{ warna }}</option>
                    </select>

                    <!-- Status Stok Filter -->
                    <select v-model="filterStok" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                        <option value="">Status Stok</option>
                        <option value="low">Stok Menipis</option>
                        <option value="out">Stok Habis</option>
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

            <!-- Table -->
            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md overflow-hidden shadow-sm dark:shadow-2xl border border-slate-200 dark:border-white/5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-[0.2em] font-black">
                            <tr>
                                <th class="px-8 py-5 text-[11px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400">Info Produk</th>
                                <th class="px-8 py-5 text-[11px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400">Detail</th>
                                <th class="px-8 py-5 text-[11px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400">Harga</th>
                                <th class="px-8 py-5 text-[11px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400">Stok</th>
                                <th class="px-8 py-5 text-[11px] font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
                            <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-md bg-slate-100 dark:bg-gradient-to-br dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden shadow-inner">
                                            <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover" />
                                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-900 dark:text-white text-sm transition-colors capitalize">{{ product.nama_produk }}</h4>
                                            <p class="text-[10px] text-slate-500 mt-0.5 font-mono uppercase tracking-tighter">{{ product.kode_produk }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex gap-2">
                                        <span class="px-2 py-1 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded text-[10px] font-bold uppercase">{{ product.warna }}</span>
                                        <span class="px-2 py-1 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded text-[10px] font-bold uppercase">{{ product.ukuran }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="text-slate-900 dark:text-white font-black text-sm tracking-tighter">{{ formatPrice(product.harga) }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-2">
                                        <div :class="[product.stok < product.stok_minimal ? 'bg-rose-500' : 'bg-emerald-500', 'w-1.5 h-1.5 rounded-full shadow-[0_0_8px] shadow-current']"></div>
                                        <span class="text-slate-600 dark:text-slate-300 font-bold text-sm">{{ product.stok }} item</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('products.edit', product.id)" class="p-2 text-slate-400 hover:text-blue-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button @click="confirmDelete(product)" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
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
                        Menampilkan {{ products.from }}-{{ products.to }} dari {{ products.total }}
                    </p>
                    <Pagination :links="products.links" />
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 z-[100] overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showDeleteModal = false"></div>

                    <div class="relative bg-white dark:bg-slate-900 rounded-md overflow-hidden shadow-2xl transform transition-all sm:max-w-lg w-full">
                        <div class="p-8">
                            <div class="flex items-center justify-center w-16 h-16 mx-auto bg-red-100 dark:bg-red-500/10 rounded-md mb-6">
                                <svg class="w-8 h-8 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="text-center">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Hapus Produk?</h3>
                                <p class="text-slate-500 dark:text-slate-400">Tindakan ini tidak dapat dibatalkan. Produk <strong>{{ productToDelete?.nama_produk }}</strong> akan dihapus permanen.</p>
                            </div>
                        </div>
                        <div class="p-6 bg-slate-50 dark:bg-white/5 flex flex-col sm:flex-row gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 px-6 py-3 font-bold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-800 rounded-md hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                                Batal
                            </button>
                            <button @click="deleteProduct" class="flex-1 px-6 py-3 font-bold text-white bg-red-600 rounded-md shadow-lg shadow-red-500/20 hover:bg-red-500 transition-all active:scale-95">
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
