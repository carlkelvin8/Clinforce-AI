<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import Button from "primevue/button";
import Message from "primevue/message";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import AppLayout from "@/Components/AppLayout.vue";
import { http } from "../../../lib/http";

const route = useRoute();
const router = useRouter();

const id = computed(() => route.params.id);

const loading = ref(true);
const working = ref(false);
const error = ref("");

const job = ref(null);
const apps = ref([]);
const appsError = ref("");
const analytics = ref(null);
const exportingReport = ref(false);
const bulkMessageDialog = ref(false);
const bulkMessageText = ref('');
const sendingBulk = ref(false);

function normalizeJob(payload) {
  return payload?.data ?? payload ?? null;
}

function normalizeList(payload) {
  const d = payload?.data ?? payload ?? [];
  if (Array.isArray(d)) return d;
  if (Array.isArray(d?.data)) return d.data;
  if (Array.isArray(d?.items)) return d.items;
  return [];
}

const status = computed(() => {
  const s = String(job.value?.status || "").toLowerCase();
  if (s === "published") return "published";
  if (s === "archived") return "archived";
  return "draft";
});

const statusText = computed(() => {
  if (status.value === "published") return "Published";
  if (status.value === "archived") return "Archived";
  return "Draft";
});

function formatDate(d) {
  if (!d) return "—";
  const dt = new Date(d);
  if (!Number.isFinite(dt.getTime())) return "—";
  return dt.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" });
}

async function load() {
  loading.value = true;
  error.value = "";
  appsError.value = "";

  try {
    const jr = await http.get(`/jobs/${id.value}`);
    job.value = normalizeJob(jr?.data);

    // optional preview (won't break if forbidden/not found)
    try {
      const ar = await http.get("/applications", { params: { scope: "owned" } });
      const all = normalizeList(ar?.data);
      apps.value = all
        .filter(a => String(a.job_id || a.job?.id) === String(id.value))
        .slice(0, 5);
    } catch (e) {
      const code = e?.response?.status;
      if (code === 403) appsError.value = "Applicants preview is not allowed for your account.";
      else if (code === 404) appsError.value = "Applicants endpoint not available yet.";
      else appsError.value = "Applicants preview unavailable.";
      apps.value = [];
    }
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || "Failed to load job.";
    job.value = null;
  } finally {
    loading.value = false;
  }
}

async function publish() {
  working.value = true;
  try {
    await http.post(`/jobs/${id.value}/publish`);
    await load();
  } finally {
    working.value = false;
  }
}

async function archive() {
  working.value = true;
  try {
    await http.post(`/jobs/${id.value}/archive`);
    await load();
  } finally {
    working.value = false;
  }
}

async function destroyJob() {
  if (!confirm("Delete this role?")) return;
  working.value = true;
  try {
    await http.delete(`/jobs/${id.value}`);
    router.push({ name: "employer.jobs" });
  } finally {
    working.value = false;
  }
}

async function copyShareLink() {
  const url = `${window.location.origin}/candidate/jobs/${id.value}`;
  try {
    // Track the share via API
    try {
      await http.post(`/jobs/${id.value}/share`, { channel: 'link' })
    } catch {}
    await navigator.clipboard.writeText(url);
    alert("Link copied.");
  } catch {
    prompt("Copy this link:", url);
  }
}

async function loadAnalytics() {
  if (!id.value) return
  try {
    const res = await http.get(`/jobs/${id.value}/analytics`)
    analytics.value = res.data?.data ?? res.data
  } catch {}
}

async function downloadPipelineReport() {
  exportingReport.value = true
  try {
    const res = await http.get(`/jobs/${id.value}/pipeline-report`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }))
    const a = document.createElement('a'); a.href = url; a.download = `pipeline-job-${id.value}.csv`; a.click()
    URL.revokeObjectURL(url)
  } catch {} finally { exportingReport.value = false }
}

async function sendBulkMessage() {
  if (!bulkMessageText.value.trim()) return
  sendingBulk.value = true
  try {
    const res = await http.post(`/jobs/${id.value}/bulk-message`, { message: bulkMessageText.value })
    const data = res.data?.data ?? res.data
    alert(`Sent to ${data?.sent ?? 0} candidate(s)`)
    bulkMessageDialog.value = false
    bulkMessageText.value = ''
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to send')
  } finally { sendingBulk.value = false }
}

onMounted(() => { load(); loadAnalytics(); });
</script>

