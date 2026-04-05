<template>
  <AppLayout>
    <div class="min-h-screen font-sans pb-6 profile-page">
      <div class="w-full max-w-4xl lg:max-w-5xl mx-auto px-3 md:px-4 py-4 md:py-6 space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-[1.7fr,1.3fr] gap-3">
          <Card class="rounded-3xl shadow-sm bg-white transition-all">
            <template #content>
              <div class="px-4 py-4 md:px-5 md:py-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-6">
                  <div class="flex items-start gap-3 md:gap-4">
                  <div class="relative w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden bg-gradient-to-br from-sky-100 via-sky-200 to-indigo-200 ring-4 ring-sky-100 shadow-md flex items-center justify-center">
                    <img
                      v-if="auth.avatar"
                      :src="auth.avatar"
                      alt="Profile photo"
                      class="w-full h-full object-cover"
                    >
                    <span
                      v-else
                      class="text-xl md:text-2xl font-semibold text-slate-600"
                    >
                      {{ initials }}
                    </span>
                    <span class="absolute bottom-1 right-1 w-3 h-3 md:w-3.5 md:h-3.5 rounded-full bg-emerald-500 shadow-sm"></span>
                  </div>
                  <div class="space-y-2 min-w-0">
                    <div class="inline-flex flex-wrap items-center gap-2">
                      <h1 class="text-2xl md:text-3xl font-semibold text-slate-900 tracking-tight">
                        {{ displayName }}
                      </h1>
                      <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-sky-50 text-sky-700">
                        Candidate
                      </span>
                      <!-- Open to work toggle -->
                      <button @click="form.open_to_work = !form.open_to_work; saveProfile()"
                        :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold transition-all',
                          form.open_to_work
                            ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                            : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200']">
                        <span class="w-1.5 h-1.5 rounded-full" :class="form.open_to_work ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                        {{ form.open_to_work ? 'Open to work' : 'Not looking' }}
                      </button>
                    </div>
                    <p class="text-sm text-slate-500 max-w-xl">
                      {{ form.headline || 'Add a short professional headline so employers understand your role at a glance.' }}
                    </p>
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 md:gap-3 md:justify-end mt-2 md:mt-0 md:self-center">
                  <div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-slate-400">
                      <i class="pi pi-map-marker text-xs"></i>
                    </div>
                    <div class="flex flex-col min-w-0 max-w-[180px]">
                      <div class="text-[11px] uppercase tracking-wide text-slate-400">Location</div>
                      <div class="text-sm font-medium text-slate-800 truncate whitespace-nowrap">
                        {{ [form.city, form.state].filter(Boolean).join(', ') || 'Add city' }}
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-slate-400">
                      <i class="pi pi-flag text-xs"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                      <div class="text-[11px] uppercase tracking-wide text-slate-400">Country</div>
                      <div class="text-sm font-medium text-slate-800 truncate whitespace-nowrap">
                        {{ (form.country_code || '—').toUpperCase() }}
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-slate-400">
                      <i class="pi pi-briefcase text-xs"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                      <div class="text-[11px] uppercase tracking-wide text-slate-400">Experience</div>
                      <div class="text-sm font-medium text-slate-800 truncate whitespace-nowrap">
                        {{ form.years_experience != null ? form.years_experience + ' yrs' : 'Add years' }}
                      </div>
                    </div>
                  </div>
                </div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="rounded-3xl shadow-sm bg-white transition-all">
            <template #content>
              <div class="flex flex-col gap-3 px-4 py-4 md:px-5 md:py-4">
                <div class="flex flex-col gap-1.5">
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                      <span class="uppercase tracking-wide font-medium">Profile completeness</span>
                    </div>
                    <Tag :value="`${completeness}%`" severity="secondary" rounded />
                  </div>
                  <div class="flex items-center gap-3">
                    <ProgressBar
                      :value="completeness"
                      :showValue="false"
                      class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden"
                    />
                    <span class="text-xs font-semibold text-slate-700">
                      {{ completeness }}%
                    </span>
                  </div>
                  <p class="text-xs text-slate-500">
                    Complete your profile to improve matches with employers.
                  </p>
                </div>
                <!-- Completeness nudge checklist -->
                <div v-if="completeness < 100" class="flex flex-col gap-1.5 mt-1">
                  <span class="text-[11px] uppercase tracking-wide font-medium text-slate-500">Missing fields</span>
                  <div v-for="nudge in missingNudges" :key="nudge.field"
                    class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer hover:text-sky-600 transition-colors group"
                    @click="focusField(nudge.field)"
                  >
                    <i class="pi pi-circle text-[10px] text-slate-300 group-hover:text-sky-400"></i>
                    <span>{{ nudge.label }}</span>
                    <i class="pi pi-arrow-right text-[10px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                  </div>
                </div>
                <div v-else class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium mt-1">
                  <i class="pi pi-check-circle"></i> Profile complete
                </div>
                <div class="flex flex-col gap-0.5 text-xs text-slate-500">
                  <span class="uppercase tracking-wide font-medium">Account</span>
                  <span>Signed in as <span class="font-medium text-slate-700">{{ auth.email || "—" }}</span></span>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 mt-1">
                  <Button
                    label="Save changes"
                    icon="pi pi-check"
                    @click="saveProfile"
                    :loading="saving"
                    class="w-full sm:w-auto !px-5 !py-2.5 !rounded-full !bg-slate-900 hover:!bg-slate-800 active:!bg-slate-950 !border-slate-900 !text-sm shadow-sm hover:shadow-md transition-all"
                  />
                  <Button
                    label="Refresh"
                    icon="pi pi-refresh"
                    severity="secondary"
                    outlined
                    @click="fetchAll"
                    :loading="loading"
                    class="w-full sm:w-auto !px-4 !py-2.5 !rounded-full !text-slate-700 hover:!bg-slate-50 !text-sm"
                  />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mb-2">{{ error }}</Message>
        <Message v-if="success" severity="success" :closable="false" class="mb-2">{{ success }}</Message>

        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr,1.1fr] gap-3">
          <Card class="rounded-3xl shadow-sm bg-white transition-all">
                <template #title>
                  <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                      <span class="text-sm font-semibold text-slate-900">Profile details</span>
                      <span class="text-xs text-slate-500">Tell employers who you are and what you do.</span>
                    </div>
                  </div>
                </template>
                <template #content>
                  <div class="px-4 py-4 md:px-5 md:py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4 max-w-4xl mx-auto mt-2 profile-form-grid">
                      <div class="flex flex-col gap-1.5">
                        <label for="firstName" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">First name</label>
                        <InputText
                          id="firstName"
                          v-model="form.first_name"
                          class="!h-11 !px-3 !rounded-xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                        />
                      </div>
                      <div class="flex flex-col gap-1.5">
                        <label for="lastName" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Last name</label>
                        <InputText
                          id="lastName"
                          v-model="form.last_name"
                          class="!h-11 !px-3 !rounded-xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                        />
                      </div>
                      <div class="flex flex-col gap-1.5">
                        <label for="headline" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Headline</label>
                        <InputText
                          id="headline"
                          v-model="form.headline"
                          placeholder="e.g. ICU Nurse • RN"
                          class="!h-11 !px-3 !rounded-xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                        />
                        <small class="text-[11px] text-slate-400">Short title recruiters will see first.</small>
                      </div>
                      <div class="flex flex-col gap-1.5">
                        <label for="experience" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Years of experience</label>
                        <InputNumber
                          id="experience"
                          v-model="form.years_experience"
                          :min="0"
                          :max="80"
                          class="w-full !h-11 !px-3 !rounded-xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                        />
                        <small class="text-[11px] text-slate-400">Use whole years of professional experience.</small>
                      </div>
                      <div class="flex flex-col gap-1.5">
                        <label for="city" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">City</label>
                        <InputText
                          id="city"
                          v-model="form.city"
                          placeholder="Makati"
                          class="!h-11 !px-3 !rounded-xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                        />
                      </div>
                      <div class="flex flex-col gap-1.5">
                        <label for="state" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">State/Province</label>
                        <InputText
                          id="state"
                          v-model="form.state"
                          placeholder="NCR"
                          class="!h-11 !px-3 !rounded-xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                        />
                      </div>
                      <div class="flex flex-col gap-1.5">
                        <label for="country" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Country</label>
                        <Select
                          id="country"
                          v-model="form.country"
                          :options="countries"
                          optionLabel="label"
                          optionValue="value"
                          filter
                          placeholder="Select country"
                          class="!h-11 !rounded-xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                        />
                      </div>
                      <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label for="summary" class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Summary</label>
                        <Textarea
                          id="summary"
                          v-model="form.summary"
                          rows="5"
                          placeholder="Short professional summary…"
                          class="!rounded-2xl !border-none !bg-slate-50/80 hover:!bg-white focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all !min-h-[120px]"
                        />
                      </div>
                    </div>
                    <div class="flex justify-end mt-4 pt-3">
                      <Button
                        label="Save profile"
                        icon="pi pi-check"
                        @click="saveProfile"
                        :loading="saving"
                        class="w-full sm:w-auto !px-5 !py-2.5 !rounded-full !bg-slate-900 hover:!bg-slate-800 active:!bg-slate-950 !border-slate-900 !text-sm shadow-sm hover:shadow-md transition-all"
                      />
                    </div>
                  </div>
                </template>
              </Card>

              <Card class="rounded-2xl shadow-sm bg-white transition-all">
                <template #title>
                  <div class="flex justify-between items-center gap-3">
                    <div class="flex flex-col">
                      <span class="text-sm font-semibold text-slate-900">Documents</span>
                      <span class="text-xs text-slate-500">Upload your resume and important documents.</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <Tag
                        :value="`${docs.length} file${docs.length === 1 ? '' : 's'}`"
                        severity="secondary"
                        rounded
                        v-if="docs"
                        class="hidden sm:inline-flex"
                      />
                      <Button label="Add" icon="pi pi-plus" size="small" @click="openUpload" />
                    </div>
                  </div>
                </template>
                <template #content>
                  <div class="px-4 py-4 md:px-5 md:py-4">
                    <div v-if="docsLoading" class="py-4 text-center">
                      <i class="pi pi-spin pi-spinner text-2xl mb-2"></i>
                      <div>Loading…</div>
                    </div>

                    <div v-else>
                      <div
                        class="mb-3 rounded-2xl bg-slate-50/70 px-3 py-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 cursor-pointer hover:bg-slate-50 transition-colors"
                        @click="openUpload"
                      >
                        <div class="flex items-center gap-3">
                          <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-slate-400">
                            <i class="pi pi-upload text-base"></i>
                          </div>
                          <div class="flex flex-col">
                            <span class="text-sm font-medium text-slate-900">Upload documents</span>
                            <span class="text-xs text-slate-500">Drag & drop files here, or click Browse.</span>
                          </div>
                        </div>
                        <Button label="Browse" size="small" class="w-full sm:w-auto" @click.stop="openUpload" />
                      </div>

                      <div v-if="docs.length === 0" class="text-center py-6">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2">
                          <i class="pi pi-file text-slate-400 text-lg"></i>
                        </div>
                        <div class="font-medium text-slate-900 mb-1">No documents uploaded</div>
                        <div class="text-xs text-slate-600">Add your resume and licenses to speed up applications.</div>
                      </div>

                      <div v-else class="flex flex-col gap-3">
                      <div
                        v-for="d in docs"
                        :key="d.id"
                        class="bg-white rounded-xl p-4 shadow-sm flex flex-col gap-2 border"
                        :class="d.status === 'active' ? 'border-emerald-200' : 'border-slate-100 opacity-75'"
                      >
                          <div class="flex justify-between items-start gap-2">
                            <div class="flex items-start gap-3 min-w-0">
                              <div class="mt-0.5 w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                                <i class="pi pi-file text-xs"></i>
                              </div>
                              <div class="flex flex-col gap-1 min-w-0">
                                <div class="font-semibold text-slate-900 truncate flex items-center gap-2">
                                  {{ d.file_name || "Document" }}
                                  <span v-if="d.status === 'active' && d.doc_type === 'resume'"
                                    class="text-[10px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full font-semibold">Active</span>
                                  <span v-else-if="d.doc_type === 'resume'"
                                    class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full">Inactive</span>
                                </div>
                                <div class="flex items-center flex-wrap gap-2 text-[11px] text-slate-600">
                                  <Tag :value="d.doc_type || '—'" severity="secondary" />
                                  <span>•</span>
                                  <span>{{ bytesToLabel(d.file_size_bytes) }}</span>
                                  <span v-if="d.created_at">• {{ formatDate(d.created_at) }}</span>
                                </div>
                              </div>
                            </div>
                            <div class="flex items-center gap-1">
                              <div class="hidden sm:flex items-center gap-1">
                                <!-- Set as active resume -->
                                <Button
                                  v-if="d.doc_type === 'resume' && d.status !== 'active'"
                                  label="Set active"
                                  size="small"
                                  severity="success"
                                  outlined
                                  class="!text-xs !py-1"
                                  :loading="settingActiveId === d.id"
                                  @click.stop="setActiveResume(d)"
                                />
                                <Button
                                  v-if="d.mime_type === 'application/pdf' || (d.file_name || '').endsWith('.pdf')"
                                  icon="pi pi-eye"
                                  text
                                  rounded
                                  size="small"
                                  aria-label="Preview"
                                  @click.stop="previewDoc(d)"
                                />
                                <a
                                  v-if="d.file_url"
                                  :href="d.file_url"
                                  target="_blank"
                                  rel="noreferrer"
                                  class="no-underline"
                                >
                                  <Button icon="pi pi-external-link" text rounded size="small" aria-label="Open" />
                                </a>
                                <Button
                                  icon="pi pi-trash"
                                  text
                                  rounded
                                  severity="danger"
                                  size="small"
                                  :loading="removingId === d.id"
                                  @click="confirmRemoveDoc(d)"
                                  aria-label="Remove"
                                />
                              </div>
                              <Button
                                class="flex sm:hidden"
                                icon="pi pi-ellipsis-h"
                                text
                                rounded
                                size="small"
                                @click="openDocActions(d)"
                                aria-label="Actions"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>
            </div>
          </div>
        </div>

        <!-- Portfolio Links -->
        <div class="grid grid-cols-1 gap-3">
          <Card class="rounded-2xl shadow-sm bg-white">
            <template #title>
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-sm font-semibold text-slate-900">Portfolio & Links</span>
                  <p class="text-xs text-slate-500 mt-0.5">Add links to your portfolio, GitHub, LinkedIn, or personal site.</p>
                </div>
              </div>
            </template>
            <template #content>
              <div class="px-4 py-2 space-y-3">
                <div class="flex gap-2">
                  <InputText v-model="newPortfolioUrl" placeholder="https://github.com/yourname" class="flex-1 !rounded-xl !text-sm" @keyup.enter="addPortfolioLink" />
                  <Button icon="pi pi-plus" @click="addPortfolioLink" outlined />
                </div>
                <div v-if="!portfolioLinks.length" class="text-sm text-slate-400 py-2">No links added yet.</div>
                <div v-else class="space-y-2">
                  <div v-for="(link, idx) in portfolioLinks" :key="idx"
                    class="flex items-center justify-between gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <a :href="link" target="_blank" rel="noreferrer" class="text-sm text-blue-600 hover:underline truncate flex-1">{{ link }}</a>
                    <Button icon="pi pi-times" text rounded severity="danger" size="small" @click="removePortfolioLink(idx)" />
                  </div>
                </div>
                <div class="flex justify-end pt-1">
                  <Button label="Save links" icon="pi pi-check" size="small" :loading="savingPortfolio" @click="savePortfolioLinks"
                    class="!rounded-full !bg-slate-900 hover:!bg-slate-800 !border-slate-900 !text-sm" />
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Document Preview Dialog -->
        <Dialog v-model:visible="showPreview" modal :header="'Preview'" :style="{ width: '90vw', maxWidth: '900px' }" :contentStyle="{ padding: 0 }" @hide="closePreview">
          <iframe
            v-if="previewUrl"
            :src="previewUrl"
            class="w-full"
            style="height: 80vh; border: none;"
            title="Document Preview"
          ></iframe>
        </Dialog>

        <Dialog v-model:visible="showUpload" header="Add document" modal :style="{ width: '500px' }" class="p-fluid">          <template #header>
            <div class="flex flex-column">
              <span class="text-xl font-bold">Add document</span>
              <span class="text-sm text-600 mt-1">Creates via <span class="font-mono">POST /api/documents</span></span>
            </div>
          </template>

          <Message v-if="uploadError" severity="error" :closable="false" class="mb-3">{{ uploadError }}</Message>

          <div class="field">
            <label for="docType" class="font-semibold">Document type</label>
            <Select id="docType" v-model="upload.doc_type" :options="allowedDocTypes" placeholder="Select type" class="w-full" />
          </div>

          <div class="field">
            <label for="fileUpload" class="font-semibold">File</label>
            <input
              ref="fileEl"
              type="file"
              class="w-full p-2 rounded-md bg-slate-50"
              accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
              @change="onPickFile"
            />
            <div v-if="upload.fileName" class="text-sm text-600 mt-2">
              Selected: <span class="font-mono font-bold">{{ upload.fileName }}</span>
              <span v-if="upload.fileSize"> ({{ bytesToLabel(upload.fileSize) }})</span>
            </div>
          </div>

          <template #footer>
            <Button label="Cancel" icon="pi pi-times" text @click="closeUpload" :disabled="uploading" />
            <Button label="Save" icon="pi pi-check" @click="confirmUpload" :loading="uploading" autofocus />
          </template>
        </Dialog>
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
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import { countries } from '@/lib/countries';

