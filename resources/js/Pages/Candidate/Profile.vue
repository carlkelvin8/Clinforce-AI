<template>
  <AppLayout>
    <div class="min-h-screen font-sans pb-4 profile-page">
      <div class="w-full max-w-6xl mx-auto px-3 md:px-4 py-3 md:py-5 space-y-3">

        <!-- Top bar: Profile card + Right sidebar -->
        <div class="grid grid-cols-1 lg:grid-cols-[1.5fr,1fr] gap-3">

          <!-- Profile header card -->
          <Card class="rounded-2xl shadow-sm bg-white">
            <template #content>
              <div class="px-4 py-3 md:px-5 md:py-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 md:gap-4">
                  <!-- Avatar with upload -->
                  <div class="relative shrink-0 cursor-pointer group" @click="avatarInput?.click()" title="Click to change photo">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden bg-gradient-to-br from-sky-100 via-sky-200 to-indigo-200 ring-4 ring-sky-100 shadow-md flex items-center justify-center">
                      <img v-if="auth.avatar" :src="auth.avatar" alt="Profile" class="w-full h-full object-cover">
                      <span v-else class="text-xl md:text-2xl font-semibold text-slate-600">{{ initials }}</span>
                    </div>
                    <div class="absolute inset-0 rounded-full bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                      <i class="pi pi-camera text-white text-lg"></i>
                    </div>
                    <span class="absolute bottom-1 right-1 w-3 h-3 md:w-3.5 md:h-3.5 rounded-full bg-emerald-500 shadow-sm"></span>
                  </div>
                  <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="hidden" @change="onPickAvatar">

                  <!-- Name + info -->
                  <div class="flex-1 min-w-0 space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                      <h1 class="text-xl md:text-2xl font-semibold text-slate-900 tracking-tight">{{ displayName }}</h1>
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-sky-50 text-sky-700">Candidate</span>
                      <button @click="form.open_to_work = !form.open_to_work; saveProfile()"
                        :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold transition-all',
                          form.open_to_work ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200']">
                        <span class="w-1.5 h-1.5 rounded-full" :class="form.open_to_work ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                        {{ form.open_to_work ? 'Open to work' : 'Not looking' }}
                      </button>
                    </div>
                    <p class="text-xs text-slate-500 max-w-xl">{{ form.headline || 'Add a short professional headline.' }}</p>
                    <div class="flex flex-wrap gap-3 text-xs text-slate-600">
                      <span class="flex items-center gap-1"><i class="pi pi-map-marker text-[10px]"></i> {{ [form.city, form.state].filter(Boolean).join(', ') || 'Add city' }}</span>
                      <span class="flex items-center gap-1"><i class="pi pi-flag text-[10px]"></i> {{ (form.country || '—').toUpperCase() }}</span>
                      <span class="flex items-center gap-1"><i class="pi pi-briefcase text-[10px]"></i> {{ form.years_experience != null ? form.years_experience + ' yrs' : 'Add years' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Right: Completeness + quick actions -->
          <Card class="rounded-2xl shadow-sm bg-white">
            <template #content>
              <div class="px-4 py-3 md:px-5 md:py-3 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] uppercase tracking-wide font-medium text-slate-500">Profile completeness</span>
                  <Tag :value="`${completeness}%`" severity="secondary" rounded class="text-[10px]" />
                </div>
                <div class="flex items-center gap-2">
                  <ProgressBar :value="completeness" :showValue="false" class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden" />
                  <span class="text-[10px] font-semibold text-slate-700">{{ completeness }}%</span>
                </div>
                <div v-if="completeness < 100" class="space-y-0.5">
                  <div v-for="nudge in missingNudges.slice(0,3)" :key="nudge.field"
                    class="flex items-center gap-1.5 text-[11px] text-slate-500 cursor-pointer hover:text-sky-600"
                    @click="focusField(nudge.field)">
                    <i class="pi pi-circle text-[8px] text-slate-300"></i>
                    <span>{{ nudge.label }}</span>
                  </div>
                </div>
                <div v-else class="flex items-center gap-1 text-[11px] text-emerald-600 font-medium">
                  <i class="pi pi-check-circle"></i> Profile complete
                </div>
                <div class="flex gap-2 pt-1">
                  <Button label="Save" icon="pi pi-check" size="small" @click="saveProfile" :loading="saving" class="flex-1 !rounded-lg !text-xs" />
                  <Button icon="pi pi-refresh" size="small" severity="secondary" outlined @click="fetchAll" :loading="loading" class="!rounded-lg" />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mb-1">{{ error }}</Message>
        <Message v-if="success" severity="success" :closable="false" class="mb-1">{{ success }}</Message>

        <!-- Bottom: Profile form + Documents side by side -->
        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr,1.1fr] gap-3">

          <!-- Profile details form -->
          <Card class="rounded-2xl shadow-sm bg-white">
            <template #content>
              <div class="px-4 py-3 md:px-5 md:py-3">
                <div class="text-sm font-semibold text-slate-900 mb-3">Profile details</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 profile-form-grid">
                  <div class="flex flex-col gap-1">
                    <label for="firstName" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">First name</label>
                    <InputText id="firstName" v-model="form.first_name" class="!h-9 !px-2.5 !rounded-lg !border-slate-200 !text-sm" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label for="lastName" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Last name</label>
                    <InputText id="lastName" v-model="form.last_name" class="!h-9 !px-2.5 !rounded-lg !border-slate-200 !text-sm" />
                  </div>
                  <div class="flex flex-col gap-1 sm:col-span-2">
                    <label for="headline" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Headline</label>
                    <InputText id="headline" v-model="form.headline" placeholder="e.g. ICU Nurse • RN" class="!h-9 !px-2.5 !rounded-lg !border-slate-200 !text-sm" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label for="city" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">City</label>
                    <InputText id="city" v-model="form.city" placeholder="Makati" class="!h-9 !px-2.5 !rounded-lg !border-slate-200 !text-sm" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label for="state" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">State/Province</label>
                    <InputText id="state" v-model="form.state" placeholder="NCR" class="!h-9 !px-2.5 !rounded-lg !border-slate-200 !text-sm" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label for="country" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Country</label>
                    <Select id="country" v-model="form.country" :options="countries" optionLabel="label" optionValue="value" filter placeholder="Select" class="!h-9 !rounded-lg !border-slate-200 !text-sm" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label for="experience" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Years of experience</label>
                    <InputNumber id="experience" v-model="form.years_experience" :min="0" :max="80" class="w-full !h-9 !rounded-lg !border-slate-200 !text-sm" />
                  </div>
                  <div class="flex flex-col gap-1 sm:col-span-2">
                    <label for="summary" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Summary</label>
                    <Textarea id="summary" v-model="form.summary" rows="4" placeholder="Short professional summary…" class="!rounded-lg !border-slate-200 !text-sm !min-h-[90px]" />
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Documents card -->
          <Card class="rounded-2xl shadow-sm bg-white">
            <template #content>
              <div class="px-4 py-3 md:px-5 md:py-3">
                <div class="flex items-center justify-between mb-2">
                  <div class="text-sm font-semibold text-slate-900">Documents</div>
                  <Button label="Upload" icon="pi pi-plus" size="small" @click="openUpload" class="!rounded-lg !text-xs" />
                </div>

                <!-- Upload drop zone -->
                <div v-if="docsLoading" class="py-4 text-center"><i class="pi pi-spin pi-spinner text-lg"></i></div>
                <div v-else-if="docs.length === 0" class="text-center py-4">
                  <i class="pi pi-file text-slate-300 text-2xl mb-1"></i>
                  <div class="text-[11px] text-slate-500">No documents yet</div>
                  <Button label="Upload resume" size="small" text @click="openUpload" class="!text-xs !mt-1" />
                </div>
                <div v-else class="space-y-1.5 max-h-[260px] overflow-y-auto pr-0.5">
                  <div v-for="d in docs" :key="d.id"
                    class="bg-slate-50 rounded-lg p-2.5 flex items-center gap-2 border border-slate-100"
                    :class="d.status === 'active' ? 'border-emerald-200' : ''">
                    <div class="w-7 h-7 rounded-full bg-white flex items-center justify-center text-slate-400 shrink-0">
                      <i class="pi pi-file text-[10px]"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-[11px] font-medium text-slate-900 truncate">
                        {{ d.file_name || "Document" }}
                        <span v-if="d.status === 'active' && d.doc_type === 'resume'" class="text-[8px] bg-emerald-100 text-emerald-700 px-1 py-px rounded-full font-semibold ml-1">Active</span>
                      </div>
                      <div class="text-[10px] text-slate-500">{{ bytesToLabel(d.file_size_bytes) }} · {{ formatDate(d.created_at) }}</div>
                    </div>
                    <div class="flex gap-0.5 shrink-0">
                      <Button v-if="d.doc_type === 'resume' && d.status !== 'active'" icon="pi pi-check" text rounded size="small" :loading="settingActiveId === d.id" @click.stop="setActiveResume(d)" title="Set active" />
                      <Button icon="pi pi-trash" text rounded size="small" severity="danger" :loading="removingId === d.id" @click="confirmRemoveDoc(d)" title="Remove" />
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Upload document dialog -->
      <Dialog v-model:visible="showUpload" header="Add document" modal :style="{ width: '420px' }">
        <Message v-if="uploadError" severity="error" :closable="false" class="mb-2">{{ uploadError }}</Message>
        <div class="space-y-3">
          <div>
            <label class="text-xs font-semibold text-slate-700">Document type</label>
            <Select id="docType" v-model="upload.doc_type" :options="allowedDocTypes" class="w-full !rounded-lg !text-sm mt-1" />
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-700">File</label>
            <input ref="fileEl" type="file" class="w-full text-sm mt-1" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" @change="onPickFile" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" icon="pi pi-times" text @click="closeUpload" :disabled="uploading" />
          <Button label="Upload" icon="pi pi-check" @click="confirmUpload" :loading="uploading" />
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";
import Swal from "sweetalert2";
import { getCachedUser } from "@/lib/auth";

import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import { countries } from '@/lib/countries';

const loading = ref(false);
const saving = ref(false);
const docsLoading = ref(false);
const uploading = ref(false);
const removingId = ref(null);
const settingActiveId = ref(null);
const error = ref("");
const success = ref("");
const avatarInput = ref(null);
const avatarUploading = ref(false);

const auth = ref({ id: null, role: null, email: null, avatar: null });

const form = ref({
  first_name: "",
  last_name: "",
  headline: "",
  summary: "",
  years_experience: null,
  country: "",
  state: "",
  city: "",
  open_to_work: false,
});

const docs = ref([]);
const allowedDocTypes = ["resume", "license", "certificate", "id_document", "other"];
const showUpload = ref(false);
const uploadError = ref("");
const fileEl = ref(null);
const upload = ref({ doc_type: "resume", file: null, fileName: "", fileSize: 0 });

// ---- Avatar upload ----
async function onPickAvatar(ev) {
  const file = ev.target.files?.[0];
  if (!file) return;
  if (!file.type.startsWith('image/')) {
    await Swal.fire({ icon: 'warning', title: 'Invalid file', text: 'Please select an image file.' });
    return;
  }

  avatarUploading.value = true;
  try {
    const fd = new FormData();
    fd.append('avatar', file);
    const res = await api.post('/me/applicant/avatar', fd);
    const data = res.data?.data ?? res.data;
    const url = data?.avatar_url || data?.avatar;
    if (url) {
      auth.value.avatar = url.startsWith('/') ? url : `/${url}`;
      // Update cached user
      try {
        const prev = JSON.parse(localStorage.getItem("auth_user") || "{}");
        localStorage.setItem("auth_user", JSON.stringify({ ...prev, avatar: auth.value.avatar, avatar_url: auth.value.avatar }));
        window.dispatchEvent(new Event("auth:changed"));
      } catch {}
      await Swal.fire({ icon: 'success', title: 'Photo updated', timer: 1200, showConfirmButton: false, toast: true, position: 'top-end' });
    }
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Upload failed', text: e?.response?.data?.message || 'Could not upload photo.' });
  } finally {
    avatarUploading.value = false;
    if (avatarInput.value) avatarInput.value.value = '';
  }
}

// ---- Helpers ----
function unwrap(resData) { return resData?.data ?? resData; }

function buildAvatarUrl(raw) {
  if (!raw) return null;
  const v = String(raw);
  if (/^https?:\/\//i.test(v)) return v;
  if (v.startsWith("/uploads/") || v.startsWith("/storage/")) return v;
  if (v.startsWith("uploads/") || v.startsWith("storage/")) return `/${v}`;
  return `/storage/${v.replace(/^\/+/, "")}`;
}

const displayName = computed(() => {
  const f = (form.value.first_name || "").trim();
  const l = (form.value.last_name || "").trim();
  return (f || l) ? `${f} ${l}`.trim() : "User";
});

const initials = computed(() => {
  const f = (form.value.first_name || "").trim()[0] || "";
  const l = (form.value.last_name || "").trim()[0] || "";
  const s = (f + l).toUpperCase();
  return s || "ME";
});

const completeness = computed(() => {
  const fields = [
    form.value.first_name, form.value.last_name, form.value.headline,
    form.value.summary, String(form.value.years_experience ?? ""),
    form.value.country, form.value.state, form.value.city,
  ];
  const filled = fields.filter((v) => String(v || "").trim().length > 0).length;
  return Math.round((filled / fields.length) * 100);
});

const nudgeFields = [
  { field: 'firstName', key: 'first_name', label: 'Add first name' },
  { field: 'lastName', key: 'last_name', label: 'Add last name' },
  { field: 'headline', key: 'headline', label: 'Add headline' },
  { field: 'summary', key: 'summary', label: 'Write a summary' },
  { field: 'experience', key: 'years_experience', label: 'Add years of experience' },
  { field: 'country', key: 'country', label: 'Select country' },
  { field: 'state', key: 'state', label: 'Add state' },
  { field: 'city', key: 'city', label: 'Add city' },
];
const missingNudges = computed(() => nudgeFields.filter(n => !String(form.value[n.key] ?? '').trim()));

function focusField(fieldId) {
  const el = document.getElementById(fieldId);
  if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); setTimeout(() => el.focus(), 300); }
}

