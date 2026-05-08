<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const activeTab = ref('profile');

const tabs = [
    { id: 'profile', name: 'Profil Personal', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 'security', name: 'Keamanan Akun', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
    { id: 'danger', name: 'Zona Bahaya', icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' },
];
</script>

<template>
    <Head title="Pengaturan Akun" />

    <AppLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header Section (Simplified & Professional) -->
            <div class="relative overflow-hidden bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-white/5 shadow-sm">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-1">Pengaturan Akun</h1>
                        <p class="text-slate-500 dark:text-slate-400 font-medium">Kelola profil, keamanan, dan preferensi akun Anda.</p>
                    </div>
                    <div class="hidden md:flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-800/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Navigation Tabs -->
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 dark:border-white/5 pb-2">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="flex items-center gap-2 px-6 py-3 rounded-t-2xl transition-all duration-200 group relative"
                    :class="activeTab === tab.id 
                        ? 'text-blue-600 dark:text-blue-400' 
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                    </svg>
                    <span class="font-bold text-xs tracking-wide uppercase">{{ tab.name }}</span>
                    
                    <!-- Active Bottom Line -->
                    <div v-if="activeTab === tab.id" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 dark:bg-blue-400 rounded-t-full"></div>
                </button>
            </div>

            <!-- Content Area -->
            <div class="max-w-4xl">
                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-4"
                >
                    <div :key="activeTab">
                        <!-- Profile Section -->
                        <div v-if="activeTab === 'profile'" class="bg-white dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-8 shadow-sm">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Informasi Profil</h3>
                                    <p class="text-slate-500 text-xs">Perbarui data diri dan alamat email Anda.</p>
                                </div>
                            </div>
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>

                        <!-- Security Section -->
                        <div v-if="activeTab === 'security'" class="bg-white dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-8 shadow-sm">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-10 h-10 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Keamanan Akun</h3>
                                    <p class="text-slate-500 text-xs">Pastikan akun Anda tetap aman dengan kata sandi yang kuat.</p>
                                </div>
                            </div>
                            <UpdatePasswordForm />
                        </div>

                        <!-- Danger Section -->
                        <div v-if="activeTab === 'danger'" class="bg-white dark:bg-slate-900/60 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-8 shadow-sm">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="w-10 h-10 bg-red-50 dark:bg-red-900/30 rounded-xl flex items-center justify-center text-red-600 dark:text-red-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">Hapus Akun</h3>
                                    <p class="text-slate-500 text-xs">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
                                </div>
                            </div>
                            <DeleteUserForm />
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </AppLayout>
</template>
