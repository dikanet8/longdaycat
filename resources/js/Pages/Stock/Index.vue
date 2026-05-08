<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    products: Object,
    history: Object,
    users: Array,
    filters: Object
});

const perPage = ref(props.filters?.per_page || 10);

watch(perPage, (value) => {
    router.get(route('stock.index'), { per_page: value }, { preserveState: true, replace: true });
});

const search = ref('');
const showUpdateModal = ref(false);
const selectedProduct = ref(null);
const activeTab = ref('products'); // 'products' or 'history'

// History Filters
const historySearch = ref('');
const historyDate = ref('');
const historyActivity = ref('');
const historyUser = ref('');

const form = useForm({
    kode_produk: '',
    jumlah: 1,
    jenis_aktivitas: 'masuk',
    keterangan: ''
});

const filteredProducts = computed(() => {
    return props.products.data.filter(p => 
        p.nama_produk.toLowerCase().includes(search.value.toLowerCase()) ||
        p.kode_produk.toLowerCase().includes(search.value.toLowerCase())
    );
});

const filteredHistory = computed(() => {
    return props.history.data.filter(log => {
        const matchesSearch = log.produk?.nama_produk?.toLowerCase().includes(historySearch.value.toLowerCase()) ||
                            log.kode_produk.toLowerCase().includes(historySearch.value.toLowerCase());
        const matchesDate = historyDate.value ? log.created_at.startsWith(historyDate.value) : true;
        const matchesActivity = historyActivity.value ? log.jenis_aktivitas === historyActivity.value : true;
        const matchesUser = historyUser.value ? log.user_id?.toString() === historyUser.value.toString() : true;

        return matchesSearch && matchesDate && matchesActivity && matchesUser;
    });
});

const openUpdateModal = (product) => {
    selectedProduct.value = product;
    form.kode_produk = product.kode_produk;
    form.jumlah = 1;
    form.jenis_aktivitas = 'masuk';
    form.keterangan = '';
    showUpdateModal.value = true;
};

