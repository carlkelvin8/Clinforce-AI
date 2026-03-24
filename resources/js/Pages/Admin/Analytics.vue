<script setup>
import { ref, computed, onMounted, watch, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useAdminTheme } from '@/composables/useAdminTheme';

const { isDark, card, text, textSub, textMuted, skeleton, border, divider } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const loading = ref(true);
const period = ref('monthly');
const data = ref(null);
const exporting = ref(false);

const periods = [
  { label: 'Weekly',  value: 'weekly' },
  { label: 'Monthly', value: 'monthly' },
  { label: 'Yearly',  value: 'yearly' },
];

async function fetchAnalytics() {
  loading.value = true;
  try {
    const res = await api.get('/admin/analytics', { params: { period: period.value } });
    data.value = res?.data?.data || res?.data;
  } finally { loading.value = false; }
}

async function exportCsv(type) {
  exporting.value = true;
  try {
    const res = await api.get('/admin/export', { params: { type }, responseType: 'blob' });
    const url = URL.createObjectURL(res.data);
    const a = document.createElement('a'); a.href = url; a.download = `${type}-export.csv`; a.click();
    URL.revokeObjectURL(url);
  } finally { exporting.value = false; }
}

watch(period, fetchAnalytics);
onMounted(() => { setBreadcrumb([{ label: 'Analytics' }]); fetchAnalytics(); });

function formatLabel(p) {
  if (!p) return '';
  if (period.value === 'weekly') { const [, w] = p.split('-'); return `W${w}`; }
  if (period.value === 'yearly') return p;
  const [y, m] = p.split('-');
  return new Date(+y, +m - 1).toLocaleString('default', { month: 'short' });
}

const totalRevenue  = computed(() => (data.value?.revenue || []).reduce((s, r) => s + parseFloat(r.revenue || 0), 0).toFixed(2));
const totalNewSubs  = computed(() => (data.value?.revenue || []).reduce((s, r) => s + (r.count || 0), 0));
const totalNewUsers = computed(() => (data.value?.users   || []).reduce((s, r) => s + (r.count || 0), 0));

// ── SVG Line/Area chart helpers ──────────────────────────────────────
const W = 600, H = 160, PAD = { t: 10, r: 10, b: 30, l: 44 };