function formatDate(v) { if (!v) return "N/A"; const d = new Date(v); return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleDateString(); }
function bytesToLabel(n) { const v = Number(n || 0); if (!v) return "—"; const kb = v / 1024; return kb < 1024 ? `${kb.toFixed(1)} KB` : `${(kb / 1024).toFixed(1)} MB`; }
function toast(icon, title) { return Swal.fire({ toast: true, position: "top-end", icon, title, showConfirmButton: false, timer: 1600, timerProgressBar: true }); }

// ---- Fetch ----
async function fetchMe() {
  const res = await api.get("/me");
  const u = unwrap(res.data);
  const avatarRaw = u?.avatar_url || u?.avatar || null;
  const avatarUrl = buildAvatarUrl(avatarRaw);
  auth.value = { id: u?.id ?? null, role: u?.role ?? null, email: u?.email ?? null, avatar: avatarUrl };

  try {
    const prev = JSON.parse(localStorage.getItem("auth_user") || "{}");
    localStorage.setItem("auth_user", JSON.stringify({ ...prev, id: auth.value.id, role: auth.value.role, email: auth.value.email, avatar: auth.value.avatar, avatar_url: auth.value.avatar }));
    window.dispatchEvent(new Event("auth:changed"));
  } catch {}

  const ap = u?.applicant_profile || u?.applicantProfile || null;
  form.value = {
    first_name: ap?.first_name ?? "", last_name: ap?.last_name ?? "",
    headline: ap?.headline ?? "", summary: ap?.summary ?? "",
    years_experience: ap?.years_experience ?? null,
    country: ap?.country ?? ap?.country_code ?? "",
    state: ap?.state ?? "", city: ap?.city ?? "",
    open_to_work: ap?.open_to_work ?? false,
  };
}