const meName = ref("ME");

const loading = ref(false);
const saving = ref(false);
const docsLoading = ref(false);
const uploading = ref(false);
const removingId = ref(null);
const settingActiveId = ref(null);

async function setActiveResume(doc) {
  settingActiveId.value = doc.id
  try {
    await api.patch(`/documents/${doc.id}/set-active`)
    // Update local state — deactivate all resumes, activate this one
    docs.value.forEach(d => {
      if (d.doc_type === 'resume') d.status = d.id === doc.id ? 'active' : 'inactive'
    })
    await toast('success', 'Active resume updated')
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Failed', text: e?.response?.data?.message || 'Could not update' })
  } finally {
    settingActiveId.value = null
  }
}

const error = ref("");
const success = ref("");

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

// Portfolio links
const portfolioLinks = ref([])
const newPortfolioUrl = ref('')
const savingPortfolio = ref(false)

function addPortfolioLink() {
  const url = newPortfolioUrl.value.trim()
  if (!url) return
  if (!portfolioLinks.value.includes(url)) portfolioLinks.value.push(url)
  newPortfolioUrl.value = ''
}

function removePortfolioLink(idx) {
  portfolioLinks.value.splice(idx, 1)
}

async function savePortfolioLinks() {
  savingPortfolio.value = true
  try {
    await api.put('/me/applicant', { portfolio_links: portfolioLinks.value })
    await toast('success', 'Portfolio links saved')
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Failed', text: e?.__payload?.message || e?.message || 'Save failed' })
  } finally { savingPortfolio.value = false }
}

