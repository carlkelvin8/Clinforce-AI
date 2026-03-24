<template>
  <AdminLayout>
    <div class="space-y-8 max-w-7xl mx-auto pb-12">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 :class="['text-3xl font-black tracking-tight', text]">System Status</h1>
          <p :class="['text-sm mt-1.5 font-medium opacity-60', textSub]">Monitor platform health, manage maintenance, and inspect infrastructure.</p>
        </div>
        <div class="flex gap-2.5">
          <Button icon="pi pi-refresh" severity="secondary" outlined @click="fetchStatus" :loading="loading" 
            class="!rounded-xl !border-slate-200 dark:!border-white/10 shadow-sm" />
          <Button label="Flush System Cache" icon="pi pi-trash" severity="danger" @click="flushCache" :loading="flushingCache" 
            class="!rounded-xl shadow-lg shadow-red-500/20" />
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Health & Logs -->
        <div class="lg:col-span-2 space-y-8">
          
          <!-- System Info Grid -->
          <div class="grid sm:grid-cols-2 gap-4">
            <div v-for="(val, label) in systemCards" :key="label" 
              :class="['rounded-3xl border p-5 transition-all duration-300 hover:shadow-xl hover:-translate-y-1', card]">
              <div class="flex items-center justify-between mb-4">
                <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center shadow-sm', val.bg]">
                  <i :class="[val.icon, val.color, 'text-base']"></i>
                </div>
                <Tag v-if="val.tag" :severity="val.severity" :value="val.tag" class="!text-[10px] !font-black !uppercase" />
              </div>
              <p :class="['text-[10px] font-black uppercase tracking-[0.2em] opacity-50 mb-1', textMuted]">{{ label }}</p>
              <p :class="['text-xl font-bold tracking-tight', text]">{{ val.value }}</p>
            </div>
          </div>

          <!-- Queue Monitor -->
          <div :class="['rounded-[2rem] border overflow-hidden shadow-sm', card]">
            <div :class="['px-8 py-6 border-b flex items-center justify-between', border]">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                  <i class="pi pi-bolt text-amber-500 text-lg"></i>
                </div>
                <div>
                  <h2 :class="['font-bold tracking-tight', text]">Queue Monitor</h2>
                  <p class="text-[11px] font-medium opacity-50">Background job processing status</p>
                </div>
              </div>
              <Tag v-if="queueData" :severity="queueData.count > 0 ? 'danger' : 'success'" 
                :value="queueData.count + ' FAILED JOBS'" class="!px-3 !py-1 !rounded-full !text-[10px] !font-black" />
            </div>
            
            <div class="p-2">
              <div v-if="queueLoading" class="p-6 space-y-4">
                <div v-for="i in 3" :key="i" class="h-16 bg-slate-100 dark:bg-white/5 animate-pulse rounded-2xl"></div>
              </div>
              <div v-else-if="!queueData?.failed?.length" class="text-center py-16">
                <div class="w-20 h-20 rounded-[2.5rem] bg-emerald-500/10 flex items-center justify-center mx-auto mb-4 border border-emerald-500/20">
                  <i class="pi pi-check-circle text-4xl text-emerald-500"></i>
                </div>
                <p :class="['text-base font-bold tracking-tight', text]">Systems Operational</p>
                <p :class="['text-xs font-medium mt-1 opacity-50', textMuted]">No failed jobs detected in the queue</p>
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                  <thead>
                    <tr :class="['text-[10px] font-black uppercase tracking-widest opacity-40', textMuted]">
                      <th class="py-4 px-6">Job Details</th>
                      <th class="py-4 px-6">Failure Time</th>
                      <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody :class="divider">
                    <tr v-for="job in queueData.failed" :key="job.id" 
                      :class="['group transition-colors duration-200', rowHover]">
                      <td class="py-5 px-6">
                        <div class="font-bold tracking-tight mb-1" :class="text">{{ job.job }}</div>
                        <div class="text-[10px] font-mono opacity-50 truncate max-w-[300px] bg-slate-100 dark:bg-white/5 px-2 py-1 rounded-md" :class="textMuted">{{ job.exception }}</div>
                      </td>
                      <td class="py-5 px-6">
                        <div class="text-xs font-bold" :class="text">{{ new Date(job.failed_at).toLocaleDateString() }}</div>
                        <div class="text-[10px] font-medium opacity-50" :class="textMuted">{{ new Date(job.failed_at).toLocaleTimeString() }}</div>
                      </td>
                      <td class="py-5 px-6 text-right">
                        <Button icon="pi pi-refresh" label="Retry" size="small" text raised
                          :loading="retrying === job.uuid" @click="retryJob(job.uuid)" 
                          class="!rounded-xl !text-[10px] !font-black !uppercase !tracking-widest" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Webhook Logs -->
          <div :class="['rounded-[2rem] border overflow-hidden shadow-sm', card]">
            <div :class="['px-8 py-6 border-b flex items-center justify-between flex-wrap gap-4', border]">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 flex items-center justify-center">
                  <i class="pi pi-webhook text-indigo-500 text-lg"></i>
                </div>
                <div>
                  <h2 :class="['font-bold tracking-tight', text]">Webhook Logs</h2>
                  <p class="text-[11px] font-medium opacity-50">Real-time external event tracking</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-widest opacity-40 mr-2">Filter</span>
                <select v-model="webhookSource" @change="fetchWebhookLogs" 
                  class="text-[11px] font-bold bg-slate-100 dark:bg-white/5 border-0 rounded-xl px-4 py-2 outline-none appearance-none cursor-pointer transition-all hover:ring-2 hover:ring-blue-500/20" :class="text">
                  <option value="">All Sources</option>
                  <option value="stripe">Stripe Payments</option>
                  <option value="zoom">Zoom Video</option>
                </select>
              </div>
            </div>
            
            <div class="p-2">
              <div v-if="webhookLoading" class="p-6 space-y-4">
                <div v-for="i in 3" :key="i" class="h-16 bg-slate-100 dark:bg-white/5 animate-pulse rounded-2xl"></div>
              </div>
              <div v-else-if="!webhookLogs.length" class="text-center py-16">
                <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4 opacity-50">
                  <i class="pi pi-inbox text-2xl"></i>
                </div>
                <p :class="['text-sm font-bold opacity-40', textMuted]">No webhook data recorded</p>
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                  <thead>
                    <tr :class="['text-[10px] font-black uppercase tracking-widest opacity-40', textMuted]">
                      <th class="py-4 px-6">Source & Event</th>
                      <th class="py-4 px-6">Status</th>
                      <th class="py-4 px-6 text-right">Timestamp</th>
                    </tr>
                  </thead>
                  <tbody :class="divider">
                    <tr v-for="log in webhookLogs" :key="log.id" 
                      :class="['group transition-colors duration-200', rowHover]">
                      <td class="py-5 px-6">
                        <div class="flex items-center gap-3">
                          <div :class="['w-8 h-8 rounded-xl flex items-center justify-center text-[10px] font-black uppercase', 
                            log.source === 'stripe' ? 'bg-blue-500/10 text-blue-500' : 'bg-orange-500/10 text-orange-500']">
                            {{ log.source[0] }}
                          </div>
                          <div>
                            <div class="font-bold tracking-tight" :class="text">{{ log.event_type }}</div>
                            <div class="text-[10px] font-medium opacity-50 uppercase tracking-tighter" :class="textMuted">{{ log.source }} engine</div>
                          </div>
                        </div>
                      </td>
                      <td class="py-5 px-6">
                        <Tag :severity="log.status === 'processed' ? 'success' : 'danger'" 
                          :value="log.status" class="!text-[9px] !font-black !uppercase !px-2 !py-0.5 !rounded-lg" />
                      </td>
                      <td class="py-5 px-6 text-right">
                        <div class="text-xs font-bold" :class="text">{{ new Date(log.created_at).toLocaleDateString() }}</div>
                        <div class="text-[10px] font-medium opacity-50" :class="textMuted">{{ new Date(log.created_at).toLocaleTimeString() }}</div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Actions & Stats -->
        <div class="space-y-8">
          
          <!-- Maintenance Mode -->
          <div :class="['rounded-[2rem] border overflow-hidden shadow-lg', card]">
            <div :class="['px-6 py-5 border-b bg-slate-50/50 dark:bg-white/2', border]">
              <h2 :class="['font-bold flex items-center gap-3 tracking-tight', text]">
                <div class="w-8 h-8 rounded-xl bg-slate-500/10 flex items-center justify-center">
                  <i class="pi pi-cog text-slate-500 text-sm"></i>
                </div>
                Maintenance
              </h2>
            </div>
            <div class="p-6 space-y-6">
              <div class="flex items-center justify-between p-4 rounded-2xl bg-blue-500/5 border border-blue-500/10">
                <div>
                  <span class="text-sm font-bold block" :class="text">Go Offline</span>
                  <span class="text-[10px] font-medium opacity-50 block uppercase tracking-wider" :class="textMuted">Disables public access</span>
                </div>
                <Checkbox v-model="maintenance.maintenance" binary class="scale-125" />
              </div>
              
              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-[0.2em] opacity-50 pl-1" :class="textMuted">System Message</label>
                <InputText v-model="maintenance.message" placeholder="e.g. System upgrade in progress..." 
                  class="!rounded-2xl !py-3 !px-4 !text-sm !font-medium !shadow-sm !border-slate-200 dark:!border-white/10" fluid />
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-[0.2em] opacity-50 pl-1" :class="textMuted">Site Announcement</label>
                <Textarea v-model="maintenance.announcement" rows="3" placeholder="Banner message for all users..." 
                  class="!rounded-2xl !py-3 !px-4 !text-sm !font-medium !shadow-sm !border-slate-200 dark:!border-white/10" fluid />
              </div>

              <Button label="Sync System State" icon="pi pi-check" fluid @click="saveMaintenance" :loading="savingMaintenance" 
                class="!rounded-2xl !py-3 !font-bold !shadow-lg shadow-blue-500/20" />
            </div>
          </div>

          <!-- DB Table Sizes -->
          <div :class="['rounded-[2rem] border overflow-hidden shadow-sm', card]">
            <div :class="['px-6 py-5 border-b flex items-center justify-between', border]">
              <h2 :class="['font-bold flex items-center gap-3 tracking-tight', text]">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                  <i class="pi pi-database text-emerald-500 text-sm"></i>
                </div>
                Storage Analytics
              </h2>
              <span class="text-[10px] font-black opacity-30 uppercase tracking-widest">{{ tableSizes.length }} Tables</span>
            </div>
            <div class="p-6">
              <div v-if="tableSizesLoading" class="space-y-6">
                <div v-for="i in 5" :key="i" class="space-y-2">
                  <div class="flex justify-between"><div class="w-20 h-3 bg-slate-100 dark:bg-white/5 animate-pulse rounded"></div><div class="w-10 h-3 bg-slate-100 dark:bg-white/5 animate-pulse rounded"></div></div>
                  <div class="h-2 bg-slate-100 dark:bg-white/5 animate-pulse rounded-full"></div>
                </div>
              </div>
              <div v-else class="space-y-6 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                <div v-for="table in tableSizes" :key="table.table" class="space-y-2 group">
                  <div class="flex justify-between text-xs items-end">
                    <div>
                      <span class="font-bold tracking-tight" :class="text">{{ table.table }}</span>
                      <span class="text-[9px] font-black uppercase opacity-30 ml-2 tracking-tighter">{{ table.row_count.toLocaleString() }} R</span>
                    </div>
                    <span class="font-black text-[10px] opacity-60" :class="textMuted">{{ table.size_mb }} MB</span>
                  </div>
                  <div class="h-2 w-full bg-slate-100 dark:bg-white/5 rounded-full overflow-hidden shadow-inner">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-1000 ease-out shadow-[0_0_8px_rgba(16,185,129,0.4)]" 
                      :style="{ width: (table.size_mb / maxTableSize() * 100) + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Errors -->
          <div :class="['rounded-[2rem] border overflow-hidden border-red-500/10 shadow-sm', card]">
            <div :class="['px-6 py-5 border-b bg-red-500/5', border]">
              <h2 :class="['font-bold flex items-center gap-3 tracking-tight', text]">
                <div class="w-8 h-8 rounded-xl bg-red-500/10 flex items-center justify-center">
                  <i class="pi pi-exclamation-circle text-red-500 text-sm"></i>
                </div>
                Incident Log
              </h2>
            </div>
            <div class="p-4">
              <div v-if="logsLoading" class="space-y-3">
                <div v-for="i in 3" :key="i" class="h-16 bg-slate-100 dark:bg-white/5 animate-pulse rounded-2xl"></div>
              </div>
              <div v-else-if="!logs.length" class="text-center py-10">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                  <i class="pi pi-check text-emerald-500"></i>
                </div>
                <p :class="['text-[10px] font-black uppercase tracking-widest opacity-40', textMuted]">Clear logs</p>
              </div>
              <div v-else class="space-y-2.5 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                <div v-for="(log, i) in logs" :key="i" 
                  class="p-3 rounded-2xl bg-red-500/5 border border-red-500/10 text-[9px] font-mono whitespace-pre-wrap break-all leading-relaxed transition-all hover:bg-red-500/10" :class="text">
                  <div class="opacity-40 mb-1 font-black">{{ log.substring(0, 20) }}</div>
                  {{ log.substring(21) }}
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';
import Swal from 'sweetalert2';

