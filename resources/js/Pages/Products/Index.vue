<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from'@inertiajs/vue3';
import { ref, computed, watch } from'vue';
import Pagination from'@/Components/Pagination.vue';

const props = defineProps({
  products: Object,
  categories: Array,
  filters: Object
});

const perPage = ref(props.filters?.per_page || 10);

const search = ref('');
const filterUkuran = ref('');
const filterWarna = ref('');
const filterStok = ref('');

const showMobileFilters = ref(false);

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
    if (filterStok.value ==='low') {
      matchesStok = product.stok > 0 && product.stok < product.stok_minimal;
    } else if (filterStok.value ==='out') {
      matchesStok = product.stok <= 0;
    }

    return matchesSearch && matchesUkuran && matchesWarna && matchesStok;
  });
});

const formatPrice = (price) => {
  return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits: 0 }).format(price);
};

const showDeleteModal = ref(false);
const isDeleting = ref(false);
const productToDelete = ref(null);

const confirmDelete = (product) => {
  productToDelete.value = product;
  showDeleteModal.value = true;
};

const deleteProduct = () => {
  if (productToDelete.value) {
    isDeleting.value = true;
    router.delete(route('products.destroy', productToDelete.value.id), {
      onSuccess: () => {
        showDeleteModal.value = false;
        productToDelete.value = null;
      },
      onFinish: () => {
        isDeleting.value = false;
      }
    });
  }
};

const resetFilters = () => {
  search.value ='';
  filterUkuran.value ='';
  filterWarna.value ='';
  filterStok.value ='';
  perPage.value = 10;
};

// --- Modal & Form Logic ---
const showProductModal = ref(false);
const isEditMode = ref(false);
const imagePreview = ref(null);

const form = useForm({
  id: null,
  _method: 'post',
  kode_produk: '',
  nama_produk: '',
  kategori_id: '',
  gambar: null,
  ukuran: 'M',
  warna: '',
  harga: '',
  stok: '',
  stok_minimal: 5,
});

const handleImageChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.gambar = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const openCreateModal = () => {
  isEditMode.value = false;
  form.reset();
  form.clearErrors();
  imagePreview.value = null;
  form.id = null;
  form._method = 'post';
  form.ukuran = 'M';
  form.stok_minimal = 5;
  showProductModal.value = true;
};

const openEditModal = (product) => {
  isEditMode.value = true;
  form.reset();
  form.clearErrors();
  
  form.id = product.id;
  form._method = 'put';
  form.kode_produk = product.kode_produk;
  form.nama_produk = product.nama_produk;
  form.kategori_id = product.kategori_id || '';
  form.gambar = null;
  form.ukuran = product.ukuran;
  form.warna = product.warna;
  form.harga = product.harga;
  form.stok = product.stok;
  form.stok_minimal = product.stok_minimal;
  
  imagePreview.value = product.gambar;
  showProductModal.value = true;
};

const closeProductModal = () => {
  showProductModal.value = false;
  form.reset();
  form.clearErrors();
  imagePreview.value = null;
};

const submitProduct = () => {
  if (isEditMode.value) {
    form.post(route('products.update', form.id), {
      forceFormData: true,
      onSuccess: () => closeProductModal(),
    });
  } else {
    form.post(route('products.store'), {
      forceFormData: true,
      onSuccess: () => closeProductModal(),
    });
  }
};

watch(perPage, (value) => {
  router.get(route('products.index'), { per_page: value }, { preserveState: true, preserveScroll: true, replace: true });
});
</script>

