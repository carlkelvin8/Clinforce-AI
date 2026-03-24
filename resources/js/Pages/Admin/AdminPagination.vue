<script setup>
import { useAdminTheme } from '@/composables/useAdminTheme';

const props = defineProps({
  page: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, default: null },
  perPage: { type: Number, default: null },
});
const emit = defineEmits(['change']);

const { isDark, textSub, textMuted } = useAdminTheme();

function pages() {
  const p = props.page, last = props.lastPage;
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
  const set = new Set([1, 2, p - 1, p, p + 1, last - 1, last].filter(n => n >= 1 && n <= last));
  const arr = [...set].sort((a, b) => a - b);
  const result = [];
  for (let i = 0; i < arr.length; i++) {
    if (i > 0 && arr[i] - arr[i - 1] > 1) result.push('...');
    result.push(arr[i]);
  }
  return result;
}
</script>

<template>
  <div v-if="lastPage > 1" class="flex items-center justify-between flex-wrap gap-3">
    <p v-if="total != null" :class="['text-xs', textMuted]">
      {{ total }} total
      <span v-if="perPage"> · page {{ page }} of {{ lastPage }}</span>
    </p>
    <div class="flex items-center gap-1">
      <button
        :disabled="page <= 1"
        @click="emit('change', page - 1)"
        :class="['w-8 h-8 rounded-lg flex items-center justify-center text-xs transition-all disabled:opacity-30',
          isDark ? 'hover:bg-white/5 text-slate-400' : 'hover:bg-slate-100 text-slate-500']">
        <i class="pi pi-chevron-left text-xs"></i>
      </button>

      <template v-for="p in pages()" :key="p">
        <span v-if="p === '...'" :class="['w-8 h-8 flex items-center justify-center text-xs', textMuted]">…</span>
        <button v-else @click="emit('change', p)"
          :class="['w-8 h-8 rounded-lg text-xs font-medium transition-all',
            p === page
              ? 'bg-blue-600 text-white shadow-sm'
              : isDark ? 'text-slate-400 hover:bg-white/5' : 'text-slate-600 hover:bg-slate-100']">
          {{ p }}
        </button>
      </template>

      <button
        :disabled="page >= lastPage"
        @click="emit('change', page + 1)"
        :class="['w-8 h-8 rounded-lg flex items-center justify-center text-xs transition-all disabled:opacity-30',
          isDark ? 'hover:bg-white/5 text-slate-400' : 'hover:bg-slate-100 text-slate-500']">
        <i class="pi pi-chevron-right text-xs"></i>
      </button>
    </div>
  </div>
</template>
