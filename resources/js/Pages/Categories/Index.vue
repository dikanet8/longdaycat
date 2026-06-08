<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, router, useForm } from'@inertiajs/vue3';
import { ref, watch } from'vue';
import Pagination from'@/Components/Pagination.vue';

const props = defineProps({
  categories: Object,
  filters: Object
});

const search = ref(props.filters?.search ||'');
const perPage = ref(props.filters?.per_page || 10);

const showModal = ref(false);
const editMode = ref(false);

const form = useForm({
  id: null,
  nama_kategori:'',
  deskripsi:''
});

let searchTimeout = null;
watch(search, (value) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/categories', { search: value, per_page: perPage.value }, {
      preserveState: true,
      replace: true
    });
  }, 300);
});

watch(perPage, (value) => {
  router.get('/categories', { search: search.value, per_page: value }, {
    preserveState: true,
    replace: true
  });
});

const openCreateModal = () => {
  editMode.value = false;
  form.reset();
  form.clearErrors();
  showModal.value = true;
};

const openEditModal = (category) => {
  editMode.value = true;
  form.id = category.id;
  form.nama_kategori = category.nama_kategori;
  form.deskripsi = category.deskripsi;
  form.clearErrors();
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
  form.clearErrors();
};

const saveCategory = () => {
  if (editMode.value) {
    form.put(`/categories/${form.id}`, {
      onSuccess: () => closeModal()
    });
  } else {
    form.post('/categories', {
      onSuccess: () => closeModal()
    });
  }
};

const confirmDelete = (category) => {
  if (confirm(`Apakah Anda yakin ingin menghapus kategori"${category.nama_kategori}"?`)) {
    router.delete(`/categories/${category.id}`);
  }
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day:'numeric',
    month:'short',
    year:'numeric'
  });
};
</script>

