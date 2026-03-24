<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import { get } from '@/lib/http';
import { useRouter } from 'vue-router';
import Tag from 'primevue/tag';
import { useAdminTheme } from '@/composables/useAdminTheme';
import api from '@/lib/api';

const router = useRouter();
const { isDark, card, text, textSub, textMuted, divider, border } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});
const stats = ref(null);
const mrr = ref(null);
const loading = ref(true);

onMounted(async () => {
  setBreadcrumb([{ label: 'Dashboard' }]);
  try {
    const [statsRes, mrrRes] = await Promise.all([
      get('/api/admin/stats'),
      api.get('/admin/mrr'),
    ]);
    stats.value = statsRes?.data?.data || statsRes?.data;
    mrr.value = mrrRes?.data?.data || mrrRes?.data;
  } finally {
    loading.value = false;
  }
});

const statCards = [
  { key: 'total_users',           label: 'Total Users',    icon: 'pi pi-users',       color: 'blue' },
  { key: 'total_employers',       label: 'Employers',      icon: 'pi pi-building',    color: 'indigo' },
  { key: 'total_candidates',      label: 'Candidates',     icon: 'pi pi-user',        color: 'violet' },
  { key: 'active_jobs',           label: 'Active Jobs',    icon: 'pi pi-briefcase',   color: 'emerald' },
  { key: 'total_applications',    label: 'Applications',   icon: 'pi pi-file',        color: 'cyan' },
  { key: 'active_subscriptions',  label: 'Subscriptions',  icon: 'pi pi-credit-card', color: 'amber' },
  { key: 'total_revenue',         label: 'Total Revenue',  icon: 'pi pi-dollar',      color: 'green', prefix: '$' },
  { key: 'pending_verifications', label: 'Pending Verif.', icon: 'pi pi-clock',       color: 'red' },
];

const colorMap = {
  blue:    { ring: 'ring-blue-500/20',    icon: 'text-blue-500',    bg: 'bg-blue-500/10' },
  indigo:  { ring: 'ring-indigo-500/20',  icon: 'text-indigo-500',  bg: 'bg-indigo-500/10' },
  violet:  { ring: 'ring-violet-500/20',  icon: 'text-violet-500',  bg: 'bg-violet-500/10' },
  emerald: { ring: 'ring-emerald-500/20', icon: 'text-emerald-500', bg: 'bg-emerald-500/10' },
  cyan:    { ring: 'ring-cyan-500/20',    icon: 'text-cyan-500',    bg: 'bg-cyan-500/10' },
  amber:   { ring: 'ring-amber-500/20',   icon: 'text-amber-500',   bg: 'bg-amber-500/10' },
  green:   { ring: 'ring-green-500/20',   icon: 'text-green-500',   bg: 'bg-green-500/10' },
  red:     { ring: 'ring-red-500/20',     icon: 'text-red-500',     bg: 'bg-red-500/10' },
};

const roleSeverity   = { admin: 'danger', employer: 'info', agency: 'secondary', applicant: 'contrast' };
const statusSeverity = { active: 'success', suspended: 'warn', banned: 'danger' };

const mrrCards = computed(() => [
  { label: 'MRR',            value: mrr.value?.mrr != null ? '$' + mrr.value.mrr : '—',                          sub: 'Monthly recurring', color: 'text-blue-500' },
  { label: 'New This Month', value: mrr.value?.new_this_month != null ? '+' + mrr.value.new_this_month : '—',     sub: 'Subscriptions',     color: 'text-emerald-500' },
  { label: 'Churn Rate',     value: mrr.value?.churn_rate != null ? mrr.value.churn_rate + '%' : '—',             sub: 'This month',        color: mrr.value?.churn_rate > 5 ? 'text-red-500' : 'text-amber-500' },
  { label: 'Cancelled',      value: mrr.value?.cancelled_this_month != null ? String(mrr.value.cancelled_this_month) : '—', sub: 'This month', color: 'text-red-500' },
]);