<template>
  <Head title="Manajemen Produk" />

  <AppLayout>
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Manajemen Produk</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Kelola daftar menu dan inventaris produk Anda.</p>
        </div>
        <!-- Desktop Add Button -->
        <button @click="openCreateModal" class="hidden md:flex px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-md shadow-lg shadow-blue-500/20 transition-all font-bold text-sm items-center gap-2 active:scale-[0.98]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Tambah Produk Baru
        </button>
      </div>

      <!-- Mobile Add Button (Floating) -->
      <button @click="openCreateModal" class="md:hidden fixed bottom-24 right-6 w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 active:scale-90 transition-transform">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>

      <!-- Search & Filters (Synced Layout) -->
      <div class="bg-white dark:bg-slate-900 p-3 md:p-2 rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
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
                v-model="search"
                type="text" 
                placeholder="Cari produk..." 
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

          <div :class="[showMobileFilters ? 'grid' : 'hidden md:flex']" class="grid-cols-2 sm:grid-cols-4 md:items-center gap-2 w-full">
            <!-- Ukuran Filter -->
            <select v-model="filterUkuran" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
              <option value="">Semua Ukuran</option>
              <option value="S">Size S</option>
              <option value="M">Size M</option>
              <option value="L">Size L</option>
              <option value="XL">Size XL</option>
              <option value="XXL">Size XXL</option>
            </select>

            <!-- Warna Filter -->
            <select v-model="filterWarna" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
              <option value="">Semua Warna</option>
              <option v-for="warna in uniqueColors" :key="warna" :value="warna">{{ warna }}</option>
            </select>

            <!-- Status Stok Filter -->
            <select v-model="filterStok" class="w-full md:w-40 bg-slate-50 dark:bg-white/5 border-none rounded-md py-2.5 md:py-2 px-3 text-[11px] md:text-xs font-bold text-slate-600 dark:text-slate-400 focus:ring-1 focus:ring-blue-500 transition-all cursor-pointer ring-1 ring-slate-200 dark:ring-white/10">
              <option value="">Status Stok</option>
              <option value="low">Stok Menipis</option>
              <option value="out">Stok Habis</option>
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

      <!-- Table & Card List Container -->
      <div class="bg-white dark:bg-slate-900 rounded-md overflow-hidden shadow-sm dark:shadow-2xl border border-slate-200 dark:border-white/5">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-[0.2em] font-black">
              <tr>
                <th class="pl-8 pr-4 py-5 w-[180px]">Gambar</th>
                <th class="pl-4 pr-8 py-5">Nama Produk</th>
                <th class="px-8 py-5">Ukuran</th>
                <th class="px-8 py-5">Harga</th>
                <th class="px-8 py-5">Stok</th>
                <th class="px-8 py-5 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y-2 divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
              <template v-if="filteredProducts.length > 0">
                <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                <td class="pl-8 pr-4 py-5 w-[180px]">
                  <div class="w-28 h-28 rounded-lg bg-slate-100 dark:bg-gradient-to-br dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden shadow-inner flex-shrink-0">
                    <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover" />
                    <svg v-else class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                </td>
                <td class="pl-4 pr-8 py-5">
                  <div>
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm transition-colors capitalize">{{ product.nama_produk }}</h4>
                    <p class="text-[10px] text-slate-500 mt-0.5 font-mono uppercase tracking-tighter">{{ product.kode_produk }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                      <span v-if="product.kategori" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded text-[9px] font-bold uppercase">{{ product.kategori.nama_kategori }}</span>
                      <span class="px-2 py-0.5 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded text-[9px] font-bold uppercase">{{ product.warna }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <span class="px-2 py-1 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded text-[10px] font-bold uppercase">{{ product.ukuran }}</span>
                </td>
                <td class="px-8 py-5">
                  <span class="text-slate-900 dark:text-white font-black text-sm tracking-tighter">{{ formatPrice(product.harga) }}</span>
                </td>
                <td class="px-8 py-5">
                  <div class="flex items-center gap-2">
                    <span class="text-slate-600 dark:text-slate-300 font-bold text-sm">{{ product.stok }} item</span>
                  </div>
                </td>
                <td class="px-8 py-5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="openEditModal(product)" class="p-2 text-slate-400 hover:text-blue-500 transition-colors">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="confirmDelete(product)" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
                </tr>
              </template>
              <tr v-else>
                <td colspan="6" class="px-8 py-20 text-center">
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
          <template v-if="filteredProducts.length > 0">
            <div v-for="product in filteredProducts" :key="'mobile-'+product.id" class="p-5 flex flex-col gap-4">
            <div class="flex items-start gap-4">
              <div class="w-20 h-20 rounded-md bg-slate-100 dark:bg-gradient-to-br dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500 overflow-hidden shadow-inner flex-shrink-0">
                <img v-if="product.gambar" :src="product.gambar" class="w-full h-full object-cover" />
                <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start gap-2">
                  <h4 class="font-black text-slate-900 dark:text-white text-base truncate capitalize leading-tight">{{ product.nama_produk }}</h4>
                  <span class="text-sm font-black text-blue-600 dark:text-blue-400 whitespace-nowrap">{{ formatPrice(product.harga) }}</span>
                </div>
                <p class="text-[10px] text-slate-500 mt-1 font-mono uppercase tracking-tighter">{{ product.kode_produk }}</p>
                
                <div class="flex flex-wrap gap-1.5 mt-2">
                  <span v-if="product.kategori" class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg text-[9px] font-black uppercase">{{ product.kategori.nama_kategori }}</span>
                  <span class="px-2 py-0.5 bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 rounded-lg text-[9px] font-black uppercase">{{ product.warna }}</span>
                  <span class="px-2 py-0.5 bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 rounded-lg text-[9px] font-black uppercase">{{ product.ukuran }}</span>
                </div>
                
                <div class="flex items-center gap-1.5 mt-2.5">
                  <span class="text-[10px] font-bold uppercase tracking-wider" :class="product.stok < product.stok_minimal ?'text-rose-600 dark:text-rose-400' :'text-emerald-600 dark:text-emerald-400'">{{ product.stok }} Item Tersedia</span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 border-t border-slate-50 dark:border-white/5 pt-3 -mt-1">
              <button @click="openEditModal(product)" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded-md transition-colors active:scale-95" title="Edit">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
              </button>
              <button @click="confirmDelete(product)" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/20 rounded-md transition-colors active:scale-95" title="Hapus">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Hapus
              </button>
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
        <!-- Pagination & Per Page -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
          <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
            <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
              Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ products.from }}-{{ products.to }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ products.total }}</span> produk
            </p>
            <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
              <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
            <Pagination :links="products.links" />
          </div>
        </div>
      </div>

      <!-- Product Form Modal -->
      <Teleport to="body">
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-if="showProductModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50">
            <Transition
              enter-active-class="ease-out duration-300"
              enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to-class="opacity-100 translate-y-0 sm:scale-100"
              leave-active-class="ease-in duration-200"
              leave-from-class="opacity-100 translate-y-0 sm:scale-100"
              leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <div v-if="showProductModal" class="relative bg-white dark:bg-slate-900 rounded-md shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ isEditMode ? 'Edit Produk' : 'Tambah Produk Baru' }}</h3>
                      <p class="text-xs text-slate-500 dark:text-slate-400">Lengkapi formulir informasi produk di bawah ini</p>
                    </div>
                  </div>
                  <button type="button" @click="closeProductModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <!-- Body (Scrollable) -->
                <div class="p-6 overflow-y-auto custom-scrollbar flex-1">
                  <form @submit.prevent="submitProduct" class="space-y-8" id="productForm">
                    <!-- IMAGE UPLOAD -->
                    <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-md bg-slate-50 dark:bg-white/5 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-all group relative">
                      <div v-if="imagePreview" class="relative w-48 h-48 mb-4">
                        <img :src="imagePreview" class="w-full h-full object-cover rounded-md shadow-2xl border-2 border-blue-500/20" />
                        <button @click="imagePreview = null; form.gambar = null" type="button" class="absolute -top-2 -right-2 bg-red-500 text-white p-1 rounded-full shadow-lg z-10">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                      <div v-else class="text-center">
                        <div class="w-16 h-16 bg-blue-600/10 text-blue-600 dark:text-blue-400 rounded-md flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                          </svg>
                        </div>
                        <p class="text-slate-700 dark:text-white font-bold">Pilih Foto Produk</p>
                        <p class="text-slate-500 text-xs mt-1">PNG, JPG atau JPEG (Max. 2MB)</p>
                      </div>
                      <input 
                        type="file" 
                        @change="handleImageChange"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        accept="image/*"
                      />
                      <div v-if="form.errors.gambar" class="text-red-400 text-xs mt-4 text-center font-bold">{{ form.errors.gambar }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <!-- Kode Produk -->
                      <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Kode Produk</label>
                        <input 
                          v-model="form.kode_produk"
                          type="text" 
                          class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                          placeholder="Contoh: PRD001"
                          required
                        />
                        <div v-if="form.errors.kode_produk" class="text-red-400 text-xs mt-1">{{ form.errors.kode_produk }}</div>
                      </div>

                      <!-- Nama Produk -->
                      <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Nama Produk</label>
                        <input 
                          v-model="form.nama_produk"
                          type="text" 
                          class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                          placeholder="Masukkan nama produk"
                          required
                        />
                        <div v-if="form.errors.nama_produk" class="text-red-400 text-xs mt-1">{{ form.errors.nama_produk }}</div>
                      </div>

                      <!-- Harga -->
                      <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Harga (IDR)</label>
                        <input 
                          v-model="form.harga"
                          type="number" 
                          class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                          placeholder="0"
                          required
                        />
                        <div v-if="form.errors.harga" class="text-red-400 text-xs mt-1">{{ form.errors.harga }}</div>
                      </div>

                      <!-- Kategori Produk -->
                      <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Kategori Produk</label>
                        <select 
                          v-model="form.kategori_id"
                          class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                        >
                          <option value="">Pilih Kategori (Opsional)</option>
                          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nama_kategori }}</option>
                        </select>
                        <div v-if="form.errors.kategori_id" class="text-red-400 text-xs mt-1">{{ form.errors.kategori_id }}</div>
                      </div>

                      <!-- Kategori (Ukuran & Warna) -->
                      <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                          <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Ukuran</label>
                          <select 
                            v-model="form.ukuran"
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                          >
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                          </select>
                        </div>
                        <div class="space-y-2">
                          <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Warna</label>
                          <input 
                            v-model="form.warna"
                            type="text" 
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                            placeholder="Contoh: Merah"
                            required
                          />
                        </div>
                      </div>

                      <!-- Stok & Stok Minimal -->
                      <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                          <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">
                            {{ isEditMode ? 'Stok (Readonly)' : 'Stok Awal' }}
                          </label>
                          <input 
                            v-model="form.stok"
                            type="number" 
                            :disabled="isEditMode"
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all disabled:opacity-50"
                            placeholder="0"
                            :required="!isEditMode"
                          />
                        </div>
                        <div class="space-y-2">
                          <label class="block text-sm font-bold text-slate-500 dark:text-slate-300 uppercase tracking-wider">Stok Minimal</label>
                          <input 
                            v-model="form.stok_minimal"
                            type="number" 
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl px-4 py-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 transition-all"
                            placeholder="5"
                            required
                          />
                        </div>
                      </div>
                    </div>
                  </form>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                  <button @click="closeProductModal" class="px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Batal
                  </button>
                  <button 
                    type="submit" 
                    form="productForm"
                    :disabled="form.processing"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2"
                  >
                    <span v-if="form.processing" class="flex items-center gap-2">
                      <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Menyimpan...
                    </span>
                    <span v-else>Simpan Produk</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </Transition>
      </Teleport>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
        <div v-if="showDeleteModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50">
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div v-if="showDeleteModal" class="relative bg-white dark:bg-slate-900 rounded-md shadow-xl w-full max-w-sm overflow-hidden flex flex-col">
              <!-- Header -->
              <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-600 dark:text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Produk</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Konfirmasi tindakan</p>
                  </div>
                </div>
                <button type="button" @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6">
                <div class="p-4 rounded-xl border border-red-100 dark:border-red-900/50 bg-red-50/50 dark:bg-red-900/20 text-center">
                  <p class="text-sm text-red-900 dark:text-red-200">
                    Apakah Anda yakin ingin menghapus produk <span class="font-bold">{{ productToDelete?.nama_produk }}</span>? Tindakan ini permanen.
                  </p>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                <button @click="showDeleteModal = false" :disabled="isDeleting" class="px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                  Batal
                </button>
                <button @click="deleteProduct" :disabled="isDeleting" class="flex items-center justify-center min-w-[100px] px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm shadow-red-500/20 transition-all disabled:opacity-70 disabled:cursor-not-allowed">
                  <svg v-if="isDeleting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ isDeleting ? 'Menghapus...' : 'Hapus' }}</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
        </Transition>
      </Teleport>
    </div>
  </AppLayout>
</template>