<template>
  <Head title="Manajemen Kategori" />

  <AppLayout>
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Manajemen Kategori</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Kelola pengelompokan produk Anda.</p>
        </div>
        
        <!-- Desktop Add Button -->
        <button @click="openCreateModal" class="hidden md:flex px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white rounded-md shadow-lg shadow-blue-500/20 transition-all font-bold text-sm items-center gap-2 active:scale-[0.98]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Tambah Kategori Baru
        </button>
      </div>

      <!-- Mobile Add Button (Floating) -->
      <button @click="openCreateModal" class="md:hidden fixed bottom-24 right-6 w-14 h-14 bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 active:scale-90 transition-transform">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>

      <!-- Search & Filters (Synced Layout) -->
      <div class="bg-white dark:bg-slate-900 p-3 md:p-2.5 rounded-3xl md:rounded-md border border-slate-200 dark:border-white/5 shadow-sm">
        <div class="flex items-center gap-2.5">
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
              placeholder="Cari kategori..." 
              class="block w-full pl-10 pr-3 py-2.5 md:py-2 border-none bg-slate-50 dark:bg-white/5 rounded-md text-xs ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
            >
          </div>
        </div>
      </div>

      <!-- Content section -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-3xl md:rounded-md shadow-sm overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
              <tr>
                <th class="px-6 py-4">Nama Kategori</th>
                <th class="px-6 py-4">Deskripsi</th>
                <th class="px-6 py-4 text-center">Jumlah Produk</th>
                <th class="px-6 py-4 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5 text-slate-700 dark:text-slate-200">
              <tr v-for="category in categories.data" :key="category.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors group">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-md bg-gradient-to-tr from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 flex items-center justify-center border border-blue-200 dark:border-blue-800/50">
                      <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                      </svg>
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white text-sm capitalize">{{ category.nama_kategori }}</p>
                      <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider mt-0.5">Dibuat {{ formatDate(category.created_at) }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs truncate" :title="category.deskripsi">{{ category.deskripsi ||'-' }}</p>
                </td>
                <td class="px-6 py-4 text-center">
                  <span class="inline-flex items-center justify-center px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded text-[10px] font-black">{{ category.produk_count || 0 }} Produk</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="openEditModal(category)" class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-all" title="Edit">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="confirmDelete(category)" class="p-2 text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-all" title="Hapus">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card List -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="category in categories.data" :key="'mobile-'+category.id" class="p-5 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
              <div class="w-12 h-12 rounded-md bg-blue-600/10 flex items-center justify-center shrink-0 border border-blue-500/20">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="font-black text-slate-900 dark:text-white text-base capitalize truncate leading-tight">{{ category.nama_kategori }}</p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">{{ category.produk_count || 0 }} PRODUK</span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <button @click="openEditModal(category)" class="p-2.5 bg-slate-100 dark:bg-white/5 text-slate-500 rounded-md active:scale-90 transition-transform">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button @click="confirmDelete(category)" class="p-2.5 bg-rose-50 dark:bg-rose-500/10 text-rose-500 rounded-md active:scale-90 transition-transform">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="categories.data.length === 0" class="px-6 py-24 text-center">
          <div class="flex flex-col items-center justify-center animate-in fade-in zoom-in duration-500">
            <div class="relative mb-6">
              <div class="absolute inset-0 bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-xl animate-pulse"></div>
              <div class="w-20 h-20 bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-800/50 rounded-full flex items-center justify-center relative shadow-lg border border-white dark:border-white/5">
                <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
              </div>
            </div>
            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-2">Kategori Kosong</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium max-w-sm mx-auto mb-6">
              Anda belum menambahkan kategori apapun.
            </p>
            <button @click="openCreateModal" class="px-6 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-bold uppercase tracking-widest rounded-md hover:scale-105 transition-all shadow-lg">
              Tambah Kategori
            </button>
          </div>
        </div>

        <!-- Pagination & Per Page -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
          <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
            <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
              Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ categories.from || 0 }}-{{ categories.to || 0 }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ categories.total || 0 }}</span> kategori
            </p>
            <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
              <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
            <Pagination :links="categories.links" />
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 ">
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div v-if="showModal" class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
              <form @submit.prevent="saveCategory" class="flex flex-col h-full">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ editMode ? 'Edit Kategori' : 'Tambah Kategori' }}</h3>
                      <p class="text-xs text-slate-500 dark:text-slate-400">Silakan tinjau dan isi form di bawah ini</p>
                    </div>
                  </div>
                  <button type="button" @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <!-- Body -->
                <div class="p-6 overflow-y-auto space-y-4">
                  <!-- Content Form Container -->
                  <div class="p-5 rounded-xl border border-blue-100 dark:border-blue-900/50 bg-blue-50/50 dark:bg-blue-900/20 space-y-4">
                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Nama Kategori</label>
                      <input type="text" v-model="form.nama_kategori" required class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all" placeholder="Contoh: Makanan Kucing">
                      <p v-if="form.errors.nama_kategori" class="text-red-500 text-xs mt-1">{{ form.errors.nama_kategori }}</p>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Deskripsi Singkat</label>
                      <textarea v-model="form.deskripsi" rows="3" class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all resize-none" placeholder="Penjelasan singkat..."></textarea>
                      <p v-if="form.errors.deskripsi" class="text-red-500 text-xs mt-1">{{ form.errors.deskripsi }}</p>
                    </div>
                  </div>

                  <!-- Optional Info Box like the yellow one in the image -->
                  <div class="p-5 rounded-xl border border-yellow-200 dark:border-yellow-900/50 bg-yellow-50 dark:bg-yellow-900/20">
                    <h4 class="text-xs font-bold text-yellow-800 dark:text-yellow-200 mb-1">Informasi Kategori</h4>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300">Dengan membuat kategori ini, Anda dapat mengelompokkan produk dengan lebih rapi. Kategori yang sudah memiliki produk tidak dapat dihapus secara langsung.</p>
                  </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                  <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    Batal
                  </button>
                  <button type="submit" :disabled="form.processing" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Kategori' }}
                  </button>
                </div>
              </form>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

  </AppLayout>
</template>