const quickLinks = [
  { label: 'Manage Users',         icon: 'pi pi-users',       route: 'admin.users',         color: 'text-blue-500',    bg: 'bg-blue-500/10' },
  { label: 'Review Verifications', icon: 'pi pi-verified',    route: 'admin.verifications', color: 'text-amber-500',   bg: 'bg-amber-500/10' },
  { label: 'View Jobs',            icon: 'pi pi-briefcase',   route: 'admin.jobs',          color: 'text-emerald-500', bg: 'bg-emerald-500/10' },
  { label: 'Analytics',            icon: 'pi pi-chart-line',  route: 'admin.analytics',     color: 'text-violet-500',  bg: 'bg-violet-500/10' },
  { label: 'Subscriptions',        icon: 'pi pi-credit-card', route: 'admin.subscriptions', color: 'text-cyan-500',    bg: 'bg-cyan-500/10' },
  { label: 'Audit Logs',           icon: 'pi pi-list',        route: 'admin.audit-logs',    color: 'text-slate-400',   bg: 'bg-slate-500/10' },
];

// ── SVG chart helpers ────────────────────────────────────────────────
const W = 500, H = 140, PAD = { t: 8, r: 8, b: 24, l: 36 };

function buildLinePath(rows, valFn, fill = false) {
  if (!rows || !rows.length) return '';
  const vals = rows.map(valFn);
  const max = Math.max(...vals, 1);
  const cw = W - PAD.l - PAD.r, ch = H - PAD.t - PAD.b;
  const pts = rows.map((_, i) => [
    PAD.l + (i / (rows.length - 1 || 1)) * cw,
    PAD.t + ch - (vals[i] / max) * ch,
  ]);
  const line = pts.map((p, i) => (i === 0 ? 'M' : 'L') + p[0].toFixed(1) + ',' + p[1].toFixed(1)).join(' ');
  if (!fill) return line;
  const last = pts[pts.length - 1], first = pts[0];
  return line + ' L' + last[0].toFixed(1) + ',' + (PAD.t + ch).toFixed(1) + ' L' + first[0].toFixed(1) + ',' + (PAD.t + ch).toFixed(1) + ' Z';
}

function dotPoints(rows, valFn) {
  if (!rows || !rows.length) return [];
  const vals = rows.map(valFn);
  const max = Math.max(...vals, 1);
  const cw = W - PAD.l - PAD.r, ch = H - PAD.t - PAD.b;
  return rows.map((r, i) => ({
    x: PAD.l + (i / (rows.length - 1 || 1)) * cw,
    y: PAD.t + ch - (vals[i] / max) * ch,
    val: vals[i],
    label: r.month ? r.month.slice(5) : String(i),
  }));
}

function xLabels(rows, labelFn) {
  if (!rows || !rows.length) return [];
  const cw = W - PAD.l - PAD.r;
  const step = rows.length > 6 ? Math.ceil(rows.length / 5) : 1;
  return rows
    .map((r, i) => ({ x: PAD.l + (i / (rows.length - 1 || 1)) * cw, label: labelFn(r), i }))
    .filter((_, i) => i % step === 0 || i === rows.length - 1);
}

