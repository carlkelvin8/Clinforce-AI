<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';
import Swal from 'sweetalert2';

import AdminPagination from './AdminPagination.vue';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, skeleton, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const users = ref([]);
const meta = ref({});
const loading = ref(false);
const search = ref('');
const roleFilter = ref('');
const statusFilter = ref('');
const page = ref(1);
const selected = ref(new Set());

const allSelected = computed(() =>
  users.value.length > 0 && users.value.every(u => selected.value.has(u.id))
);
function toggleAll() {
  if (allSelected.value) users.value.forEach(u => selected.value.delete(u.id));
  else users.value.forEach(u => selected.value.add(u.id));
  selected.value = new Set(selected.value);
}
function toggleOne(id) {
  if (selected.value.has(id)) selected.value.delete(id);
  else selected.value.add(id);
  selected.value = new Set(selected.value);
}

const resetDialog = ref(false);
const resetTarget = ref(null);
const newPassword = ref('');
const saving = ref(false);
const editRoleDialog = ref(false);
const editRoleTarget = ref(null);
const editRoleValue = ref('');
const detailDialog = ref(false);
const detailUser = ref(null);
const detailLoading = ref(false);
const detailTab = ref('overview');
const userNotes = ref([]);
const loginHistory = ref([]);
const newNote = ref('');
const savingNote = ref(false);
const grantDialog = ref(false);
const grantTarget = ref(null);
const grantPlanId = ref('');
const grantDays = ref(30);
const grantNote = ref('');
const plans = ref([]);

async function loadPlans() {
  if (plans.value.length) return;
  try {
    const res = await api.get('/admin/plans');
    plans.value = (res?.data?.data || res?.data || []).filter(p => p.is_active);
  } catch {}
}

async function addNote() {
  if (!newNote.value.trim()) return;
  savingNote.value = true;
  try {
    const res = await api.post(`/admin/users/${detailUser.value.user.id}/notes`, { note: newNote.value });
    userNotes.value.unshift(res?.data?.data || res?.data);
    newNote.value = '';
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save note', life: 3000 });
  } finally { savingNote.value = false; }
}

async function deleteNote(noteId) {
  try {
    await api.delete(`/admin/users/${detailUser.value.user.id}/notes/${noteId}`);
    userNotes.value = userNotes.value.filter(n => n.id !== noteId);
  } catch {}
}

function openGrant(u) {
  grantTarget.value = u;
  grantPlanId.value = '';
  grantDays.value = 30;
  grantNote.value = '';
  grantDialog.value = true;
  loadPlans();
}

async function doGrantSubscription() {
  if (!grantPlanId.value) return;
  saving.value = true;
  try {
    await api.post(`/admin/users/${grantTarget.value.id}/grant-subscription`, {
      plan_id: grantPlanId.value,
      days: grantDays.value,
      note: grantNote.value,
    });
    toast.add({ severity: 'success', summary: 'Granted', detail: 'Subscription granted', life: 2000 });
    grantDialog.value = false;
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  } finally { saving.value = false; }
}
const emailDialog = ref(false);
const emailTarget = ref(null);
const emailSubject = ref('');
const emailBody = ref('');

const roleOptions = [
  { label: 'All Roles', value: '' },
  { label: 'Admin', value: 'admin' },
  { label: 'Employer', value: 'employer' },
  { label: 'Agency', value: 'agency' },
  { label: 'Candidate', value: 'applicant' },
];
const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Banned', value: 'banned' },
];
const roleSeverity = { admin: 'danger', employer: 'info', agency: 'secondary', applicant: 'contrast' };
const statusSeverity = { active: 'success', suspended: 'warn', banned: 'danger' };

onMounted(() => {
  setBreadcrumb([{ label: 'Users' }]);
  fetchUsers();
});

