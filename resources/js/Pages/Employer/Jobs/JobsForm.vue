<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import Swal from "sweetalert2";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import DatePicker from "primevue/datepicker";
import Message from "primevue/message";
import { http } from "@/lib/http";
import { countries } from "@/lib/countries";

const route = useRoute();
const router = useRouter();

const id = computed(() => route.params.id || null);
const editing = computed(() => Boolean(id.value));

const loading = ref(false);
const error = ref("");
const exportingReport = ref(false);

async function downloadPipelineReport() {
    if (!editing.value || !id.value) return;
    exportingReport.value = true;
    try {
        const res = await http.get(`/jobs/${id.value}/pipeline-report`, { responseType: 'blob' });
        const url = URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }));
        const a = document.createElement('a');
        a.href = url; a.download = `pipeline-job-${id.value}.csv`; a.click();
        URL.revokeObjectURL(url);
    } catch {} finally { exportingReport.value = false; }
}

const form = ref({
    title: "",
    description: "",
    employment_type: "full_time",
    work_mode: "on_site",
    country: "",
    state: "",
    city: "",
    salary_min: "",
    salary_max: "",
    salary_type: "annually",
    salary_currency: "USD",
    closes_at: null,
});

// Computed formatted display values for salary fields
const salaryMinDisplay = computed({
    get() { return formatNumber(form.value.salary_min); },
    set(val) { form.value.salary_min = stripNumber(val); },
});
const salaryMaxDisplay = computed({
    get() { return formatNumber(form.value.salary_max); },
    set(val) { form.value.salary_max = stripNumber(val); },
});

// Format a raw number string with commas and 2 decimals: "10000" -> "10,000.00"
function formatNumber(raw) {
    const stripped = String(raw || "").replace(/[^0-9.]/g, "");
    if (stripped === "" || stripped === ".") return "";
    // Allow partial typing: don't format if user is still typing decimals
    const num = parseFloat(stripped);
    if (isNaN(num)) return "";
    // If ends with dot or has incomplete decimal, show as-is with commas on integer part
    if (stripped.endsWith(".")) {
        const parts = stripped.split(".");
        return Number(parts[0] || "0").toLocaleString("en-US") + ".";
    }
    const parts = stripped.split(".");
    const intPart = Number(parts[0] || "0").toLocaleString("en-US");
    if (parts.length === 2) {
        // Pad or truncate decimal to 2
        let dec = parts[1].slice(0, 2);
        return intPart + "." + dec;
    }
    return intPart + ".00";
}

// Strip formatting to get raw number: "10,000.00" -> "10000"
function stripNumber(formatted) {
    return String(formatted || "").replace(/[^0-9.]/g, "");
}

// Duplicate detection
const duplicates = ref([]);
let dupTimer = null;
watch(() => form.value.title, (val) => {
    clearTimeout(dupTimer);
    duplicates.value = [];
    if (!editing.value && val.trim().length >= 5) {
        dupTimer = setTimeout(async () => {
            try {
                const res = await http.get('/jobs/duplicate-check', { params: { title: val.trim() } });
                duplicates.value = res.data?.data?.duplicates || [];
            } catch {}
        }, 600);
    }
});

const employmentTypes = [
    { label: 'Full-Time/Part-Time', value: 'full_time_part_time' },
    { label: 'Contract/Temporary', value: 'contract_temporary' },
    { label: 'Internship', value: 'internship' }
];

const workModes = [
    { label: 'On-site', value: 'on_site' },
    { label: 'Hybrid', value: 'hybrid' },
    { label: 'Remote', value: 'remote' }
];

function validate() {
    const t = String(form.value.title || "").trim();
    const d = String(form.value.description || "").trim();

    if (t.length < 5) return "Title must be at least 5 characters.";
    if (d.length < 30) return "Description must be at least 30 characters.";
    if (!form.value.employment_type) return "Employment type is required.";
    if (!form.value.work_mode) return "Work mode is required.";

    return "";
}

