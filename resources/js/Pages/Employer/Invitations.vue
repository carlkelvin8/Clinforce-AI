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
import Swal from 'sweetalert2';

const router = useRouter();

const invitations = ref([]);
const loading = ref(true);

function avatarUrl(inv) {
    const cp = inv?.candidate_profile;
    const u = inv?.candidate;
    return u?.avatar_url || (cp?.avatar ? `/storage/${cp.avatar}` : null) || null;
}

async function load() {
    loading.value = true;
    try {
        const res = await http.get('/invitations');
        // Handle pagination structure if present
        const raw = res.data?.data || res.data || [];
        invitations.value = Array.isArray(raw) ? raw : (raw.data || []);
    } catch (e) {
        console.error(e);
        await Swal.fire({
            icon: 'error',
            title: 'Failed to load invitations',
            text: e?.response?.data?.message || e?.message || 'Something went wrong while loading your invitations.',
        });
    } finally {
        loading.value = false;
    }
}

function viewApplicant(inv) {
  const userId =
    inv?.candidate_id ||
    inv?.candidate?.id ||
    inv?.candidate_profile?.user_id ||
    null;
  if (!userId) {
    Swal.fire({
      icon: 'info',
      title: 'No applicant data',
      text: 'Could not determine which applicant to open for this invitation.',
    });
    return;
  }
  router.push({ name: 'employer.candidates.view', params: { id: userId } });
}

onMounted(load);

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
                <h1 class="text-2xl font-bold text-gray-900">Sent Invitations</h1>
                <p class="text-gray-500 mt-1">Track candidates you have invited to apply.</p>
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
                        <h3 class="text-lg font-medium text-gray-900">No invitations sent</h3>
                        <p class="text-gray-500 mb-6">Start by searching for talent.</p>
                        <Button label="Search Talent" icon="pi pi-search" @click="$router.push({name: 'employer.talentsearch'})" />
                    </div>
                </template>

                <Column header="Candidate">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3 py-2 cursor-pointer hover:bg-slate-50 rounded-lg -mx-2 px-2" @click="viewApplicant(data)">
                            <Avatar
                                :image="avatarUrl(data)"
                                :label="!avatarUrl(data) ? (data.candidate_profile?.last_name?.[0] || data.candidate_profile?.first_name?.[0] || 'C') : null"
                                shape="circle"
                                class="w-10 h-10 bg-indigo-50 text-indigo-600 border border-indigo-100 text-sm font-semibold"
                            />
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-900 text-sm truncate">
                                    {{ data.candidate_name || (data.candidate_profile ? `${data.candidate_profile.first_name} ${data.candidate_profile.last_name?.[0] || ''}.`.trim() : 'Unknown candidate') }}
                                </div>
                                <div class="flex flex-wrap items-center gap-1 text-xs text-gray-500">
                                    <span class="truncate max-w-[180px]">
                                        {{ data.candidate_profile?.headline || 'No headline provided' }}
                                    </span>
                                    <span v-if="data.candidate_profile?.city || data.candidate_profile?.country_code">•</span>
                                    <span v-if="data.candidate_profile?.city || data.candidate_profile?.country_code" class="truncate">
                                        {{ [data.candidate_profile?.city, data.candidate_profile?.country_code].filter(Boolean).join(', ') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="status" header="Status">
                     <template #body="{ data }">
                        <Tag :value="data.status" :severity="getSeverity(data.status)" class="uppercase text-[10px]" rounded />
                     </template>
                </Column>

                <Column field="created_at" header="Sent On">
                    <template #body="{ data }">
                        <span class="text-sm text-gray-600">{{ formatDate(data.created_at) }}</span>
                    </template>
                </Column>

                <Column header="Actions">
                  <template #body="{ data }">
                    <Button
                      label="View applicant"
                      icon="pi pi-user"
                      size="small"
                      text
                      class="!text-indigo-600"
                      @click.stop="viewApplicant(data)"
                    />
                  </template>
                </Column>
            </DataTable>
        </div>
    </div>
  </AppLayout>
</template>
