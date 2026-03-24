<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { useAdminTheme } from '@/composables/useAdminTheme';

import AdminPagination from './AdminPagination.vue';

const { isDark, card, text, textSub, textMuted, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const contacts = ref([]);
const meta = ref({});
const loading = ref(false);
const search = ref('');
const page = ref(1);
const viewDialog = ref(false);
const viewItem = ref(null);

onMounted(() => {
  setBreadcrumb([{ label: 'Contacts' }]);
  fetchContacts();
});

async function fetchContacts(p = 1) {
  loading.value = true;
  page.value = p;
  try {
    const res = await api.get('/admin/contacts', { params: { q: search.value, page: p } });
    const d = res?.data?.data || res?.data;
    contacts.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

function openView(c) {
  viewItem.value = c;
  viewDialog.value = true;
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Contact Submissions</h1>
        <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} total messages</p>
      </div>

      <div class="flex gap-3">
        <InputText v-model="search" placeholder="Search name, email, message..." :class="['w-72', input]" @keydown.enter="fetchContacts(1)" />
        <Button icon="pi pi-search" label="Search" size="small" @click="fetchContacts(1)" />
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Name</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Email</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Message</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Date</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="6" class="px-5 py-10 text-center">
                <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
              </td>
            </tr>
            <tr v-else-if="!contacts.length">
              <td colspan="6" :class="['px-5 py-10 text-center text-sm', textMuted]">No contacts found</td>
            </tr>
            <tr v-else v-for="c in contacts" :key="c.id"
              :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ c.id }}</td>
              <td :class="['px-5 py-3.5 font-medium', text]">{{ c.name || '—' }}</td>
              <td :class="['px-5 py-3.5 text-xs', textSub]">{{ c.email || '—' }}</td>
              <td :class="['px-5 py-3.5 text-xs max-w-xs truncate', textMuted]">{{ c.message }}</td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ c.created_at ? new Date(c.created_at).toLocaleDateString() : '—' }}</td>
              <td class="px-5 py-3.5">
                <Button icon="pi pi-eye" size="small" severity="secondary" v-tooltip="'View'" @click="openView(c)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetchContacts" />
    </div>

    <Dialog v-model:visible="viewDialog" header="Contact Message" :style="{ width: '520px' }" modal>
      <div v-if="viewItem" class="space-y-4 pt-2">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-1', textMuted]">Name</p>
            <p :class="['text-sm', text]">{{ viewItem.name || '—' }}</p>
          </div>
          <div>
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-1', textMuted]">Email</p>
            <p :class="['text-sm', text]">{{ viewItem.email || '—' }}</p>
          </div>
        </div>
        <div>
          <p :class="['text-xs font-semibold uppercase tracking-wider mb-1', textMuted]">Message</p>
          <div :class="['rounded-xl p-4 text-sm leading-relaxed', isDark ? 'bg-slate-800 text-slate-300' : 'bg-slate-50 text-slate-700']">
            {{ viewItem.message }}
          </div>
        </div>
        <p :class="['text-xs', textMuted]">Received {{ viewItem.created_at ? new Date(viewItem.created_at).toLocaleString() : '—' }}</p>
      </div>
    </Dialog>
  </AdminLayout>
</template>
