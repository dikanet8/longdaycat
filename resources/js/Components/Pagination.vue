<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap gap-1">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" 
                class="px-4 py-2 text-sm leading-4 text-slate-400 border border-slate-200 dark:border-white/5 rounded-md cursor-default"
                v-html="link.label" 
            />
            <Link v-else
                class="px-4 py-2 text-sm leading-4 border border-slate-200 dark:border-white/5 rounded-md transition-all hover:bg-slate-50 dark:hover:bg-white/5"
                :class="{ 'bg-blue-600 border-blue-600 text-white hover:bg-blue-700': link.active }"
                :href="link.url"
                v-html="link.label"
            />
        </template>
    </div>
</template>
