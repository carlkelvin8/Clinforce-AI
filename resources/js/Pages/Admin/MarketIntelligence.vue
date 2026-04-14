<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import AdminPagination from './AdminPagination.vue';
import api from '@/lib/api';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const activeTab = ref('benchmarks');

// Salary Benchmarks
const benchmarks = ref([]);
const benchmarksMeta = ref({});
const benchmarksLoading = ref(false);
const benchmarksPage = ref(1);

// Supply/Demand
const supplyDemand = ref([]);
const supplyMeta = ref({});
const supplyLoading = ref(false);
const supplyPage = ref(1);

// Trending Skills
const skills = ref([]);
const skillsMeta = ref({});
const skillsLoading = ref(false);
const skillsPage = ref(1);

// Competitors
const competitors = ref([]);
const competitorsMeta = ref({});
const competitorsLoading = ref(false);
const competitorsPage = ref(1);

// Dialogs
const benchmarkDialog = ref(false);
const newBenchmark = ref({
  job_title: '',
  normalized_title: '',
  country: 'Philippines',
  state: '',
  city: '',
  salary_min_cents: 0,
  salary_max_cents: 0,
  salary_median_cents: 0,
  currency_code: 'PHP',
});

const supplyDialog = ref(false);
const newSupply = ref({
  job_category: '',
  specialty: '',
  country: 'Philippines',
  state: '',
  city: '',
  active_jobs_count: 0,
  active_candidates_count: 0,
  market_temperature: 'warm',
});

const skillDialog = ref(false);
const newSkill = ref({
  skill_name: '',
  skill_category: 'technical',
  mention_count: 0,
  trend_direction: 'stable',
});

async function loadBenchmarks(p = 1) {
  benchmarksLoading.value = true; benchmarksPage.value = p;
  try {
    // Mock data for now - replace with actual API call
    benchmarks.value = [
      { id: 1, normalized_title: 'Registered Nurse', country: 'Philippines', state: 'NCR', salary_median_cents: 45000000, currency_code: 'PHP', sample_size: 150 },
      { id: 2, normalized_title: 'Staff Nurse', country: 'Philippines', state: 'Cebu', salary_median_cents: 38000000, currency_code: 'PHP', sample_size: 89 },
      { id: 3, normalized_title: 'ICU Nurse', country: 'Philippines', state: 'NCR', salary_median_cents: 55000000, currency_code: 'PHP', sample_size: 67 },
    ];
    benchmarksMeta.value = { total: 3, last_page: 1 };
  } finally { benchmarksLoading.value = false; }
}

async function loadSupplyDemand(p = 1) {
  supplyLoading.value = true; supplyPage.value = p;
  try {
    // Mock data
    supplyDemand.value = [
      { id: 1, job_category: 'Nurse', country: 'Philippines', state: 'NCR', city: 'Manila', active_jobs_count: 245, active_candidates_count: 89, market_temperature: 'hot' },
      { id: 2, job_category: 'Nurse', country: 'Philippines', state: 'Cebu', city: 'Cebu City', active_jobs_count: 156, active_candidates_count: 134, market_temperature: 'warm' },
      { id: 3, job_category: 'Doctor', country: 'Philippines', state: 'NCR', city: 'Quezon City', active_jobs_count: 78, active_candidates_count: 23, market_temperature: 'very_hot' },
    ];
    supplyMeta.value = { total: 3, last_page: 1 };
  } finally { supplyLoading.value = false; }
}

async function loadSkills(p = 1) {
  skillsLoading.value = true; skillsPage.value = p;
  try {
    // Mock data
    skills.value = [
      { id: 1, skill_name: 'BLS Certification', skill_category: 'certification', mention_count: 456, trend_direction: 'rising' },
      { id: 2, skill_name: 'ICU Experience', skill_category: 'clinical', mention_count: 389, trend_direction: 'stable' },
      { id: 3, skill_name: 'ACLS Certification', skill_category: 'certification', mention_count: 234, trend_direction: 'rising' },
    ];
    skillsMeta.value = { total: 3, last_page: 1 };
  } finally { skillsLoading.value = false; }
}

