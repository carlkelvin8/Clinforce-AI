<script setup>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import api from '@/lib/api';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import FileUpload from 'primevue/fileupload';
import Checkbox from 'primevue/checkbox';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const activeTab = ref('templates');
const loading = ref(false);

// Document Templates
const templates = ref([]);
const templateDialog = ref(false);
const newTemplate = ref({
  name: '',
  description: '',
  type: 'offer_letter',
  template_content: '',
  required_fields: [],
  optional_fields: [],
  file_format: 'pdf',
  is_default: false,
  letterhead_url: '',
  styling_options: {},
});

// Generated Documents
const documents = ref([]);
const generateDialog = ref(false);
const generateData = ref({
  template_id: null,
  application_id: null,
  recipient_user_id: null,
  document_name: '',
  field_values: {},
});

// Analytics
const analytics = ref({});

const templateTypes = [
  { label: 'Offer Letter', value: 'offer_letter' },
  { label: 'Employment Contract', value: 'employment_contract' },
  { label: 'Reference Letter', value: 'reference_letter' },
  { label: 'Onboarding Packet', value: 'onboarding_packet' },
  { label: 'Termination Letter', value: 'termination_letter' },
  { label: 'Custom Document', value: 'custom' },
];

const fileFormats = [
  { label: 'PDF', value: 'pdf' },
  { label: 'Word Document', value: 'docx' },
  { label: 'HTML', value: 'html' },
];

const statusColors = {
  draft: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
  generated: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  sent: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  signed: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
  archived: 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200',
};

