<script setup>
import { ref, computed, onMounted, onBeforeUnmount, provide, watch } from 'vue';
import { useRouter, RouterLink, useRoute } from 'vue-router';
import Menu from 'primevue/menu';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import api from '@/lib/api';
import { useAdminTheme } from '@/composables/useAdminTheme';

const router = useRouter();
const route = useRoute();

// ── Admin dark mode (drives PrimeVue via .dark on <html>) ──────────────
const ADMIN_DARK_KEY = 'admin-dark-mode';
const adminDark = ref(false);

function initAdminDark() {
  const stored = localStorage.getItem(ADMIN_DARK_KEY);
  adminDark.value = stored !== null ? stored === 'true' : window.matchMedia('(prefers-color-scheme: dark)').matches;
  applyAdminDark();
}
function applyAdminDark() {
  document.documentElement.classList.toggle('dark', adminDark.value);
}
function toggleDarkMode() {
  adminDark.value = !adminDark.value;
  localStorage.setItem(ADMIN_DARK_KEY, String(adminDark.value));
  applyAdminDark();
}

provide('adminDark', adminDark);

// Use central theme tokens
const { 
  isDark, bg, card, sidebar: sidebarClass, header: headerClass, border: borderClass,
  text: textClass, textSub, textMuted, rowHover 
} = useAdminTheme();

// ── User ───────────────────────────────────────────────────────────────
const user = ref({});
function loadUser() {
  try { user.value = JSON.parse(localStorage.getItem('auth_user') || '{}'); } catch { user.value = {}; }
}
const initials = computed(() => (user.value?.email || 'A').charAt(0).toUpperCase());

// ── Sidebar ────────────────────────────────────────────────────────────
const sidebarOpen = ref(true);

const navGroups = [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard',  icon: 'pi pi-home',        to: { name: 'admin.dashboard' } },
      { label: 'Analytics',  icon: 'pi pi-chart-line',  to: { name: 'admin.analytics' } },
    ],
  },
  {
    label: 'Management',
    items: [
      { label: 'Users',          icon: 'pi pi-users',       to: { name: 'admin.users' } },
      { label: 'Jobs',           icon: 'pi pi-briefcase',   to: { name: 'admin.jobs' } },
      { label: 'Subscriptions',  icon: 'pi pi-credit-card', to: { name: 'admin.subscriptions' } },
      { label: 'Plans',          icon: 'pi pi-tag',         to: { name: 'admin.plans' } },
    ],
  },
  {
    label: 'Moderation',
    items: [
      { label: 'Verifications',  icon: 'pi pi-verified',      to: { name: 'admin.verifications' } },
      { label: 'Contacts',       icon: 'pi pi-envelope',      to: { name: 'admin.contacts' } },
      { label: 'AI Screenings',  icon: 'pi pi-microchip-ai',  to: { name: 'admin.ai-screenings' } },
    ],
  },
  {
    label: 'System',
    items: [
      { label: 'Audit Logs',  icon: 'pi pi-list',    to: { name: 'admin.audit-logs' } },
      { label: 'System',      icon: 'pi pi-server',  to: { name: 'admin.system' } },
    ],
  },
];

function isActive(item) { return route.name === item.to.name; }

// ── Global search ──────────────────────────────────────────────────────
const searchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref(null);
const searchLoading = ref(false);
let searchTimer = null;

function handleGlobalKey(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); searchOpen.value = true; }
  if (e.key === 'Escape') { searchOpen.value = false; }
}

watch(searchQuery, (val) => {
  clearTimeout(searchTimer);
  if (val.length < 2) { searchResults.value = null; return; }
  searchTimer = setTimeout(async () => {
    searchLoading.value = true;
    try {
      const res = await api.get('/admin/search', { params: { q: val } });
      searchResults.value = res?.data?.data || res?.data;
    } finally { searchLoading.value = false; }
  }, 300);
});

function goToResult(type, item) {
  searchOpen.value = false; searchQuery.value = ''; searchResults.value = null;
  if (type === 'user') router.push({ name: 'admin.users', query: { q: item.email || item.id } });
  else if (type === 'job') router.push({ name: 'admin.jobs', query: { q: item.title } });
  else router.push({ name: 'admin.subscriptions' });
}

