<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import AdminPagination from './AdminPagination.vue';
import api from '@/lib/api';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const activeTab = ref('red-flags');

// Red Flags
const flags = ref([]);
const flagsMeta = ref({});
const flagsLoading = ref(false);
const flagsPage = ref(1);
const flagsStatus = ref('');

// Reviews
const reviews = ref([]);
const reviewsMeta = ref({});
const reviewsLoading = ref(false);
const reviewsPage = ref(1);
const reviewsStatus = ref('pending');

// Trust Scores
const scores = ref([]);
const scoresMeta = ref({});
const scoresLoading = ref(false);
const scoresPage = ref(1);

// Dialogs
const flagDialog = ref(false);
const flagTarget = ref(null);
const flagStatus = ref('under_review');
const flagNotes = ref('');
const saving = ref(false);

const reviewDialog = ref(false);
const reviewTarget = ref(null);
const reviewDecision = ref('approved');

const flagStatusOptions = [
  { label: 'Under Review', value: 'under_review' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Dismissed', value: 'dismissed' },
];

const flagSeverity = { low: 'secondary', medium: 'warn', high: 'danger', critical: 'danger' };
const flagStatusSeverity = { reported: 'warn', under_review: 'info', confirmed: 'danger', dismissed: 'secondary' };
const reviewStatusSeverity = { pending: 'warn', approved: 'success', rejected: 'danger' };

async function fetchFlags(p = 1) {
  flagsLoading.value = true; flagsPage.value = p;
  try {
    const res = await api.get('/admin/trust/red-flags', { params: { page: p, status: flagsStatus.value || undefined } });
    const d = res?.data?.data || res?.data;
    flags.value = d?.data || d || [];
    flagsMeta.value = { total: d?.total, last_page: d?.last_page };
  } finally { flagsLoading.value = false; }
}

async function fetchReviews(p = 1) {
  reviewsLoading.value = true; reviewsPage.value = p;
  try {
    const res = await api.get('/admin/trust/employer-reviews', { params: { page: p, status: reviewsStatus.value || undefined } });
    const d = res?.data?.data || res?.data;
    reviews.value = d?.data || d || [];
    reviewsMeta.value = { total: d?.total, last_page: d?.last_page };
  } finally { reviewsLoading.value = false; }
}

async function fetchScores(p = 1) {
  scoresLoading.value = true; scoresPage.value = p;
  try {
    const res = await api.get('/admin/trust/employer-scores', { params: { page: p } });
    const d = res?.data?.data || res?.data;
    scores.value = d?.data || d || [];
    scoresMeta.value = { total: d?.total, last_page: d?.last_page };
  } finally { scoresLoading.value = false; }
}

function openFlagDialog(flag) {
  flagTarget.value = flag;
  flagStatus.value = flag.status === 'reported' ? 'under_review' : flag.status;
  flagNotes.value = flag.resolution_notes || '';
  flagDialog.value = true;
}

async function submitFlagUpdate() {
  saving.value = true;
  try {
    await api.patch(`/admin/trust/red-flags/${flagTarget.value.id}`, {
      status: flagStatus.value,
      resolution_notes: flagNotes.value,
    });
    toast.add({ severity: 'success', summary: 'Done', detail: 'Red flag updated', life: 2000 });
    flagDialog.value = false;
    fetchFlags(flagsPage.value);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

function openReviewDialog(review) {
  reviewTarget.value = review;
  reviewDecision.value = 'approved';
  reviewDialog.value = true;
}

async function submitReviewDecision() {
  saving.value = true;
  try {
    await api.patch(`/admin/trust/employer-reviews/${reviewTarget.value.id}`, { status: reviewDecision.value });
    toast.add({ severity: 'success', summary: 'Done', detail: `Review ${reviewDecision.value}`, life: 2000 });
    reviewDialog.value = false;
    fetchReviews(reviewsPage.value);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'red-flags' && !flags.value.length) fetchFlags(1);
  if (tab === 'reviews' && !reviews.value.length) fetchReviews(1);
  if (tab === 'scores' && !scores.value.length) fetchScores(1);
}

onMounted(() => {
  setBreadcrumb([{ label: 'Trust & Safety', to: '/admin/trust' }, { label: 'Employer Trust' }]);
  fetchFlags();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Employer Trust &amp; Safety</h1>
        <p :class="['text-sm mt-1', textSub]">Red flags, interview reviews, and trust scores</p>
      </div>

      <!-- Tabs -->
      <div :class="['flex gap-1 p-1 rounded-xl w-fit', isDark ? 'bg-slate-800' : 'bg-slate-100']">
        <button v-for="tab in [
          { key: 'red-flags', label: 'Red Flags' },
          { key: 'reviews', label: 'Interview Reviews' },
          { key: 'scores', label: 'Trust Scores' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition', activeTab === tab.key
            ? (isDark ? 'bg-slate-700 text-white' : 'bg-white text-slate-900 shadow-sm')
            : (isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900')]"
          @click="switchTab(tab.key)">
          {{ tab.label }}
        </button>
      </div>

      <!-- Red Flags Tab -->
      <template v-if="activeTab === 'red-flags'">
        <div class="flex gap-3">
          <Select v-model="flagsStatus" :options="[
            { label: 'All', value: '' },
            { label: 'Reported', value: 'reported' },
            { label: 'Under Review', value: 'under_review' },
            { label: 'Confirmed', value: 'confirmed' },
            { label: 'Dismissed', value: 'dismissed' },
          ]" optionLabel="label" optionValue="value" :class="['text-sm', input]" @change="fetchFlags(1)" />
        </div>

        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Employer</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Flag Type</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Severity</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Reported By</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Date</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="flagsLoading"><td colspan="7" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!flags.length"><td colspan="7" :class="['px-5 py-10 text-center text-sm', textMuted]">No red flags found</td></tr>
              <tr v-else v-for="flag in flags" :key="flag.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td class="px-5 py-3.5">
                  <div :class="['text-xs font-medium', text]">{{ flag.business_name || flag.employer_email }}</div>
                  <div v-if="flag.business_name" :class="['text-xs', textMuted]">{{ flag.employer_email }}</div>
                </td>
                <td :class="['px-5 py-3.5 text-xs capitalize', textSub]">{{ flag.flag_type?.replace(/_/g, ' ') }}</td>
                <td class="px-5 py-3.5"><Tag :value="flag.severity" :severity="flagSeverity[flag.severity] || 'secondary'" class="text-xs" /></td>
                <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ flag.reporter_email || 'System' }}</td>
                <td class="px-5 py-3.5"><Tag :value="flag.status?.replace(/_/g, ' ')" :severity="flagStatusSeverity[flag.status] || 'secondary'" class="text-xs" /></td>
                <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ flag.created_at ? new Date(flag.created_at).toLocaleDateString() : '—' }}</td>
                <td class="px-5 py-3.5">
                  <Button label="Update" size="small" @click="openFlagDialog(flag)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="flagsPage" :last-page="flagsMeta.last_page || 1" :total="flagsMeta.total" @change="fetchFlags" />
      </template>

      <!-- Interview Reviews Tab -->
      <template v-else-if="activeTab === 'reviews'">
        <div class="flex gap-3">
          <Select v-model="reviewsStatus" :options="[
            { label: 'Pending', value: 'pending' },
            { label: 'Approved', value: 'approved' },
            { label: 'Rejected', value: 'rejected' },
            { label: 'All', value: '' },
          ]" optionLabel="label" optionValue="value" :class="['text-sm', input]" @change="fetchReviews(1)" />
        </div>

        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Employer</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Reviewer</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Rating</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Would Recommend</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Anonymous</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="reviewsLoading"><td colspan="7" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!reviews.length"><td colspan="7" :class="['px-5 py-10 text-center text-sm', textMuted]">No reviews found</td></tr>
              <tr v-else v-for="r in reviews" :key="r.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td class="px-5 py-3.5">
                  <div :class="['text-xs font-medium', text]">{{ r.business_name || r.employer_email }}</div>
                  <div v-if="r.business_name" :class="['text-xs', textMuted]">{{ r.employer_email }}</div>
                </td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ r.is_anonymous ? 'Anonymous' : r.applicant_email }}</td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1">
                    <span v-for="i in 5" :key="i" :class="['text-sm', i <= Math.round(r.overall_rating) ? 'text-yellow-400' : (isDark ? 'text-slate-600' : 'text-slate-300')]">★</span>
                    <span :class="['text-xs ml-1', textMuted]">{{ Number(r.overall_rating).toFixed(1) }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <Tag :value="r.would_recommend ? 'Yes' : 'No'" :severity="r.would_recommend ? 'success' : 'danger'" class="text-xs" />
                </td>
                <td class="px-5 py-3.5">
                  <Tag :value="r.is_anonymous ? 'Yes' : 'No'" severity="secondary" class="text-xs" />
                </td>
                <td class="px-5 py-3.5"><Tag :value="r.status" :severity="reviewStatusSeverity[r.status] || 'secondary'" class="text-xs" /></td>
                <td class="px-5 py-3.5">
                  <Button v-if="r.status === 'pending'" label="Moderate" size="small" @click="openReviewDialog(r)" />
                  <span v-else :class="['text-xs', textMuted]">Done</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="reviewsPage" :last-page="reviewsMeta.last_page || 1" :total="reviewsMeta.total" @change="fetchReviews" />
      </template>

      <!-- Trust Scores Tab -->
      <template v-else>
        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Employer</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Trust Score</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Avg Rating</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Reviews</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Identity</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Business</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Last Calc</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="scoresLoading"><td colspan="7" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!scores.length"><td colspan="7" :class="['px-5 py-10 text-center text-sm', textMuted]">No trust scores yet</td></tr>
              <tr v-else v-for="s in scores" :key="s.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td class="px-5 py-3.5">
                  <div :class="['text-xs font-medium', text]">{{ s.business_name || s.email }}</div>
                  <div v-if="s.business_name" :class="['text-xs', textMuted]">{{ s.email }}</div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-2">
                    <div class="w-16 h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                      <div class="h-full rounded-full transition-all"
                        :style="{ width: s.overall_score + '%' }"
                        :class="s.overall_score >= 70 ? 'bg-green-500' : s.overall_score >= 40 ? 'bg-yellow-500' : 'bg-red-500'">
                      </div>
                    </div>
                    <span :class="['text-xs font-semibold', s.overall_score >= 70 ? 'text-green-500' : s.overall_score >= 40 ? 'text-yellow-500' : 'text-red-500']">
                      {{ Number(s.overall_score).toFixed(0) }}
                    </span>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1">
                    <span class="text-yellow-400 text-sm">★</span>
                    <span :class="['text-xs', text]">{{ Number(s.average_rating).toFixed(1) }}</span>
                  </div>
                </td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ s.total_reviews }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ Number(s.identity_score).toFixed(0) }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ Number(s.business_score).toFixed(0) }}</td>
                <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ s.last_calculated_at ? new Date(s.last_calculated_at).toLocaleDateString() : '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="scoresPage" :last-page="scoresMeta.last_page || 1" :total="scoresMeta.total" @change="fetchScores" />
      </template>
    </div>

    <!-- Red Flag Dialog -->
    <Dialog v-model:visible="flagDialog" header="Update Red Flag" :style="{ width: '440px' }" modal>
      <div v-if="flagTarget" class="space-y-4 pt-2">
        <div :class="['text-sm p-3 rounded-lg', isDark ? 'bg-slate-800' : 'bg-slate-50']">
          <div :class="['font-medium', text]">{{ flagTarget.business_name || flagTarget.employer_email }}</div>
          <div :class="['text-xs mt-1 capitalize', textSub]">{{ flagTarget.flag_type?.replace(/_/g, ' ') }}</div>
          <div v-if="flagTarget.description" :class="['text-xs mt-2', textMuted]">{{ flagTarget.description }}</div>
        </div>
        <div>
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Status</label>
          <Select v-model="flagStatus" :options="flagStatusOptions" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
        </div>
        <div>
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Resolution Notes</label>
          <Textarea v-model="flagNotes" rows="3" class="w-full mt-1.5" placeholder="Add investigation notes…" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="flagDialog = false" />
          <Button label="Save" :loading="saving" @click="submitFlagUpdate" />
        </div>
      </div>
    </Dialog>

    <!-- Review Moderation Dialog -->
    <Dialog v-model:visible="reviewDialog" header="Moderate Review" :style="{ width: '440px' }" modal>
      <div v-if="reviewTarget" class="space-y-4 pt-2">
        <div :class="['text-sm p-3 rounded-lg', isDark ? 'bg-slate-800' : 'bg-slate-50']">
          <div :class="['font-medium', text]">{{ reviewTarget.business_name || reviewTarget.employer_email }}</div>
          <div :class="['text-xs mt-1', textSub]">Reviewed by: {{ reviewTarget.is_anonymous ? 'Anonymous' : reviewTarget.applicant_email }}</div>
          <div v-if="reviewTarget.comments" :class="['text-xs mt-2 italic', textMuted]">"{{ reviewTarget.comments }}"</div>
        </div>
        <div>
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Decision</label>
          <div class="flex gap-2 mt-2">
            <button v-for="opt in ['approved', 'rejected']" :key="opt"
              :class="['px-4 py-2 rounded-lg text-sm font-medium border transition', reviewDecision === opt
                ? (opt === 'approved' ? 'bg-green-500 text-white border-green-500' : 'bg-red-500 text-white border-red-500')
                : (isDark ? 'border-slate-600 text-slate-300 hover:bg-slate-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50')]"
              @click="reviewDecision = opt">
              {{ opt === 'approved' ? '✓ Approve' : '✗ Reject' }}
            </button>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="reviewDialog = false" />
          <Button :label="reviewDecision === 'approved' ? 'Approve' : 'Reject'"
            :severity="reviewDecision === 'approved' ? 'success' : 'danger'"
            :loading="saving" @click="submitReviewDecision" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