const docs = ref([]);

const allowedDocTypes = ["resume", "license", "certificate", "id_document", "other"];

const showUpload = ref(false);
const uploadError = ref("");
const fileEl = ref(null);
const upload = ref({
  doc_type: "resume",
  file: null,
  fileName: "",
  fileSize: 0,
});

function unwrap(resData) {
  return resData?.data ?? resData;
}

function buildAvatarUrl(raw) {
  if (!raw) return null;
  const v = String(raw);
  if (/^https?:\/\//i.test(v)) return v;
  if (v.startsWith("/uploads/")) return v;
  if (v.startsWith("uploads/")) return `/${v}`;
  if (v.startsWith("/storage/")) return v;
  if (v.startsWith("storage/")) return `/${v}`;
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
    form.value.first_name,
    form.value.last_name,
    form.value.headline,
    form.value.summary,
    String(form.value.years_experience ?? ""),
    form.value.country,
    form.value.state,
    form.value.city,
  ];
  const filled = fields.filter((v) => String(v || "").trim().length > 0).length;
  return Math.round((filled / fields.length) * 100);
});

const nudgeFields = [
  { field: 'firstName', key: 'first_name', label: 'Add your first name' },
  { field: 'lastName', key: 'last_name', label: 'Add your last name' },
  { field: 'headline', key: 'headline', label: 'Add a professional headline' },
  { field: 'summary', key: 'summary', label: 'Write a short summary' },
  { field: 'experience', key: 'years_experience', label: 'Add years of experience' },
  { field: 'country', key: 'country', label: 'Select your country' },
  { field: 'state', key: 'state', label: 'Add your state/province' },
  { field: 'city', key: 'city', label: 'Add your city' },
];