const { isDark, card, text, textSub, textMuted, divider, border, input, rowHover } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});
const toast = useToast();

const status = ref(null);
const logs = ref([]);
const loading = ref(true);
const logsLoading = ref(true);

// Queue
const queueData = ref(null);
const queueLoading = ref(false);
const retrying = ref(null);

// Webhook logs
const webhookLogs = ref([]);
const webhookLoading = ref(false);
const webhookSource = ref('');

// DB table sizes
const tableSizes = ref([]);
const tableSizesLoading = ref(false);

// Maintenance
const maintenance = ref({ maintenance: false, message: '', announcement: '' });
const maintenanceLoading = ref(false);
const savingMaintenance = ref(false);

// Cache
const flushingCache = ref(false);

const systemCards = computed(() => {
  if (!status.value) return {};
  return {
    'Database Engine': {
      value: status.value.database.driver.toUpperCase(),
      icon: 'pi pi-database',
      color: 'text-emerald-500',
      bg: 'bg-emerald-500/10',
      tag: status.value.database.ok ? 'Connected' : 'Error',
      severity: status.value.database.ok ? 'success' : 'danger'
    },
    'Storage Volume': {
      value: formatBytes(status.value.storage.used_bytes),
      icon: 'pi pi-folder-open',
      color: 'text-blue-500',
      bg: 'bg-blue-500/10',
      tag: status.value.storage.ok ? 'Writable' : 'Error',
      severity: status.value.storage.ok ? 'success' : 'danger'
    },
    'Environment': {
      value: status.value.env.toUpperCase(),
      icon: 'pi pi-globe',
      color: 'text-indigo-500',
      bg: 'bg-indigo-500/10',
      tag: 'System',
      severity: 'info'
    },
    'Runtime Versions': {
      value: `PHP ${status.value.php_version} / Laravel ${status.value.laravel_version}`,
      icon: 'pi pi-code',
      color: 'text-orange-500',
      bg: 'bg-orange-500/10',
      tag: 'Core',
      severity: 'warning'
    }
  };
});