const submitUpdate = () => {
    form.post(route('stock.update'), {
        onSuccess: () => {
            showUpdateModal.value = false;
        }
    });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const resetProductsFilter = () => {
    search.value = '';
};

const resetHistoryFilters = () => {
    historySearch.value = '';
    historyDate.value = '';
    historyActivity.value = '';
    historyUser.value = '';
};
</script>

<template>
    <Head title="Manajemen Stok" />

    <AppLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Manajemen Stok</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Pantau dan kelola persediaan produk Anda.</p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex gap-2 p-1 bg-slate-100 dark:bg-white/5 rounded-md w-fit">
                    <button 
                        @click="activeTab = 'products'"
                        :class="[activeTab === 'products' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-white']"
                        class="px-6 py-2.5 rounded-md text-xs font-bold transition-all"
                    >
                        Kelola Produk
                    </button>
                    <button 
                        @click="activeTab = 'history'"
                        :class="[activeTab === 'history' ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-white']"
                        class="px-6 py-2.5 rounded-md text-xs font-bold transition-all"
                    >
                        Riwayat Perubahan
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="transition-all duration-300">
                <!-- Tab: Kelola Produk -->
                <div v-if="activeTab === 'products'" class="animate-in fade-in slide-in-from-bottom-4 duration-500 space-y-6">
                    <!-- Filter Section (Synced) -->
                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md p-2.5 rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <div class="relative w-full md:w-64">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="Cari produk..." 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 pl-10 pr-3 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ring-1 ring-slate-200 dark:ring-white/10"
                                />
                            </div>
                            <button 
                                @click="resetProductsFilter"
                                class="p-2.5 bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-blue-500 rounded-md transition-all shadow-inner flex-shrink-0 group"
                                title="Reset Search"
                            >
                                <svg class="w-4 h-4 transition-transform duration-500 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Main Table Container -->
                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-[0.2em] font-black">
                                    <tr>
                                        <th class="px-8 py-5">Produk</th>
                                        <th class="px-8 py-5 text-center">Stok</th>
                                        <th class="px-8 py-5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                                        <td class="px-8 py-5">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-md bg-slate-100 dark:bg-slate-800 overflow-hidden shadow-inner flex-shrink-0 border border-slate-200 dark:border-white/5">
                                                    <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover" />
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-900 dark:text-white text-sm capitalize truncate">{{ product.nama_produk }}</p>
                                                    <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-0.5">{{ product.kode_produk }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5">
                                            <div class="flex flex-col items-center">
                                                <span :class="[product.stok <= product.stok_minimal ? 'text-rose-500' : 'text-slate-900 dark:text-white', 'text-sm md:text-base font-black']">
                                                    {{ product.stok }}
                                                </span>
                                                <span class="text-[9px] text-slate-400 uppercase font-black tracking-widest mt-1">Min: {{ product.stok_minimal }}</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <button 
                                                @click="openUpdateModal(product)"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-wider rounded-md shadow-lg shadow-blue-500/20 transition-all active:scale-95"
                                            >
                                                Update
                                            </button>
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
                </div>

                <!-- Tab: Riwayat Perubahan -->
                <div v-if="activeTab === 'history'" class="animate-in fade-in slide-in-from-bottom-4 duration-500 space-y-6">
                    <!-- Filter Section (Synced) -->
                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md p-2.5 rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- Search Field -->
                            <div class="relative w-full md:w-64">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input 
                                    v-model="historySearch"
                                    type="text" 
                                    placeholder="Cari produk/kode..." 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 pl-10 pr-3 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ring-1 ring-slate-200 dark:ring-white/10"
                                />
                            </div>

                            <!-- Date Filter -->
                            <input 
                                v-model="historyDate"
                                type="date" 
                                class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all ring-1 ring-slate-200 dark:ring-white/10"
                            />

                            <!-- Activity Filter -->
                            <select v-model="historyActivity" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                                <option value="">Semua Aktivitas</option>
                                <option value="masuk">Stok Masuk</option>
                                <option value="keluar">Stok Keluar</option>
                            </select>

                            <!-- User Filter -->
                            <select v-model="historyUser" class="w-full md:w-48 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2 px-3 text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
                                <option value="">Semua Kasir</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>

                            <!-- Reset Button -->
                            <button 
                                @click="resetHistoryFilters"
                                class="p-2.5 bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-blue-500 rounded-md transition-all shadow-inner flex-shrink-0 group"
                                title="Reset Filter"
                            >
                                <svg class="w-4 h-4 transition-transform duration-500 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                                    <tr>
                                        <th class="px-8 py-5">Tanggal</th>
                                        <th class="px-8 py-5">Produk</th>
                                        <th class="px-8 py-5 text-center">Aktivitas</th>
                                        <th class="px-8 py-5 text-center">Jumlah</th>
                                        <th class="hidden md:table-cell px-8 py-5">Keterangan</th>
                                        <th class="hidden lg:table-cell px-8 py-5">User</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                                    <tr v-for="log in filteredHistory" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                        <td class="px-8 py-5">
                                            <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">{{ formatDate(log.created_at) }}</p>
                                        </td>
                                        <td class="px-8 py-5">
                                            <div class="min-w-[150px]">
                                                <p class="font-bold text-slate-900 dark:text-white text-sm capitalize truncate">{{ log.produk?.nama_produk }}</p>
                                                <p class="text-[10px] text-slate-500 uppercase font-mono tracking-tighter mt-0.5">{{ log.kode_produk }}</p>
                                            </div>
                                        </td>
                                        <td class="px-8 py-5 text-center">
                                            <span :class="[log.jenis_aktivitas === 'masuk' ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400', 'px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider']">
                                                {{ log.jenis_aktivitas }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 text-center font-black text-sm" :class="log.jenis_aktivitas === 'masuk' ? 'text-emerald-500' : 'text-rose-500'">
                                            {{ log.jenis_aktivitas === 'masuk' ? '+' : '-' }}{{ log.jumlah }}
                                        </td>
                                        <td class="hidden md:table-cell px-8 py-5 text-xs text-slate-500 dark:text-slate-400 font-medium max-w-xs truncate">
                                            {{ log.keterangan || '-' }}
                                        </td>
                                        <td class="hidden lg:table-cell px-8 py-5 text-xs font-bold text-slate-600 dark:text-slate-400">
                                            {{ log.user?.name || 'Sistem' }}
                                        </td>
                                    </tr>
                                    <tr v-if="filteredHistory.length === 0">
                                        <td colspan="6" class="px-8 py-20 text-center">
                                            <div class="flex flex-col items-center">
                                                <div class="w-16 h-16 bg-slate-50 dark:bg-white/5 rounded-md flex items-center justify-center mb-4">
                                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                                    </svg>
                                                </div>
                                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium italic">Tidak ada riwayat yang cocok dengan filter.</p>
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
                                Menampilkan {{ history.from }}-{{ history.to }} dari {{ history.total }}
                            </p>
                            <Pagination :links="history.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Modal -->
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showUpdateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 py-8">
                    <div class="fixed inset-0 bg-slate-950/80" @click="showUpdateModal = false"></div>
                    
                    <div class="relative bg-white dark:bg-slate-900 rounded-md shadow-2xl w-full max-w-md overflow-hidden transform transition-all border border-slate-200 dark:border-white/10">
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h3 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Update Stok</h3>
                                    <p class="text-slate-500 dark:text-slate-400 text-xs font-bold capitalize mt-1">{{ selectedProduct?.nama_produk }}</p>
                                </div>
                                <button @click="showUpdateModal = false" class="p-2 bg-slate-50 dark:bg-white/5 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-md transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <form @submit.prevent="submitUpdate" class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <button 
                                        type="button"
                                        @click="form.jenis_aktivitas = 'masuk'"
                                        :class="[form.jenis_aktivitas === 'masuk' ? 'bg-emerald-500 text-white shadow-emerald-500/20' : 'bg-slate-50 dark:bg-white/5 text-slate-400', 'py-4 rounded-md font-bold text-xs transition-all shadow-lg']"
                                    >
                                        Stok Masuk
                                    </button>
                                    <button 
                                        type="button"
                                        @click="form.jenis_aktivitas = 'keluar'"
                                        :class="[form.jenis_aktivitas === 'keluar' ? 'bg-rose-500 text-white shadow-rose-500/20' : 'bg-slate-50 dark:bg-white/5 text-slate-400', 'py-4 rounded-md font-bold text-xs transition-all shadow-lg']"
                                    >
                                        Stok Keluar
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-2 ml-1">Jumlah</label>
                                    <input 
                                        v-model="form.jumlah"
                                        type="number" 
                                        class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-md py-4 px-6 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-bold"
                                        min="1"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 mb-2 ml-1">Keterangan</label>
                                    <textarea 
                                        v-model="form.keterangan"
                                        rows="3"
                                        placeholder="Contoh: Stok dari supplier, Barang rusak, dll."
                                        class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-md py-4 px-6 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm font-medium"
                                    ></textarea>
                                </div>

                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="w-full bg-blue-600 text-white font-bold py-5 rounded-md shadow-2xl hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 text-sm"
                                >
                                    {{ form.processing ? 'Memproses...' : 'Simpan Perubahan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
