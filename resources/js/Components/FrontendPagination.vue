<script setup>
import { computed } from 'vue';

const props = defineProps({
  total: {
    type: Number,
    required: true
  },
  perPage: {
    type: Number,
    required: true
  },
  modelValue: {
    type: Number,
    required: true
  }
});

const emit = defineEmits(['update:modelValue']);

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    emit('update:modelValue', page);
  }
};

const links = computed(() => {
  const result = [];
  
  // Previous
  result.push({
    label: '&laquo; Sebelumnya',
    page: props.modelValue > 1 ? props.modelValue - 1 : null,
    active: false
  });

  const generatePageLink = (pageNum) => ({
    label: String(pageNum),
    page: pageNum,
    active: pageNum === props.modelValue
  });

  const createEllipsis = () => ({
    label: '...',
    page: null,
    active: false
  });

  const numPages = totalPages.value;
  const current = props.modelValue;

  if (numPages <= 7) {
    for (let i = 1; i <= numPages; i++) {
      result.push(generatePageLink(i));
    }
  } else {
    // Complex pagination logic similar to Laravel's
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        result.push(generatePageLink(i));
      }
      result.push(createEllipsis());
      result.push(generatePageLink(numPages - 1));
      result.push(generatePageLink(numPages));
    } else if (current > numPages - 4) {
      result.push(generatePageLink(1));
      result.push(generatePageLink(2));
      result.push(createEllipsis());
      for (let i = numPages - 4; i <= numPages; i++) {
        result.push(generatePageLink(i));
      }
    } else {
      result.push(generatePageLink(1));
      result.push(generatePageLink(2));
      result.push(createEllipsis());
      for (let i = current - 1; i <= current + 1; i++) {
        result.push(generatePageLink(i));
      }
      result.push(createEllipsis());
      result.push(generatePageLink(numPages - 1));
      result.push(generatePageLink(numPages));
    }
  }

  // Next
  result.push({
    label: 'Berikutnya &raquo;',
    page: props.modelValue < totalPages.value ? props.modelValue + 1 : null,
    active: false
  });

  return result;
});
</script>

<template>
  <div v-if="totalPages > 1" class="w-full sm:w-auto">
    <!-- Mobile view: simple Prev/Next -->
    <div class="flex sm:hidden items-center justify-between w-full gap-3">
      <!-- Previous Button -->
      <div v-if="links[0].page === null" 
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase text-slate-400 border border-slate-200 dark:border-white/5 rounded-xl cursor-default bg-slate-50/50 dark:bg-slate-800/10"
        v-html="links[0].label"
      />
      <button v-else
        @click="changePage(links[0].page)"
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase border border-slate-200 dark:border-white/5 rounded-xl transition-all bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 active:scale-[0.98]"
        v-html="links[0].label"
      ></button>

      <!-- Next Button -->
      <div v-if="links[links.length - 1].page === null" 
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase text-slate-400 border border-slate-200 dark:border-white/5 rounded-xl cursor-default bg-slate-50/50 dark:bg-slate-800/10"
        v-html="links[links.length - 1].label"
      />
      <button v-else
        @click="changePage(links[links.length - 1].page)"
        class="flex-1 text-center px-4 py-2.5 text-xs font-bold uppercase border border-slate-200 dark:border-white/5 rounded-xl transition-all bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5 active:scale-[0.98]"
        v-html="links[links.length - 1].label"
      ></button>
    </div>

    <!-- Desktop view: full numbered list -->
    <div class="hidden sm:flex flex-wrap gap-1">
      <template v-for="(link, key) in links" :key="key">
        <div v-if="link.page === null" 
          class="px-3.5 py-2 text-xs leading-4 text-slate-400 border border-slate-200 dark:border-white/5 rounded-lg cursor-default font-semibold"
          v-html="link.label" 
        />
        <button v-else
          @click="changePage(link.page)"
          class="px-3.5 py-2 text-xs leading-4 border border-slate-200 dark:border-white/5 rounded-lg transition-all hover:bg-slate-50 dark:hover:bg-white/5 font-semibold cursor-pointer"
          :class="{'bg-blue-600 border-blue-600 text-white hover:bg-blue-700': link.active }"
          v-html="link.label"
        ></button>
      </template>
    </div>
  </div>
</template>
