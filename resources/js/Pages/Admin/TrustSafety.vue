<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import { useAdminTheme } from '@/composables/useAdminTheme';
import { useRouter } from 'vue-router';

const { isDark, card, text, textSub, textMuted, divider, border, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});
const router = useRouter();

const loading = ref(false);
const stats = ref({});
const recentReports = ref([]);
const recentFraud = ref([]);

async function load() {
  loading.value = true;
  try {
    const res = await api.get('/admin/trust/dashboard');
    const d = res?.data?.data || res?.data;
    stats.value = d?.stats || {};
    recentReports.value = d?.recent_reports || [];
    recentFraud.value = d?.recent_fraud || [];
  } finally { loading.value = false; }
}

const statCards = [
  { key: 'pending_identity_verifications', label: 'Pending ID Verifications', icon: 'pi-id-card', color: 'text-blue-500', route: 'admin.identity-verifications' },
  { key: 'pending_reports',                label: 'Pending Reports',           icon: 'pi-flag',    color: 'text-red-500',  route: 'admin.content-reports' },
  { key: 'pending_moderation',             label: 'Moderation Queue',          icon: 'pi-shield',  color: 'text-orange-500', route: 'admin.moderation-queue' },
  { key: 'fraud_alerts',                   label: 'Fraud Alerts',              icon: 'pi-exclamation-triangle', color: 'text-yellow-500', route: 'admin.fraud-logs' },
  { key: 'employer_red_flags',             label: 'Employer Red Flags',        icon: 'pi-ban',     color: 'text-red-600',  route: 'admin.employer-trust' },
  { key: 'pending_employer_reviews',       label: 'Pending Employer Reviews',  icon: 'pi-star',    color: 'text-purple-500', route: 'admin.employer-trust' },
];

const reasonSeverity = { inappropriate: 'warn', spam: 'secondary', scam: 'danger', harassment: 'danger', discrimination: 'danger', fake: 'warn', offensive: 'warn', other: 'secondary' };
const fraudSeverity  = { low: 'secondary', medium: 'warn', high: 'danger', critical: 'danger' };

onMounted(() => {
  setBreadcrumb([{ label: 'Trust & Safety' }]);
  load();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Trust &amp; Safety</h1>
        <p :class="['text-sm mt-1', textSub]">Platform safety overview and moderation dashboard</p>
      </div>

      <!-- Stat Cards -->
      <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div v-for="i in 6" :key="i" :class="['rounded-2xl border p-5 h-24 animate-pulse', card, border]"></div>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <button
          v-for="s in statCards" :key="s.key"
          :class="['rounded-2xl border p-5 text-left transition hover:ring-2 hover:ring-blue-500/30', card, border]"
          @click="router.push({ name: s.route })"
        >
          <div class="flex items-center gap-3">
            <i :class="['pi text-2xl', s.icon, s.color]"></i>
            <div>
              <div :class="['text-2xl font-bold', text]">{{ stats[s.key] ?? 0 }}</div>
              <div :class="['text-xs mt-0.5', textMuted]">{{ s.label }}</div>
            </div>
          </div>
        </button>
      </div>

      <!-- Quick Nav -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <Button label="ID Verifications" icon="pi pi-id-card" severity="secondary" class="w-full" @click="router.push({ name: 'admin.identity-verifications' })" />
        <Button label="Content Reports" icon="pi pi-flag" severity="secondary" class="w-full" @click="router.push({ name: 'admin.content-reports' })" />
        <Button label="Employer Trust" icon="pi pi-building" severity="secondary" class="w-full" @click="router.push({ name: 'admin.employer-trust' })" />
        <Button label="Fraud Logs" icon="pi pi-exclamation-triangle" severity="secondary" class="w-full" @click="router.push({ name: 'admin.fraud-logs' })" />
      </div>

      <div class="grid md:grid-cols-2 gap-6">
        <!-- Recent Reports -->
        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <div :class="['px-5 py-4 border-b flex items-center justify-between', border]">
            <h2 :class="['font-semibold text-sm', text]">Recent Reports</h2>
            <Button label="View All" size="small" text @click="router.push({ name: 'admin.content-reports' })" />
          </div>
          <div v-if="!recentReports.length" :class="['px-5 py-8 text-center text-sm', textMuted]">No recent reports</div>
          <div v-else :class="['divide-y', divider]">
            <div v-for="r in recentReports" :key="r.id" class="px-5 py-3 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div :class="['text-xs font-medium truncate', text]">{{ r.reporter_email }}</div>
                <div :class="['text-xs mt-0.5', textMuted]">{{ r.reportable_type?.split('\\').pop() }} #{{ r.reportable_id }}</div>
              </div>
              <Tag :value="r.reason" :severity="reasonSeverity[r.reason] || 'secondary'" class="text-xs shrink-0" />
            </div>
          </div>
        </div>

        <!-- Recent Fraud Alerts -->
        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <div :class="['px-5 py-4 border-b flex items-center justify-between', border]">
            <h2 :class="['font-semibold text-sm', text]">Recent Fraud Alerts</h2>
            <Button label="View All" size="small" text @click="router.push({ name: 'admin.fraud-logs' })" />
          </div>
          <div v-if="!recentFraud.length" :class="['px-5 py-8 text-center text-sm', textMuted]">No fraud alerts</div>
          <div v-else :class="['divide-y', divider]">
            <div v-for="f in recentFraud" :key="f.id" class="px-5 py-3 flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div :class="['text-xs font-medium truncate', text]">{{ f.user_email || 'Unknown' }}</div>
                <div :class="['text-xs mt-0.5', textMuted]">{{ f.fraud_type?.replace(/_/g, ' ') }}</div>
              </div>
              <Tag :value="f.severity" :severity="fraudSeverity[f.severity] || 'secondary'" class="text-xs shrink-0" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