const missingNudges = computed(() =>
  nudgeFields.filter(n => !String(form.value[n.key] ?? '').trim())
);

function focusField(fieldId) {
  const el = document.getElementById(fieldId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => el.focus(), 300);
  }
}

// Document preview
const showDocPreview = ref(false);
const previewDocItem = ref(null);
const docPreviewLoading = ref(false);
const docPreviewError = ref('');
const docPreviewBlobUrl = ref('');

async function openDocPreview(d) {
  previewDocItem.value = d;
  showDocPreview.value = true;
  docPreviewLoading.value = true;
  docPreviewError.value = '';
  docPreviewBlobUrl.value = '';
  try {
    const token = localStorage.getItem('auth_token') || '';
    const res = await fetch(`/api/documents/${d.id}/stream`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (!res.ok) throw new Error('Could not load document');
    const blob = await res.blob();
    docPreviewBlobUrl.value = URL.createObjectURL(blob);
  } catch (e) {
    docPreviewError.value = e?.message || 'Failed to load preview.';
  } finally {
    docPreviewLoading.value = false;
  }
}

function closeDocPreview() {
  if (docPreviewBlobUrl.value) {
    URL.revokeObjectURL(docPreviewBlobUrl.value);
    docPreviewBlobUrl.value = '';
  }
  previewDocItem.value = null;
}

function formatDate(v) {
  if (!v) return "N/A";
  const d = new Date(v);
  return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleString();
}

function bytesToLabel(n) {
  const v = Number(n || 0);
  if (!v) return "—";
  const kb = v / 1024;
  if (kb < 1024) return `${kb.toFixed(1)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}

function toast(icon, title) {
  return Swal.fire({
    toast: true,
    position: "top-end",
    icon,
    title,
    showConfirmButton: false,
    timer: 1600,
    timerProgressBar: true,
  });
}

async function fetchMe() {
  const res = await api.get("/me");
  const u = unwrap(res.data);

  meName.value = u?.full_name || u?.name || u?.user?.name || "ME";

  const avatarRaw = u?.avatar_url || u?.avatar || null;
  const avatarUrl = buildAvatarUrl(avatarRaw);

  auth.value = {
    id: u?.id ?? null,
    role: u?.role ?? null,
    email: u?.email ?? null,
    avatar: avatarUrl,
  };

  try {
    const prevRaw = localStorage.getItem("auth_user") || "{}";
    const prev = JSON.parse(prevRaw);
    const merged = {
      ...prev,
      id: auth.value.id,
      role: auth.value.role,
      email: auth.value.email,
      avatar: auth.value.avatar,
      avatar_url: auth.value.avatar,
    };
    localStorage.setItem("auth_user", JSON.stringify(merged));
    window.dispatchEvent(new Event("auth:changed"));
  } catch {}

  const ap = u?.applicant_profile || u?.applicantProfile || null;
  form.value = {
    first_name: ap?.first_name ?? "",
    last_name: ap?.last_name ?? "",
    headline: ap?.headline ?? "",
    summary: ap?.summary ?? "",
    years_experience: ap?.years_experience ?? null,
    country: ap?.country ?? ap?.country_code ?? "",
    state: ap?.state ?? "",
    city: ap?.city ?? "",
    open_to_work: ap?.open_to_work ?? false,
  };
  portfolioLinks.value = Array.isArray(ap?.portfolio_links) ? [...ap.portfolio_links] : [];
}

async function fetchDocs() {
  docsLoading.value = true;
  try {
    const res = await api.get("/documents");
    const arr = unwrap(res.data);
    docs.value = Array.isArray(arr) ? arr : Array.isArray(arr?.data) ? arr.data : [];
  } finally {
    docsLoading.value = false;
  }
}

async function fetchAll() {
  loading.value = true;
  error.value = "";
  success.value = "";
  try {
    await fetchMe();
    await fetchDocs();
  } catch (e) {
    error.value = e?.__payload?.message || e?.message || "Request failed";
    await Swal.fire({ icon: "error", title: "Request failed", text: error.value });
  } finally {
    loading.value = false;
  }
}

async function saveProfile() {
  saving.value = true;
  error.value = "";
  success.value = "";
  try {
    const payload = {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      headline: form.value.headline,
      summary: form.value.summary,
      years_experience: form.value.years_experience,
      country: form.value.country,
      state: form.value.state,
      city: form.value.city,
    };

    await api.put("/me/applicant", payload);
    success.value = "Saved.";
    await fetchMe();
    await toast("success", "Profile saved");
  } catch (e) {
    const errs = e?.__payload?.errors;
    if (errs && typeof errs === "object") {
      const firstKey = Object.keys(errs)[0];
      error.value = errs[firstKey]?.[0] || e?.__payload?.message || "Save failed";
    } else {
      error.value = e?.__payload?.message || e?.message || "Save failed";
    }
    await Swal.fire({ icon: "error", title: "Save failed", text: error.value });
  } finally {
    saving.value = false;
  }
}

/* Upload modal */
function openUpload() {
  uploadError.value = "";
  upload.value = { doc_type: "resume", file: null, fileName: "", fileSize: 0 };
  showUpload.value = true;
}

function closeUpload() {
  showUpload.value = false;
  uploadError.value = "";
  if (fileEl.value) fileEl.value.value = "";
}

function onPickFile(ev) {
  const f = ev?.target?.files?.[0] || null;
  upload.value.file = f;
  upload.value.fileName = f?.name || "";
  upload.value.fileSize = f?.size || 0;
}

async function confirmUpload() {
  uploadError.value = "";

  if (!upload.value.file) {
    uploadError.value = "Please choose a file to upload.";
    // await Swal.fire({ icon: "warning", title: "No file selected", text: uploadError.value });
    return;
  }

  const result = await Swal.fire({
    icon: "question",
    title: "Upload this document?",
    html: `
      <div style="text-align:left">
        <div><b>Type:</b> ${String(upload.value.doc_type)}</div>
        <div><b>File:</b> ${String(upload.value.fileName || "")}</div>
        <div><b>Size:</b> ${bytesToLabel(upload.value.fileSize)}</div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: "Upload",
    cancelButtonText: "Cancel",
  });

  if (!result.isConfirmed) return;

  await submitUpload();
}