<template>
  <AppLayout>
    <div class="space-y-6 font-sans">
      <!-- Top Actions -->
      <div class="flex items-center justify-between gap-2">
        <Button
          label="Back to roles"
          icon="pi pi-arrow-left"
          outlined
          severity="secondary"
          size="small"
          @click="$router.push({ name: 'employer.jobs' })"
          class="!bg-white !border-slate-300 !text-slate-700 !rounded-md !px-3 !py-2 hover:!bg-slate-50"
        />
        <Button
          :label="loading ? 'Refreshing…' : 'Refresh'"
          :icon="loading ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'"
          @click="load"
          :disabled="loading"
          outlined
          severity="secondary"
          size="small"
          class="!bg-white !border-slate-300 !text-slate-700 !rounded-md shadow-sm hover:!bg-slate-50"
        />
      </div>

      <div v-if="loading" class="text-center py-12 text-slate-500">
        <i class="pi pi-spin pi-spinner text-3xl mb-3 text-blue-600"></i>
        <div>Loading role details…</div>
      </div>

      <template v-else>
        <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

        <template v-if="job">
          <!-- Header -->
          <div class="bg-white rounded-2xl border border-slate-200 p-6 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 opacity-70"></div>
            <div class="flex flex-col md:flex-row justify-between gap-6">
              <div class="flex-1">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Role details</div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ job.title || "Role" }}</h1>
                
                <div class="flex items-center gap-3 text-sm text-slate-600 mb-4 flex-wrap">
                  <span class="flex items-center gap-1"><i class="pi pi-map-marker text-xs"></i> {{ job.city || "—" }}</span>
                  <span class="text-slate-300">•</span>
                  <span>{{ job.country_code || "—" }}</span>
                  <span class="text-slate-300">•</span>
                  <span class="text-slate-400 font-mono text-xs">ID: {{ job.id }}</span>
                </div>

                <div class="flex flex-wrap gap-2">
                  <Tag :value="statusText" :severity="status === 'published' ? 'success' : status === 'archived' ? 'secondary' : 'warn'" rounded class="!text-xs !font-bold uppercase tracking-wide" />
                  <Tag v-if="job.employment_type" :value="job.employment_type" severity="info" rounded class="!bg-slate-100 !text-slate-600 !border-slate-200" />
                  <Tag v-if="job.work_mode" :value="job.work_mode" severity="info" rounded class="!bg-slate-100 !text-slate-600 !border-slate-200" />
                  <Tag v-if="job.published_at" :value="`Published ${formatDate(job.published_at)}`" severity="contrast" rounded class="!bg-slate-800 !text-white" />
                  <Tag v-if="job.archived_at" :value="`Archived ${formatDate(job.archived_at)}`" severity="contrast" rounded class="!bg-slate-800 !text-white" />
                </div>
              </div>

              <div class="flex flex-wrap items-center justify-end md:justify-start gap-1 p-1">
              <Button
                label="Edit"
                icon="pi pi-pencil"
                @click="$router.push({ name: 'employer.jobs.edit', params: { id: job.id } })"
                text
                size="small"
                class="!bg-transparent !border-transparent !text-slate-700 hover:!bg-transparent hover:!text-slate-900"
              />
              <Button
                label="Publish"
                icon="pi pi-check"
                @click="publish"
                :loading="working"
                :disabled="working"
                text
                size="small"
                class="!bg-transparent !border-transparent !text-green-600 hover:!bg-transparent hover:!text-green-700"
              />
              <Button
                label="Archive"
                icon="pi pi-ban"
                @click="archive"
                :loading="working"
                :disabled="working"
                text
                size="small"
                class="!bg-transparent !border-transparent !text-slate-600 hover:!bg-transparent hover:!text-slate-800"
              />
              <Button
                label="Delete"
                icon="pi pi-trash"
                @click="destroyJob"
                :loading="working"
                :disabled="working"
                text
                size="small"
                class="!bg-transparent !border-transparent !text-red-600 hover:!bg-transparent hover:!text-red-700"
              />
              </div>
            </div>
          </div>

          <!-- Content Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="md:col-span-2 flex flex-col gap-6">
              <!-- Description Card -->
              <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Role description</h3>
                <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ job.description || "—" }}</p>
                
                <div class="flex flex-col gap-3 mt-8 pt-6 border-t border-slate-100">
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-500 font-medium">Employment type</span>
                    <span class="text-slate-900 font-semibold">{{ job.employment_type || "—" }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-500 font-medium">Work mode</span>
                    <span class="text-slate-900 font-semibold">{{ job.work_mode || "—" }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-slate-500 font-medium">Location</span>
                    <span class="text-slate-900 font-semibold">{{ job.city || "—" }}, {{ job.country_code || "—" }}</span>
                  </div>
                </div>
              </div>

              <!-- Applicants Preview Card -->
              <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-slate-900">Applicants preview</h3>
                    <p class="text-xs text-slate-500 mt-1">Uses /applications?scope=owned (optional)</p>
                </div>
                
                <Message v-if="appsError" severity="warn" :closable="false" class="mb-4">{{ appsError }}</Message>

                <div v-if="apps.length" class="flex flex-col gap-3">
                    <div v-for="a in apps" :key="a.id" class="p-3 bg-slate-50 rounded-lg border border-slate-200 flex justify-between items-center">
                      <div>
                        <div class="font-bold text-slate-900 text-sm mb-1">
                          {{ a.applicant_name || a.user?.name || "Applicant" }}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                          <span class="uppercase font-bold bg-white border border-slate-200 text-slate-700 px-1.5 py-0.5 rounded text-[10px]">{{ a.status || "submitted" }}</span>
                          <span>•</span>
                          <span>#{{ a.id }}</span>
                        </div>
                      </div>
                      <Button 
                        label="View" 
                        icon="pi pi-arrow-right" 
                        iconPos="right"
                        size="small"
                        outlined
                        severity="secondary"
                        @click="$router.push({ name: 'applicants.view', params: { id: a.id } })"
                        class="!border-slate-300 !text-slate-700 hover:!bg-slate-100"
                      />
                    </div>
                </div>
                <div v-else class="text-slate-500 italic text-sm py-4 text-center bg-slate-50 rounded-lg border border-dashed border-slate-200">
                    No applicants found yet.
                </div>
              </div>
            </div>

            <div class="md:col-span-1 flex flex-col gap-6">
              <!-- Quick Actions Card -->
              <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Quick actions</h3>
                <div class="flex flex-col gap-3">
                    <Button 
                      label="Copy share link" 
                      icon="pi pi-copy" 
                      @click="copyShareLink" 
                      severity="success"
                      class="w-full !bg-blue-50 !text-blue-600 !border-blue-200 hover:!bg-blue-100"
                    />
                    <Button 
                      label="Edit role" 
                      icon="pi pi-pencil" 
                      @click="$router.push({ name: 'employer.jobs.edit', params: { id: job.id } })" 
                      severity="secondary" 
                      outlined
                      class="w-full !bg-white !border-slate-300 !text-slate-700 hover:!bg-slate-50"
                    />
                    <Button
                      label="Message shortlisted"
                      icon="pi pi-envelope"
                      @click="bulkMessageDialog = true"
                      severity="secondary"
                      outlined
                      class="w-full !bg-white !border-slate-300 !text-slate-700 hover:!bg-slate-50"
                    />
                    <Button
                      label="Pipeline report"
                      icon="pi pi-download"
                      @click="downloadPipelineReport"
                      severity="secondary"
                      outlined
                      :loading="exportingReport"
                      class="w-full !bg-white !border-slate-300 !text-slate-700 hover:!bg-slate-50"
                    />
                </div>
              </div>

              <!-- Job Analytics Card -->
              <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Analytics</h3>
                <div v-if="analytics" class="space-y-3 text-sm">
                  <div class="flex justify-between">
                    <span class="text-slate-500">Views</span>
                    <span class="font-bold text-slate-900">{{ analytics.view_count }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-500">Applications</span>
                    <span class="font-bold text-slate-900">{{ analytics.total_apps }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-500">Conversion</span>
                    <span class="font-bold text-emerald-600">{{ analytics.conversion_rate }}%</span>
                  </div>
                  <div v-for="(count, status) in analytics.by_status" :key="status" class="flex justify-between">
                    <span class="text-slate-500 capitalize">{{ status }}</span>
                    <span class="font-semibold text-slate-700">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-slate-400 text-sm">Loading...</div>
              </div>

              <!-- Timeline Card -->
              <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Timeline</h3>
                <div class="flex flex-col gap-3 text-sm">
                    <div class="flex justify-between items-center pb-3 border-b border-slate-50">
                      <span class="text-slate-500">Created</span>
                      <span class="font-semibold text-slate-900">{{ formatDate(job.created_at) }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-3 border-b border-slate-50">
                      <span class="text-slate-500">Updated</span>
                      <span class="font-semibold text-slate-900">{{ formatDate(job.updated_at) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-slate-500">Status</span>
                      <Tag :value="statusText" :severity="status === 'published' ? 'success' : status === 'archived' ? 'secondary' : 'warn'" class="!text-[10px] !uppercase !font-bold" rounded />
                    </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <div v-else class="text-center py-16 text-slate-500 bg-white rounded-xl border border-dashed border-slate-200">
          Role not found.
        </div>
      </template>

    </div>
  </AppLayout>

  <!-- Bulk Message Dialog -->
  <Dialog v-model:visible="bulkMessageDialog" header="Message shortlisted candidates" :style="{ width: '480px' }" modal>
    <div class="space-y-4 pt-2">
      <p class="text-sm text-slate-600">Send a message to all <strong>shortlisted</strong> candidates for this job.</p>
      <div class="space-y-1.5">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Message</label>
        <textarea v-model="bulkMessageText" rows="5"
          placeholder="e.g. We'd like to invite you to the next stage of our hiring process..."
          class="w-full text-sm p-3 rounded-xl border border-slate-200 focus:border-blue-400 focus:outline-none resize-none"></textarea>
      </div>
      <div class="flex justify-end gap-2 pt-1">
        <Button label="Cancel" severity="secondary" @click="bulkMessageDialog = false" />
        <Button label="Send to all shortlisted" icon="pi pi-send" :loading="sendingBulk" :disabled="!bulkMessageText.trim()" @click="sendBulkMessage" />
      </div>
    </div>
  </Dialog>
</template>

<style scoped>
/* Tailwind handles styling */
</style>
