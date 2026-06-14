<script setup>
import { Link } from'@inertiajs/vue3';

defineProps({
  links: Array
});
</script>

<template>
  <div v-if="links.length > 3" class="w-full sm:w-auto">
    <!-- Mobile view: simple Prev/Next -->
    <div class="flex sm:hidden items-center justify-between w-full gap-3">
      <!-- Previous Button -->
      <div v-if="links[0].url === null" 
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase text-slate-400 border border-slate-200 dark:border-white/5 rounded-xl cursor-default bg-slate-50/50 dark:bg-slate-800/10"
        v-html="links[0].label"
      />
      <Link v-else
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase border border-slate-200 dark:border-white/5 rounded-xl transition-all bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 active:scale-[0.98]"
        :href="links[0].url"
        preserve-scroll
        preserve-state
        v-html="links[0].label"
      />

      <!-- Next Button -->
      <div v-if="links[links.length - 1].url === null" 
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase text-slate-400 border border-slate-200 dark:border-white/5 rounded-xl cursor-default bg-slate-50/50 dark:bg-slate-800/10"
        v-html="links[links.length - 1].label"
      />
      <Link v-else
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase border border-slate-200 dark:border-white/5 rounded-xl transition-all bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 active:scale-[0.98]"
        :href="links[links.length - 1].url"
        preserve-scroll
        preserve-state
        v-html="links[links.length - 1].label"
      />
    </div>

    <!-- Desktop view: full numbered list -->
    <div class="hidden sm:flex flex-wrap gap-1">
      <template v-for="(link, key) in links" :key="key">
        <div v-if="link.url === null" 
          class="px-3.5 py-2 text-xs leading-4 text-slate-400 border border-slate-200 dark:border-white/5 rounded-lg cursor-default font-semibold"
          v-html="link.label" 
        />
        <Link v-else
          class="px-3.5 py-2 text-xs leading-4 border border-slate-200 dark:border-white/5 rounded-lg transition-all hover:bg-slate-50 dark:hover:bg-white/5 font-semibold cursor-pointer"
          :class="{'bg-blue-600 border-blue-600 text-white hover:bg-blue-700': link.active }"
          :href="link.url"
          preserve-scroll
          preserve-state
          v-html="link.label"
        />
      </template>
    </div>
  </div>
</template>
