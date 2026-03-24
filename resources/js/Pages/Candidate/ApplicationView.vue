<template>
    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Application Details</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Track your application status and history</p>
                </div>
                <div class="flex gap-2">
                    <Button label="Refresh" icon="pi pi-refresh" :loading="loading" @click="fetchData" severity="secondary" outlined />
                    <Button label="Back" icon="pi pi-arrow-left" @click="goBack" severity="secondary" text />
                </div>
            </div>

            <!-- Loading/Error/Content -->
            <div v-if="loading && !app" class="flex justify-center p-8">
                <ProgressSpinner />
            </div>

            <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>

            <Message v-else-if="!app" severity="warn" :closable="false">No application found.</Message>

            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Summary -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <template #title>
                            <div class="flex justify-between items-start">
                                <span>{{ app.job?.title || 'Job Application' }}</span>
                                <Tag :value="app.status" :severity="getSeverity(app.status)" />
                            </div>
                        </template>
                        <template #subtitle>
                            Submitted <span class="font-bold text-gray-900">{{ formatDate(app.submitted_at) }}</span>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <span class="block text-sm font-medium text-gray-500">Job</span>
                                    <span class="block text-gray-900 font-semibold">{{ app.job?.title || "Job" }}</span>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500">Status</span>
                                    <span class="block text-gray-900 font-semibold capitalize">{{ app.status }}</span>
                                </div>
                            </div>

                            <div v-if="app.cover_letter" class="mt-6 p-4 bg-gray-50 rounded-lg">
                                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Cover Letter</h3>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ app.cover_letter }}</p>
                            </div>

                            <div class="mt-6 flex flex-wrap gap-2">
                                <Button v-if="canWithdraw" label="Withdraw application" icon="pi pi-times" severity="danger" :loading="busy" @click="confirmWithdraw" />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Right Column: Timeline & Interview -->
                <div class="space-y-6">
                    <Card>
                        <template #title>Status History</template>
                        <template #subtitle>Updates over time</template>
                        <template #content>
                            <div v-if="history.length === 0" class="text-sm text-gray-500 italic">No status history yet.</div>
                            <Timeline v-else :value="history" class="app-timeline">
                                <template #marker="slotProps">
                                    <span
                                        class="flex w-8 h-8 items-center justify-center rounded-full border-2 shadow-sm"
                                        :class="timelineDotClass(slotProps.item.to_status)"
                                    >
                                        <i :class="timelineDotIcon(slotProps.item.to_status)" class="text-xs"></i>
                                    </span>
                                </template>
                                <template #opposite="slotProps">
                                    <small class="text-gray-400 text-xs whitespace-nowrap">{{ formatDate(slotProps.item.created_at) }}</small>
                                </template>
                                <template #content="slotProps">
                                    <div class="flex flex-col gap-1 pb-4">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span v-if="slotProps.item.from_status" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                {{ formatStatus(slotProps.item.from_status) }}
                                            </span>
                                            <i v-if="slotProps.item.from_status" class="pi pi-arrow-right text-xs text-gray-400"></i>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="statusBadgeClass(slotProps.item.to_status)">
                                                {{ formatStatus(slotProps.item.to_status) }}
                                            </span>
                                        </div>
                                        <span v-if="slotProps.item.note" class="text-xs text-gray-500 italic">{{ slotProps.item.note }}</span>
                                    </div>
                                </template>
                            </Timeline>
                        </template>
                    </Card>

                    <Card>
                        <template #title>Interview</template>
                        <template #subtitle>If scheduled</template>
                        <template #content>
                            <div v-if="app.interview" class="space-y-3">
                                <div class="flex justify-between border-b pb-2">
                                    <span class="text-sm font-medium text-gray-500">Scheduled</span>
                                    <span class="text-sm font-medium text-gray-900">{{ formatDate(app.interview.scheduled_at) }}</span>
                                </div>
                                <div class="flex justify-between border-b pb-2">
                                    <span class="text-sm font-medium text-gray-500">Mode</span>
                                    <span class="text-sm font-medium text-gray-900">{{ app.interview.mode || "—" }}</span>
                                </div>
                                <div class="flex justify-between border-b pb-2">
                                    <span class="text-sm font-medium text-gray-500">Location</span>
                                    <span class="text-sm font-medium text-gray-900">{{ app.interview.location || "—" }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-500">Status</span>
                                    <Tag :value="app.interview.status || '—'" severity="info" />
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic">
                                No interview scheduled.
                            </div>
                        </template>
                    </Card>

                    <!-- Resume Preview Card -->
                    <Card v-if="app.has_resume">
                        <template #title>Resume</template>
                        <template #content>
                            <Button label="Preview Resume" icon="pi pi-eye" class="w-full" outlined @click="openResumePreview" :loading="previewLoading" />
                        </template>
                    </Card>
                </div>
            </div>
        </div>
        <ConfirmDialog />
        <Toast />

        <!-- Resume Preview Dialog -->
        <Dialog v-model:visible="showPreview" modal header="Resume Preview" :style="{ width: '90vw', maxWidth: '900px' }" :contentStyle="{ padding: 0 }">
            <div v-if="previewLoading" class="flex justify-center items-center h-64">
                <ProgressSpinner />
            </div>
            <div v-else-if="previewError" class="p-6 text-center text-red-500">{{ previewError }}</div>
            <iframe
                v-else-if="previewBlobUrl"
                :src="previewBlobUrl"
                class="w-full"
                style="height: 80vh; border: none;"
                title="Resume Preview"
            ></iframe>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import Timeline from 'primevue/timeline';
import ProgressSpinner from 'primevue/progressspinner';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const route = useRoute();
const router = useRouter();
const confirm = useConfirm();
const toast = useToast();

const loading = ref(false);
const busy = ref(false);
const error = ref("");
const app = ref(null);

const id = computed(() => String(route.params.id || "").trim());

const history = computed(() => {
    const rows = app.value?.status_history || app.value?.statusHistory || [];
    return Array.isArray(rows) ? rows : [];
});

function formatDate(v) {
    if (!v) return "N/A";
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return String(v);
    return d.toLocaleString();
}

function getSeverity(status) {
    switch (status) {
        case 'shortlisted': return 'info';
        case 'hired': return 'success';
        case 'rejected': return 'danger';
        case 'interview': return 'warn';
        case 'submitted': return 'secondary';
        case 'withdrawn': return 'secondary';
        default: return 'secondary';
    }
}

function formatStatus(s) {
    if (!s) return '—';
    return s.charAt(0).toUpperCase() + s.slice(1);
}

function timelineDotClass(status) {
    const map = {
        submitted:   'bg-blue-50 border-blue-400 text-blue-600',
        shortlisted: 'bg-indigo-50 border-indigo-400 text-indigo-600',
        interview:   'bg-amber-50 border-amber-400 text-amber-600',
        hired:       'bg-green-50 border-green-500 text-green-600',
        rejected:    'bg-red-50 border-red-400 text-red-600',
        withdrawn:   'bg-gray-100 border-gray-400 text-gray-500',
    };
    return map[status] || 'bg-gray-100 border-gray-300 text-gray-500';
}

function timelineDotIcon(status) {
    const map = {
        submitted:   'pi pi-send',
        shortlisted: 'pi pi-star',
        interview:   'pi pi-calendar',
        hired:       'pi pi-check-circle',
        rejected:    'pi pi-times-circle',
        withdrawn:   'pi pi-minus-circle',
    };
    return map[status] || 'pi pi-circle';
}

function statusBadgeClass(status) {
    const map = {
        submitted:   'bg-blue-100 text-blue-700',
        shortlisted: 'bg-indigo-100 text-indigo-700',
        interview:   'bg-amber-100 text-amber-700',
        hired:       'bg-green-100 text-green-700',
        rejected:    'bg-red-100 text-red-700',
        withdrawn:   'bg-gray-100 text-gray-600',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

const canWithdraw = computed(() => {
    const s = String(app.value?.status || "");
    if (!app.value) return false;
    return s && !["rejected", "hired", "withdrawn"].includes(s);
});

async function fetchData() {
    if (!id.value) {
        error.value = "Missing application id.";
        app.value = null;
        return;
    }

    loading.value = true;
    error.value = "";

    try {
        const res = await api.get(`/applications/${id.value}`);
        app.value = res?.data?.data || null;
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Request failed";
        app.value = null;
    } finally {
        loading.value = false;
    }
}

function goBack() {
    router.push({ name: "candidate.myapplications" });
}

function confirmWithdraw() {
    confirm.require({
        message: 'This will mark your application as withdrawn.',
        header: 'Withdraw application?',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Withdraw',
            severity: 'danger'
        },
        accept: () => {
            withdraw();
        }
    });
}

async function withdraw() {
    if (!app.value?.id) return;

    busy.value = true;
    try {
        await api.post(`/applications/${app.value.id}/status`, { status: "withdrawn" });
        await fetchData();
        toast.add({ severity: 'success', summary: 'Withdrawn', detail: 'Application withdrawn successfully', life: 3000 });
    } catch (e) {
        const msg = e?.response?.data?.message || e?.message || "Withdraw failed";
        toast.add({ severity: 'error', summary: 'Failed', detail: msg, life: 3000 });
    } finally {
        busy.value = false;
    }
}

// Resume preview
const showPreview = ref(false);
const previewLoading = ref(false);
const previewError = ref('');
const previewBlobUrl = ref('');

async function openResumePreview() {
    showPreview.value = true;
    previewLoading.value = true;
    previewError.value = '';
    if (previewBlobUrl.value) {
        previewLoading.value = false;
        return;
    }
    try {
        const token = localStorage.getItem('auth_token') || '';
        const res = await fetch(`/api/applications/${app.value.id}/resume`, {
            headers: { Authorization: `Bearer ${token}` }
        });
        if (!res.ok) throw new Error('Could not load resume');
        const blob = await res.blob();
        previewBlobUrl.value = URL.createObjectURL(blob);
    } catch (e) {
        previewError.value = e?.message || 'Failed to load resume preview.';
    } finally {
        previewLoading.value = false;
    }
}

onMounted(() => fetchData());
</script>

<style scoped>
:deep(.p-card) {
  border: 1px solid #e2e8f0 !important;
  box-shadow: none !important;
  background-color: #ffffff !important;
}
.dark :deep(.p-card) {
  border-color: #374151 !important;
  background-color: #111827 !important;
  color: #f9fafb !important;
}
.dark :deep(.p-card .p-card-title),
.dark :deep(.p-card .p-card-subtitle),
.dark :deep(.p-card .p-card-content) {
  color: #f9fafb !important;
}
:deep(.app-timeline .p-timeline-event-connector) {
  background-color: #e2e8f0;
}
</style>