async function loadTemplates() {
  loading.value = true;
  try {
    const res = await api.get('/document-templates');
    templates.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

async function loadDocuments() {
  loading.value = true;
  try {
    const res = await api.get('/generated-documents');
    documents.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

async function loadAnalytics() {
  try {
    const res = await api.get('/document-analytics');
    analytics.value = res?.data?.data || res?.data || {};
  } catch (e) {
    console.error('Failed to load analytics:', e);
  }
}

async function createTemplate() {
  try {
    await api.post('/document-templates', newTemplate.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Document template created', life: 2000 });
    templateDialog.value = false;
    resetTemplateForm();
    loadTemplates();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function generateDocument() {
  try {
    const res = await api.post('/generate-document', generateData.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Document generated successfully', life: 2000 });
    generateDialog.value = false;
    resetGenerateForm();
    loadDocuments();
    
    // Optionally open the generated document
    if (res?.data?.data?.file_url) {
      window.open(res.data.data.file_url, '_blank');
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function deleteTemplate(templateId) {
  if (!confirm('Are you sure you want to delete this template?')) return;
  
  try {
    await api.delete(`/document-templates/${templateId}`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Template deleted', life: 2000 });
    loadTemplates();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function sendDocument(documentId) {
  const email = prompt('Enter recipient email address:');
  if (!email) return;
  
  try {
    await api.post(`/generated-documents/${documentId}/send`, {
      recipient_email: email,
      subject: 'Document from ClinForce',
      message: 'Please find the attached document.',
    });
    toast.add({ severity: 'success', summary: 'Success', detail: 'Document sent successfully', life: 2000 });
    loadDocuments();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function downloadDocument(documentId) {
  try {
    const res = await api.get(`/generated-documents/${documentId}/download`);
    if (res?.data?.data?.download_url) {
      window.open(res.data.data.download_url, '_blank');
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to download document', life: 3000 });
  }
}

function resetTemplateForm() {
  newTemplate.value = {
    name: '',
    description: '',
    type: 'offer_letter',
    template_content: '',
    required_fields: [],
    optional_fields: [],
    file_format: 'pdf',
    is_default: false,
    letterhead_url: '',
    styling_options: {},
  };
}

function resetGenerateForm() {
  generateData.value = {
    template_id: null,
    application_id: null,
    recipient_user_id: null,
    document_name: '',
    field_values: {},
  };
}

function openGenerateDialog(template) {
  generateData.value.template_id = template.id;
  generateData.value.document_name = `${template.name} - ${new Date().toLocaleDateString()}`;
  
  // Pre-populate field values based on template
  const requiredFields = template.required_fields ? JSON.parse(template.required_fields) : [];
  const optionalFields = template.optional_fields ? JSON.parse(template.optional_fields) : [];
  
  generateData.value.field_values = {};
  [...requiredFields, ...optionalFields].forEach(field => {
    generateData.value.field_values[field] = '';
  });
  
  generateDialog.value = true;
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'templates') loadTemplates();
  if (tab === 'documents') loadDocuments();
  if (tab === 'analytics') loadAnalytics();
}

const selectedTemplate = computed(() => {
  return templates.value.find(t => t.id === generateData.value.template_id);
});

const templateFields = computed(() => {
  if (!selectedTemplate.value) return [];
  
  const required = selectedTemplate.value.required_fields ? JSON.parse(selectedTemplate.value.required_fields) : [];
  const optional = selectedTemplate.value.optional_fields ? JSON.parse(selectedTemplate.value.optional_fields) : [];
  
  return [
    ...required.map(field => ({ name: field, required: true })),
    ...optional.map(field => ({ name: field, required: false })),
  ];
});

onMounted(() => {
  loadTemplates();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Document Generation</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Create and manage document templates for automated generation</p>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 rounded-xl w-fit bg-slate-100 dark:bg-slate-800">
        <button v-for="tab in [
          { key: 'templates', label: 'Templates', icon: 'pi-file' },
          { key: 'documents', label: 'Generated', icon: 'pi-download' },
          { key: 'analytics', label: 'Analytics', icon: 'pi-chart-bar' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Templates Tab -->
      <template v-if="activeTab === 'templates'">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Document Templates</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Create reusable templates for offer letters, contracts, and more</p>
          </div>
          <Button label="Create Template" icon="pi pi-plus" @click="templateDialog = true" />
        </div>

        <div v-if="loading" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 h-48 animate-pulse"></div>
        </div>
        <div v-else-if="!templates.length" class="text-center py-12">
          <i class="pi pi-file text-4xl text-slate-400 mb-4"></i>
          <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No templates yet</h3>
          <p class="text-slate-600 dark:text-slate-400 mb-4">Create your first document template to get started</p>
          <Button label="Create Template" icon="pi pi-plus" @click="templateDialog = true" />
        </div>
        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="template in templates" :key="template.id" 
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h4 class="font-semibold text-slate-900 dark:text-white">{{ template.name }}</h4>
                <p v-if="template.description" class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ template.description }}</p>
              </div>
              <div class="flex gap-1">
                <span v-if="template.is_default" class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs rounded font-medium">Default</span>
                <span v-if="!template.is_active" class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 text-xs rounded font-medium">Inactive</span>
              </div>
            </div>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-tag text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300 capitalize">{{ template.type.replace(/_/g, ' ') }}</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-file-pdf text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300 uppercase">{{ template.file_format }}</span>
              </div>
            </div>

            <div class="flex gap-2">
              <Button label="Generate" size="small" @click="openGenerateDialog(template)" />
              <Button label="Edit" size="small" text />
              <Button label="Delete" size="small" text severity="danger" @click="deleteTemplate(template.id)" />
            </div>
          </div>
        </div>
      </template>

      <!-- Generated Documents Tab -->
      <template v-else-if="activeTab === 'documents'">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Generated Documents</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">View and manage all generated documents</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" @click="loadDocuments" />
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900">
              <tr>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Document</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Template</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Status</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Generated</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="loading">
                <td colspan="5" class="px-6 py-10 text-center">
                  <i class="pi pi-spin pi-spinner text-2xl text-slate-400"></i>
                </td>
              </tr>
              <tr v-else-if="!documents.length">
                <td colspan="5" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                  No documents generated yet
                </td>
              </tr>
              <tr v-else v-for="document in documents" :key="document.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-6 py-3">
                  <div class="font-medium text-slate-900 dark:text-white">{{ document.document_name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ document.file_format.toUpperCase() }} • {{ Math.round((document.file_size_bytes || 0) / 1024) }}KB</div>
                </td>
                <td class="px-6 py-3">
                  <div class="text-slate-600 dark:text-slate-300">{{ document.template_name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 capitalize">{{ document.template_type?.replace(/_/g, ' ') }}</div>
                </td>
                <td class="px-6 py-3">
                  <span :class="['px-2 py-1 rounded text-xs font-medium capitalize', statusColors[document.status] || statusColors.draft]">
                    {{ document.status }}
                  </span>
                </td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">
                  {{ document.generated_at ? new Date(document.generated_at).toLocaleDateString() : '—' }}
                </td>
                <td class="px-6 py-3">
                  <div class="flex gap-2">
                    <Button label="Download" size="small" text @click="downloadDocument(document.id)" />
                    <Button v-if="document.status === 'generated'" label="Send" size="small" text @click="sendDocument(document.id)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Analytics Tab -->
      <template v-else>
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Document Analytics</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Track document generation and usage statistics</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" @click="loadAnalytics" />
        </div>

        <div v-if="analytics.stats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.stats.total_documents || 0 }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Total Documents</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.stats.generated_count || 0 }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Generated</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.stats.sent_count || 0 }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Sent</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.stats.signed_count || 0 }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Signed</div>
          </div>
        </div>

        <div v-if="analytics.by_type?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
          <h4 class="font-medium text-slate-900 dark:text-white mb-4">Documents by Type</h4>
          <div class="space-y-3">
            <div v-for="item in analytics.by_type" :key="item.type" class="flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-300 capitalize">{{ item.type.replace(/_/g, ' ') }}</span>
              <span class="font-medium text-slate-900 dark:text-white">{{ item.count }}</span>
            </div>
          </div>
        </div>

        <div v-if="analytics.recent_activity?.length" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
          <h4 class="font-medium text-slate-900 dark:text-white mb-4">Recent Activity</h4>
          <div class="space-y-3">
            <div v-for="activity in analytics.recent_activity" :key="activity.id" class="flex items-center justify-between">
              <div>
                <div class="font-medium text-slate-900 dark:text-white">{{ activity.document_name }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400 capitalize">{{ activity.type?.replace(/_/g, ' ') }}</div>
              </div>
              <div class="text-right">
                <div class="text-sm font-medium text-slate-900 dark:text-white capitalize">{{ activity.status }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">{{ new Date(activity.generated_at).toLocaleDateString() }}</div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Create Template Dialog -->
    <Dialog v-model:visible="templateDialog" header="Create Document Template" :style="{ width: '800px' }" modal>
      <div class="space-y-4 pt-2">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Template Name</label>
            <InputText v-model="newTemplate.name" class="w-full mt-1.5" placeholder="e.g. Standard Offer Letter" />
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Document Type</label>
            <Select v-model="newTemplate.type" :options="templateTypes" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
          </div>
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</label>
          <Textarea v-model="newTemplate.description" rows="2" class="w-full mt-1.5" placeholder="Describe this template..." />
        </div>

        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Template Content</label>
          <Textarea v-model="newTemplate.template_content" rows="10" class="w-full mt-1.5" 
            placeholder="Dear {{candidate_name}},&#10;&#10;We are pleased to offer you the position of {{job_title}} at {{company_name}}.&#10;&#10;Available placeholders: {{candidate_name}}, {{job_title}}, {{company_name}}, {{salary}}, {{start_date}}, {{current_date}}" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">File Format</label>
            <Select v-model="newTemplate.file_format" :options="fileFormats" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
          </div>
          <div class="flex items-center gap-2 mt-6">
            <Checkbox v-model="newTemplate.is_default" inputId="is_default" />
            <label for="is_default" class="text-sm text-slate-700 dark:text-slate-300">Set as default template</label>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="templateDialog = false" />
          <Button label="Create Template" @click="createTemplate" />
        </div>
      </div>
    </Dialog>

    <!-- Generate Document Dialog -->
    <Dialog v-model:visible="generateDialog" header="Generate Document" :style="{ width: '600px' }" modal>
      <div class="space-y-4 pt-2">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Document Name</label>
          <InputText v-model="generateData.document_name" class="w-full mt-1.5" placeholder="Enter document name" />
        </div>

        <div v-if="templateFields.length">
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3 block">Template Fields</label>
          <div class="space-y-3">
            <div v-for="field in templateFields" :key="field.name">
              <label class="text-sm text-slate-700 dark:text-slate-300 capitalize">
                {{ field.name.replace(/_/g, ' ') }}
                <span v-if="field.required" class="text-red-500">*</span>
              </label>
              <InputText v-model="generateData.field_values[field.name]" class="w-full mt-1" 
                :placeholder="`Enter ${field.name.replace(/_/g, ' ')}`" />
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="generateDialog = false" />
          <Button label="Generate Document" @click="generateDocument" />
        </div>
      </div>
    </Dialog>
  </AppLayout>
</template>