async function fetchDocs() {
  docsLoading.value = true;
  try {
    const res = await api.get("/documents");
    const arr = unwrap(res.data);
    docs.value = Array.isArray(arr) ? arr : Array.isArray(arr?.data) ? arr.data : [];
  } finally { docsLoading.value = false; }
}

async function fetchAll() {
  loading.value = true; error.value = ""; success.value = "";
  try { await fetchMe(); await fetchDocs(); }
  catch (e) { error.value = e?.__payload?.message || e?.message || "Request failed"; }
  finally { loading.value = false; }
}

async function saveProfile() {
  saving.value = true; error.value = ""; success.value = "";
  try {
    await api.put("/me/applicant", {
      first_name: form.value.first_name, last_name: form.value.last_name,
      headline: form.value.headline, summary: form.value.summary,
      years_experience: form.value.years_experience, country: form.value.country,
      state: form.value.state, city: form.value.city,
      open_to_work: form.value.open_to_work,
    });
    success.value = "Saved."; await fetchMe(); await toast("success", "Profile saved");
  } catch (e) { error.value = e?.__payload?.message || e?.message || "Save failed"; }
  finally { saving.value = false; }
}

// ---- Document upload ----
function openUpload() { uploadError.value = ""; upload.value = { doc_type: "resume", file: null, fileName: "", fileSize: 0 }; showUpload.value = true; }
function closeUpload() { showUpload.value = false; uploadError.value = ""; if (fileEl.value) fileEl.value.value = ""; }
function onPickFile(ev) { const f = ev?.target?.files?.[0] || null; upload.value.file = f; upload.value.fileName = f?.name || ""; upload.value.fileSize = f?.size || 0; }

