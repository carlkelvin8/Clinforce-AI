import { inject, computed } from 'vue';

export function useAdminTheme() {
  const isDark = inject('adminDark', { value: false });
  const d = computed(() => !!isDark.value);

  // Surfaces
  const bg        = computed(() => d.value ? 'bg-[#0b0f1a]'  : 'bg-slate-50');
  const card      = computed(() => d.value ? 'bg-[#111827] border-white/5'  : 'bg-white border-slate-200');
  const cardAlt   = computed(() => d.value ? 'bg-[#0d1424] border-white/5'  : 'bg-slate-50 border-slate-200');
  const cardHover = computed(() => d.value ? 'hover:bg-white/5' : 'hover:bg-slate-50');
  const input     = computed(() => d.value
    ? '!bg-white/5 !border-white/10 !text-white !placeholder-slate-500 focus:!border-blue-500'
    : '!bg-white !border-slate-200 !text-slate-900 !placeholder-slate-400 focus:!border-blue-500');
  const divider   = computed(() => d.value ? 'divide-white/5' : 'divide-slate-100');
  const border    = computed(() => d.value ? 'border-white/5' : 'border-slate-200');
  const thead     = computed(() => d.value ? 'bg-white/3' : 'bg-slate-50');
  const sidebar   = computed(() => d.value ? 'bg-[#0d1117] border-white/5' : 'bg-white border-slate-200');
  const header    = computed(() => d.value ? 'bg-[#0d1117]/80 border-white/5 backdrop-blur-xl' : 'bg-white/80 border-slate-200 backdrop-blur-xl');

  // Text
  const text      = computed(() => d.value ? 'text-white'      : 'text-slate-900');
  const textSub   = computed(() => d.value ? 'text-slate-400'  : 'text-slate-500');
  const textMuted = computed(() => d.value ? 'text-slate-500'  : 'text-slate-400');

  // Skeleton
  const skeleton  = computed(() => d.value ? 'bg-white/5' : 'bg-slate-200');

  // Row hover
  const rowHover  = computed(() => d.value ? 'hover:bg-white/3' : 'hover:bg-slate-50/80');

  return {
    isDark: d,
    bg, card, cardAlt, cardHover, input, divider, border, thead, sidebar, header,
    text, textSub, textMuted, skeleton, rowHover,
  };
}
