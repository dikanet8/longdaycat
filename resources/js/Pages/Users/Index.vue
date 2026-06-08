<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import Pagination from'@/Components/Pagination.vue';
import { Head, Link, useForm, router } from'@inertiajs/vue3';
import { ref, computed, watch } from'vue';

const props = defineProps({
  users: Object,
  filters: Object
});

const search = ref(props.filters?.search ||'');
const perPage = ref(props.filters?.per_page || 10);

let searchTimeout = null;
watch(search, (value) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(route('users.index'), { search: value, per_page: perPage.value }, {
      preserveState: true,
      replace: true
    });
  }, 300);
});

watch(perPage, (value) => {
  router.get(route('users.index'), { search: search.value, per_page: value }, {
    preserveState: true,
    replace: true
  });
});

const isDeleteModalOpen = ref(false);
const selectedUser = ref(null);

const form = useForm({});

// Removed filteredUsers as we now use props.users.data directly from server
const usersList = computed(() => props.users.data);

const isUserModalOpen = ref(false);
const editMode = ref(false);

const userForm = useForm({
  id: null,
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'kasir',
  status: 'active'
});

const openUserModal = (user = null) => {
  if (user) {
    editMode.value = true;
    userForm.id = user.id;
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.password = '';
    userForm.password_confirmation = '';
    userForm.role = user.role;
    userForm.status = user.status;
  } else {
    editMode.value = false;
    userForm.reset();
  }
  userForm.clearErrors();
  isUserModalOpen.value = true;
};

const closeUserModal = () => {
  isUserModalOpen.value = false;
  setTimeout(() => userForm.reset(), 300);
};

const submitUser = () => {
  if (editMode.value) {
    userForm.put(route('users.update', userForm.id), {
      onSuccess: () => closeUserModal()
    });
  } else {
    userForm.post(route('users.store'), {
      onSuccess: () => closeUserModal()
    });
  }
};

const openDeleteModal = (user) => {
  selectedUser.value = user;
  isDeleteModalOpen.value = true;
};

const deleteUser = () => {
  form.delete(route('users.destroy', selectedUser.value.id), {
    onSuccess: () => {
      isDeleteModalOpen.value = false;
    }
  });
};

const getRoleColor = (role) => {
  return role ==='owner' 
    ?'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400 border-purple-200 dark:border-purple-500/20' 
    :'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:border-blue-500/20';
};

const getStatusColor = (status) => {
  return status ==='active'
    ?'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20'
    :'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400 border-slate-200 dark:border-slate-500/20';
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    day:'numeric',
    month:'short',
    year:'numeric'
  });
};
</script>

