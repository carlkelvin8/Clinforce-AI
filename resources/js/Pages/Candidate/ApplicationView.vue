<template>
    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Application Details</h1>
                    <p class="text-sm text-gray-500 mt-1">Pulled from <span class="font-mono text-xs bg-gray-100 px-1 py-0.5 rounded">GET /api/applications/:id</span></p>
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
                                <span>Application #{{ app.id }}</span>
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
                                    <span class="block text-sm font-medium text-gray-500">Job ID</span>
                                    <span class="block text-gray-900 font-semibold">{{ app.job_id }}</span>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500">Applicant User ID</span>
                                    <span class="block text-gray-900 font-semibold">{{ app.applicant_user_id }}</span>
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
                            <div v-if="history.length === 0" class="text-sm text-gray-500">No status history.</div>
                            <Timeline v-else :value="history">
                                <template #opposite="slotProps">
                                    <small class="text-surface-500 dark:text-surface-400">{{ formatDate(slotProps.item.created_at) }}</small>
                                </template>
                                <template #content="slotProps">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-mono text-gray-500">{{ slotProps.item.from_status || "—" }}</span>
                                            <i class="pi pi-arrow-right text-xs text-gray-400"></i>
                                            <span class="text-sm font-mono font-bold text-gray-900">{{ slotProps.item.to_status }}</span>
                                        </div>
                                        <span v-if="slotProps.item.note" class="text-xs text-gray-600 mt-1 italic">{{ slotProps.item.note }}</span>
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
                </div>
            </div>
        </div>
        <ConfirmDialog />
        <Toast />
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

onMounted(() => fetchData());
</script>

<style scoped>
:deep(.p-card) {
  border: 1px solid #e2e8f0 !important;
  box-shadow: none !important;
  background-color: #ffffff !important;
}
</style>