onMounted(async () => {
  setBreadcrumb([{ label: 'System Status' }]);
  await Promise.all([
    fetchStatus(),
    fetchLogs(),
    fetchQueue(),
    fetchWebhookLogs(),
    fetchTableSizes(),
    fetchMaintenance(),
  ]);
});

async function fetchStatus() {
  loading.value = true;
  try {
    const res = await api.get('/admin/system-status');
    status.value = res?.data?.data || res?.data;
  } finally { loading.value = false; }
}

async function fetchLogs() {
  logsLoading.value = true;
  try {
    const res = await api.get('/admin/error-logs');
    const d = res?.data?.data || res?.data;
    logs.value = d?.lines || [];
  } finally { logsLoading.value = false; }
}

async function fetchQueue() {
  queueLoading.value = true;
  try {
    const res = await api.get('/admin/queue-monitor');
    queueData.value = res?.data?.data || res?.data;
  } finally { queueLoading.value = false; }
}

async function retryJob(uuid) {
  retrying.value = uuid;
  try {
    await api.post('/admin/queue/retry', { uuid });
    toast.add({ severity: 'success', summary: 'Queued', detail: 'Job queued for retry', life: 2000 });
    await fetchQueue();
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Retry failed', life: 3000 });
  } finally { retrying.value = null; }
}

