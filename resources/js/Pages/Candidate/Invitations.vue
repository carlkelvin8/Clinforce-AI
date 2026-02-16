<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '@/Components/AppLayout.vue';
import { http } from '@/lib/http';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';

const router = useRouter();
const invitations = ref([]);
const loading = ref(true);
const busyId = ref(null);

async function load() {
    loading.value = true;
    try {
        const res = await http.get('/invitations');
        // Handle pagination structure if present
        const raw = res.data?.data || res.data || [];
        invitations.value = Array.isArray(raw) ? raw : (raw.data || []);
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

onMounted(load);

function goToJobs(inv) {
    const eid = inv?.employer_id || inv?.employer?.id;
    if (!eid) return;
    router.push({
        name: 'candidate.jobs',
        query: { employer_id: String(eid) }
    });
}

async function accept(inv) {
    if (!inv?.id || busyId.value) return;
    busyId.value = inv.id;
    try {
        const res = await http.post(`/invitations/${inv.id}/accept`);
        const updated = res.data?.data || res.data || null;
        invitations.value = invitations.value.map((i) =>
            i.id === inv.id && updated ? updated : i
        );
    } catch (e) {
        console.error(e);
    } finally {
        busyId.value = null;
    }
}

function getSeverity(status) {
    switch(String(status).toLowerCase()) {
        case 'accepted': return 'success';
        case 'declined': return 'danger';
        default: return 'warning'; // pending
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    });
}
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Received Invitations</h1>
                <p class="text-gray-500 mt-1">Manage invitations from employers.</p>
            </div>
            <Button icon="pi pi-refresh" label="Refresh" text @click="load" :loading="loading" />
        </div>

        <div class="card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
            <DataTable :value="invitations" :loading="loading" stripedRows responsiveLayout="scroll">
                <template #empty>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-envelope text-gray-300 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">No invitations received</h3>
                        <p class="text-gray-500 mb-6">Invitations from employers will appear here.</p>
                    </div>
                </template>

                <Column header="Employer">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <Avatar 
                                label="C" 
                                shape="circle" 
                                class="bg-purple-50 text-purple-600 border border-purple-100" 
                            />
                            <div>
                                <div class="font-bold text-gray-900">
                                    Confidential Employer
                                </div>
                                <div class="text-xs text-gray-500">
                                    <span v-if="data.employer?.employer_profile">
                                        <span>
                                            {{ data.employer.employer_profile.business_type || 'Healthcare organization' }}
                                        </span>
                                        <span v-if="data.employer.employer_profile.city || data.employer.employer_profile.country_code">
                                            •
                                            <span>
                                                {{ [data.employer.employer_profile.city, data.employer.employer_profile.country_code].filter(Boolean).join(', ') }}
                                            </span>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="message" header="Message">
                     <template #body="{ data }">
                        <span class="text-sm text-gray-600 italic">"{{ data.message || 'No message' }}"</span>
                     </template>
                </Column>

                <Column field="status" header="Status">
                     <template #body="{ data }">
                        <Tag :value="data.status" :severity="getSeverity(data.status)" class="uppercase text-[10px]" rounded />
                     </template>
                </Column>

                <Column field="created_at" header="Received On">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>

                <Column header="Actions">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                          <Button 
                              label="Accept" 
                              icon="pi pi-check" 
                              size="small" 
                              severity="success"
                              :disabled="String(data.status).toLowerCase() === 'accepted' || !!busyId"
                              :loading="busyId === data.id"
                              @click.stop="accept(data)" 
                          />
                          <Button 
                              label="View jobs" 
                              icon="pi pi-briefcase" 
                              size="small" 
                              class="!text-sm"
                              @click.stop="goToJobs(data)" 
                          />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
  </AppLayout>
</template>
