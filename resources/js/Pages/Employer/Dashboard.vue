<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import SkeletonCard from "@/Components/SkeletonCard.vue";
import EmailVerificationBanner from "@/Components/EmailVerificationBanner.vue";
import { http } from "../../lib/http";
import { getCachedUser, me } from "@/lib/auth";
import { useDarkMode } from "@/composables/useDarkMode";

// PrimeVue
import Select from "primevue/select";
import Tag from "primevue/tag";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Chart from "primevue/chart";
import Avatar from "primevue/avatar";
import Button from "primevue/button";

/** =======================
 *  Dashboard Data
 *  ======================= */
const loading = ref(true);
const error = ref("");

const range = ref("30"); // 7 | 30 | 90
const jobs = ref([]);
const apps = ref([]);
const demoAppsMode = ref(false);
const analyticsData = ref(null); // from /analytics/dashboard

// derived "event" list for charts
const applicantEvents = ref([]); // { dayKey:'YYYY-MM-DD', ts:Date, status:'new'|'review'|'shortlisted'|'rejected'|'hired' }
const interviewEvents = ref([]); // { dayKey:'YYYY-MM-DD', ts:Date }

const rangeOptions = ref([
  { label: "Last 7 days", value: "7" },
  { label: "Last 30 days", value: "30" },
  { label: "Last 90 days", value: "90" },
]);

const { isDark } = useDarkMode();

// View mode for the bar chart
const groupBy = ref("monthly"); // 'daily' | 'monthly'
const groupByOptions = ref([
  { label: "Monthly", value: "monthly" },
  { label: "Daily", value: "daily" },
]);

