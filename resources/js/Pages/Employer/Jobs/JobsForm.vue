<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import Message from "primevue/message";
import { http } from "@/lib/http";
import { countries } from "@/lib/countries";

const route = useRoute();
const router = useRouter();

const id = computed(() => route.params.id || null);
const editing = computed(() => Boolean(id.value));

const loading = ref(false);
const error = ref("");

const form = ref({
    title: "",
    description: "",
    employment_type: "full_time",
    work_mode: "on_site",
    country: "",
    city: "",
    salary_min: "",
    salary_max: "",
    salary_currency: "USD",
});

const employmentTypes = [
    { label: 'Full-time', value: 'full_time' },
    { label: 'Part-time', value: 'part_time' },
    { label: 'Contract', value: 'contract' },
    { label: 'Temporary', value: 'temporary' },
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
        form.value.city = j.city || "";
        form.value.salary_min = j.salary_min ?? "";
        form.value.salary_max = j.salary_max ?? "";
        form.value.salary_currency = j.salary_currency || "USD";
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
        city: String(form.value.city || "").trim() || null,
        salary_min: form.value.salary_min !== "" ? Number(form.value.salary_min) : null,
        salary_max: form.value.salary_max !== "" ? Number(form.value.salary_max) : null,
        salary_currency: form.value.salary_currency || null,
    };

    try {
        let res;
        if (editing.value) res = await http.put(`/jobs/${id.value}`, payload);
        else res = await http.post(`/jobs`, payload);

        const job = res.data?.data ?? res.data ?? {};
        const jobId = job?.id || id.value;

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

onMounted(load);
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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="flex flex-col gap-2">
                                    <label for="salary_min" class="font-medium text-slate-700">Minimum</label>
                                    <InputText id="salary_min" v-model="form.salary_min" type="number" min="0" placeholder="e.g. 30000" :disabled="loading" class="w-full !border-slate-300" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="salary_max" class="font-medium text-slate-700">Maximum</label>
                                    <InputText id="salary_max" v-model="form.salary_max" type="number" min="0" placeholder="e.g. 60000" :disabled="loading" class="w-full !border-slate-300" />
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
                            <Button label="Cancel" text class="!text-slate-600 hover:!bg-slate-50" @click="router.push({ name: 'employer.jobs' })" :disabled="loading" />
                            <Button type="submit" :label="loading ? 'Saving...' : (editing ? 'Save Changes' : 'Publish Job')" icon="pi pi-check" :loading="loading" class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
