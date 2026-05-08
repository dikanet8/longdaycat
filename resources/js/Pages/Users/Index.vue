<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object
});

const perPage = ref(props.filters?.per_page || 10);

watch(perPage, (value) => {
    router.get(route('users.index'), { per_page: value }, { preserveState: true, replace: true });
});

const searchQuery = ref('');
const isDeleteModalOpen = ref(false);
const selectedUser = ref(null);

const form = useForm({});

const filteredUsers = computed(() => {
    return props.users.data.filter(user => 
        user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        user.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

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
    return role === 'owner' 
        ? 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400 border-purple-200 dark:border-purple-500/20' 
        : 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:border-blue-500/20';
};

const getStatusColor = (status) => {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20'
        : 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400 border-slate-200 dark:border-slate-500/20';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Manajemen Akun" />

    <AppLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-1">Manajemen Akun</h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Kelola akses pengguna, peran, dan status akun sistem.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Cari akun..." 
                            class="block w-full pl-10 pr-3 py-2 border-none bg-white dark:bg-slate-900 rounded-xl text-sm shadow-sm ring-1 ring-slate-200 dark:ring-white/5 focus:ring-2 focus:ring-blue-500 transition-all dark:text-white"
                        >
                    </div>
                    <Link :href="route('users.create')" class="hidden md:flex px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl shadow-lg shadow-blue-500/20 transition-all font-bold text-sm items-center gap-2 active:scale-95">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Akun
                    </Link>
                </div>
            </div>

            <!-- Mobile Floating Action Button -->
            <Link :href="route('users.create')" class="md:hidden fixed bottom-24 right-6 w-14 h-14 bg-blue-600 text-white rounded-full shadow-2xl shadow-blue-600/40 flex items-center justify-center z-50 active:scale-90 transition-all border-4 border-white dark:border-slate-900">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </Link>

            <!-- Users Table Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-white/5 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-white/[0.02] border-b border-slate-100 dark:border-white/5">
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Pengguna</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Peran</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Status</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">Bergabung</th>
                                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50/80 dark:hover:bg-white/[0.01] transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center font-black text-slate-500 dark:text-slate-400 text-sm shadow-inner group-hover:scale-110 transition-transform">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ user.name }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border', getRoleColor(user.role)]">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span :class="['w-2 h-2 rounded-full', user.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-400 capitalize">{{ user.status }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ formatDate(user.created_at) }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-4">
                                        <Link :href="route('users.edit', user.id)" class="text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button 
                                            @click="openDeleteModal(user)" 
                                            v-if="user.id !== $page.props.auth.user.id"
                                            class="text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors"
                                        >
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
                        Menampilkan {{ users.from }}-{{ users.to }} dari {{ users.total }}
                    </p>
                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>

        <!-- DELETE MODAL -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="isDeleteModalOpen = false">
                <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2rem] shadow-2xl overflow-hidden border border-slate-200 dark:border-white/10 animate-in zoom-in-95 duration-200">
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-red-100 dark:bg-red-500/10 rounded-2xl flex items-center justify-center text-red-600 dark:text-red-400 mx-auto mb-4 border border-red-200 dark:border-red-500/20">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-1">Hapus Akun?</h3>
                        <p class="text-sm text-slate-500 mb-6">Apakah Anda yakin ingin menghapus akun <span class="font-bold text-slate-900 dark:text-white">{{ selectedUser?.name }}</span>? Tindakan ini tidak dapat dibatalkan.</p>
                        
                        <div class="flex gap-3">
                            <button @click="isDeleteModalOpen = false" class="flex-1 px-4 py-3 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 rounded-xl text-sm font-bold hover:bg-slate-200 dark:hover:bg-white/10 transition-all">Batal</button>
                            <button @click="deleteUser" :disabled="form.processing" class="flex-1 px-4 py-3 bg-red-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-red-600/20 hover:bg-red-500 transition-all disabled:opacity-50">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