async function submitUpload() {
  uploadError.value = "";
  uploading.value = true;

  try {
    const fd = new FormData();
    fd.append("doc_type", upload.value.doc_type);
    fd.append("file", upload.value.file);

    await api.post("/documents", fd);

    await fetchDocs();
    showUpload.value = false;
    success.value = "Uploaded.";
    await toast("success", "Document uploaded");
  } catch (e) {
    const errs = e?.__payload?.errors;
    if (errs && typeof errs === "object") {
      const firstKey = Object.keys(errs)[0];
      uploadError.value = errs[firstKey]?.[0] || e?.__payload?.message || "Upload failed";
    } else {
      uploadError.value = e?.__payload?.message || e?.message || "Upload failed";
    }
    await Swal.fire({ icon: "error", title: "Upload failed", text: uploadError.value });
  } finally {
    uploading.value = false;
    if (fileEl.value) fileEl.value.value = "";
  }
}

async function openDocActions(d) {
  if (!d) return;

  const result = await Swal.fire({
    icon: "question",
    title: d.file_name || "Document",
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: "Remove",
    denyButtonText: "Preview",
    cancelButtonText: "Cancel",
  });

  if (result.isDenied) {
    previewDoc(d);
  } else if (result.isConfirmed) {
    await removeDoc(d);
  }
}