async function confirmUpload() {
  uploadError.value = "";
  if (!upload.value.file) { uploadError.value = "Please choose a file."; return; }
  uploading.value = true;
  try {
    const fd = new FormData(); fd.append("doc_type", upload.value.doc_type); fd.append("file", upload.value.file);
    await api.post("/documents", fd); await fetchDocs(); showUpload.value = false;
    await toast("success", "Document uploaded");
  } catch (e) { uploadError.value = e?.__payload?.message || e?.message || "Upload failed"; }
  finally { uploading.value = false; }
}

async function setActiveResume(doc) {
  settingActiveId.value = doc.id;
  try {
    await api.patch(`/documents/${doc.id}/set-active`);
    docs.value.forEach(d => { if (d.doc_type === 'resume') d.status = d.id === doc.id ? 'active' : 'inactive'; });
    await toast('success', 'Active resume updated');
  } catch (e) { await Swal.fire({ icon: 'error', title: 'Failed', text: e?.response?.data?.message || 'Could not update' }); }
  finally { settingActiveId.value = null; }
}

async function confirmRemoveDoc(d) {
  const result = await Swal.fire({ icon: "warning", title: "Remove?", text: d?.file_name || "Document", showCancelButton: true, confirmButtonText: "Remove", cancelButtonText: "Cancel" });
  if (!result.isConfirmed) return;
  removingId.value = d.id;
  try { await api.delete(`/documents/${d.id}`); await fetchDocs(); await toast("success", "Document removed"); }
  catch (e) { error.value = e?.__payload?.message || e?.message || "Remove failed"; }
  finally { removingId.value = null; }
}