function buildLinePath(rows, valFn, fill = false) {
  if (!rows.length) return '';
  const vals = rows.map(valFn);
  const max = Math.max(...vals, 1);
  const cw = W - PAD.l - PAD.r;
  const ch = H - PAD.t - PAD.b;
  const pts = rows.map((_, i) => [
    PAD.l + (i / (rows.length - 1 || 1)) * cw,
    PAD.t + ch - (vals[i] / max) * ch,
  ]);
  const line = pts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p[0].toFixed(1)},${p[1].toFixed(1)}`).join(' ');
  if (!fill) return line;
  const last = pts[pts.length - 1];
  const first = pts[0];
  return `${line} L${last[0].toFixed(1)},${(PAD.t + ch).toFixed(1)} L${first[0].toFixed(1)},${(PAD.t + ch).toFixed(1)} Z`;
}

function xLabels(rows) {
  if (!rows.length) return [];
  const cw = W - PAD.l - PAD.r;
  const step = rows.length > 8 ? Math.ceil(rows.length / 6) : 1;
  return rows
    .map((r, i) => ({ x: PAD.l + (i / (rows.length - 1 || 1)) * cw, label: formatLabel(r.period), i }))
    .filter((_, i) => i % step === 0 || i === rows.length - 1);
}

function yLabels(rows, valFn) {
  const max = Math.max(...rows.map(valFn), 1);
  const ch = H - PAD.t - PAD.b;
  return [0, 0.25, 0.5, 0.75, 1].map(f => ({
    y: PAD.t + ch - f * ch,
    label: max * f >= 1000 ? `${(max * f / 1000).toFixed(1)}k` : (max * f).toFixed(f === 0 ? 0 : 0),
  }));
}

function dotPoints(rows, valFn) {
  if (!rows.length) return [];
  const vals = rows.map(valFn);
  const max = Math.max(...vals, 1);
  const cw = W - PAD.l - PAD.r;
  const ch = H - PAD.t - PAD.b;
  return rows.map((r, i) => ({
    x: PAD.l + (i / (rows.length - 1 || 1)) * cw,
    y: PAD.t + ch - (vals[i] / max) * ch,
    val: vals[i],
    label: formatLabel(r.period),
  }));
}

// ── Donut chart for plan distribution ───────────────────────────────
const DONUT_COLORS = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4'];

function donutSegments(items, valFn) {
  const total = items.reduce((s, i) => s + valFn(i), 0) || 1;
  let offset = 0;
  const r = 54, cx = 70, cy = 70, circ = 2 * Math.PI * r;
  return items.map((item, idx) => {
    const pct = valFn(item) / total;
    const dash = pct * circ;
    const seg = { offset: offset * circ, dash, pct: (pct * 100).toFixed(1), color: DONUT_COLORS[idx % DONUT_COLORS.length] };
    offset += pct;
    return { ...item, ...seg };
  });
}

// ── Role distribution donut ──────────────────────────────────────────
const roleData = computed(() => {
  const d = data.value?.role_distribution || [];
  return donutSegments(d, r => r.count || 0);
});

const planData = computed(() => {
  const d = data.value?.by_plan || [];
  return donutSegments(d, p => p.count || 0);
});

const revRows = computed(() => data.value?.revenue || []);
const userRows = computed(() => data.value?.users || []);
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 :class="['text-2xl font-bold', text]">Analytics</h1>
          <p :class="['text-sm mt-1', textSub]">Revenue, subscriptions, and user growth</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex gap-2">
            <Button label="Users" icon="pi pi-download" size="small" severity="secondary" :loading="exporting" @click="exportCsv('users')" />
            <Button label="Revenue" icon="pi pi-download" size="small" severity="secondary" :loading="exporting" @click="exportCsv('revenue')" />
            <Button label="Subs" icon="pi pi-download" size="small" severity="secondary" :loading="exporting" @click="exportCsv('subscriptions')" />
          </div>
          <div class="flex gap-1 p-1 rounded-xl" :class="isDark ? 'bg-slate-800' : 'bg-slate-100'">
            <button v-for="p in periods" :key="p.value" @click="period = p.value"
              :class="['px-4 py-1.5 rounded-lg text-sm font-medium transition-all',
                period === p.value ? 'bg-blue-600 text-white shadow' : isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900']">
              {{ p.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div :class="['rounded-2xl p-6 border', card]">
          <p :class="['text-xs uppercase tracking-wider font-semibold mb-2', textMuted]">Total Revenue</p>
          <p :class="['text-4xl font-bold', text]">
            <span v-if="loading" :class="['inline-block w-28 h-9 rounded animate-pulse', isDark ? 'bg-white/5' : 'bg-slate-100']"></span>
            <span v-else>${{ totalRevenue }}</span>
          </p>
          <p :class="['text-xs mt-1.5', textMuted]">{{ period }} period</p>
        </div>
        <div :class="['rounded-2xl p-6 border', card]">
          <p :class="['text-xs uppercase tracking-wider font-semibold mb-2', textMuted]">New Subscriptions</p>
          <p :class="['text-4xl font-bold', text]">
            <span v-if="loading" :class="['inline-block w-20 h-9 rounded animate-pulse', isDark ? 'bg-white/5' : 'bg-slate-100']"></span>
            <span v-else>{{ totalNewSubs }}</span>
          </p>
          <p :class="['text-xs mt-1.5', textMuted]">{{ period }} period</p>
        </div>
        <div :class="['rounded-2xl p-6 border', card]">
          <p :class="['text-xs uppercase tracking-wider font-semibold mb-2', textMuted]">New Users</p>
          <p :class="['text-4xl font-bold', text]">
            <span v-if="loading" :class="['inline-block w-20 h-9 rounded animate-pulse', isDark ? 'bg-white/5' : 'bg-slate-100']"></span>
            <span v-else>{{ totalNewUsers }}</span>
          </p>
          <p :class="['text-xs mt-1.5', textMuted]">{{ period }} period</p>
        </div>
      </div>

      <!-- Revenue Line Chart -->
      <div :class="['rounded-2xl border p-6', card]">
        <h2 :class="['font-semibold mb-5', text]">Revenue Over Time</h2>
        <div v-if="loading" :class="['h-48 animate-pulse rounded-xl', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
        <div v-else-if="!revRows.length" :class="['h-48 flex items-center justify-center text-sm', textMuted]">No data for this period</div>
        <div v-else class="overflow-x-auto">
          <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" style="min-width:320px;height:160px">
            <!-- Grid lines -->
            <line v-for="y in yLabels(revRows, r => parseFloat(r.revenue||0))" :key="y.y"
              :x1="PAD.l" :y1="y.y" :x2="W - PAD.r" :y2="y.y"
              :stroke="isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)'" stroke-width="1" />
            <!-- Y labels -->
            <text v-for="y in yLabels(revRows, r => parseFloat(r.revenue||0))" :key="'yl'+y.y"
              :x="PAD.l - 6" :y="y.y + 4" text-anchor="end" font-size="10"
              :fill="isDark ? '#475569' : '#94a3b8'">{{ y.label }}</text>
            <!-- Area fill -->
            <path :d="buildLinePath(revRows, r => parseFloat(r.revenue||0), true)"
              fill="url(#revGrad)" opacity="0.3" />
            <!-- Line -->
            <path :d="buildLinePath(revRows, r => parseFloat(r.revenue||0))"
              fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
            <!-- Dots -->
            <circle v-for="d in dotPoints(revRows, r => parseFloat(r.revenue||0))" :key="d.label+d.x"
              :cx="d.x" :cy="d.y" r="4" fill="#3b82f6" stroke="white" stroke-width="2" />
            <!-- X labels -->
            <text v-for="l in xLabels(revRows)" :key="'xl'+l.i"
              :x="l.x" :y="H - 6" text-anchor="middle" font-size="10"
              :fill="isDark ? '#475569' : '#94a3b8'">{{ l.label }}</text>
            <defs>
              <linearGradient id="revGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#3b82f6" />
                <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
              </linearGradient>
            </defs>
          </svg>
        </div>
      </div>

      <!-- User Growth Line Chart -->
      <div :class="['rounded-2xl border p-6', card]">
        <h2 :class="['font-semibold mb-5', text]">User Registrations</h2>
        <div v-if="loading" :class="['h-48 animate-pulse rounded-xl', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
        <div v-else-if="!userRows.length" :class="['h-48 flex items-center justify-center text-sm', textMuted]">No data</div>
        <div v-else class="overflow-x-auto">
          <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" style="min-width:320px;height:160px">
            <line v-for="y in yLabels(userRows, r => r.count||0)" :key="y.y"
              :x1="PAD.l" :y1="y.y" :x2="W - PAD.r" :y2="y.y"
              :stroke="isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.06)'" stroke-width="1" />
            <text v-for="y in yLabels(userRows, r => r.count||0)" :key="'yl'+y.y"
              :x="PAD.l - 6" :y="y.y + 4" text-anchor="end" font-size="10"
              :fill="isDark ? '#475569' : '#94a3b8'">{{ y.label }}</text>
            <path :d="buildLinePath(userRows, r => r.count||0, true)"
              fill="url(#userGrad)" opacity="0.3" />
            <path :d="buildLinePath(userRows, r => r.count||0)"
              fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
            <circle v-for="d in dotPoints(userRows, r => r.count||0)" :key="d.label+d.x"
              :cx="d.x" :cy="d.y" r="4" fill="#10b981" stroke="white" stroke-width="2" />
            <text v-for="l in xLabels(userRows)" :key="'xl'+l.i"
              :x="l.x" :y="H - 6" text-anchor="middle" font-size="10"
              :fill="isDark ? '#475569' : '#94a3b8'">{{ l.label }}</text>
            <defs>
              <linearGradient id="userGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#10b981" />
                <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
              </linearGradient>
            </defs>
          </svg>
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-6">

        <!-- Revenue by Plan — Donut -->
        <div :class="['rounded-2xl border p-6', card]">
          <h2 :class="['font-semibold mb-5', text]">Revenue by Plan</h2>
          <div v-if="loading" :class="['h-40 animate-pulse rounded-xl', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
          <div v-else-if="!data?.by_plan?.length" :class="['h-40 flex items-center justify-center text-sm', textMuted]">No data</div>
          <div v-else class="flex items-center gap-6">
            <!-- Donut SVG -->
            <svg viewBox="0 0 140 140" class="w-32 h-32 flex-shrink-0 -rotate-90">
              <circle cx="70" cy="70" r="54" fill="none"
                :stroke="isDark ? 'rgba(255,255,255,0.05)' : '#f1f5f9'" stroke-width="20" />
              <circle v-for="seg in planData" :key="seg.plan_name || seg.id"
                cx="70" cy="70" r="54" fill="none"
                :stroke="seg.color" stroke-width="20"
                :stroke-dasharray="`${seg.dash} ${2 * Math.PI * 54 - seg.dash}`"
                :stroke-dashoffset="-seg.offset"
                stroke-linecap="butt" />
            </svg>
            <!-- Legend -->
            <div class="flex-1 space-y-2 min-w-0">
              <div v-for="seg in planData" :key="seg.plan_name" class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                  <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: seg.color }"></div>
                  <span :class="['text-xs truncate', text]">{{ seg.plan_name || '—' }}</span>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                  <span :class="['text-xs font-semibold', text]">${{ parseFloat(seg.revenue||0).toFixed(0) }}</span>
                  <span :class="['text-xs', textMuted]">{{ seg.pct }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Employers -->
        <div :class="['rounded-2xl border p-6', card]">
          <h2 :class="['font-semibold mb-4', text]">Top Employers by Jobs</h2>
          <div v-if="loading" class="space-y-3">
            <div v-for="i in 5" :key="i" :class="['h-8 animate-pulse rounded-lg', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
          </div>
          <div v-else-if="!data?.top_employers?.length" :class="['text-sm py-6 text-center', textMuted]">No data</div>
          <div v-else class="space-y-2">
            <div v-for="(e, i) in data.top_employers" :key="e.owner_user_id"
              :class="['flex items-center gap-3 p-2.5 rounded-xl', isDark ? 'hover:bg-white/5' : 'hover:bg-slate-50']">
              <span :class="['text-xs font-bold w-5 text-center flex-shrink-0', textMuted]">{{ i + 1 }}</span>
              <div class="flex-1 min-w-0">
                <p :class="['text-sm truncate', text]">{{ e.owner?.email || 'User #' + e.owner_user_id }}</p>
              </div>
              <!-- Mini bar -->
              <div class="w-20 h-1.5 rounded-full overflow-hidden flex-shrink-0" :class="isDark ? 'bg-white/5' : 'bg-slate-100'">
                <div class="h-full bg-blue-500 rounded-full"
                  :style="{ width: (e.job_count / (data.top_employers[0]?.job_count || 1) * 100) + '%' }"></div>
              </div>
              <Tag :value="e.job_count + ''" severity="info" class="text-xs flex-shrink-0" />
            </div>
          </div>
        </div>

        <!-- Role Distribution Donut -->
        <div :class="['rounded-2xl border p-6', card]">
          <h2 :class="['font-semibold mb-5', text]">User Role Distribution</h2>
          <div v-if="loading" :class="['h-40 animate-pulse rounded-xl', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
          <div v-else-if="!data?.role_distribution?.length" :class="['h-40 flex items-center justify-center text-sm', textMuted]">No data</div>
          <div v-else class="flex items-center gap-6">
            <svg viewBox="0 0 140 140" class="w-32 h-32 flex-shrink-0 -rotate-90">
              <circle cx="70" cy="70" r="54" fill="none"
                :stroke="isDark ? 'rgba(255,255,255,0.05)' : '#f1f5f9'" stroke-width="20" />
              <circle v-for="seg in roleData" :key="seg.role"
                cx="70" cy="70" r="54" fill="none"
                :stroke="seg.color" stroke-width="20"
                :stroke-dasharray="`${seg.dash} ${2 * Math.PI * 54 - seg.dash}`"
                :stroke-dashoffset="-seg.offset"
                stroke-linecap="butt" />
            </svg>
            <div class="flex-1 space-y-2">
              <div v-for="seg in roleData" :key="seg.role" class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: seg.color }"></div>
                  <span :class="['text-xs capitalize', text]">{{ seg.role }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span :class="['text-xs font-semibold', text]">{{ seg.count }}</span>
                  <span :class="['text-xs', textMuted]">{{ seg.pct }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Subscription Status Breakdown -->
        <div :class="['rounded-2xl border p-6', card]">
          <h2 :class="['font-semibold mb-4', text]">Subscription Status</h2>
          <div v-if="loading" class="space-y-3">
            <div v-for="i in 4" :key="i" :class="['h-8 animate-pulse rounded-lg', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
          </div>
          <div v-else-if="!data?.subscription_status?.length" :class="['text-sm py-6 text-center', textMuted]">No data</div>
          <div v-else class="space-y-3">
            <div v-for="s in data.subscription_status" :key="s.status">
              <div class="flex items-center justify-between mb-1">
                <span :class="['text-xs font-medium capitalize', text]">{{ s.status }}</span>
                <span :class="['text-xs', textMuted]">{{ s.count }}</span>
              </div>
              <div class="h-2 rounded-full overflow-hidden" :class="isDark ? 'bg-white/5' : 'bg-slate-100'">
                <div class="h-full rounded-full transition-all duration-700"
                  :class="{
                    'bg-emerald-500': s.status === 'active',
                    'bg-blue-500': s.status === 'trial',
                    'bg-red-500': s.status === 'cancelled',
                    'bg-slate-400': s.status === 'expired',
                  }"
                  :style="{ width: (s.count / Math.max(...(data.subscription_status||[]).map(x=>x.count),1) * 100) + '%' }">
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AdminLayout>
</template>
