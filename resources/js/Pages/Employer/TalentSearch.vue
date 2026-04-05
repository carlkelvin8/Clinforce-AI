<script setup>
import { computed, onMounted, ref, watch as vueWatch } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import { http } from "../../lib/http";
import Swal from "sweetalert2";

// PrimeVue Components
import Button from "primevue/button";
import Card from "primevue/card";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Avatar from "primevue/avatar";
import Tag from "primevue/tag";
import Badge from "primevue/badge";

/** ===== Router ===== */
const route = useRoute();
const router = useRouter();

/** ===== Talent Search logic ===== */
const loading = ref(true);
const error = ref("");

const q = ref("");
const locationFilter = ref("");
const openToWorkOnly = ref(false);
const candidates = ref([]);

// Debounce helper
let timer = null;
function debounceLoad() {
  clearTimeout(timer);
  timer = setTimeout(() => {
    load();
  }, 500);
}

async function load() {
  loading.value = true;
  error.value = "";

  try {
    // Use new dedicated Talent Search endpoint
    const params = {};
    if (q.value.trim()) params.q = q.value.trim();
    if (locationFilter.value && locationFilter.value !== "all") params.location = locationFilter.value;
    if (openToWorkOnly.value) params.open_to_work = 1;

    const res = await http.get("/talent-search", { params });
    
    // Robust array extraction
    let raw = res.data?.data || res.data || [];
    if (!Array.isArray(raw)) {
       if (raw.data && Array.isArray(raw.data)) raw = raw.data;
       else if (raw.items && Array.isArray(raw.items)) raw = raw.items;
       else raw = [];
    }
    
    candidates.value = raw.map(p => {
      // Mask last name — show first name + last initial only
      const nameParts = String(p.name || '').trim().split(/\s+/)
      const maskedName = nameParts.length >= 2
        ? `${nameParts[0]} ${nameParts[nameParts.length - 1][0].toUpperCase()}.`
        : (p.name || 'Candidate')
      return {
        id: p.id,
        name: maskedName,
        location: [p.city, p.country_code].filter(Boolean).join(", ") || "—",
        specialty: p.headline || "—",
        role: p.headline || "—",
        last_active: p.updated_at,
        match: p.match,
        blurb: p.summary || "",
        avatar: p.avatar
      }
    })

  } catch (e) {
    console.error(e);
    const code = e?.response?.status;
    error.value =
      e?.response?.data?.message ||
      (code ? `Request failed (${code})` : "") ||
      e?.message ||
      "Failed to load candidates.";
    candidates.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(() => load());

// Watchers for server-side search
vueWatch([q, locationFilter, openToWorkOnly], () => {
  debounceLoad();
});

// Use candidates directly since filtering is server-side
const filtered = computed(() => candidates.value);

function safeDate(val) {
  const d = new Date(val || 0);
  return Number.isFinite(d.getTime()) ? d : null;
}
function fmtDate(val) {
  const d = safeDate(val);
  if (!d) return "—";
  return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" });
}
function initials(name) {
  const t = String(name || "").trim();
  if (!t) return "C";
  const parts = t.split(/\s+/).filter(Boolean);
  const a = parts[0]?.[0] || "C";
  const b = parts.length > 1 ? parts[1]?.[0] : (parts[0]?.[1] || "");
  return (a + b).toUpperCase();
}

const inviting = ref({});
const invited = ref({});
const selected = ref(new Set());
const bulkInviting = ref(false);

function toggleSelect(id) {
  const s = new Set(selected.value);
  s.has(id) ? s.delete(id) : s.add(id);
  selected.value = s;
}

function toggleSelectAll() {
  if (selected.value.size === filtered.value.length) {
    selected.value = new Set();
  } else {
    selected.value = new Set(filtered.value.map(c => c.id));
  }
}

async function invite(c) {
  if (inviting.value[c.id] || invited.value[c.id]) return;
  inviting.value[c.id] = true;
  try {
    await http.post('/invitations', { candidate_id: c.id });
    invited.value[c.id] = true;
  } catch (e) {
    const msg = e.response?.data?.message || "Failed to send invitation.";
    await Swal.fire({ icon: 'error', title: 'Invitation failed', text: msg });
  } finally {
    inviting.value[c.id] = false;
  }
}

async function bulkInvite() {
  if (!selected.value.size) return;
  const ids = Array.from(selected.value).filter(id => !invited.value[id]);
  if (!ids.length) return;

  const result = await Swal.fire({
    icon: 'question',
    title: `Invite ${ids.length} candidate(s)?`,
    text: 'An invitation will be sent to each selected candidate.',
    showCancelButton: true,
    confirmButtonText: 'Send Invitations',
    confirmButtonColor: '#2563eb',
  });
  if (!result.isConfirmed) return;

  bulkInviting.value = true;
  let ok = 0, fail = 0;
  for (const id of ids) {
    try {
      await http.post('/invitations', { candidate_id: id });
      invited.value[id] = true;
      ok++;
    } catch { fail++; }
  }
  bulkInviting.value = false;
  selected.value = new Set();
  await Swal.fire({
    icon: fail === 0 ? 'success' : 'warning',
    title: `${ok} sent${fail ? `, ${fail} failed` : ''}`,
    timer: 2000,
    showConfirmButton: false,
  });
}
</script>

<template>
  <AppLayout>
    <div class="flex flex-col gap-8 max-w-[1600px] mx-auto">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Clinicians</h1>
          <p class="text-gray-500 mt-2 text-lg">Find and connect with top clinicians from your pool.</p>
        </div>
        <Button 
          :label="loading ? 'Refreshing…' : 'Refresh List'" 
          :icon="loading ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'" 
          @click="load" 
          :disabled="loading" 
          text 
          severity="secondary" 
        />
      </div>

      <!-- Error -->
      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-lg text-sm font-medium">
        {{ error }}
      </div>

      <!-- Search/Filters -->
      <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-2">
         <div class="relative flex-1">
            <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <InputText 
              v-model="q" 
              placeholder="Search by name, headline, or keywords..." 
              class="w-full !pl-10 !border-0 !shadow-none !bg-transparent focus:!ring-0 text-lg" 
            />
         </div>
         <div class="h-px md:h-auto md:w-px bg-gray-200 mx-2"></div>
         <div class="relative md:w-64">
            <i class="pi pi-map-marker absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <InputText 
              v-model="locationFilter" 
              placeholder="Location..." 
              class="w-full !pl-10 !border-0 !shadow-none !bg-transparent focus:!ring-0 text-lg" 
            />
         </div>
         <div class="h-px md:h-auto md:w-px bg-gray-200 mx-2"></div>
         <button @click="openToWorkOnly = !openToWorkOnly"
           :class="['flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all border',
             openToWorkOnly ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:border-emerald-300']">
           <span class="w-2 h-2 rounded-full" :class="openToWorkOnly ? 'bg-white' : 'bg-emerald-400'"></span>
           Open to work
         </button>
      </div>

      <!-- Results Area -->
      <div>
         <div class="flex items-center justify-between mb-6 px-1">
            <div class="flex items-center gap-4">
              <div class="text-sm font-medium text-gray-500">
                Showing <span class="text-gray-900 font-bold">{{ filtered.length }}</span> candidates
              </div>
              <button
                v-if="filtered.length"
                class="text-xs text-blue-600 hover:underline font-medium"
                @click="toggleSelectAll"
              >
                {{ selected.size === filtered.length ? 'Deselect all' : 'Select all' }}
              </button>
            </div>
            <!-- Bulk invite bar -->
            <transition name="fade">
              <div v-if="selected.size > 0" class="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-xl px-4 py-2">
                <span class="text-sm font-semibold text-blue-700">{{ selected.size }} selected</span>
                <Button
                  label="Bulk Invite"
                  icon="pi pi-send"
                  size="small"
                  :loading="bulkInviting"
                  @click="bulkInvite"
                  class="!rounded-lg"
                />
                <Button
                  icon="pi pi-times"
                  text
                  rounded
                  size="small"
                  severity="secondary"
                  @click="selected = new Set()"
                />
              </div>
            </transition>
         </div>

         <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="n in 6" :key="n" class="h-64 bg-gray-50 rounded-xl animate-pulse"></div>
         </div>

         <div v-else-if="filtered.length === 0" class="text-center py-20 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
               <i class="pi pi-search text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900">No candidates found</h3>
            <p class="text-gray-500">Try adjusting your search terms or location.</p>
         </div>

         <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="c in filtered" :key="c.id" class="group bg-white rounded-xl border hover:border-blue-300 hover:shadow-md transition-all duration-200 flex flex-col overflow-hidden"
              :class="selected.has(c.id) ? 'border-blue-400 ring-2 ring-blue-200' : 'border-gray-200'"
            >
               <!-- Card Header -->
               <div class="p-6 pb-4 flex items-start justify-between gap-4">
                  <div class="flex items-center gap-3">
                    <input
                      type="checkbox"
                      :checked="selected.has(c.id)"
                      @change="toggleSelect(c.id)"
                      class="w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer"
                    />
                    <Avatar
                      :image="c.avatar"
                      :label="!c.avatar ? initials(c.name) : null"
                      shape="circle"
                      size="xlarge"
                      class="!w-16 !h-16 text-xl shadow-sm border border-gray-100"
                      :style="{ backgroundColor: !c.avatar ? '#eff6ff' : undefined, color: !c.avatar ? '#3b82f6' : undefined }"
                    />
                  </div>
                  <div v-if="c.match" class="flex flex-col items-end">
                     <span class="text-xs font-bold uppercase text-green-600 tracking-wide">Match</span>
                     <span class="text-lg font-bold text-gray-900">{{ c.match }}%</span>
                  </div>
               </div>

               <!-- Content -->
               <div class="px-6 flex-1">
                  <h3 class="text-lg font-bold text-gray-900 leading-tight mb-1 group-hover:text-blue-600 transition-colors">{{ c.name }}</h3>
                  <p class="text-sm text-gray-500 font-medium mb-4">{{ c.role }}</p>
                  <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
                     <i class="pi pi-map-marker text-gray-400"></i>
                     <span>{{ c.location }}</span>
                  </div>
                  <p class="text-sm text-gray-600 line-clamp-3 mb-4 leading-relaxed">
                     {{ c.blurb || 'No summary provided.' }}
                  </p>
               </div>

               <!-- Footer -->
               <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between mt-auto">
                  <span class="text-xs text-gray-400 font-medium">Active: {{ fmtDate(c.last_active) }}</span>
                  <Button
                     :icon="invited[c.id] ? 'pi pi-check' : 'pi pi-send'"
                     :label="invited[c.id] ? 'Invited' : 'Invite'"
                     rounded
                     size="small"
                     :severity="invited[c.id] ? 'success' : 'secondary'"
                     class="!h-8 !px-3"
                     @click="invite(c)"
                     :loading="inviting[c.id]"
                     :disabled="invited[c.id]"
                  />
               </div>
            </div>
         </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