const previewUrl = ref(null);
const showPreview = ref(false);

async function previewDoc(d) {
  if (!d?.id) return;
  try {
    const response = await api.get(`/documents/${d.id}/stream`, {
      responseType: 'blob'
    });
    const blob = new Blob([response.data], { type: response.data.type || 'application/pdf' });
    previewUrl.value = window.URL.createObjectURL(blob);
    showPreview.value = true;
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Preview failed', text: 'Could not load document preview.' });
  }
}

function closePreview() {
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = null;
  showPreview.value = false;
}

async function confirmRemoveDoc(d) {
  if (!d?.id) return;

  const result = await Swal.fire({
    icon: "warning",
    title: "Remove this document?",
    text: d?.file_name || "Document",
    showCancelButton: true,
    confirmButtonText: "Remove",
    cancelButtonText: "Cancel",
  });

  if (!result.isConfirmed) return;

  await removeDoc(d);
}

async function removeDoc(d) {
  removingId.value = d.id;
  error.value = "";
  success.value = "";
  try {
    await api.delete(`/documents/${d.id}`);
    await fetchDocs();
    success.value = "Removed.";
    await toast("success", "Document removed");
  } catch (e) {
    error.value = e?.__payload?.message || e?.message || "Remove failed";
    await Swal.fire({ icon: "error", title: "Remove failed", text: error.value });
  } finally {
    removingId.value = null;
  }
}

onMounted(() => {
  try {
    const cached = getCachedUser();
    if (cached) {
      auth.value = {
        id: cached.id ?? null,
        role: cached.role ?? null,
        email: cached.email ?? null,
        avatar: buildAvatarUrl(cached.avatar_url || cached.avatar || null),
      };
    }
  } catch {}
  fetchAll();
});
</script>

<style scoped>
.profile-page :deep(.p-card) {
  border-radius: 0.5rem;
  border-width: 1px !important;
  border-style: solid !important;
  border-color: #e5e7eb !important;
  box-shadow: none !important;
}

.p-progressbar {
  border-radius: 9999px;
  overflow: hidden;
  background-color: #e0f2fe;
}

:deep(.p-progressbar-value) {
  background: linear-gradient(to right, #0ea5e9, #6366f1) !important;
  transition: width 0.25s ease-out;
}

.profile-page :deep(.p-inputtext),
.profile-page :deep(.p-inputnumber-input),
.profile-page :deep(.p-inputtextarea),
.profile-page :deep(input),
.profile-page :deep(textarea) {
  border-width: 1px !important;
  border-style: solid !important;
  border-color: #e5e7eb !important;
  box-shadow: none !important;
  outline: none !important;
}
</style>