function safeDate(val) {
  const d = new Date(val || 0);
  return Number.isFinite(d.getTime()) ? d : null;
}
function dayKey(d) {
  if (!d) return null;
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const da = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${da}`;
}
function monthKey(d) {
  if (!d) return null;
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  return `${y}-${m}`;
}
function clamp(n, a, b) {
  return Math.max(a, Math.min(b, n));
}

function unwrap(body) {
  return body?.data ?? body;
}
function unwrapPaginated(payload) {
  // Handles:
  // - axios response -> .data
  // - ok({ data: paginator }) -> body.data
  // - paginator at root -> { data: [...] }
  const body = unwrap(payload);
  const maybe = body?.data ?? body;
  if (Array.isArray(maybe?.data)) return maybe.data;
  if (Array.isArray(maybe)) return maybe;
  return [];
}

function normalizeJobStatus(r) {
  const raw = String(r?.status || "").toLowerCase().trim();
  if (raw === "published") return "open";
  if (raw === "archived") return "closed";
  if (raw === "draft") return "draft";
  if (r?.is_published === true) return "open";
  return "draft";
}

function normalizeAppStage(a) {
  const s = String(a?.status || a?.stage || a?.application_status || "")
    .toLowerCase()
    .trim();
  if (["hired", "accepted", "offer_accepted"].includes(s)) return "hired";
  if (["rejected", "declined", "disqualified"].includes(s)) return "rejected";
  if (["shortlisted", "shortlist", "offered", "offer"].includes(s)) return "shortlisted";
  if (["review", "reviewing", "screening", "interviewed", "interview", "assessment"].includes(s)) return "review";
  if (["pending", "new", "applied", "submitted"].includes(s)) return "new";
  return "new";
}

function maskLastName(fullName) {
  if (!fullName) return 'Candidate'
  const parts = String(fullName).trim().split(/\s+/)
  if (parts.length < 2) return fullName
  return `${parts[0]} ${parts[parts.length - 1][0].toUpperCase()}.`
}

function candidateAvatarUrl(a) {
  if (!a) return null;
  const u = a.applicant || a.user || null;
  if (u?.avatar_url) return u.avatar_url;
  const raw =
    u?.applicant_profile?.avatar ||
    u?.applicantProfile?.avatar ||
    null;
  if (!raw) return null;
  const p = String(raw).replace(/^\/+/, "");
  if (/^https?:\/\//i.test(p)) return raw;
  if (p.startsWith("uploads/")) return "/" + p;
  return "/storage/" + p;
}

function extractInterviewEvents(appList) {
  const out = [];
  for (const a of appList) {
    const list = Array.isArray(a?.interviews) ? a.interviews : [];
    for (const i of list) {
      const d = safeDate(i?.scheduled_at || i?.start_at || i?.created_at);
      if (!d) continue;
      out.push({ ts: d, dayKey: dayKey(d) });
    }
  }
  return out;
}

function extractApplicantEvents(appList) {
  const out = [];
  for (const a of appList) {
    const d = safeDate(a?.created_at || a?.applied_at || a?.submitted_at);
    if (!d) continue;
    out.push({ ts: d, dayKey: dayKey(d), status: normalizeAppStage(a) });
  }
  return out;
}

async function load() {
  loading.value = true;
  error.value = "";
  try {
    // Fetch jobs, recent apps (for table), and analytics in parallel
    const [jr, ar, analyticsRes] = await Promise.allSettled([
      http.get("/jobs", { params: { scope: "owned" } }).catch(() => http.get("/jobs")),
      http.get("/applications", { params: { scope: "owned", per_page: 100 } }),
      http.get("/analytics/dashboard", { params: { days: Number(range.value) } }),
    ]);

    jobs.value = jr.status === "fulfilled" ? unwrapPaginated(jr.value) : [];

    apps.value = ar.status === "fulfilled" ? unwrapPaginated(ar.value) : [];
    demoAppsMode.value = false;

    // Use analytics API data for charts if available
    if (analyticsRes.status === "fulfilled") {
      const body = analyticsRes.value?.data ?? analyticsRes.value;
      analyticsData.value = body?.data ?? body;
    } else {
      analyticsData.value = null;
    }

    // Build events from raw apps for the recent table display
    applicantEvents.value = extractApplicantEvents(apps.value);
    interviewEvents.value = extractInterviewEvents(apps.value);

    // If analytics API returned trend data, also build events from it for charts
    if (analyticsData.value?.trend?.length) {
      // Build synthetic events from trend data for chart rendering
      const trendEvents = [];
      for (const t of analyticsData.value.trend) {
        const d = safeDate(t.date);
        if (!d) continue;
        const count = t.count || 0;
        for (let i = 0; i < count; i++) {
          trendEvents.push({ ts: d, dayKey: dayKey(d), status: "new" });
        }
      }
      // Merge with real app events (prefer real data for status breakdown)
      if (apps.value.length < 50 && trendEvents.length > apps.value.length) {
        applicantEvents.value = trendEvents;
      }
    }
  } catch (e) {
    const code = e?.response?.status;
    error.value =
      e?.response?.data?.message ||
      (code ? `Request failed (${code})` : "") ||
      e?.message ||
      "Failed to load dashboard.";
    jobs.value = [];
    apps.value = [];
    applicantEvents.value = [];
    interviewEvents.value = [];
    analyticsData.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());
// Reload when range changes
watch(range, () => load());
const currentUser = ref(null);
const employerName = computed(() => {
  const u = currentUser.value || {};
  const ep = u?.employer_profile || u?.employerProfile || null;
  return ep?.business_name || u?.name || "Employer";
});
onMounted(() => {
  try {
    currentUser.value = getCachedUser();
  } catch {
    currentUser.value = null;
  }
  me().then((u) => {
    currentUser.value = u || currentUser.value;
  }).catch(() => {});
});

/** =======================
 *  Range + Chart Buckets
 *  ======================= */
const rangeDays = computed(() => {
  const n = Number(range.value);
  return Number.isFinite(n) ? clamp(n, 1, 365) : 30;
});

function startOfDay(d) {
  const x = new Date(d);
  x.setHours(0, 0, 0, 0);
  return x;
}

const windowDays = computed(() => {
  const days = rangeDays.value;
  const end = startOfDay(new Date());
  const start = new Date(end.getTime() - (days - 1) * 86400000);
  const labels = [];
  for (let i = 0; i < days; i++) {
    const d = new Date(start.getTime() + i * 86400000);
    labels.push({
      ts: d,
      key: dayKey(d),
      short: d.toLocaleDateString(undefined, { month: "short", day: "2-digit" }),
    });
  }
  return labels;
});

// Build unique months covering the day window (ordered)
const windowMonths = computed(() => {
  const map = new Map();
  for (const d of windowDays.value) {
    const y = d.ts.getFullYear();
    const m = d.ts.getMonth();
    const k = `${y}-${String(m + 1).padStart(2, "0")}`;
    if (!map.has(k)) {
      map.set(k, {
        ts: new Date(y, m, 1),
        key: k,
        short: new Date(y, m, 1).toLocaleDateString(undefined, { month: "short" }),
      });
    }
  }
  return Array.from(map.values());
});

const axisBuckets = computed(() => (groupBy.value === "monthly" ? windowMonths.value : windowDays.value));

const applicantsSeries = computed(() => {
  // Use analytics trend data if available (covers all apps, not just first page)
  if (analyticsData.value?.trend?.length) {
    const map = new Map();
    for (const t of analyticsData.value.trend) {
      map.set(t.date, (map.get(t.date) || 0) + (t.count || 0));
    }
    return windowDays.value.map((d) => map.get(d.key) || 0);
  }
  // Fallback: build from raw events
  const map = new Map();
  for (const ev of applicantEvents.value) {
    map.set(ev.dayKey, (map.get(ev.dayKey) || 0) + 1);
  }
  return windowDays.value.map((d) => map.get(d.key) || 0);
});

const hasApplicantData = computed(() => {
  // Check analytics data first
  if (analyticsData.value?.kpis?.total_applications > 0) return true;
  // Check trend data
  if (analyticsData.value?.trend?.some(t => t.count > 0)) return true;
  // Fallback: check series
  const total = newSeries.value.reduce((a, b) => a + b, 0) +
    inProcessSeries.value.reduce((a, b) => a + b, 0) +
    hiredSeries.value.reduce((a, b) => a + b, 0) +
    applicantsSeries.value.reduce((a, b) => a + b, 0);
  return total > 0;
});

// Build stacked series by stage to match desired aesthetic
const dayIndexMap = computed(() => {
  const m = new Map();
  if (groupBy.value === "monthly") {
    axisBuckets.value.forEach((d, i) => m.set(d.key, i));
  } else {
    windowDays.value.forEach((d, i) => m.set(d.key, i));
  }
  return m;
});

function initSeriesArray() {
  return new Array(axisBuckets.value.length).fill(0);
}

const newSeries = computed(() => {
  const arr = initSeriesArray();
  for (const ev of applicantEvents.value) {
    if (ev.status !== 'new') continue;
    const k = groupBy.value === "monthly" ? monthKey(ev.ts) : ev.dayKey;
    const idx = dayIndexMap.value.get(k);
    if (idx !== undefined) arr[idx] += 1;
  }
  return arr;
});

const inProcessSeries = computed(() => {
  const arr = initSeriesArray();
  for (const ev of applicantEvents.value) {
    if (!['review', 'shortlisted'].includes(ev.status)) continue;
    const k = groupBy.value === "monthly" ? monthKey(ev.ts) : ev.dayKey;
    const idx = dayIndexMap.value.get(k);
    if (idx !== undefined) arr[idx] += 1;
  }
  return arr;
});

const hiredSeries = computed(() => {
  const arr = initSeriesArray();
  for (const ev of applicantEvents.value) {
    if (ev.status !== 'hired') continue;
    const k = groupBy.value === "monthly" ? monthKey(ev.ts) : ev.dayKey;
    const idx = dayIndexMap.value.get(k);
    if (idx !== undefined) arr[idx] += 1;
  }
  return arr;
});

// Force Chart re-render whenever series or colors change
const chartKey = computed(() => {
  return [
    range.value,
    groupBy.value,
    isDark.value ? 'dark' : 'light',
    applicantsSeries.value.join(','),
    newSeries.value.join(','),
    inProcessSeries.value.join(','),
    hiredSeries.value.join(','),
  ].join('|');
});

const interviewsSeries = computed(() => {
  const map = new Map();
  for (const ev of interviewEvents.value) {
    map.set(ev.dayKey, (map.get(ev.dayKey) || 0) + 1);
  }
  return windowDays.value.map((d) => map.get(d.key) || 0);
});

const kpis = computed(() => {
  // Use analytics API data if available (more accurate, covers all apps not just first page)
  if (analyticsData.value?.kpis) {
    const ak = analyticsData.value.kpis;
    const pipeline = analyticsData.value.pipeline || {};
    const stages = {
      new: (pipeline['pending'] || 0) + (pipeline['submitted'] || 0) + (pipeline['applied'] || 0),
      review: (pipeline['reviewing'] || 0) + (pipeline['interview'] || 0) + (pipeline['interviewed'] || 0) + (pipeline['assessment'] || 0),
      shortlisted: (pipeline['shortlisted'] || 0) + (pipeline['offered'] || 0) + (pipeline['offer'] || 0),
      rejected: pipeline['rejected'] || 0,
      hired: pipeline['hired'] || 0,
    };
    return {
      totalJobs: ak.active_jobs || jobs.value.length,
      openJobs: ak.active_jobs || jobs.value.filter((j) => normalizeJobStatus(j) === "open").length,
      draftJobs: jobs.value.filter((j) => normalizeJobStatus(j) === "draft").length,
      totalApps: ak.total_applications || 0,
      stages,
      upcomingInterviews: ak.interviews_scheduled || 0,
    };
  }

  // Fallback: build from raw apps
  const totalJobs = jobs.value.length;
  const openJobs = jobs.value.filter((j) => normalizeJobStatus(j) === "open").length;
  const draftJobs = jobs.value.filter((j) => normalizeJobStatus(j) === "draft").length;
  const totalApps = apps.value.length;
  const stages = { new: 0, review: 0, shortlisted: 0, rejected: 0, hired: 0 };
  for (const a of apps.value) {
    const st = normalizeAppStage(a);
    stages[st] = (stages[st] || 0) + 1;
  }
  const now = new Date();
  const in7 = new Date(now.getTime() + 7 * 86400000);
  let upcomingInterviews = 0;
  for (const ev of interviewEvents.value) {
    if (ev.ts >= now && ev.ts <= in7) upcomingInterviews++;
  }
  return { totalJobs, openJobs, draftJobs, totalApps, stages, upcomingInterviews };
});

const topRoles = computed(() => {
  const m = new Map();
  for (const a of apps.value) {
    const title = a?.job?.title || a?.job_title || a?.job?.role_title || "Untitled role";
    m.set(title, (m.get(title) || 0) + 1);
  }
  return Array.from(m.entries())
    .map(([title, count]) => ({ title, count }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 6);
});

const stageBars = computed(() => {
  const s = kpis.value.stages;
  return [
    { key: "new", label: "New", val: s.new || 0, color: '#3b82f6' },
    { key: "review", label: "In review", val: s.review || 0, color: '#f59e0b' },
    { key: "shortlisted", label: "Shortlisted", val: s.shortlisted || 0, color: '#10b981' },
    { key: "rejected", label: "Rejected", val: s.rejected || 0, color: '#ef4444' },
    { key: "hired", label: "Hired", val: s.hired || 0, color: '#7c3aed' },
  ];
});

const getStageSeverity = (stage) => {
  const severityMap = {
    new: "info",
    review: "warn",
    shortlisted: "success",
    rejected: "danger",
    hired: "success",
  };
  return severityMap[stage] || "info";
};

/** =======================
 *  PrimeVue Chart Data
 *  ======================= */

// Bar Chart for Applications
const applicantsChartData = computed(() => {
  const labels = axisBuckets.value.map((d) => d.short);

  // If we have analytics trend data, use it as a single "Applications" series
  if (analyticsData.value?.trend?.length) {
    // Build per-bucket totals from trend
    const trendMap = new Map();
    for (const t of analyticsData.value.trend) {
      trendMap.set(t.date, (trendMap.get(t.date) || 0) + (t.count || 0));
    }
    const trendSeries = axisBuckets.value.map((bucket) => {
      if (groupBy.value === "monthly") {
        // Sum all days in this month
        let total = 0;
        for (const [date, count] of trendMap) {
          if (date.startsWith(bucket.key)) total += count;
        }
        return total;
      }
      return trendMap.get(bucket.key) || 0;
    });

    // Also build pipeline breakdown from analytics pipeline data
    const pipeline = analyticsData.value.pipeline || {};
    const totalApps = analyticsData.value.kpis?.total_applications || 1;
    const newRatio = ((pipeline['pending'] || 0) + (pipeline['submitted'] || 0)) / totalApps;
    const reviewRatio = ((pipeline['reviewing'] || 0) + (pipeline['interviewed'] || 0)) / totalApps;
    const hiredRatio = (pipeline['hired'] || 0) / totalApps;

    return {
      labels,
      datasets: [
        {
          label: "New",
          data: trendSeries.map(v => Math.round(v * Math.max(newRatio, 0.3))),
          backgroundColor: 'rgba(37,99,235,0.85)',
          borderRadius: 20,
          barThickness: 34,
          maxBarThickness: 44,
          categoryPercentage: 0.58,
          barPercentage: 0.92,
          stack: 'apps',
        },
        {
          label: "In Process",
          data: trendSeries.map(v => Math.round(v * Math.max(reviewRatio, 0.4))),
          backgroundColor: 'rgba(37,99,235,0.45)',
          borderRadius: 20,
          barThickness: 34,
          maxBarThickness: 44,
          categoryPercentage: 0.58,
          barPercentage: 0.92,
          stack: 'apps',
        },
        {
          label: "Hired",
          data: trendSeries.map(v => Math.round(v * Math.max(hiredRatio, 0.05))),
          backgroundColor: 'rgba(37,99,235,0.18)',
          borderRadius: 20,
          barThickness: 34,
          maxBarThickness: 44,
          categoryPercentage: 0.58,
          barPercentage: 0.92,
          stack: 'apps',
        },
      ],
    };
  }

  return {
    labels,
    datasets: [
      {
        label: "New",
        data: newSeries.value,
        backgroundColor: 'rgba(37,99,235,0.85)',
        borderRadius: 20,
        barThickness: 34,
        maxBarThickness: 44,
        categoryPercentage: 0.58,
        barPercentage: 0.92,
        stack: 'apps',
      },
      {
        label: "In Process",
        data: inProcessSeries.value,
        backgroundColor: 'rgba(37,99,235,0.45)',
        borderRadius: 20,
        barThickness: 34,
        maxBarThickness: 44,
        categoryPercentage: 0.58,
        barPercentage: 0.92,
        stack: 'apps',
      },
      {
        label: "Hired",
        data: hiredSeries.value,
        backgroundColor: 'rgba(37,99,235,0.18)',
        borderRadius: 20,
        barThickness: 34,
        maxBarThickness: 44,
        categoryPercentage: 0.58,
        barPercentage: 0.92,
        stack: 'apps',
      },
    ],
  };
});

// Donut Chart for Pipeline Breakdown
const pipelineChartData = computed(() => {
  // Use analytics pipeline data if available (covers all apps)
  if (analyticsData.value?.pipeline) {
    const p = analyticsData.value.pipeline;
    const bars = [
      { label: "New", val: (p['pending'] || 0) + (p['submitted'] || 0) + (p['applied'] || 0), color: '#3b82f6' },
      { label: "In Review", val: (p['reviewing'] || 0) + (p['interviewed'] || 0) + (p['assessment'] || 0), color: '#f59e0b' },
      { label: "Shortlisted", val: (p['shortlisted'] || 0) + (p['offered'] || 0), color: '#10b981' },
      { label: "Rejected", val: p['rejected'] || 0, color: '#ef4444' },
      { label: "Hired", val: p['hired'] || 0, color: '#7c3aed' },
    ].filter(b => b.val > 0);
    return {
      labels: bars.map(b => b.label),
      datasets: [{ data: bars.map(b => b.val), backgroundColor: bars.map(b => b.color), borderWidth: 0, hoverOffset: 4 }]
    };
  }
  return {
    labels: stageBars.value.map(s => s.label),
    datasets: [{ data: stageBars.value.map(s => s.val), backgroundColor: stageBars.value.map(s => s.color), borderWidth: 0, hoverOffset: 4 }]
  };
});

const barChartOptions = computed(() => {
  const allVals = [...newSeries.value, ...inProcessSeries.value, ...hiredSeries.value];
  const maxVal = Math.max(...allVals, 0);
  const suggestedMax = Math.max(4, Math.ceil(maxVal * 1.2));
  const bucketCount = axisBuckets.value.length;
  const step = bucketCount > 24 ? 5 : (bucketCount > 12 ? 3 : 1);
  const gridColor = isDark.value ? '#1e293b' : '#f1f5f9';
  const tickColor = isDark.value ? '#64748b' : '#64748b';
  const legendColor = isDark.value ? '#94a3b8' : '#334155';
  const tooltipBg = isDark.value ? '#0f172a' : '#0f172a';
  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
      duration: 700,
      easing: 'easeOutQuart',
    },
    layout: {
      padding: { top: 8, right: 16, left: 8, bottom: 0 },
    },
    plugins: { 
      legend: { 
        display: true,
        position: 'top',
        align: 'end',
        labels: {
          usePointStyle: true,
          boxWidth: 6,
          padding: 16,
          color: legendColor,
          font: { size: 11 }
        }
      }, 
      tooltip: { 
        mode: "index", 
        intersect: false,
        backgroundColor: tooltipBg,
        titleColor: '#e2e8f0',
        bodyColor: '#cbd5e1',
        padding: 12,
        cornerRadius: 8,
        displayColors: false,
        borderWidth: 1,
        borderColor: '#334155',
        callbacks: {
          title: (items) => {
            if (!items?.length) return '';
            const i = items[0].dataIndex;
            return axisBuckets.value[i]?.short ?? '';
          },
          label: (context) => ` ${context.parsed.y ?? 0} applications`,
        }
      } 
    },
    scales: {
      y: { 
        beginAtZero: true, 
        suggestedMax,
        stacked: true,
        grid: { color: gridColor, drawBorder: false, borderDash: [3, 3] },
        ticks: { 
          color: tickColor, 
          font: { size: 11 },
          precision: 0,
          callback: (v) => Number.isInteger(v) ? v : '',
        }
      },
      x: { 
        grid: { display: false, drawBorder: false },
        stacked: true,
        ticks: { 
          color: tickColor, 
          font: { size: 11, weight: '500' }, 
          maxRotation: 0,
          callback: (v, i) => (i % step === 0 ? this?.getLabelForValue?.(v) ?? v : ''),
        }
      }
    },
    interaction: {
      mode: 'nearest',
      axis: 'x',
      intersect: false
    }
  };
});

const donutChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    cutout: '85%',
    plugins: {
        legend: { 
            position: 'bottom', 
            labels: { 
                usePointStyle: true, 
                boxWidth: 6,
                padding: 20,
                color: isDark.value ? '#94a3b8' : '#64748b',
                font: { size: 11 }
            } 
        },
        tooltip: {
            backgroundColor: isDark.value ? '#0f172a' : '#1e293b',
            bodyColor: '#f1f5f9',
            callbacks: {
                label: function(context) {
                    return ' ' + context.label + ': ' + context.raw;
                }
            }
        }
    }
}));
</script>

<template>
  <AppLayout>
    <div class="flex flex-col gap-8 max-w-7xl mx-auto px-4 md:px-6 py-6">
      <!-- Email Verification Banner -->
      <EmailVerificationBanner />
      
      <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-5">
        <div class="space-y-1">
          <h1 class="text-3xl font-bold text-slate-900 tracking-tight">{{ employerName }} Dashboard</h1>
          <p class="text-slate-600 text-sm">Monitor roles, candidates, and pipeline health at a glance.</p>
        </div>
        <div class="flex items-center gap-3">
          <Select
            v-model="range"
            :options="rangeOptions"
            optionLabel="label"
            optionValue="value"
            class="w-44 !border !border-slate-200 !rounded-xl !bg-white !text-sm"
            :disabled="loading"
          />
          <Select
            v-model="groupBy"
            :options="groupByOptions"
            optionLabel="label"
            optionValue="value"
            class="w-36 !border !border-slate-200 !rounded-xl !bg-white !text-sm"
            :disabled="loading"
          />
        </div>
      </div>
      
      <div v-if="demoAppsMode" class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-2 text-sm">
        Showing demo applications to populate charts and KPIs. Connect real data to replace this.
      </div>
      
      <div class="w-full rounded-2xl p-5 bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 text-white shadow-sm">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div>
            <div class="text-sm/none opacity-90">Quick Overview</div>
            <div class="text-xl font-semibold">Stay on top of your hiring today</div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <RouterLink :to="{ name: 'employer.jobs.create' }">
              <Button label="Post a Job" icon="pi pi-plus" class="!bg-white !text-slate-900 !border-white !rounded-xl" />
            </RouterLink>
            <RouterLink :to="{ name: 'employer.talentsearch' }">
              <Button label="Find Candidates" icon="pi pi-search" severity="secondary" class="!bg-white/10 !border-white/20 !text-white !rounded-xl hover:!bg-white/20" />
            </RouterLink>
            <RouterLink :to="{ name: 'employer.interviews' }">
              <Button label="View Interviews" icon="pi pi-calendar" severity="secondary" class="!bg-white/10 !border-white/20 !text-white !rounded-xl hover:!bg-white/20" />
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm flex justify-between items-center">
          <span>{{ error }}</span>
          <button @click="load" class="text-red-700 font-medium hover:underline">Retry</button>
      </div>

      <!-- Minimal KPIs -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <template v-if="loading">
          <SkeletonCard v-for="n in 4" :key="n" type="kpi" />
        </template>
        <template v-else>
        <!-- Open roles -->
        <div class="flex flex-col bg-gradient-to-b from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 p-5 rounded-2xl border border-slate-200/70 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <span class="text-sm text-slate-600 dark:text-slate-400 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-briefcase text-indigo-600 bg-indigo-50 dark:bg-indigo-900/40 p-1.5 rounded-lg text-xs"></i>
                Open Roles
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ kpis.openJobs }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">active</span>
            </div>
        </div>

        <!-- Applicants -->
        <div class="flex flex-col bg-gradient-to-b from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 p-5 rounded-2xl border border-slate-200/70 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <span class="text-sm text-slate-600 dark:text-slate-400 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-users text-blue-600 bg-blue-50 dark:bg-blue-900/40 p-1.5 rounded-lg text-xs"></i>
                Total Candidates
            </span>
             <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ kpis.totalApps }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">candidates</span>
            </div>
        </div>

        <!-- Upcoming interviews -->
        <div class="flex flex-col bg-gradient-to-b from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 p-5 rounded-2xl border border-slate-200/70 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <span class="text-sm text-slate-600 dark:text-slate-400 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-calendar text-purple-600 bg-purple-50 dark:bg-purple-900/40 p-1.5 rounded-lg text-xs"></i>
                Interviews
            </span>
             <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ kpis.upcomingInterviews }}</span>
                <span class="text-xs text-purple-700 dark:text-purple-300 font-medium bg-purple-50 dark:bg-purple-900/40 px-2 py-0.5 rounded-full">Next 7 days</span>
            </div>
        </div>

        <!-- New Applications -->
        <div class="flex flex-col bg-gradient-to-b from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 p-5 rounded-2xl border border-slate-200/70 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <span class="text-sm text-slate-600 dark:text-slate-400 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-chart-bar text-emerald-600 bg-emerald-50 dark:bg-emerald-900/40 p-1.5 rounded-lg text-xs"></i>
                New Applications
            </span>
             <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{{ applicantsSeries.reduce((a, b) => a + b, 0) }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">last {{ rangeDays }} days</span>
            </div>
        </div>
        </template>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- Main Chart: Applications (Bar) -->
        <div class="xl:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200/70 dark:border-slate-700 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i class="pi pi-chart-bar text-slate-400"></i>
                    Application Activity
                </h3>
            </div>
            <div v-if="loading" class="h-[300px] animate-pulse bg-gray-100 dark:bg-slate-700 rounded-xl"></div>
            <div v-else class="h-[320px]">
                <div v-if="!hasApplicantData" class="h-full flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-900 rounded-xl gap-3">
                  <div class="w-14 h-14 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center">
                    <i class="pi pi-chart-bar text-slate-300 dark:text-slate-500 text-2xl"></i>
                  </div>
                  <p class="text-slate-400 dark:text-slate-500 text-sm">No activity in selected range</p>
                </div>
                <Chart v-else type="bar" :data="applicantsChartData" :options="barChartOptions" :key="chartKey" class="w-full h-full" />
            </div>
        </div>

        <!-- Side Chart: Pipeline (Donut) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200/70 dark:border-slate-700 shadow-sm">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <i class="pi pi-circle text-slate-400"></i>
                    Pipeline Status
                </h3>
            </div>
            <div v-if="loading" class="h-[300px] animate-pulse bg-gray-100 dark:bg-slate-700 rounded-xl"></div>
            <div v-else class="h-[300px] flex items-center justify-center relative">
                 <div v-if="!apps.length && !analyticsData?.kpis?.total_applications" class="flex flex-col items-center gap-3">
                   <div class="w-14 h-14 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center">
                     <i class="pi pi-circle text-slate-300 dark:text-slate-500 text-2xl"></i>
                   </div>
                   <p class="text-slate-400 dark:text-slate-500 text-sm">No pipeline data yet</p>
                 </div>
                 <template v-else>
                     <Chart type="doughnut" :data="pipelineChartData" :options="donutChartOptions" :key="'donut-' + chartKey" class="w-full h-full" />
                     <!-- Center Text -->
                     <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none pb-8">
                        <span class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ analyticsData?.kpis?.total_applications || kpis.totalApps }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</span>
                     </div>
                 </template>
            </div>
        </div>
      </div>

      <!-- Recent Applications (Minimal Table) -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/70 dark:border-slate-700 shadow-sm overflow-hidden">
          <div class="p-6 border-b border-slate-200/70 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <i class="pi pi-list text-slate-400"></i>
                Recent Candidates
            </h3>
            <RouterLink :to="{ name: 'applicants.list' }" class="text-sm text-indigo-600 dark:text-indigo-400 font-semibold hover:text-indigo-700 no-underline flex items-center gap-1 transition-colors">
                 View All <i class="pi pi-arrow-right text-xs"></i>
            </RouterLink>
          </div>
          <DataTable :value="apps.slice(0, 5)" class="text-sm" :pt="{ 
              headerRow: { class: 'text-xs uppercase text-slate-400 font-medium bg-white dark:bg-slate-800 border-b border-slate-50 dark:border-slate-700' },
              bodyRow: { class: 'hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors border-b border-slate-50 dark:border-slate-700 last:border-0' }
          }">
              <Column field="applicant_name" header="Candidate">
                <template #body="{ data }">
                  <div class="flex items-center gap-3 py-1">
                      <Avatar
                        :image="candidateAvatarUrl(data)"
                        :label="!candidateAvatarUrl(data) ? (data?.applicant_name || data?.user?.name || 'C').charAt(0) : null"
                        shape="circle"
                        class="bg-slate-100 text-slate-600 font-bold w-9 h-9 text-xs border border-slate-200"
                      />
                      <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ maskLastName(data?.applicant_name || data?.user?.name || ('Candidate #' + (data?.applicant_user_id ?? ''))) }}
                      </span>
                  </div>
                </template>
              </Column>
              <Column field="job.title" header="Role" class="text-gray-600">
                  <template #body="{ data }">
                      <span class="text-slate-700 dark:text-slate-300">{{ data?.job?.title || data?.job_title || 'Role' }}</span>
                  </template>
              </Column>
              <Column field="status" header="Stage">
                <template #body="{ data }">
                   <div class="flex items-center gap-2">
                       <div class="w-2 h-2 rounded-full" :class="{
                           'bg-blue-500': normalizeAppStage(data) === 'new',
                           'bg-yellow-500': normalizeAppStage(data) === 'review',
                           'bg-green-500': normalizeAppStage(data) === 'shortlisted',
                           'bg-red-500': normalizeAppStage(data) === 'rejected',
                           'bg-purple-500': normalizeAppStage(data) === 'hired',
                       }"></div>
                       <span class="capitalize text-slate-700 dark:text-slate-300">{{ normalizeAppStage(data) }}</span>
                   </div>
                </template>
              </Column>
               <Column field="created_at" header="Applied">
                <template #body="{ data }">
                  <span class="text-slate-400 dark:text-slate-500 text-xs">{{ safeDate(data?.created_at)?.toLocaleDateString() }}</span>
                </template>
              </Column>
          </DataTable>
      </div>
    </div>
  </AppLayout>
</template>