<template>
  <Head title="Manajemen Akun" />

  <AppLayout>
    <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Manajemen Akun</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Kelola akses pengguna, peran, dan status akun sistem.</p>
        </div>
        <!-- Desktop Add Button -->
        <button @click="openUserModal()" class="hidden md:flex px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-md shadow-lg shadow-blue-500/20 transition-all font-bold text-sm items-center gap-2 active:scale-[0.98]">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Tambah Akun Baru
        </button>
      </div>

      <!-- Mobile Add Button (Floating) -->
      <button @click="openUserModal()" class="md:hidden fixed bottom-24 right-6 w-14 h-14 bg-blue-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 active:scale-90 transition-transform">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>

      <!-- Search & Filters (Synced Layout) -->
      <div class="bg-white dark:bg-slate-900 p-2.5 rounded-md border border-slate-200 dark:border-white/5 shadow-sm space-y-2.5">
        <div class="flex flex-wrap items-center gap-2.5">
          <!-- Search Field -->
          <div class="relative w-full md:w-80">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
              <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input 
              v-model="search"
              type="text" 
              placeholder="Cari nama atau email..." 
              class="block w-full pl-10 pr-3 py-2 border-none bg-slate-50 dark:bg-white/5 rounded-md text-xs ring-1 ring-slate-200 dark:ring-white/10 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
            >
          </div>

        </div>
      </div>

      <!-- Users List (Table on Desktop, Cards on Mobile) -->
      <div class="bg-white dark:bg-slate-900 rounded-md border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-white/[0.02] border-b border-slate-100 dark:border-white/5">
                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Pengguna</th>
                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Peran</th>
                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 text-center">Status</th>
                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Bergabung</th>
                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.01] transition-colors group">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-md bg-gradient-to-tr from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center font-black text-slate-500 dark:text-slate-400 text-xs shadow-inner group-hover:scale-110 transition-transform">
                      {{ user.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="text-xs font-bold text-slate-900 dark:text-white leading-tight">{{ user.name }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="['px-3 py-1 rounded-md text-[10px] font-black uppercase tracking-tighter border', getRoleColor(user.role)]">
                    {{ user.role }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-center gap-2">
                    <span :class="['w-1.5 h-1.5 rounded-full', user.status ==='active' ?'bg-emerald-500' :'bg-slate-400']"></span>
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400 capitalize">{{ user.status }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ formatDate(user.created_at) }}</p>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-3">
                    <button @click="openUserModal(user)" class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-md">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button 
                      @click="openDeleteModal(user)" 
                      v-if="user.id !== $page.props.auth.user.id"
                      class="p-2 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-md"
                    >
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

        <!-- Mobile Cards -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="user in users.data" :key="'mob-'+user.id" class="p-5 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-md bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center font-black text-white text-xs shadow-lg">
                  {{ user.name.charAt(0) }}
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ user.name }}</p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ user.email }}</p>
                </div>
              </div>
              <span :class="['px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-wider border', getRoleColor(user.role)]">
                {{ user.role }}
              </span>
            </div>
            
            <div class="flex items-center justify-between pt-2">
              <div class="flex flex-col gap-1">
                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Status & Bergabung</p>
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-1.5">
                    <span :class="['w-1.5 h-1.5 rounded-full', user.status ==='active' ?'bg-emerald-500' :'bg-slate-400']"></span>
                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 capitalize">{{ user.status }}</span>
                  </div>
                  <span class="text-slate-300 dark:text-white/10">•</span>
                  <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ formatDate(user.created_at) }}</p>
                </div>
              </div>
              
              <div class="flex items-center gap-2">
                <button @click="openUserModal(user)" class="p-2.5 bg-slate-50 dark:bg-white/5 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-md border border-slate-100 dark:border-white/5 transition-all active:scale-95">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button 
                  v-if="user.id !== $page.props.auth.user.id"
                  @click="openDeleteModal(user)" 
                  class="p-2.5 bg-slate-50 dark:bg-white/5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-md border border-slate-100 dark:border-white/5 transition-all active:scale-95"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination & Per Page -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
          <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
            <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
              Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ users.from }}-{{ users.to }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ users.total }}</span> akun
            </p>
            <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
              <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                <option v-for="n in [5, 10, 15, 20, 50]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
            <Pagination :links="users.links" />
          </div>
        </div>
      </div>
    </div>

    <!-- DELETE MODAL -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 ">
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div v-if="isDeleteModalOpen" class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-sm overflow-hidden flex flex-col">
              <!-- Header -->
              <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-600 dark:text-red-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Hapus Akun</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Konfirmasi tindakan</p>
                  </div>
                </div>
                <button type="button" @click="isDeleteModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6">
                <div class="p-4 rounded-xl border border-red-100 dark:border-red-900/50 bg-red-50/50 dark:bg-red-900/20 text-center">
                  <p class="text-sm text-red-900 dark:text-red-200">
                    Apakah Anda yakin ingin menghapus akun <span class="font-bold">{{ selectedUser?.name }}</span>? Tindakan ini permanen.
                  </p>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                <button @click="isDeleteModalOpen = false" class="px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                  Batal
                </button>
                <button @click="deleteUser" :disabled="form.processing" class="px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm shadow-red-500/20 transition-all disabled:opacity-50">
                  Hapus
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
    <!-- User Form Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="isUserModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 ">
          <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
          >
            <div v-if="isUserModalOpen" class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
              <!-- Header -->
              <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ editMode ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Silakan tinjau dan isi form di bawah ini</p>
                  </div>
                </div>
                <button @click="closeUserModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>

              <!-- Body -->
              <div class="p-6 overflow-y-auto space-y-4">
                <div class="p-5 rounded-xl border border-blue-100 dark:border-blue-900/50 bg-blue-50/50 dark:bg-blue-900/20 space-y-4">
                  <div>
                    <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Nama Lengkap</label>
                    <input v-model="userForm.name" type="text" class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all" placeholder="Masukkan nama...">
                    <p v-if="userForm.errors.name" class="mt-1.5 text-xs text-rose-500 font-medium">{{ userForm.errors.name }}</p>
                  </div>

                  <div>
                    <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Alamat Email</label>
                    <input v-model="userForm.email" type="email" class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all" placeholder="email@contoh.com">
                    <p v-if="userForm.errors.email" class="mt-1.5 text-xs text-rose-500 font-medium">{{ userForm.errors.email }}</p>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Kata Sandi</label>
                      <input v-model="userForm.password" type="password" class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all" :placeholder="editMode ? 'Kosongkan jika tak diubah' : 'Minimal 8 karakter'">
                      <p v-if="userForm.errors.password" class="mt-1.5 text-xs text-rose-500 font-medium">{{ userForm.errors.password }}</p>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Ulangi Sandi</label>
                      <input v-model="userForm.password_confirmation" type="password" class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all" placeholder="Ulangi sandi...">
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Peran Sistem</label>
                      <select v-model="userForm.role" class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer">
                        <option value="kasir">Kasir</option>
                        <option value="owner">Owner</option>
                      </select>
                      <p v-if="userForm.errors.role" class="mt-1.5 text-xs text-rose-500 font-medium">{{ userForm.errors.role }}</p>
                    </div>
                    <div>
                      <label class="block text-xs font-bold text-blue-900 dark:text-blue-100 mb-1.5">Status Akun</label>
                      <select v-model="userForm.status" class="w-full bg-white dark:bg-slate-800 border border-blue-200 dark:border-blue-800 rounded-lg py-2.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                      </select>
                      <p v-if="userForm.errors.status" class="mt-1.5 text-xs text-rose-500 font-medium">{{ userForm.errors.status }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="p-5 rounded-xl border border-yellow-200 dark:border-yellow-900/50 bg-yellow-50 dark:bg-yellow-900/20">
                  <h4 class="text-xs font-bold text-yellow-800 dark:text-yellow-200 mb-1">Keamanan Akun</h4>
                  <p class="text-xs text-yellow-700 dark:text-yellow-300">Pastikan password yang digunakan kuat dan status akun sudah disesuaikan agar keamanan sistem tetap terjaga.</p>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0 bg-slate-50/50 dark:bg-slate-900/50">
                <button @click="closeUserModal" type="button" class="px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                  Batal
                </button>
                <button @click="submitUser" :disabled="userForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2">
                  <svg v-if="userForm.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Simpan Data
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>