onMounted(() => {
  try {
    const cached = getCachedUser();
    if (cached) auth.value = { id: cached.id ?? null, role: cached.role ?? null, email: cached.email ?? null, avatar: buildAvatarUrl(cached.avatar_url || cached.avatar || null) };
  } catch {}
  fetchAll();
});
</script>

<style scoped>
.profile-page :deep(.p-card) {
  border-radius: 0.5rem; border-width: 1px !important; border-style: solid !important; border-color: #e5e7eb !important; box-shadow: none !important;
}
.p-progressbar { border-radius: 9999px; overflow: hidden; background-color: #e0f2fe; }
:deep(.p-progressbar-value) { background: linear-gradient(to right, #0ea5e9, #6366f1) !important; transition: width 0.25s ease-out; }
.profile-page :deep(.p-inputtext), .profile-page :deep(.p-inputnumber-input), .profile-page :deep(.p-inputtextarea) {
  border-width: 1px !important; border-style: solid !important; border-color: #e5e7eb !important; box-shadow: none !important;
}
.profile-page :deep(.p-inputtext:focus), .profile-page :deep(.p-inputnumber-input:focus), .profile-page :deep(.p-inputtextarea:focus) {
  border-color: #bae6fd !important; box-shadow: 0 0 0 2px #e0f2fe !important;
}
.profile-page :deep(.p-select) { border-width: 1px !important; border-color: #e5e7eb !important; }
@media (max-width: 768px) {
  .profile-form-grid { grid-template-columns: 1fr !important; }
}
</style>