async function fetchWebhookLogs() {
  webhookLoading.value = true;
  try {
    const res = await api.get('/admin/webhook-logs', { params: { source: webhookSource.value || undefined } });
    webhookLogs.value = res?.data?.data || res?.data || [];
  } finally { webhookLoading.value = false; }
}

async function fetchTableSizes() {
  tableSizesLoading.value = true;
  try {
    const res = await api.get('/admin/db-table-sizes');
    tableSizes.value = res?.data?.data || res?.data || [];
  } finally { tableSizesLoading.value = false; }
}

async function fetchMaintenance() {
  maintenanceLoading.value = true;
  try {
    const res = await api.get('/admin/maintenance');
    maintenance.value = res?.data?.data || res?.data || maintenance.value;
  } finally { maintenanceLoading.value = false; }
}

async function saveMaintenance() {
  savingMaintenance.value = true;
  try {
    await api.post('/admin/maintenance', maintenance.value);
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Maintenance settings updated', life: 2000 });
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save', life: 3000 });
  } finally { savingMaintenance.value = false; }
}

async function flushCache() {
  const result = await Swal.fire({
    title: 'Flush System Cache?',
    text: 'This will purge all cached data and temporary files. This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, purge cache',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444',
    background: isDark.value ? '#111827' : '#fff',
    color: isDark.value ? '#f1f5f9' : '#0f172a',
    customClass: {
      popup: 'rounded-[2rem] border-0 shadow-2xl',
      confirmButton: 'rounded-xl font-bold uppercase tracking-widest text-[10px] px-6 py-3',
      cancelButton: 'rounded-xl font-bold uppercase tracking-widest text-[10px] px-6 py-3'
    }
  });
  if (!result.isConfirmed) return;
  flushingCache.value = true;
  try {
    await api.post('/admin/cache/flush');
    toast.add({ severity: 'success', summary: 'Done', detail: 'System cache purged successfully', life: 2000 });
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Cache purge failed', life: 3000 });
  } finally { flushingCache.value = false; }
}

function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function maxTableSize() {
  return Math.max(...tableSizes.value.map(t => parseFloat(t.size_mb || 0)), 1);
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.05); border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); }
</style>
