<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'kasir',
    status: 'active',
});

const submit = () => {
    form.post(route('users.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Tambah Akun" />

    <AppLayout>
        <div class="max-w-3xl space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header -->
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <Link :href="route('users.index')" class="p-2 bg-slate-100 dark:bg-white/5 text-slate-500 hover:text-blue-600 rounded-xl transition-all" title="Kembali">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Tambah Akun</h1>
                </div>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-sm ml-[52px]">Daftarkan pengguna baru ke dalam sistem.</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-white/5 overflow-hidden">
                <div class="p-8 md:p-10">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nama -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Nama Lengkap</label>
                                <input 
                                    v-model="form.name" 
                                    type="text" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white p-4 transition-all" 
                                    required 
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500 ml-1">{{ form.errors.name }}</p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Alamat Email</label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white p-4 transition-all" 
                                    required 
                                />
                                <p v-if="form.errors.email" class="text-xs text-red-500 ml-1">{{ form.errors.email }}</p>
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Password</label>
                                <input 
                                    v-model="form.password" 
                                    type="password" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white p-4 transition-all" 
                                    required 
                                />
                                <p v-if="form.errors.password" class="text-xs text-red-500 ml-1">{{ form.errors.password }}</p>
                            </div>

                            <!-- Konfirmasi Password -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Konfirmasi Password</label>
                                <input 
                                    v-model="form.password_confirmation" 
                                    type="password" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white p-4 transition-all" 
                                    required 
                                />
                            </div>

                            <!-- Peran -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Peran Akses</label>
                                <select 
                                    v-model="form.role" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white p-4 transition-all appearance-none"
                                >
                                    <option value="owner">Owner</option>
                                    <option value="kasir">Kasir</option>
                                </select>
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Status Akun</label>
                                <select 
                                    v-model="form.status" 
                                    class="w-full bg-slate-50 dark:bg-white/5 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white p-4 transition-all appearance-none"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100 dark:border-white/5 flex justify-end">
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-md text-sm font-black uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all active:scale-95 disabled:opacity-50"
                            >
                                Simpan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