async function fetchUsers(p = 1) {
  loading.value = true;
  page.value = p;
  selected.value = new Set();
  try {
    const res = await api.get('/admin/users', {
      params: { q: search.value, role: roleFilter.value, status: statusFilter.value, page: p }
    });
    const d = res?.data?.data || res?.data;
    users.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

async function setStatus(u, status) {
  const label = status === 'suspended' ? 'Suspend' : status === 'banned' ? 'Ban' : 'Activate';
  const result = await Swal.fire({
    title: `${label} user?`,
    text: u.email,
    icon: status === 'active' ? 'question' : 'warning',
    showCancelButton: true,
    confirmButtonText: label,
    confirmButtonColor: status === 'banned' ? '#ef4444' : status === 'suspended' ? '#f59e0b' : '#22c55e',
    background: isDark.value ? '#1e293b' : '#fff',
    color: isDark.value ? '#f1f5f9' : '#0f172a',
  });
  if (!result.isConfirmed) return;
  try {
    await api.patch(`/admin/users/${u.id}`, { status });
    u.status = status;
    toast.add({ severity: 'success', summary: 'Updated', detail: `User ${status}`, life: 2000 });
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed', life: 3000 });
  }
}

function openRoleEdit(u) {
  editRoleTarget.value = u;
  editRoleValue.value = u.role;
  editRoleDialog.value = true;
}

async function saveRole() {
  saving.value = true;
  try {
    await api.patch(`/admin/users/${editRoleTarget.value.id}`, { role: editRoleValue.value });
    editRoleTarget.value.role = editRoleValue.value;
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Role changed', life: 2000 });
    editRoleDialog.value = false;
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

function openReset(u) {
  resetTarget.value = u;
  newPassword.value = '';
  resetDialog.value = true;
}

async function doResetPassword() {
  if (newPassword.value.length < 8) return;
  saving.value = true;
  try {
    await api.post(`/admin/users/${resetTarget.value.id}/reset-password`, { password: newPassword.value });
    toast.add({ severity: 'success', summary: 'Done', detail: 'Password reset', life: 2000 });
    resetDialog.value = false;
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

async function openDetail(u) {
  detailUser.value = { user: u, subscriptions: [], jobs: [], applications: [] };
  detailDialog.value = true;
  detailLoading.value = true;
  detailTab.value = 'overview';
  userNotes.value = [];
  loginHistory.value = [];
  try {
    const [detailRes, notesRes, historyRes] = await Promise.all([
      api.get(`/admin/users/${u.id}/detail`),
      api.get(`/admin/users/${u.id}/notes`),
      api.get(`/admin/users/${u.id}/login-history`),
    ]);
    detailUser.value = detailRes?.data?.data || detailRes?.data;
    userNotes.value = notesRes?.data?.data || notesRes?.data || [];
    loginHistory.value = historyRes?.data?.data || historyRes?.data || [];
  } finally { detailLoading.value = false; }
}

function openEmail(u) {
  emailTarget.value = u;
  emailSubject.value = '';
  emailBody.value = '';
  emailDialog.value = true;
}

async function sendEmail() {
  if (!emailSubject.value || !emailBody.value) return;
  saving.value = true;
  try {
    await api.post(`/admin/users/${emailTarget.value.id}/email`, {
      subject: emailSubject.value,
      body: emailBody.value
    });
    toast.add({ severity: 'success', summary: 'Sent', detail: 'Email sent', life: 2000 });
    emailDialog.value = false;
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to send', life: 3000 });
  } finally { saving.value = false; }
}

async function impersonate(u) {
  const result = await Swal.fire({
    title: 'Impersonate user?',
    text: `You will be logged in as ${u.email}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Impersonate',
    confirmButtonColor: '#3b82f6',
    background: isDark.value ? '#1e293b' : '#fff',
    color: isDark.value ? '#f1f5f9' : '#0f172a',
  });
  if (!result.isConfirmed) return;
  try {
    const res = await api.post(`/admin/users/${u.id}/impersonate`);
    const token = res?.data?.data?.token || res?.data?.token;
    if (token) {
      localStorage.setItem('auth_token', token);
      localStorage.setItem('CLINFORCE_TOKEN', token);
      localStorage.setItem('impersonating', '1');
      localStorage.removeItem('auth_user');
      window.dispatchEvent(new Event('auth:changed'));
      window.location.href = '/';
    }
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed', life: 3000 });
  }
}

async function bulkAction(action) {
  const ids = [...selected.value];
  if (!ids.length) return;
  const labels = { suspend: 'Suspend', ban: 'Ban', activate: 'Activate', export: 'Export' };
  const result = await Swal.fire({
    title: `${labels[action]} ${ids.length} user(s)?`,
    icon: action === 'export' ? 'question' : 'warning',
    showCancelButton: true,
    confirmButtonText: labels[action],
    confirmButtonColor: action === 'ban' ? '#ef4444' : '#3b82f6',
    background: isDark.value ? '#1e293b' : '#fff',
    color: isDark.value ? '#f1f5f9' : '#0f172a',
  });
  if (!result.isConfirmed) return;
  try {
    const res = await api.post('/admin/users/bulk', { ids, action });
    if (action === 'export') {
      const exportData = res?.data?.data?.export || res?.data?.export || [];
      const csv = [
        'ID,Email,Phone,Role,Status,Created At',
        ...exportData.map(u => `${u.id},${u.email || ''},${u.phone || ''},${u.role},${u.status},${u.created_at}`)
      ].join('\n');
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'users-export.csv'; a.click();
      URL.revokeObjectURL(url);
    } else {
      toast.add({ severity: 'success', summary: 'Done', detail: `${ids.length} users updated`, life: 2000 });
      fetchUsers(page.value);
    }
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Bulk action failed', life: 3000 });
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 :class="['text-2xl font-bold', text]">Users</h1>
          <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} total users</p>
        </div>
      </div>

      <div class="flex flex-wrap gap-3">
        <InputText v-model="search" placeholder="Search email, phone, ID..." :class="['w-64', input]" @keydown.enter="fetchUsers(1)" />
        <Select v-model="roleFilter" :options="roleOptions" optionLabel="label" optionValue="value" :class="['w-40', input]" @change="fetchUsers(1)" />
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" :class="['w-40', input]" @change="fetchUsers(1)" />
        <Button icon="pi pi-search" label="Search" size="small" @click="fetchUsers(1)" />
      </div>

      <div v-if="selected.size > 0"
        :class="['flex items-center gap-3 px-4 py-3 rounded-xl border', isDark ? 'bg-blue-900/20 border-blue-800' : 'bg-blue-50 border-blue-200']">
        <span :class="['text-sm font-medium', isDark ? 'text-blue-300' : 'text-blue-700']">{{ selected.size }} selected</span>
        <div class="flex gap-2 ml-2">
          <Button label="Activate" size="small" severity="success" @click="bulkAction('activate')" />
          <Button label="Suspend" size="small" severity="warn" @click="bulkAction('suspend')" />
          <Button label="Ban" size="small" severity="danger" @click="bulkAction('ban')" />
          <Button label="Export CSV" size="small" severity="secondary" icon="pi pi-download" @click="bulkAction('export')" />
        </div>
        <button @click="selected = new Set()" :class="['ml-auto text-xs', isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900']">Clear</button>
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th class="px-4 py-3.5 w-10">
                <input type="checkbox" :checked="allSelected" @change="toggleAll" class="rounded cursor-pointer" />
              </th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Email / Phone</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Role</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Joined</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="7" class="px-5 py-10 text-center">
                <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
              </td>
            </tr>
            <tr v-else-if="!users.length">
              <td colspan="7" :class="['px-5 py-10 text-center text-sm', textMuted]">No users found</td>
            </tr>
            <tr v-else v-for="u in users" :key="u.id"
              :class="['transition-colors', selected.has(u.id)
                ? (isDark ? 'bg-blue-900/10' : 'bg-blue-50')
                : (isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50')]">
              <td class="px-4 py-3.5">
                <input type="checkbox" :checked="selected.has(u.id)" @change="toggleOne(u.id)" class="rounded cursor-pointer" />
              </td>
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ u.id }}</td>
              <td class="px-5 py-3.5">
                <button @click="openDetail(u)" :class="['font-medium hover:underline text-left', text]">
                  {{ u.email || u.phone || '—' }}
                </button>
              </td>
              <td class="px-5 py-3.5">
                <button @click="openRoleEdit(u)" class="group flex items-center gap-1.5">
                  <Tag :value="u.role" :severity="roleSeverity[u.role]" class="text-xs" />
                  <i :class="['pi pi-pencil text-xs transition-opacity opacity-0 group-hover:opacity-100', textMuted]"></i>
                </button>
              </td>
              <td class="px-5 py-3.5">
                <Tag :value="u.status" :severity="statusSeverity[u.status]" class="text-xs" />
              </td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ new Date(u.created_at).toLocaleDateString() }}</td>
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-1">
                  <Button v-if="u.status === 'active'" label="Suspend" size="small" severity="warn" @click="setStatus(u, 'suspended')" />
                  <Button v-else-if="u.status === 'suspended'" label="Activate" size="small" severity="success" @click="setStatus(u, 'active')" />
                  <Button v-if="u.status !== 'banned'" icon="pi pi-ban" size="small" severity="danger" v-tooltip="'Ban'" @click="setStatus(u, 'banned')" />
                  <Button v-else label="Unban" size="small" severity="secondary" @click="setStatus(u, 'active')" />
                  <Button icon="pi pi-key" size="small" severity="secondary" v-tooltip="'Reset password'" @click="openReset(u)" />
                  <Button icon="pi pi-envelope" size="small" severity="secondary" v-tooltip="'Email user'" @click="openEmail(u)" />
                  <Button icon="pi pi-sign-in" size="small" severity="secondary" v-tooltip="'Impersonate'" @click="impersonate(u)" />
                  <Button icon="pi pi-gift" size="small" severity="secondary" v-tooltip="'Grant subscription'" @click="openGrant(u)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetchUsers" />
    </div>

    <Dialog v-model:visible="editRoleDialog" header="Change Role" :style="{ width: '340px' }" modal>
      <div class="space-y-4 pt-2">
        <p :class="['text-sm', textSub]">User: <span :class="['font-medium', text]">{{ editRoleTarget?.email }}</span></p>
        <Select v-model="editRoleValue" :options="roleOptions.slice(1)" optionLabel="label" optionValue="value" class="w-full" />
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="editRoleDialog = false" />
          <Button label="Save" :loading="saving" @click="saveRole" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="resetDialog" header="Reset Password" :style="{ width: '380px' }" modal>
      <div class="space-y-4 pt-2">
        <p :class="['text-sm', textSub]">Reset for <span :class="['font-medium', text]">{{ resetTarget?.email }}</span></p>
        <InputText v-model="newPassword" type="password" placeholder="New password (min 8 chars)" class="w-full" />
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="resetDialog = false" />
          <Button label="Reset" :loading="saving" :disabled="newPassword.length < 8" @click="doResetPassword" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="emailDialog" header="Email User" :style="{ width: '480px' }" modal>
      <div class="space-y-4 pt-2">
        <p :class="['text-sm', textSub]">To: <span :class="['font-medium', text]">{{ emailTarget?.email }}</span></p>
        <InputText v-model="emailSubject" placeholder="Subject" class="w-full" />
        <Textarea v-model="emailBody" placeholder="Message body..." rows="6" class="w-full" />
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="emailDialog = false" />
          <Button label="Send" icon="pi pi-send" :loading="saving" :disabled="!emailSubject || !emailBody" @click="sendEmail" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="detailDialog" header="User Detail" :style="{ width: '680px' }" modal>
      <div v-if="detailLoading" class="py-10 text-center">
        <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
      </div>
      <div v-else-if="detailUser" class="space-y-4 pt-2">
        <!-- User header -->
        <div :class="['rounded-xl p-4 border flex items-center gap-3', isDark ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200']">
          <div :class="['w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold flex-shrink-0',
            isDark ? 'bg-slate-700 text-slate-300' : 'bg-slate-200 text-slate-600']">
            {{ (detailUser.user?.email || '#')[0].toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <div :class="['font-semibold truncate', text]">{{ detailUser.user?.email || detailUser.user?.phone }}</div>
            <div class="flex items-center gap-2 mt-1">
              <Tag :value="detailUser.user?.role" :severity="roleSeverity[detailUser.user?.role]" class="text-xs" />
              <Tag :value="detailUser.user?.status" :severity="statusSeverity[detailUser.user?.status]" class="text-xs" />
              <span :class="['text-xs', textMuted]">Joined {{ new Date(detailUser.user?.created_at).toLocaleDateString() }}</span>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 p-1 rounded-xl" :class="isDark ? 'bg-slate-800' : 'bg-slate-100'">
          <button v-for="tab in ['overview','notes','history']" :key="tab" @click="detailTab = tab"
            :class="['flex-1 py-1.5 rounded-lg text-xs font-medium capitalize transition-all',
              detailTab === tab ? 'bg-blue-600 text-white' : isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900']">
            {{ tab === 'history' ? 'Login History' : tab.charAt(0).toUpperCase() + tab.slice(1) }}
          </button>
        </div>

        <!-- Overview tab -->
        <div v-if="detailTab === 'overview'" class="space-y-3">
          <div>
            <h3 :class="['text-xs font-semibold uppercase tracking-wider mb-2', textMuted]">Subscriptions ({{ detailUser.subscriptions?.length || 0 }})</h3>
            <div v-if="!detailUser.subscriptions?.length" :class="['text-xs py-2', textMuted]">None</div>
            <div v-else class="space-y-1.5">
              <div v-for="s in detailUser.subscriptions" :key="s.id"
                :class="['flex items-center justify-between px-3 py-2 rounded-lg text-xs', isDark ? 'bg-slate-800' : 'bg-slate-50']">
                <span :class="text">{{ s.plan?.name }}</span>
                <div class="flex items-center gap-2">
                  <span :class="textMuted">${{ (s.amount_cents / 100).toFixed(2) }}</span>
                  <Tag :value="s.status" :severity="s.status === 'active' ? 'success' : 'secondary'" class="text-xs" />
                </div>
              </div>
            </div>
          </div>
          <div v-if="detailUser.jobs?.length">
            <h3 :class="['text-xs font-semibold uppercase tracking-wider mb-2', textMuted]">Jobs Posted ({{ detailUser.jobs.length }})</h3>
            <div class="space-y-1.5">
              <div v-for="j in detailUser.jobs" :key="j.id"
                :class="['flex items-center justify-between px-3 py-2 rounded-lg text-xs', isDark ? 'bg-slate-800' : 'bg-slate-50']">
                <span :class="['truncate max-w-xs', text]">{{ j.title }}</span>
                <Tag :value="j.status" severity="secondary" class="text-xs" />
              </div>
            </div>
          </div>
          <div v-if="detailUser.applications?.length">
            <h3 :class="['text-xs font-semibold uppercase tracking-wider mb-2', textMuted]">Applications ({{ detailUser.applications.length }})</h3>
            <div class="space-y-1.5">
              <div v-for="a in detailUser.applications" :key="a.id"
                :class="['flex items-center justify-between px-3 py-2 rounded-lg text-xs', isDark ? 'bg-slate-800' : 'bg-slate-50']">
                <span :class="['truncate max-w-xs', text]">{{ a.job?.title || 'Job #' + a.job_id }}</span>
                <Tag :value="a.status" severity="secondary" class="text-xs" />
              </div>
            </div>
          </div>
        </div>

        <!-- Notes tab -->
        <div v-if="detailTab === 'notes'" class="space-y-3">
          <div class="flex gap-2">
            <Textarea v-model="newNote" placeholder="Add internal note..." rows="2" class="flex-1 text-sm" />
            <Button icon="pi pi-send" :loading="savingNote" :disabled="!newNote.trim()" @click="addNote" />
          </div>
          <div v-if="!userNotes.length" :class="['text-xs py-4 text-center', textMuted]">No notes yet</div>
          <div v-else class="space-y-2 max-h-64 overflow-y-auto">
            <div v-for="n in userNotes" :key="n.id"
              :class="['p-3 rounded-xl text-xs relative group', isDark ? 'bg-slate-800' : 'bg-slate-50']">
              <p :class="['leading-relaxed', text]">{{ n.note }}</p>
              <div class="flex items-center justify-between mt-2">
                <span :class="textMuted">{{ n.created_at ? new Date(n.created_at).toLocaleString() : '' }}</span>
                <button @click="deleteNote(n.id)" :class="['opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-500']">
                  <i class="pi pi-trash text-xs"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Login history tab -->
        <div v-if="detailTab === 'history'" class="space-y-2 max-h-72 overflow-y-auto">
          <div v-if="!loginHistory.length" :class="['text-xs py-4 text-center', textMuted]">No login history</div>
          <div v-else v-for="h in loginHistory" :key="h.id"
            :class="['flex items-center justify-between px-3 py-2.5 rounded-xl text-xs', isDark ? 'bg-slate-800' : 'bg-slate-50']">
            <div class="flex items-center gap-2">
              <i :class="['pi', h.action === 'login' ? 'pi-sign-in text-emerald-500' : 'pi-sign-out text-slate-400']"></i>
              <span :class="['font-medium capitalize', text]">{{ h.action }}</span>
            </div>
            <span :class="['font-mono', textMuted]">{{ h.ip_address || '—' }}</span>
            <span :class="textMuted">{{ h.created_at ? new Date(h.created_at).toLocaleString() : '—' }}</span>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Grant Subscription Dialog -->
    <Dialog v-model:visible="grantDialog" header="Grant Subscription" :style="{ width: '420px' }" modal>
      <div class="space-y-4 pt-2">
        <p :class="['text-sm', textSub]">User: <span :class="['font-medium', text]">{{ grantTarget?.email }}</span></p>
        <div class="space-y-1.5">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Plan</label>
          <Select v-model="grantPlanId" :options="plans" optionLabel="name" optionValue="id" placeholder="Select plan" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Duration (days)</label>
          <InputNumber v-model="grantDays" :min="1" :max="3650" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Note (optional)</label>
          <InputText v-model="grantNote" placeholder="Reason for grant..." class="w-full" />
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <Button label="Cancel" severity="secondary" @click="grantDialog = false" />
          <Button label="Grant" icon="pi pi-gift" :loading="saving" :disabled="!grantPlanId" @click="doGrantSubscription" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