// ── User menu ──────────────────────────────────────────────────────────
const userMenu = ref();
const menuItems = [
  { label: 'Back to App', icon: 'pi pi-arrow-left', command: () => router.push('/') },
  { separator: true },
  { label: 'Logout', icon: 'pi pi-sign-out', command: logout },
];
async function logout() {
  ['auth_token', 'CLINFORCE_TOKEN', 'auth_user'].forEach(k => localStorage.removeItem(k));
  window.dispatchEvent(new Event('auth:changed'));
  router.push({ name: 'auth.login' });
}

// ── Breadcrumb ─────────────────────────────────────────────────────────
const breadcrumb = ref([]);
provide('setBreadcrumb', (items) => { breadcrumb.value = items; });

// ── Lifecycle ──────────────────────────────────────────────────────────
onMounted(() => {
  loadUser();
  initAdminDark();
  window.addEventListener('auth:changed', loadUser);
  window.addEventListener('keydown', handleGlobalKey);
});
onBeforeUnmount(() => {
  window.removeEventListener('auth:changed', loadUser);
  window.removeEventListener('keydown', handleGlobalKey);
  const appDark = localStorage.getItem('clinforce-dark-mode');
  document.documentElement.classList.toggle('dark', appDark === 'true');
});
</script>

<template>
  <div class="min-h-screen flex font-sans transition-colors duration-300"
    :class="[bg, textClass]">
    <Toast position="bottom-right" />

    <!-- ── Sidebar ─────────────────────────────────────────────────── -->
    <aside :class="[sidebarOpen ? 'w-64' : 'w-[68px]', sidebarClass, borderClass]"
      class="flex-shrink-0 border-r flex flex-col transition-all duration-300 ease-in-out z-30 fixed top-0 left-0 h-full shadow-sm">

      <!-- Logo -->
      <div :class="['h-16 flex items-center border-b gap-3 px-4 flex-shrink-0', borderClass]">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/20">
          <i class="pi pi-shield text-white text-base"></i>
        </div>
        <transition name="fade">
          <div v-if="sidebarOpen" class="overflow-hidden leading-tight">
            <div :class="['font-bold text-sm tracking-tight uppercase', isDark ? 'text-white' : 'text-slate-900']">ClinForce</div>
            <div :class="['text-[10px] font-bold uppercase tracking-[0.2em]', isDark ? 'text-blue-400' : 'text-blue-600']">Admin</div>
          </div>
        </transition>
      </div>

      <!-- Nav -->
      <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-7 custom-scrollbar">
        <div v-for="group in navGroups" :key="group.label">
          <p v-if="sidebarOpen" :class="['text-[10px] font-bold uppercase tracking-widest px-3 mb-2.5 opacity-60', textMuted]">
            {{ group.label }}
          </p>
          <div v-else :class="['h-px mx-1 mb-4 opacity-10', isDark ? 'bg-white' : 'bg-slate-900']"></div>
          
          <div class="space-y-1">
            <RouterLink v-for="item in group.items" :key="item.label" :to="item.to"
              :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group relative',
                isActive(item)
                  ? (isDark ? 'bg-blue-600/15 text-blue-400' : 'bg-blue-50 text-blue-600')
                  : (isDark ? 'text-slate-400 hover:bg-white/5 hover:text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900')
              ]">
              <i :class="[item.icon, 'text-base flex-shrink-0 transition-transform duration-200 group-hover:scale-110', 
                isActive(item) ? (isDark ? 'text-blue-400' : 'text-blue-600') : 'opacity-70 group-hover:opacity-100']"></i>
              <span v-if="sidebarOpen" class="truncate tracking-tight">{{ item.label }}</span>
              
              <!-- Active indicator -->
              <div v-if="isActive(item)" 
                class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-blue-500"></div>
            </RouterLink>
          </div>
        </div>
      </nav>

      <!-- Collapse toggle -->
      <div :class="['p-4 border-t flex-shrink-0', borderClass]">
        <button @click="sidebarOpen = !sidebarOpen"
          :class="['w-full flex items-center justify-center p-2 rounded-xl transition-all duration-200',
            isDark ? 'text-slate-500 hover:text-white hover:bg-white/5' : 'text-slate-400 hover:text-slate-700 hover:bg-slate-100']">
          <i :class="sidebarOpen ? 'pi pi-chevron-left' : 'pi pi-chevron-right'" class="text-xs"></i>
        </button>
      </div>
    </aside>

    <!-- ── Main ────────────────────────────────────────────────────── -->
    <div :class="[sidebarOpen ? 'ml-64' : 'ml-[68px]', 'flex-1 flex flex-col min-w-0 transition-all duration-300']">

      <!-- Header -->
      <header :class="['h-16 border-b flex items-center justify-between px-8 flex-shrink-0 sticky top-0 z-20 shadow-sm', headerClass, borderClass]">
        <div class="flex items-center gap-4">
          <!-- Sidebar Toggle (Mobile only) -->
          <button class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5">
            <i class="pi pi-bars text-lg"></i>
          </button>

          <!-- Breadcrumb -->
          <nav v-if="breadcrumb.length" class="flex items-center gap-2 text-xs">
            <span v-for="(crumb, i) in breadcrumb" :key="i" class="flex items-center gap-2">
              <i v-if="i > 0" class="pi pi-chevron-right text-[8px] opacity-30"></i>
              <RouterLink v-if="crumb.to" :to="crumb.to" class="text-blue-500 hover:text-blue-600 font-semibold transition-colors">{{ crumb.label }}</RouterLink>
              <span v-else :class="['font-semibold opacity-80', textClass]">{{ crumb.label }}</span>
            </span>
          </nav>
          <div v-else class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-blue-500">Live Dashboard</span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Search -->
          <button @click="searchOpen = true"
            :class="['flex items-center gap-3 px-4 py-2 rounded-xl text-xs font-medium transition-all border shadow-sm',
              isDark
                ? 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10 hover:border-white/20'
                : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50 hover:border-slate-300']">
            <i class="pi pi-search text-xs opacity-70"></i>
            <span class="hidden md:block">Search anything...</span>
            <kbd :class="['px-2 py-0.5 rounded-lg text-[10px] font-bold hidden md:block shadow-inner',
              isDark ? 'bg-white/10 text-slate-400' : 'bg-slate-100 text-slate-400 border border-slate-200']">⌘K</kbd>
          </button>

          <!-- Dark toggle -->
          <button @click="toggleDarkMode"
            :class="['w-10 h-10 rounded-xl flex items-center justify-center transition-all border shadow-sm',
              isDark ? 'bg-white/5 border-white/10 text-yellow-400 hover:bg-white/10' : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50']">
            <i :class="isDark ? 'pi pi-sun' : 'pi pi-moon'" class="text-base"></i>
          </button>

          <!-- User -->
          <button @click="userMenu.toggle($event)" class="flex items-center gap-3 rounded-xl pl-1 pr-3 py-1 transition-all border shadow-sm"
            :class="isDark ? 'bg-white/5 border-white/10 hover:bg-white/10' : 'bg-white border-slate-200 hover:bg-slate-50'">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-xs font-black shadow-md">
              {{ initials }}
            </div>
            <div class="hidden sm:flex flex-col items-start leading-none gap-1 text-left">
              <span :class="['text-[11px] font-bold truncate max-w-[100px]', textClass]">
                {{ user.email?.split('@')[0] }}
              </span>
              <span :class="['text-[9px] font-bold uppercase tracking-wider', isDark ? 'text-blue-400' : 'text-blue-600']">
                Administrator
              </span>
            </div>
            <i :class="['pi pi-chevron-down text-[9px] ml-1', textMuted]"></i>
          </button>
          <Menu ref="userMenu" :model="menuItems" popup class="!rounded-2xl !p-1.5 !shadow-2xl" />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-8 custom-scrollbar relative">
        <slot />
        
        <!-- Subtle background decoration -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/5 blur-[120px] rounded-full -mr-48 -mt-48 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-500/5 blur-[100px] rounded-full -ml-32 -mb-32 pointer-events-none"></div>
      </main>
    </div>

    <!-- ── Global Search ───────────────────────────────────────────── -->
    <Dialog v-model:visible="searchOpen" :style="{ width: '640px' }" :showHeader="false" modal
      :pt="{ root: { class: '!rounded-3xl !overflow-hidden !shadow-2xl !border-0' }, content: { class: '!p-0' }, mask: { class: 'backdrop-blur-md bg-slate-900/40 dark:bg-black/60' } }">
      <div :class="[isDark ? 'bg-[#111827]' : 'bg-white', textClass]">
        <div :class="['flex items-center gap-4 px-6 py-5 border-b', borderClass]">
          <i :class="['pi pi-search text-lg', isDark ? 'text-blue-400' : 'text-blue-500']"></i>
          <input v-model="searchQuery" autofocus placeholder="Search users, jobs, subscriptions..."
            :class="['flex-1 bg-transparent outline-none text-base font-medium', isDark ? 'text-white placeholder-slate-600' : 'text-slate-900 placeholder-slate-400']" />
          <kbd :class="['px-2 py-1 rounded-lg text-[10px] font-bold shadow-sm', isDark ? 'bg-white/5 text-slate-500' : 'bg-slate-100 text-slate-400 border']">ESC</kbd>
        </div>
        
        <div class="max-h-[500px] overflow-y-auto custom-scrollbar">
          <div v-if="searchLoading" class="py-20 text-center">
            <i class="pi pi-spin pi-spinner text-3xl text-blue-500"></i>
            <p class="mt-4 text-sm font-medium opacity-50">Searching the database...</p>
          </div>
          
          <div v-else-if="!searchQuery" class="py-20 text-center">
            <div class="w-16 h-16 rounded-3xl bg-blue-500/10 flex items-center justify-center mx-auto mb-4">
              <i class="pi pi-search text-2xl text-blue-500"></i>
            </div>
            <p :class="['text-sm font-medium', textMuted]">Type at least 2 characters to begin</p>
            <p class="text-[10px] mt-2 opacity-50 uppercase tracking-widest font-bold">Search results will appear here</p>
          </div>
          
          <template v-else-if="searchResults">
            <template v-for="(group, key) in { Users: searchResults.users, Jobs: searchResults.jobs, Subscriptions: searchResults.subscriptions }" :key="key">
              <div v-if="group?.length" class="py-2">
                <div :class="['px-6 py-3 text-[10px] font-black uppercase tracking-[0.2em] mb-1', isDark ? 'text-slate-500' : 'text-slate-400']">{{ key }}</div>
                <div class="px-2">
                  <button v-for="item in group" :key="item.id"
                    @click="goToResult(key === 'Users' ? 'user' : key === 'Jobs' ? 'job' : 'sub', item)"
                    :class="['w-full flex items-center gap-4 px-4 py-3 text-left transition-all duration-200 rounded-2xl group mb-1',
                      isDark ? 'hover:bg-white/5' : 'hover:bg-slate-50']">
                    <div :class="['w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-bold flex-shrink-0 transition-transform duration-200 group-hover:scale-110 shadow-sm',
                      isDark ? 'bg-white/5 text-blue-400' : 'bg-white text-blue-600 border border-slate-100']">
                      <i v-if="key !== 'Users'" :class="key === 'Jobs' ? 'pi pi-briefcase' : 'pi pi-credit-card'" class="text-sm"></i>
                      <span v-else>{{ (item.email || '#')[0].toUpperCase() }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-bold truncate tracking-tight group-hover:text-blue-500 transition-colors">
                        {{ item.email || item.title || item.user?.email || '—' }}
                      </div>
                      <div class="text-[11px] truncate mt-0.5 opacity-60 font-medium">
                        {{ item.phone || item.company_name || item.plan?.name || '' }} · #{{ item.id }}
                      </div>
                    </div>
                    <Tag :value="item.role || item.status" severity="secondary" 
                      class="!text-[9px] !font-black !uppercase !tracking-wider !px-2.5 !py-1 !rounded-lg flex-shrink-0" />
                  </button>
                </div>
              </div>
            </template>
            <div v-if="!searchResults.users?.length && !searchResults.jobs?.length && !searchResults.subscriptions?.length"
              class="py-20 text-center">
              <i class="pi pi-search-minus text-4xl text-slate-300 mb-4"></i>
              <p :class="['text-sm font-medium', textMuted]">No results found for "{{ searchQuery }}"</p>
            </div>
          </template>
        </div>
        
        <!-- Footer -->
        <div :class="['px-6 py-4 border-t flex items-center justify-between text-[10px] font-bold uppercase tracking-widest opacity-40', borderClass]">
          <div class="flex items-center gap-4">
            <span class="flex items-center gap-1"><kbd class="bg-slate-100 dark:bg-white/10 px-1 rounded">↵</kbd> Select</span>
            <span class="flex items-center gap-1"><kbd class="bg-slate-100 dark:bg-white/10 px-1 rounded">↑↓</kbd> Navigate</span>
          </div>
          <span>Search Engine v2.0</span>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.05); border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.1); }
.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); }
</style>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateX(-10px); }
</style>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s, transform 0.15s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateX(-4px); }
</style>
