<script setup>
import AppLayout from'@/Layouts/AppLayout.vue';
import { Head, router } from'@inertiajs/vue3';
import Pagination from'@/Components/Pagination.vue';
import { ref, watch } from'vue';

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
    day:'numeric',
    month:'short',
    year:'numeric',
    hour:'2-digit',
    minute:'2-digit',
    second:'2-digit'
  });
};

const getActivityColor = (activity) => {
  const act = activity.toLowerCase();
  if (act.includes('delete') || act.includes('hapus')) return'text-rose-500 bg-rose-500/10 border-rose-500/20';
  if (act.includes('create') || act.includes('tambah') || act.includes('checkout')) return'text-emerald-500 bg-emerald-500/10 border-emerald-500/20';
  if (act.includes('update') || act.includes('edit')) return'text-amber-500 bg-amber-500/10 border-amber-500/20';
  return'text-blue-500 bg-blue-500/10 border-blue-500/20';
};
</script>

<template>
  <Head title="Log Aktivitas" />

  <AppLayout>
    <div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Log Aktivitas</h1>
          <p class="text-slate-500 dark:text-slate-400 mt-1 font-medium text-xs md:text-sm">Rekam jejak seluruh aktivitas operasional di dalam sistem.</p>
        </div>
      </div>

      <!-- Table & List Container -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-white/5 overflow-hidden shadow-sm">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
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
                      {{ log.user?.name.charAt(0) ||'S' }}
                    </div>
                    <span class="text-sm font-bold text-slate-900 dark:text-white capitalize">{{ log.user?.name ||'Sistem' }}</span>
                  </div>
                </td>
                <td class="px-8 py-5">
                  <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border', getActivityColor(log.activity)]">
                    {{ log.activity }}
                  </span>
                </td>
                <td class="px-8 py-5">
                  <p class="text-xs text-slate-500 dark:text-slate-400 font-medium max-w-md">{{ log.description ||'-' }}</p>
                </td>
                <td class="px-8 py-5 text-center">
                  <span class="text-[10px] font-mono text-slate-400 uppercase tracking-tighter">{{ log.ip_address ||'Unknown' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile List -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="log in logs.data" :key="'mobile-'+log.id" class="p-5 space-y-4">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-600/10 text-blue-600 flex items-center justify-center font-bold text-xs">
                  {{ log.user?.name.charAt(0) ||'S' }}
                </div>
                <div>
                  <p class="text-sm font-bold text-slate-900 dark:text-white capitalize leading-tight">{{ log.user?.name ||'Sistem' }}</p>
                  <p class="text-[10px] text-slate-500 font-medium">{{ formatDate(log.created_at) }}</p>
                </div>
              </div>
              <span :class="['px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border whitespace-nowrap', getActivityColor(log.activity)]">
                {{ log.activity }}
              </span>
            </div>
            <div class="pl-11">
              <p class="text-xs text-slate-600 dark:text-slate-400 font-medium leading-relaxed bg-slate-50 dark:bg-white/5 p-3 rounded-2xl border border-slate-100 dark:border-white/5">
                {{ log.description ||'-' }}
              </p>
              <div class="mt-2 flex items-center gap-1.5 text-[9px] font-mono text-slate-400 uppercase tracking-tighter">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ log.ip_address ||'Unknown IP' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="logs.data.length === 0" class="px-8 py-32 text-center">
          <div class="flex flex-col items-center justify-center animate-in fade-in zoom-in duration-500">
            <div class="relative mb-6">
              <div class="absolute inset-0 bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-xl animate-pulse"></div>
              <div class="w-24 h-24 bg-gradient-to-tr from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-800/50 rounded-full flex items-center justify-center relative shadow-lg border border-white dark:border-white/5">
                <svg class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-2">Belum Ada Aktivitas</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium max-w-sm mx-auto mb-6">
              Belum ada log aktivitas yang terekam di dalam sistem sejauh ini.
            </p>
          </div>
        </div>
        <!-- Pagination & Per Page -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 flex flex-col lg:flex-row items-center justify-between gap-6 bg-slate-50/30 dark:bg-white/[0.01]">
          <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-2 order-2 lg:order-1">
            <p class="text-xs text-slate-500 font-medium whitespace-nowrap">
              Menampilkan <span class="font-bold text-slate-900 dark:text-white">{{ logs.from || 0 }}-{{ logs.to || 0 }}</span> dari <span class="font-bold text-slate-900 dark:text-white">{{ logs.total || 0 }}</span> aktivitas
            </p>
            <div class="flex items-center gap-2 border-l border-slate-200 dark:border-white/10 pl-4 h-5">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Baris:</span>
              <select v-model="perPage" class="bg-transparent border-none p-0 text-[11px] font-black text-slate-700 dark:text-slate-300 focus:ring-0 cursor-pointer min-w-[40px]">
                <option v-for="n in [5, 10, 15, 20, 50, 100]" :key="n" :value="n" class="bg-white dark:bg-slate-900 font-sans font-normal">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="order-1 lg:order-2 w-full lg:w-auto flex justify-center">
            <Pagination :links="logs.links" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
