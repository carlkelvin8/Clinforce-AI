<!-- resources/js/Pages/Candidate/MyApplications.vue -->
<template>
  <AppLayout>
    <ConfirmDialog />
    <div class="w-full max-w-7xl mx-auto p-4">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">My Applications</h1>
          <p class="text-gray-500 text-sm mt-1">
            Pulled from <span class="font-mono">GET /api/applications?scope=mine</span>.
          </p>
        </div>
        <Button label="Refresh" icon="pi pi-refresh" :loading="loading" @click="fetchData(1)" outlined />
      </div>

      <Message v-if="error" severity="error" class="mb-6" :closable="false">{{ error }}</Message>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Filters -->
        <div class="md:col-span-1">
          <Card class="sticky top-20">
            <template #title>Filters</template>
            <template #content>
              <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                  <label class="font-bold text-xs text-gray-500 uppercase tracking-wider">Search</label>
                  <InputText v-model="search" placeholder="Job title, application ID…" @keydown.enter="applyFilters" class="w-full" />
                </div>
                <div class="flex flex-col gap-2">
                  <label class="font-bold text-xs text-gray-500 uppercase tracking-wider">Status</label>
                  <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All" class="w-full" showClear />
                </div>
                <div class="flex gap-2 mt-2">
                  <Button label="Apply" @click="applyFilters" :disabled="loading" class="flex-1" />
                  <Button label="Reset" severity="secondary" @click="resetFilters" :disabled="loading" class="flex-1" outlined />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- List -->
        <div class="md:col-span-3 flex flex-col gap-4">
          <div v-if="loading" class="flex justify-center p-8">
            <span class="pi pi-spinner pi-spin text-2xl text-gray-500"></span>
          </div>

          <Card v-else-if="items.length === 0">
            <template #content>
              <div class="text-center p-8">
                <div class="font-bold text-gray-900">No results</div>
                <div class="text-gray-500 text-sm mt-1">No applications match your filters.</div>
              </div>
            </template>
          </Card>

          <Card v-for="row in items" :key="row.id">
            <template #content>
              <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center flex-wrap gap-2 mb-2">
                    <span class="font-bold text-gray-900 text-sm">{{ row.job?.title || 'Job Application' }}</span>
                    <span class="text-gray-300">•</span>
                    <span class="font-bold text-gray-700 truncate max-w-md">{{ formatDate(row.submitted_at) }}</span>
                    <Tag :value="row.status || '—'" :severity="getSeverity(row.status)" />
                  </div>
                  <div class="text-xs text-gray-500 mb-2">
                    Submitted <span class="font-bold text-gray-900">{{ formatDate(row.submitted_at) }}</span>
                  </div>
                  <p v-if="row.cover_letter" class="text-sm text-gray-700 line-clamp-2">{{ row.cover_letter }}</p>
                </div>
                <Button label="View" icon="pi pi-eye" size="small" @click="router.push({ name: 'candidate.applications.view', params: { id: row.id } })" />
                <Button
                  v-if="!['rejected','hired','withdrawn'].includes(row.status)"
                  label="Withdraw"
                  icon="pi pi-times-circle"
                  size="small"
                  severity="danger"
                  outlined
                  :loading="withdrawing === row.id"
                  @click="confirmWithdraw(row)"
                />
              </div>
            </template>
          </Card>

          <!-- Pagination -->
          <div v-if="pagination" class="flex justify-between items-center mt-4 text-sm text-gray-500">
            <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <div class="flex gap-2">
              <Button label="Prev" size="small" outlined :disabled="pagination.current_page <= 1 || loading" @click="fetchData(pagination.current_page - 1)" />
              <Button label="Next" size="small" outlined :disabled="pagination.current_page >= pagination.last_page || loading" @click="fetchData(pagination.current_page + 1)" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";
import { toast } from "@/composables/useAppToast";

import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

const router = useRouter();
const confirm = useConfirm();

const loading = ref(false);
const error = ref("");

const status = ref(null);
const search = ref("");
const allowedStatuses = ["submitted", "shortlisted", "rejected", "interview", "hired", "withdrawn"];
const statusOptions = allowedStatuses.map(s => ({ label: s.charAt(0).toUpperCase() + s.slice(1), value: s }));

const raw = ref(null);

const items = computed(() => {
  const rows = raw.value?.data || [];
  const q = search.value.trim().toLowerCase();

  return rows.filter((r) => {
    if (status.value && r.status !== status.value) return false;
    if (!q) return true;

    const hay = [String(r.id || ""), String(r.status || ""), String(r.job?.title || "")]
      .join(" ")
      .toLowerCase();

    return hay.includes(q);
  });
});

const pagination = computed(() => {
  if (!raw.value) return null;
  return {
    current_page: raw.value.current_page,
    last_page: raw.value.last_page,
  };
});

function formatDate(v) {
  if (!v) return "N/A";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleString();
}

function getSeverity(s) {
  switch (s) {
    case "submitted":
    case "withdrawn":
      return "secondary";
    case "shortlisted":
      return "info";
    case "interview":
      return "warn";
    case "hired":
      return "success";
    case "rejected":
      return "danger";
    default:
      return "secondary";
  }
}

function normalizePayload(resData) {
  const root = resData?.data ?? resData;
  if (root?.data?.data && Array.isArray(root.data.data)) return root.data;
  if (root?.data && Array.isArray(root.data)) return root;
  if (root?.data && typeof root.data === "object" && root.data.data) return root.data;
  return root;
}

async function fetchData(page = 1) {
  loading.value = true;
  error.value = "";
  try {
    const params = { scope: "mine", page };
    if (status.value) params.status = status.value;

    const res = await api.get("/applications", { params });
    raw.value = normalizePayload(res.data);
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || "Request failed";
    raw.value = null;
  } finally {
    loading.value = false;
  }
}

const withdrawing = ref(null);

function confirmWithdraw(row) {
  confirm.require({
    message: `Withdraw your application for "${row.job?.title || 'this job'}"? This cannot be undone.`,
    header: 'Withdraw Application',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, Withdraw',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => doWithdraw(row),
  });
}

async function doWithdraw(row) {
  withdrawing.value = row.id;
  try {
    await api.post(`/applications/${row.id}/status`, { status: 'withdrawn' });
    row.status = 'withdrawn';
    toast.success('Application withdrawn.');
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Failed to withdraw application.');
  } finally {
    withdrawing.value = null;
  }
}

function applyFilters() {
  fetchData(1);
}
function resetFilters() {
  status.value = null;
  search.value = "";
  fetchData(1);
}

onMounted(() => fetchData(1));
</script>

<style scoped>
:deep(.p-card) {
  border: 1px solid #e2e8f0 !important; /* thin, light border */
  box-shadow: none !important;
  background-color: #ffffff !important;
}
</style>