async function load() {
    if (!editing.value) return;

    loading.value = true;
    error.value = "";

    try {
        const res = await http.get(`/jobs/${id.value}`);
        const j = res.data?.data ?? res.data ?? {};

        form.value.title = j.title || "";
        form.value.description = j.description || "";

        // backend fields
        form.value.employment_type = j.employment_type || "full_time";
        form.value.work_mode = j.work_mode || "on_site";
        form.value.country = j.country || j.country_code || "";
        form.value.state = j.state || "";
        form.value.city = j.city || "";
        form.value.salary_min = j.salary_min ?? "";
        form.value.salary_max = j.salary_max ?? "";
        form.value.salary_type = j.salary_type || "annually";
        form.value.salary_currency = j.salary_currency || "USD";
        form.value.closes_at = j.closes_at ? new Date(j.closes_at) : null;
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to load job";
    } finally {
        loading.value = false;
    }
}

async function save() {
    error.value = "";
    const msg = validate();
    if (msg) {
        error.value = msg;
        return;
    }

    loading.value = true;

    const payload = {
        title: String(form.value.title || "").trim(),
        description: String(form.value.description || "").trim(),
        employment_type: form.value.employment_type,
        work_mode: form.value.work_mode,
        country: form.value.country,
        state: String(form.value.state || "").trim() || null,
        city: String(form.value.city || "").trim() || null,
        salary_min: form.value.salary_min !== "" ? Number(form.value.salary_min) : null,
        salary_max: form.value.salary_max !== "" ? Number(form.value.salary_max) : null,
        salary_type: form.value.salary_type || null,
        salary_currency: form.value.salary_currency || null,
        closes_at: form.value.closes_at ? new Date(form.value.closes_at).toISOString().split('T')[0] : null,
    };

    try {
        let res;
        if (editing.value) res = await http.put(`/jobs/${id.value}`, payload);
        else res = await http.post(`/jobs`, payload);

        const job = res.data?.data ?? res.data ?? {};
        const jobId = job?.id || id.value;

        // Show popup for new jobs to choose publish or draft
        if (!editing.value) {
            const choice = await Swal.fire({
                icon: 'success',
                title: 'Job Posted!',
                html: `
                    <p class="text-slate-600 mb-4">Your job <strong>"${job.title}"</strong> has been created.</p>
                    <p class="text-sm text-slate-500">Would you like to publish it now to make it visible to candidates?</p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Yes, Publish Now',
                cancelButtonText: 'Save as Draft',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            });

            if (choice.isConfirmed) {
                // Publish the job
                try {
                    await http.post(`/jobs/${jobId}/publish`);
                    Swal.fire({
                        icon: 'success',
                        title: 'Published!',
                        text: 'Your job is now visible to candidates.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Job saved as draft',
                        text: e?.response?.data?.message || 'Could not publish immediately. You can publish it later from your jobs list.',
                    });
                }
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'Saved as Draft',
                    text: 'You can publish this job anytime from your jobs list.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            }
        }

        router.push({ name: "employer.jobs.view", params: { id: jobId } });
    } catch (e) {
        const data = e?.response?.data;
        const base = data?.message || e?.message || "Save failed";

        // show Laravel validation errors clearly
        if (data?.errors && typeof data.errors === "object") {
            const flat = Object.values(data.errors).flat().join(" ");
            error.value = `${base} ${flat}`.trim();
        } else {
            error.value = base;
        }
    } finally {
        loading.value = false;
    }
}

// ── Job template auto-fill ──────────────────────────────────
const templates = ref([])
const selectedTemplate = ref(null)

const templateOptions = computed(() =>
  templates.value.map(t => ({ label: t.title, value: t.id }))
)

async function loadTemplates() {
  if (editing.value) return
  try {
    const res = await http.get('/job-templates')
    templates.value = res.data?.data ?? res.data ?? []
  } catch {}
}

function applyTemplate(id) {
  const t = templates.value.find(x => x.id === id)
  if (!t) return
  form.value.title          = t.title          || form.value.title
  form.value.description    = t.description    || form.value.description
  form.value.employment_type= t.employment_type|| form.value.employment_type
  form.value.work_mode      = t.work_mode      || form.value.work_mode
  form.value.country        = t.country        || form.value.country
  form.value.state          = t.state          || form.value.state
  form.value.city           = t.city           || form.value.city
  form.value.salary_min     = t.salary_min     ?? form.value.salary_min
  form.value.salary_max     = t.salary_max     ?? form.value.salary_max
  form.value.salary_type    = t.salary_type    || form.value.salary_type
  form.value.salary_currency= t.salary_currency|| form.value.salary_currency
}

onMounted(() => { load(); loadTemplates() })
</script>

<template>
    <AppLayout>
        <div class="w-full max-w-5xl mx-auto px-4 py-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">
                        <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                            {{ editing ? "Edit Job Posting" : "Create New Job" }}
                        </span>
                    </h1>
                    <p class="text-slate-600 text-lg">
                        {{ editing ? "Update your job listing details below." : "Fill in the details to reach qualified candidates." }}
                    </p>
                </div>
                <Button label="Back to Jobs" icon="pi pi-arrow-left" outlined class="!border-slate-300 !text-slate-600 hover:!bg-slate-50 hover:!text-slate-900" @click="router.push({ name: 'employer.jobs' })" />
            </div>

            <!-- Error Message -->
            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="error" class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 flex items-start gap-3 text-red-700">
                    <i class="pi pi-exclamation-circle mt-0.5 text-xl"></i>
                    <div>
                        <div class="font-medium">Please check the following errors:</div>
                        <div class="mt-1 text-sm opacity-90">{{ error }}</div>
                    </div>
                </div>
            </transition>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8">
                    <!-- Template auto-fill (only on create) -->
                    <div v-if="!editing && templates.length" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-center gap-3">
                        <i class="pi pi-copy text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-800">Auto-fill from a previous job:</span>
                        <Select v-model="selectedTemplate" :options="templateOptions" optionLabel="label" optionValue="value"
                            placeholder="Select a template..." class="flex-1 max-w-xs" @update:modelValue="applyTemplate" />
                    </div>
                    <form @submit.prevent="save" class="space-y-8">
                        <!-- Basic Info Section -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <i class="pi pi-briefcase text-blue-600"></i>
                                Basic Information
                            </h3>
                            <div class="grid grid-cols-1 gap-6">
                                <!-- Title -->
                                <div class="flex flex-col gap-2">
                                    <label for="title" class="font-medium text-slate-700">Job Title <span class="text-red-500">*</span></label>
                                    <InputText id="title" v-model="form.title" placeholder="e.g. Senior ICU Nurse" :disabled="loading" class="w-full !border-slate-300 focus:!border-blue-500 focus:!ring-blue-500/20" />
                                    <!-- Duplicate warning -->
                                    <div v-if="duplicates.length" class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                                        <i class="pi pi-exclamation-triangle text-amber-500 mt-0.5 flex-shrink-0"></i>
                                        <div>
                                            <span class="font-semibold">Similar active jobs found:</span>
                                            <span v-for="d in duplicates" :key="d.id" class="ml-1 underline cursor-pointer hover:text-amber-900" @click="$router.push({ name: 'employer.jobs.view', params: { id: d.id } })">{{ d.title }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Employment Details -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <i class="pi pi-clock text-blue-600"></i>
                                Employment Details
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label for="employment_type" class="font-medium text-slate-700">Employment Type <span class="text-red-500">*</span></label>
                                    <Select id="employment_type" v-model="form.employment_type" :options="employmentTypes" optionLabel="label" optionValue="value" :disabled="loading" placeholder="Select type" class="w-full !border-slate-300" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="work_mode" class="font-medium text-slate-700">Work Mode <span class="text-red-500">*</span></label>
                                    <Select id="work_mode" v-model="form.work_mode" :options="workModes" optionLabel="label" optionValue="value" :disabled="loading" placeholder="Select mode" class="w-full !border-slate-300" />
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Location -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <i class="pi pi-map-marker text-blue-600"></i>
                                Location
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label for="country" class="font-medium text-slate-700">Country</label>
                                    <Select
                                      id="country"
                                      v-model="form.country"
                                      :options="countries"
                                      optionLabel="label"
                                      optionValue="value"
                                      filter
                                      :disabled="loading"
                                      placeholder="Select Country"
                                      class="w-full !border-slate-300"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="state" class="font-medium text-slate-700">State / Province</label>
                                    <InputText id="state" v-model="form.state" placeholder="e.g. NCR, California" :disabled="loading" class="w-full !border-slate-300" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="city" class="font-medium text-slate-700">City</label>
                                    <InputText id="city" v-model="form.city" placeholder="e.g. Quezon City" :disabled="loading" class="w-full !border-slate-300" />
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Salary -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <i class="pi pi-dollar text-blue-600"></i>
                                Salary Range <span class="text-sm font-normal text-slate-400 ml-1">(optional)</span>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label for="salary_min" class="font-medium text-slate-700">Minimum</label>
                                    <InputText id="salary_min" v-model="salaryMinDisplay" type="text" placeholder="e.g. 10,000.00" :disabled="loading" class="w-full !border-slate-300" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="salary_max" class="font-medium text-slate-700">Maximum</label>
                                    <InputText id="salary_max" v-model="salaryMaxDisplay" type="text" placeholder="e.g. 20,000.00" :disabled="loading" class="w-full !border-slate-300" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="salary_type" class="font-medium text-slate-700">Annually / Rate per hour</label>
                                    <Select
                                        id="salary_type"
                                        v-model="form.salary_type"
                                        :options="[{label:'Annually',value:'annually'},{label:'Rate per hour',value:'hourly'}]"
                                        optionLabel="label"
                                        optionValue="value"
                                        :disabled="loading"
                                        class="w-full !border-slate-300"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="salary_currency" class="font-medium text-slate-700">Currency</label>
                                    <Select
                                        id="salary_currency"
                                        v-model="form.salary_currency"
                                        :options="[{label:'USD ($)',value:'USD'},{label:'PHP (₱)',value:'PHP'},{label:'EUR (€)',value:'EUR'},{label:'GBP (£)',value:'GBP'}]"
                                        optionLabel="label"
                                        optionValue="value"
                                        :disabled="loading"
                                        class="w-full !border-slate-300"
                                    />
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100" />
                        <!-- Application Deadline -->
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <i class="pi pi-calendar text-blue-600"></i>
                                Application Deadline <span class="text-sm font-normal text-slate-400 ml-1">(optional)</span>
                            </h3>
                            <div class="flex flex-col gap-2 max-w-xs">
                                <label for="closes_at" class="font-medium text-slate-700">Closes on</label>
                                <DatePicker id="closes_at" v-model="form.closes_at" :disabled="loading"
                                    placeholder="No deadline" dateFormat="yy-mm-dd" showButtonBar
                                    class="w-full !border-slate-300" />
                                <p class="text-xs text-slate-400">Job will be automatically archived after this date.</p>
                            </div>
                        </div>

                        <hr class="border-slate-100" />
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                                <i class="pi pi-align-left text-blue-600"></i>
                                Job Description
                            </h3>
                            <div class="flex flex-col gap-2">
                                <label for="description" class="font-medium text-slate-700">Description <span class="text-red-500">*</span></label>
                                <Textarea id="description" v-model="form.description" rows="12" :disabled="loading" placeholder="Describe the role, responsibilities, requirements, and benefits..." class="w-full !border-slate-300 focus:!border-blue-500 focus:!ring-blue-500/20" />
                                <div class="flex justify-between text-sm text-slate-500">
                                    <span>Minimum 30 characters</span>
                                    <span>{{ form.description.length }} chars</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                            <Button v-if="editing" label="Pipeline Report" icon="pi pi-download" severity="secondary" outlined
                                :loading="exportingReport" @click="downloadPipelineReport" />
                            <Button label="Cancel" text class="!text-slate-600 hover:!bg-slate-50" @click="router.push({ name: 'employer.jobs' })" :disabled="loading" />
                            <Button type="submit" :label="loading ? 'Saving...' : (editing ? 'Save Changes' : 'Publish Job')" icon="pi pi-check" :loading="loading" class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
