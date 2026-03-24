<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, border } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const plans = ref([]);
const loading = ref(false);
const editDialog = ref(false);
const editTarget = ref(null);
const saving = ref(false);

async function fetchPlans() {
  loading.value = true;
  try {
    const res = await api.get('/admin/plans');
    plans.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

function openEdit(plan) {
  editTarget.value = { ...plan, price_dollars: plan.price_cents / 100 };
  editDialog.value = true;
}

async function savePlan() {
  saving.value = true;
  try {
    await api.patch(`/admin/plans/${editTarget.value.id}`, {
      name: editTarget.value.name,
      price_cents: Math.round(editTarget.value.price_dollars * 100),
      job_post_limit: editTarget.value.job_post_limit,
      ai_screening_enabled: editTarget.value.ai_screening_enabled,
      analytics_enabled: editTarget.value.analytics_enabled,
      is_active: editTarget.value.is_active,
    });
    await fetchPlans();
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Plan updated', life: 2000 });
    editDialog.value = false;
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

const features = [
  { key: 'ai_screening_enabled', label: 'AI Screening', icon: 'pi pi-microchip-ai', color: 'text-violet-500' },
  { key: 'analytics_enabled',    label: 'Analytics',    icon: 'pi pi-chart-bar',    color: 'text-blue-500' },
];

onMounted(() => {
  setBreadcrumb([{ label: 'Plans' }]);
  fetchPlans();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Plans</h1>
        <p :class="['text-sm mt-1', textSub]">Manage subscription plans</p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-if="loading" v-for="i in 3" :key="i"
          :class="['rounded-2xl border animate-pulse h-52', card]"></div>
        <div v-else v-for="plan in plans" :key="plan.id"
          :class="['rounded-2xl border p-6 flex flex-col gap-4 transition-all hover:scale-[1.01]', card,
            plan.is_active ? 'border-blue-500/30' : '']">
          <div class="flex items-start justify-between">
            <div>
              <h3 :class="['font-bold text-lg', text]">{{ plan.name }}</h3>
              <p :class="['text-xs mt-0.5', textMuted]">{{ plan.duration_months }}mo duration</p>
            </div>
            <Tag :value="plan.is_active ? 'Active' : 'Inactive'" :severity="plan.is_active ? 'success' : 'danger'" />
          </div>

          <div :class="['text-4xl font-bold', text]">
            ${{ (plan.price_cents / 100).toFixed(2) }}
            <span :class="['text-sm font-normal', textMuted]">/ mo</span>
          </div>

          <div class="space-y-2 flex-1">
            <div class="flex items-center justify-between text-xs">
              <span :class="textMuted">Job posts</span>
              <span :class="['font-semibold', text]">{{ plan.job_post_limit === 0 ? 'Unlimited' : plan.job_post_limit }}</span>
            </div>
            <div v-for="f in features" :key="f.key" class="flex items-center justify-between text-xs">
              <span :class="['flex items-center gap-1.5', textMuted]">
                <i :class="[f.icon, f.color]"></i> {{ f.label }}
              </span>
              <i :class="plan[f.key] ? 'pi pi-check text-emerald-500' : 'pi pi-times text-red-400'"></i>
            </div>
          </div>

          <Button label="Edit Plan" size="small" severity="secondary" icon="pi pi-pencil" class="w-full" @click="openEdit(plan)" />
        </div>
      </div>
    </div>

    <Dialog v-model:visible="editDialog" :header="'Edit: ' + editTarget?.name" :style="{ width: '440px' }" modal>
      <div v-if="editTarget" class="space-y-5 pt-2">
        <div class="space-y-1.5">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Plan Name</label>
          <InputText v-model="editTarget.name" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Price (USD / month)</label>
          <InputNumber v-model="editTarget.price_dollars" mode="currency" currency="USD" locale="en-US" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Job Post Limit <span class="normal-case font-normal">(0 = unlimited)</span></label>
          <InputNumber v-model="editTarget.job_post_limit" :min="0" class="w-full" />
        </div>
        <div :class="['space-y-3 pt-1 border-t', border]">
          <label :class="['text-xs font-semibold uppercase tracking-wide block pt-3', textMuted]">Features</label>
          <div class="flex items-center justify-between">
            <span :class="['text-sm flex items-center gap-2', text]"><i class="pi pi-microchip-ai text-violet-500"></i> AI Screening</span>
            <ToggleSwitch v-model="editTarget.ai_screening_enabled" />
          </div>
          <div class="flex items-center justify-between">
            <span :class="['text-sm flex items-center gap-2', text]"><i class="pi pi-chart-bar text-blue-500"></i> Analytics</span>
            <ToggleSwitch v-model="editTarget.analytics_enabled" />
          </div>
          <div class="flex items-center justify-between">
            <span :class="['text-sm flex items-center gap-2', text]"><i class="pi pi-check-circle text-emerald-500"></i> Plan Active</span>
            <ToggleSwitch v-model="editTarget.is_active" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="editDialog = false" />
          <Button label="Save Changes" :loading="saving" @click="savePlan" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