// ── Role donut ───────────────────────────────────────────────────────
const DONUT_COLORS = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b'];
const roleDonut = computed(() => {
  if (!stats.value) return [];
  const items = [
    { label: 'Employers',  count: stats.value.total_employers  || 0 },
    { label: 'Candidates', count: stats.value.total_candidates || 0 },
  ];
  const total = items.reduce((s, i) => s + i.count, 0) || 1;
  const r = 40, circ = 2 * Math.PI * r;
  let offset = 0;
  return items.map((item, idx) => {
    const pct = item.count / total;
    const seg = { ...item, pct: (pct * 100).toFixed(1), color: DONUT_COLORS[idx], dash: pct * circ, offset: offset * circ };
    offset += pct;
    return seg;
  });
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 :class="['text-2xl font-bold', text]">Dashboard</h1>
          <p :class="['text-sm mt-1', textSub]">Platform overview</p>
        </div>
        <div v-if="!loading && stats?.pending_verifications > 0"
          class="flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 rounded-xl px-4 py-2.5">
          <i class="pi pi-exclamation-triangle text-amber-500 text-sm"></i>
          <span class="text-amber-600 dark:text-amber-400 text-sm font-medium">
            {{ stats.pending_verifications }} pending verification{{ stats.pending_verifications > 1 ? 's' : '' }}
          </span>
          <button @click="router.push({ name: 'admin.verifications' })"
            class="text-amber-500 text-xs underline hover:no-underline ml-1">Review</button>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <template v-if="loading">
          <div v-for="i in 8" :key="i" :class="['rounded-2xl p-5 border animate-pulse h-[100px]', card]"></div>
        </template>
        <template v-else>
          <div v-for="c in statCards" :key="c.key"
            :class="['rounded-2xl p-4 border ring-1 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg cursor-default', card, colorMap[c.color].ring]">
            <div class="flex items-start justify-between mb-3">
              <div :class="['w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0', colorMap[c.color].bg]">
                <i :class="[c.icon, colorMap[c.color].icon, 'text-sm']"></i>
              </div>
            </div>
            <div :class="['text-2xl font-bold tracking-tight', text]">{{ c.prefix || '' }}{{ stats?.[c.key] ?? '—' }}</div>
            <div :class="['text-xs mt-1 font-medium', textMuted]">{{ c.label }}</div>
          </div>
        </template>
      </div>

      <!-- MRR Row -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <template v-if="loading">
          <div v-for="i in 4" :key="i" :class="['rounded-2xl p-5 border animate-pulse h-[90px]', card]"></div>
        </template>
        <template v-else>
          <div v-for="m in mrrCards" :key="m.label" :class="['rounded-2xl p-4 border', card]">
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-2', textMuted]">{{ m.label }}</p>
            <p :class="['text-2xl font-bold', m.color]">{{ m.value }}</p>
            <p :class="['text-xs mt-1', textMuted]">{{ m.sub }}</p>
          </div>
        </template>
      </div>

      <!-- Growth Chart + Quick Actions -->
      <div class="grid lg:grid-cols-3 gap-5">
        <div :class="['lg:col-span-2 rounded-2xl border p-6', card]">
          <div class="flex items-center justify-between mb-5">
            <h2 :class="['font-semibold', text]">User Growth</h2>
            <span :class="['text-xs', textMuted]">Last 6 months</span>
          </div>
          <div v-if="loading" :class="['h-36 animate-pulse rounded-xl', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
          <div v-else-if="!stats?.user_growth?.length" :class="['h-36 flex items-center justify-center text-sm', textMuted]">No data</div>
          <div v-else class="overflow-x-auto">
            <svg :viewBox="'0 0 ' + W + ' ' + H" class="w-full" style="min-width:280px;height:140px">
              <path :d="buildLinePath(stats.user_growth, r => r.count||0, true)"
                fill="url(#growthGrad)" opacity="0.25" />
              <path :d="buildLinePath(stats.user_growth, r => r.count||0)"
                fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              <circle v-for="d in dotPoints(stats.user_growth, r => r.count||0)" :key="d.label + d.x"
                :cx="d.x" :cy="d.y" r="4" fill="#3b82f6" stroke="white" stroke-width="2" />
              <text v-for="l in xLabels(stats.user_growth, r => r.month ? r.month.slice(5) : '')" :key="'xl' + l.i"
                :x="l.x" :y="H - 4" text-anchor="middle" font-size="10"
                :fill="isDark ? '#475569' : '#94a3b8'">{{ l.label }}</text>
              <defs>
                <linearGradient id="growthGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#3b82f6" />
                  <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
                </linearGradient>
              </defs>
            </svg>
          </div>
        </div>

        <div :class="['rounded-2xl border p-5', card]">
          <h2 :class="['font-semibold mb-4', text]">Quick Actions</h2>
          <div class="space-y-1">
            <button v-for="link in quickLinks" :key="link.route"
              @click="router.push({ name: link.route })"
              :class="['w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-left group',
                isDark ? 'hover:bg-white/5' : 'hover:bg-slate-50']">
              <div :class="['w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0', link.bg]">
                <i :class="[link.icon, link.color, 'text-sm']"></i>
              </div>
              <span :class="['text-sm font-medium flex-1', text]">{{ link.label }}</span>
              <i :class="['pi pi-chevron-right text-[10px] transition-transform group-hover:translate-x-0.5', textMuted]"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Role Donut + Recent Registrations -->
      <div class="grid lg:grid-cols-3 gap-5">

        <div :class="['rounded-2xl border p-6', card]">
          <h2 :class="['font-semibold mb-5', text]">User Breakdown</h2>
          <div v-if="loading" :class="['h-32 animate-pulse rounded-xl', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
          <div v-else class="flex items-center gap-5">
            <svg viewBox="0 0 100 100" class="w-24 h-24 flex-shrink-0 -rotate-90">
              <circle cx="50" cy="50" r="40" fill="none"
                :stroke="isDark ? 'rgba(255,255,255,0.05)' : '#f1f5f9'" stroke-width="16" />
              <circle v-for="seg in roleDonut" :key="seg.label"
                cx="50" cy="50" r="40" fill="none"
                :stroke="seg.color" stroke-width="16"
                :stroke-dasharray="seg.dash + ' ' + (2 * Math.PI * 40 - seg.dash)"
                :stroke-dashoffset="-seg.offset" />
            </svg>
            <div class="space-y-2.5 flex-1">
              <div v-for="seg in roleDonut" :key="seg.label" class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-2 h-2 rounded-full" :style="{ background: seg.color }"></div>
                  <span :class="['text-xs', text]">{{ seg.label }}</span>
                </div>
                <span :class="['text-xs font-semibold', text]">{{ seg.count }}</span>
              </div>
            </div>
          </div>
        </div>

        <div :class="['lg:col-span-2 rounded-2xl border overflow-hidden', card]">
          <div :class="['px-6 py-4 border-b flex items-center justify-between', border]">
            <h2 :class="['font-semibold', text]">Recent Registrations</h2>
            <button @click="router.push({ name: 'admin.users' })"
              class="text-xs text-blue-500 hover:text-blue-400 transition-colors font-medium">View all →</button>
          </div>
          <div :class="['divide-y', divider]">
            <div v-if="loading" v-for="i in 5" :key="i" class="px-6 py-4 flex items-center gap-3">
              <div :class="['w-9 h-9 rounded-full animate-pulse flex-shrink-0', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
              <div class="flex-1 space-y-1.5">
                <div :class="['h-3 rounded w-1/3 animate-pulse', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
                <div :class="['h-2.5 rounded w-1/5 animate-pulse', isDark ? 'bg-white/5' : 'bg-slate-100']"></div>
              </div>
            </div>
            <div v-else-if="!stats?.recent_users?.length"
              :class="['px-6 py-10 text-center text-sm', textMuted]">No recent users</div>
            <div v-else v-for="u in stats.recent_users" :key="u.id"
              :class="['px-6 py-3.5 flex items-center justify-between transition-colors',
                isDark ? 'hover:bg-white/3' : 'hover:bg-slate-50/80']">
              <div class="flex items-center gap-3">
                <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0',
                  isDark ? 'bg-slate-700 text-slate-300' : 'bg-slate-100 text-slate-600']">
                  {{ (u.email || u.phone || '#')[0].toUpperCase() }}
                </div>
                <div>
                  <div :class="['text-sm font-medium', text]">{{ u.email || u.phone || 'User #' + u.id }}</div>
                  <div :class="['text-xs mt-0.5', textMuted]">#{{ u.id }}</div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Tag :value="u.role" :severity="roleSeverity[u.role]" class="text-xs" />
                <Tag :value="u.status" :severity="statusSeverity[u.status]" class="text-xs" />
                <span :class="['text-xs ml-2 hidden sm:block', textMuted]">{{ new Date(u.created_at).toLocaleDateString() }}</span>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </AdminLayout>
</template>