async function loadCompetitors(p = 1) {
  competitorsLoading.value = true; competitorsPage.value = p;
  try {
    // Mock data
    competitors.value = [
      { id: 1, competitor_name: 'HealthJobs PH', normalized_title: 'Registered Nurse', job_count: 89, avg_min_salary: 35000, avg_max_salary: 50000, currency_code: 'PHP' },
      { id: 2, competitor_name: 'JobStreet', normalized_title: 'Staff Nurse', job_count: 67, avg_min_salary: 30000, avg_max_salary: 45000, currency_code: 'PHP' },
      { id: 3, competitor_name: 'Indeed', normalized_title: 'ICU Nurse', job_count: 45, avg_min_salary: 40000, avg_max_salary: 60000, currency_code: 'PHP' },
    ];
    competitorsMeta.value = { total: 3, last_page: 1 };
  } finally { competitorsLoading.value = false; }
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'benchmarks') loadBenchmarks(1);
  if (tab === 'supply') loadSupplyDemand(1);
  if (tab === 'skills') loadSkills(1);
  if (tab === 'competitors') loadCompetitors(1);
}

const temperatureColors = {
  cold: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  cool: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
  warm: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  hot: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
  very_hot: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const trendColors = {
  rising: 'text-green-500',
  stable: 'text-slate-400',
  declining: 'text-red-500',
};

const trendIcons = {
  rising: 'pi-arrow-up',
  stable: 'pi-minus',
  declining: 'pi-arrow-down',
};

onMounted(() => {
  setBreadcrumb([{ label: 'Market Intelligence' }]);
  loadBenchmarks();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Market Intelligence</h1>
        <p :class="['text-sm mt-1', textSub]">Salary benchmarks, supply/demand data, and market trends</p>
      </div>

      <!-- Tabs -->
      <div :class="['flex gap-1 p-1 rounded-xl w-fit', isDark ? 'bg-slate-800' : 'bg-slate-100']">
        <button v-for="tab in [
          { key: 'benchmarks', label: 'Salary Benchmarks', icon: 'pi-dollar' },
          { key: 'supply', label: 'Supply/Demand', icon: 'pi-chart-bar' },
          { key: 'skills', label: 'Trending Skills', icon: 'pi-star' },
          { key: 'competitors', label: 'Competitors', icon: 'pi-eye' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? (isDark ? 'bg-slate-700 text-white' : 'bg-white text-slate-900 shadow-sm')
            : (isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900')]"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Salary Benchmarks Tab -->
      <template v-if="activeTab === 'benchmarks'">
        <div class="flex justify-between items-center">
          <div></div>
          <Button label="Add Benchmark" icon="pi pi-plus" size="small" @click="benchmarkDialog = true" />
        </div>

        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Job Title</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Location</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Median Salary</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Sample Size</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="benchmarksLoading"><td colspan="5" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!benchmarks.length"><td colspan="5" :class="['px-5 py-10 text-center text-sm', textMuted]">No benchmarks found</td></tr>
              <tr v-else v-for="b in benchmarks" :key="b.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td :class="['px-5 py-3.5 font-medium', text]">{{ b.normalized_title }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ b.city ? `${b.city}, ` : '' }}{{ b.state }}, {{ b.country }}</td>
                <td :class="['px-5 py-3.5 font-mono text-xs', text]">{{ b.currency_code }} {{ Math.round(b.salary_median_cents / 100).toLocaleString() }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ b.sample_size }}</td>
                <td class="px-5 py-3.5">
                  <Button icon="pi pi-pencil" size="small" text />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="benchmarksPage" :last-page="benchmarksMeta.last_page || 1" :total="benchmarksMeta.total" @change="loadBenchmarks" />
      </template>

      <!-- Supply/Demand Tab -->
      <template v-else-if="activeTab === 'supply'">
        <div class="flex justify-between items-center">
          <div></div>
          <Button label="Add Data Point" icon="pi pi-plus" size="small" @click="supplyDialog = true" />
        </div>

        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Location</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Category</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Jobs</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Candidates</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Market</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="supplyLoading"><td colspan="6" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!supplyDemand.length"><td colspan="6" :class="['px-5 py-10 text-center text-sm', textMuted]">No supply/demand data</td></tr>
              <tr v-else v-for="s in supplyDemand" :key="s.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td :class="['px-5 py-3.5 text-xs', text]">{{ s.city }}, {{ s.state }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ s.job_category }}</td>
                <td :class="['px-5 py-3.5 text-xs', text]">{{ s.active_jobs_count }}</td>
                <td :class="['px-5 py-3.5 text-xs', text]">{{ s.active_candidates_count }}</td>
                <td class="px-5 py-3.5">
                  <span :class="['px-2 py-1 rounded text-xs font-medium capitalize', temperatureColors[s.market_temperature] || temperatureColors.warm]">
                    {{ s.market_temperature?.replace(/_/g, ' ') }}
                  </span>
                </td>
                <td class="px-5 py-3.5">
                  <Button icon="pi pi-pencil" size="small" text />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="supplyPage" :last-page="supplyMeta.last_page || 1" :total="supplyMeta.total" @change="loadSupplyDemand" />
      </template>

      <!-- Trending Skills Tab -->
      <template v-else-if="activeTab === 'skills'">
        <div class="flex justify-between items-center">
          <div></div>
          <Button label="Add Skill" icon="pi pi-plus" size="small" @click="skillDialog = true" />
        </div>

        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Skill Name</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Category</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Mentions</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Trend</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="skillsLoading"><td colspan="5" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!skills.length"><td colspan="5" :class="['px-5 py-10 text-center text-sm', textMuted]">No skills data</td></tr>
              <tr v-else v-for="s in skills" :key="s.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td :class="['px-5 py-3.5 font-medium', text]">{{ s.skill_name }}</td>
                <td :class="['px-5 py-3.5 text-xs capitalize', textSub]">{{ s.skill_category?.replace(/_/g, ' ') }}</td>
                <td :class="['px-5 py-3.5 text-xs', text]">{{ s.mention_count }}</td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-2">
                    <i :class="['pi text-xs', trendIcons[s.trend_direction], trendColors[s.trend_direction]]"></i>
                    <span :class="['text-xs capitalize', trendColors[s.trend_direction]]">{{ s.trend_direction }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <Button icon="pi pi-pencil" size="small" text />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="skillsPage" :last-page="skillsMeta.last_page || 1" :total="skillsMeta.total" @change="loadSkills" />
      </template>

      <!-- Competitors Tab -->
      <template v-else>
        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Competitor</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Job Title</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Job Count</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Salary Range</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="competitorsLoading"><td colspan="4" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!competitors.length"><td colspan="4" :class="['px-5 py-10 text-center text-sm', textMuted]">No competitor data</td></tr>
              <tr v-else v-for="c in competitors" :key="c.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td :class="['px-5 py-3.5 font-medium', text]">{{ c.competitor_name }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ c.normalized_title }}</td>
                <td :class="['px-5 py-3.5 text-xs', text]">{{ c.job_count }}</td>
                <td :class="['px-5 py-3.5 text-xs font-mono', text]">
                  {{ c.currency_code }} {{ Math.round(c.avg_min_salary).toLocaleString() }} - {{ Math.round(c.avg_max_salary).toLocaleString() }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="competitorsPage" :last-page="competitorsMeta.last_page || 1" :total="competitorsMeta.total" @change="loadCompetitors" />
      </template>
    </div>

    <!-- Add Benchmark Dialog -->
    <Dialog v-model:visible="benchmarkDialog" header="Add Salary Benchmark" :style="{ width: '500px' }" modal>
      <div class="space-y-4 pt-2">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Job Title</label>
            <InputText v-model="newBenchmark.job_title" class="w-full mt-1.5" />
          </div>
          <div>
            <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Normalized Title</label>
            <InputText v-model="newBenchmark.normalized_title" class="w-full mt-1.5" />
          </div>
          <div>
            <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Country</label>
            <InputText v-model="newBenchmark.country" class="w-full mt-1.5" />
          </div>
          <div>
            <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">State</label>
            <InputText v-model="newBenchmark.state" class="w-full mt-1.5" />
          </div>
          <div>
            <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Min Salary</label>
            <InputNumber v-model="newBenchmark.salary_min_cents" class="w-full mt-1.5" />
          </div>
          <div>
            <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Max Salary</label>
            <InputNumber v-model="newBenchmark.salary_max_cents" class="w-full mt-1.5" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="benchmarkDialog = false" />
          <Button label="Save" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>