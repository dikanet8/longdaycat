<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object
});

const perPage = ref(props.filters?.per_page || 10);

watch(perPage, (value) => {
    router.get(route('settings.activity-log'), { per_page: value }, { preserveState: true, replace: true });
});

const formatDate = (date) => {
    return new Date(date).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const getActivityColor = (activity) => {
    const act = activity.toLowerCase();
    if (act.includes('delete') || act.includes('hapus')) return 'text-rose-500 bg-rose-500/10 border-rose-500/20';
    if (act.includes('create') || act.includes('tambah') || act.includes('checkout')) return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
    if (act.includes('update') || act.includes('edit')) return 'text-amber-500 bg-amber-500/10 border-amber-500/20';
    return 'text-blue-500 bg-blue-500/10 border-blue-500/20';
};
</script>

<template>
    <Head title="Log Aktivitas" />

    <AppLayout>
        <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Log Aktivitas</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-sm">Rekam jejak seluruh aktivitas operasional di dalam sistem.</p>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-md border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-slate-400 text-[11px] uppercase tracking-widest font-black">
                            <tr>
                                <th class="px-8 py-5">Waktu</th>
                                <th class="px-8 py-5">User</th>
                                <th class="px-8 py-5">Aktivitas</th>
                                <th class="px-8 py-5">Keterangan</th>
                                <th class="px-8 py-5 text-center">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/[0.02] transition-colors">
                                <td class="px-8 py-5">
                                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium">{{ formatDate(log.created_at) }}</p>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-600/10 text-blue-600 flex items-center justify-center font-bold text-xs">
                                            {{ log.user?.name.charAt(0) || 'S' }}
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white capitalize">{{ log.user?.name || 'Sistem' }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border', getActivityColor(log.activity)]">
                                        {{ log.activity }}
                                    </span>
                                </td>
                                <td class="px-8 py-5">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium max-w-md">{{ log.description || '-' }}</p>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span class="text-[10px] font-mono text-slate-400 uppercase tracking-tighter">{{ log.ip_address || 'Unknown' }}</span>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-slate-50 dark:bg-white/5 rounded-md flex items-center justify-center mb-4 text-slate-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400 text-sm font-medium italic">Belum ada aktivitas yang terekam.</p>
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
                            <option v-for="n in [5, 10, 15, 20, 50, 100]" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </div>

                    <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">
                        Menampilkan {{ logs.from }}-{{ logs.to }} dari {{ logs.total }}
                    </p>
                    <Pagination :links="logs.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
