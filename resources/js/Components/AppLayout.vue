<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { me } from '@/lib/auth';
import Swal from 'sweetalert2';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Badge from 'primevue/badge';
import OverlayBadge from 'primevue/overlaybadge';
import Popover from 'primevue/popover';
import Drawer from 'primevue/drawer';
import Dialog from 'primevue/dialog';
import ChatbotWidget from '@/Components/ChatbotWidget.vue';
import MarketingPopup from '@/Components/MarketingPopup.vue';
import StickyMarketingCard from '@/Components/StickyMarketingCard.vue';
import DarkModeToggle from '@/components/DarkModeToggle.vue';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import api from '@/lib/api';

const toastService = useToast();

const router = useRouter();
const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);
const userMenu = ref();
const notificationPanel = ref();
const notifications = ref([]);
const unreadCount = ref(0);
const unreadMessages = ref(0);
let sse = null;

const props = defineProps({
  guestFull: { type: Boolean, default: false },
});

// Auth Logic
const user = ref({});
const isImpersonating = ref(false);

function loadUser() {
    try {
        user.value = JSON.parse(localStorage.getItem('auth_user') || '{}');
        // Detect impersonation token name
        const token = localStorage.getItem('auth_token') || localStorage.getItem('CLINFORCE_TOKEN');
        isImpersonating.value = !!token && (localStorage.getItem('impersonating') === '1');
    } catch { user.value = {}; }
}

const isAuthed = computed(() => {
    const token = localStorage.getItem('auth_token') || localStorage.getItem('CLINFORCE_TOKEN');
    return !!token && !!user.value?.id;
});

const isEmployer = computed(() => {
    return user.value?.role === 'employer' || user.value?.role === 'admin' || user.value?.role === 'agency';
});

const userInitials = computed(() => (user.value?.name || user.value?.email || 'U').charAt(0).toUpperCase());

const trialStatus = computed(() => {
    if (!user.value || user.value.role !== 'employer') return null;

    // Check if subscription is active
    if (user.value.has_active_subscription) return 'subscribed';

    if (user.value.on_trial) return 'trial_active';
    if (user.value.has_expired_trial) return 'trial_expired';

    return null;
});

const trialDaysLeft = computed(() => {
    if (!user.value?.trial_ends_at) return 0;
    const end = new Date(user.value.trial_ends_at);
    const now = new Date();
    const diff = end - now;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
});

const trialTimeLeft = computed(() => {
    if (!user.value?.trial_ends_at) return { days: 0, hours: 0, isPast: true };
    const end = new Date(user.value.trial_ends_at);
    const now = new Date();
    const diff = end - now;
    if (diff <= 0) return { days: 0, hours: 0, isPast: true };
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    return { days, hours, isPast: false };
});

const showTrialExpiredModal = computed(() => {
    return trialStatus.value === 'trial_expired' && user.value?.role === 'employer';
});

function goToBilling() {
    router.push('/employer/billing');
}

onMounted(async () => {
    loadUser();
    window.addEventListener('auth:changed', loadUser);
    window.addEventListener('app:toast', (e) => {
        const { severity, summary, detail, life } = e.detail || {};
        toastService.add({ severity, summary, detail, life: life ?? 3500 });
    });
    if (isAuthed.value) {
        try {
            await me();
        } catch {}
        initNotifications();
        loadSeatUsage();
        loadUnreadMessages();
    }
    document.documentElement.classList.remove('app-dark');

    // Global subscription gate handler
    window.addEventListener('subscription:required', async (e) => {
        const { message } = e.detail || {};
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Subscription Required',
            text: message || 'An active subscription is required to use this feature.',
            confirmButtonText: 'View Plans',
            showCancelButton: true,
            cancelButtonText: 'Not now',
            confirmButtonColor: '#2563eb',
        });
        if (result.isConfirmed) router.push({ name: 'employer.billing' });
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('auth:changed', loadUser);
    if (sse) {
        try { sse.close(); } catch {}
        sse = null;
    }
});

async function initNotifications() {
    try {
        const c = await api.get('/notifications/unread-count');
        unreadCount.value = c?.data?.data?.count || 0;
        const res = await api.get('/notifications?is_read=false');
        const items = res?.data?.data?.data || res?.data?.data || [];
        notifications.value = items;
    } catch {}
    try {
        if (sse) sse.close();
        // SSE disabled - not supported on shared hosting
    } catch {}
}

async function markRead(ids) {
    try {
        await api.post('/notifications/read', { ids });
        notifications.value = notifications.value.filter(n => !ids.includes(n.id));
        unreadCount.value = Math.max(0, unreadCount.value - ids.length);
    } catch {}
}

async function markAllRead() {
    try {
        await api.post('/notifications/read-all');
        notifications.value = [];
        unreadCount.value = 0;
    } catch {}
}
const seatUsage = ref(null);

async function loadSeatUsage() {
    if (!isEmployer.value) return;
    try {
        const res = await api.get('/subscriptions/usage');
        seatUsage.value = res?.data?.data || null;
    } catch {}
}

async function loadUnreadMessages() {
    try {
        const res = await api.get('/conversations/unread-count');
        unreadMessages.value = res?.data?.data?.count || 0;
    } catch {}
}

const seatPercent = computed(() => {
    if (!seatUsage.value?.job_post_limit) return 0;
    return Math.min(100, Math.round((seatUsage.value.jobs_used / seatUsage.value.job_post_limit) * 100));
});

const menuItems = [
    { label: 'Profile', icon: 'pi pi-user', command: () => router.push(isEmployer.value ? { name: 'employer.settings' } : { name: 'candidate.profile' }) },
    { label: 'Account', icon: 'pi pi-cog', command: () => router.push(isEmployer.value ? { name: 'employer.settings' } : { name: 'candidate.settings' }) },
    ...(user.value?.role === 'admin' ? [{ label: 'Admin Panel', icon: 'pi pi-shield', command: () => router.push({ name: 'admin.dashboard' }) }] : []),
    { separator: true },
    { label: 'Logout', icon: 'pi pi-sign-out', command: logout }
];

async function logout() {
    const result = await Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to sign out?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
    });

    if (!result.isConfirmed) return;

    localStorage.removeItem('auth_token');
    localStorage.removeItem('CLINFORCE_TOKEN');
    localStorage.removeItem('auth_user');
    window.dispatchEvent(new Event('auth:changed'));
    await router.push({ name: 'auth.login' });
    Swal.fire({
        icon: 'success',
        title: 'Logged out',
        text: 'You have been signed out.',
        timer: 1800,
        showConfirmButton: false,
    });
}

function stopImpersonating() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('CLINFORCE_TOKEN');
    localStorage.removeItem('auth_user');
    localStorage.removeItem('impersonating');
    window.dispatchEvent(new Event('auth:changed'));
    window.location.href = '/admin/users';
}

// Navigation Groups
const employerNavGroups = [
    {
        title: 'Overview',
        items: [
             { label: 'Dashboard', icon: 'pi pi-home', to: { name: 'employer.dashboard' } },
             { label: 'Messages', icon: 'pi pi-envelope', to: { name: 'employer.messages' } },
        ]
    },
    {
        title: 'Hiring',
        items: [
            { label: 'Jobs Posted', icon: 'pi pi-briefcase', to: { name: 'employer.jobs' } },
            { label: 'Pipeline', icon: 'pi pi-th-large', to: { name: 'employer.pipeline' } },
            { label: 'Candidates', icon: 'pi pi-users', to: { name: 'applicants.list' } },
            { label: 'Compare', icon: 'pi pi-sliders-h', to: { name: 'employer.compare' } },
            { label: 'Interviews', icon: 'pi pi-calendar', to: { name: 'employer.interviews' } },
            { label: 'Invitations', icon: 'pi pi-send', to: { name: 'employer.invitations' } },
            { label: 'Sourcing', icon: 'pi pi-search', to: { name: 'employer.talentsearch' } },
            { label: 'Job Templates', icon: 'pi pi-copy', to: { name: 'employer.job-templates' } },
        ]
    },
    {
        title: 'Workspace',
        items: [
            { label: 'Subscription', icon: 'pi pi-credit-card', to: { name: 'employer.billing' } },
            { label: 'Profile', icon: 'pi pi-user', to: { name: 'employer.settings' } }, 
        ]
    }
];

const candidateNavGroups = computed(() => [
    {
        title: 'Menu',
        items: [
            { label: 'Dashboard', icon: 'pi pi-th-large', to: { name: 'candidate.dashboard' } },
            { label: 'Find Jobs', icon: 'pi pi-search', to: { name: 'candidate.jobs' } },
            { label: 'Applications', icon: 'pi pi-file', to: { name: 'candidate.myapplications' } },
            { label: 'Interviews', icon: 'pi pi-calendar', to: { name: 'candidate.interviews' } },
            { label: 'Invitations', icon: 'pi pi-envelope', to: { name: 'candidate.invitations' } },
            { label: 'Messages', icon: 'pi pi-comments', to: { name: 'candidate.messages' }, badge: unreadMessages.value > 0 ? unreadMessages.value : null },
            { label: 'Job Alerts', icon: 'pi pi-bell', to: { name: 'candidate.job-alerts' } },
        ]
    },
    {
        title: 'Account',
        items: [
            { label: 'Profile', icon: 'pi pi-user', to: { name: 'candidate.profile' } },
            { label: 'Account', icon: 'pi pi-cog', to: { name: 'candidate.settings' } },
        ]
    }
]);

const navGroups = computed(() => isEmployer.value ? employerNavGroups : candidateNavGroups.value);

function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
}

// Dark Mode Init
onMounted(() => {
    document.documentElement.classList.remove('app-dark');
});
</script>

<template>
    <div class="min-h-screen bg-app-base flex flex-col transition-colors duration-300 font-sans">
        
        <!-- Guest View -->
        <div v-if="!isAuthed">
            <div v-if="!guestFull" class="flex items-center justify-center min-h-screen p-4">
                <div class="w-full max-w-lg">
                    <slot />
                </div>
            </div>
            <div v-else class="min-h-screen">
                <slot />
            </div>
        </div>

        <!-- Authed View -->
        <div v-else class="flex flex-col min-h-screen">
        <!-- Impersonation Banner -->
        <div v-if="isImpersonating"
          class="bg-amber-500 text-white px-4 py-2 text-sm text-center font-semibold z-50 flex items-center justify-center gap-3">
          <i class="pi pi-eye text-white"></i>
          <span>You are impersonating <strong>{{ user.email }}</strong> ({{ user.role }})</span>
          <button @click="stopImpersonating"
            class="ml-4 px-3 py-1 bg-white text-amber-700 rounded-lg text-xs font-bold hover:bg-amber-50 transition-colors">
            Stop impersonating
          </button>
        </div>

        <!-- Trial Banner -->
            <div v-if="trialStatus === 'trial_active'" class="bg-indigo-600 text-white px-4 py-2 text-sm text-center font-medium z-50">
              <template v-if="trialTimeLeft.days > 0">
                Your free trial ends in <strong>{{ trialTimeLeft.days }} day{{ trialTimeLeft.days === 1 ? '' : 's' }}</strong> and <strong>{{ trialTimeLeft.hours }}h</strong>.
              </template>
              <template v-else>
                Your free trial ends in <strong>{{ trialTimeLeft.hours }} hour{{ trialTimeLeft.hours === 1 ? '' : 's' }}</strong>.
              </template>
              <span class="underline cursor-pointer ml-1 font-bold" @click="goToBilling">Subscribe now</span> to keep access.
            </div>
            <div v-else-if="trialStatus === 'trial_expired'" class="bg-red-600 text-white px-4 py-2 text-sm text-center font-bold z-50">
              <i class="pi pi-exclamation-triangle mr-1"></i>
              Your free trial has expired. <span class="underline cursor-pointer ml-1 text-white hover:text-gray-100" @click="goToBilling">Choose a subscription plan now</span> to restore access.
            </div>
            <div v-else-if="user.in_grace_period" class="bg-amber-500 text-white px-4 py-2 text-sm text-center font-medium z-50">
              Your subscription has expired. You have a 24-hour grace period. <span class="underline cursor-pointer ml-1 font-bold" @click="goToBilling">Renew now</span> to avoid interruption.
            </div>

             <!-- Sticky Topbar -->
            <header class="h-16 flex items-center justify-between px-4 sticky top-0 z-30 bg-surface card-glass">
                <!-- Left: Logo & Toggle -->
                <div class="flex items-center gap-4">
                    <Button 
                        icon="pi pi-bars" 
                        text 
                        rounded 
                        aria-label="Menu" 
                        @click="mobileSidebarOpen = true" 
                        class="lg:hidden text-gray-500"
                    />
                    <RouterLink :to="{ name: isEmployer ? 'employer.dashboard' : 'candidate.dashboard' }" class="flex items-center">
                        <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-24 w-auto" />
                    </RouterLink>
                </div>

                <!-- Center: Search (Employer only for now) -->
                <div class="hidden md:flex flex-1 max-w-lg mx-4" v-if="isEmployer">
                    <div class="relative w-full group">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                        <input 
                            type="text" 
                            placeholder="Search... (Cmd+K)" 
                            class="w-full bg-gray-50 border border-gray-200 rounded-lg py-2 pl-10 pr-4 text-sm text-gray-900 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all placeholder:text-gray-400"
                        />
                    </div>
                </div>
                <div class="hidden md:flex flex-1" v-else></div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2">
                    <Button label="AI Clinforce" class="!bg-indigo-600 !text-white !rounded-xl" @click="() => window.dispatchEvent(new Event('chatbot:toggle'))" />
                    <DarkModeToggle />
                    <Button icon="pi pi-question-circle" text rounded severity="secondary" class="text-gray-500" />
                    <OverlayBadge :value="unreadCount" severity="danger" v-if="unreadCount > 0">
                        <Button icon="pi pi-bell" text rounded severity="secondary" @click="(e) => notificationPanel.show(e)" class="text-gray-500" />
                    </OverlayBadge>
                    <Button v-else icon="pi pi-bell" text rounded severity="secondary" @click="(e) => notificationPanel.show(e)" class="text-gray-500" />
                    
                    <div class="h-6 w-px bg-gray-200 mx-1 hidden sm:block"></div>
                    
                    <div class="flex items-center gap-2 cursor-pointer p-1 rounded-full hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100" @click="(e) => userMenu.toggle(e)">
                       
                        <i class="pi pi-chevron-down text-gray-400 text-xs hidden lg:block"></i>
                    </div>
                </div>
            </header>

            <div class="flex flex-1 relative overflow-hidden">
                <!-- Desktop Sidebar -->
                <aside 
                    class="hidden lg:flex flex-col bg-sidebar transition-all duration-300 z-20"
                    :class="[sidebarCollapsed ? 'w-20' : 'w-64']"
                >
                    <!-- Nav Items with Sections -->
                    <nav class="flex-1 overflow-y-auto py-6 px-3 flex flex-col gap-6">
                        <div v-for="group in navGroups" :key="group.title" class="flex flex-col gap-1">
                            <!-- Section Header -->
                            <div v-if="!sidebarCollapsed" class="px-3 mb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                {{ group.title }}
                            </div>
                            
                            <!-- Items -->
                             <RouterLink 
                                v-for="item in group.items" 
                                :key="item.label" 
                                :to="item.to" 
                                custom 
                                v-slot="{ href, navigate, isActive }"
                            >
                                <a 
                                    :href="href" 
                                    @click="navigate"
                                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 group relative overflow-hidden"
                                    :class="[
                                        isActive 
                                            ? 'bg-white text-blue-700 shadow-sm font-medium' 
                                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                                    ]"
                                >
                                    <div class="relative z-10 flex items-center justify-center w-5 text-center">
                                        <i :class="[item.icon, isActive ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-700', 'text-lg transition-colors']"></i>
                                    </div>
                                    
                                    <span 
                                        class="whitespace-nowrap transition-all duration-300 z-10 text-sm"
                                        :class="[sidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100']"
                                    >
                                        {{ item.label }}
                                    </span>
                                    
                                    <!-- Badge -->
                                    <Badge v-if="item.badge && !sidebarCollapsed" :value="item.badge" severity="danger" class="ml-auto z-10" />
                                </a>
                            </RouterLink>
                        </div>
                    </nav>

                    <!-- Footer area -->
                    <div class="p-4">
                         <div v-if="!sidebarCollapsed && isEmployer && seatUsage" class="p-3 bg-blue-50 rounded-lg border border-blue-100 mb-2">
                            <div class="text-xs font-semibold text-blue-900">{{ seatUsage.plan_name || 'Active Plan' }}</div>
                            <div v-if="seatUsage.job_post_limit" class="text-xs text-blue-700 mt-1">
                                {{ seatUsage.jobs_used }}/{{ seatUsage.job_post_limit }} Active Jobs
                            </div>
                            <div v-else class="text-xs text-blue-700 mt-1">Unlimited Jobs</div>
                            <div v-if="seatUsage.job_post_limit" class="w-full bg-blue-200 h-1 mt-2 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-full rounded-full transition-all" :style="{ width: seatPercent + '%' }"></div>
                            </div>
                        </div>

                        <button 
                            @click="toggleSidebar" 
                            class="w-full flex items-center justify-center p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                        >
                            <i :class="['pi', sidebarCollapsed ? 'pi-angle-double-right' : 'pi-angle-double-left']"></i>
                        </button>
                    </div>
                </aside>

                <!-- Mobile Sidebar -->
                <Drawer v-model:visible="mobileSidebarOpen" class="w-72 bg-sidebar">
                    <div class="flex items-center gap-2 mb-6 px-2"></div>
                    <nav class="flex flex-col gap-6">
                         <div v-for="group in navGroups" :key="group.title" class="flex flex-col gap-1">
                            <div class="px-2 mb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                {{ group.title }}
                            </div>
                            <RouterLink 
                                v-for="item in group.items" 
                                :key="item.label" 
                                :to="item.to" 
                                custom 
                                v-slot="{ href, navigate, isActive }"
                            >
                                <a 
                                    :href="href" 
                                    @click="navigate; mobileSidebarOpen = false"
                                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all"
                                    :class="isActive ? 'bg-white text-blue-700 shadow-sm font-medium' : 'text-gray-600 hover:bg-gray-100'"
                                >
                                    <i :class="[item.icon, 'text-lg']"></i>
                                    <span>{{ item.label }}</span>
                                </a>
                            </RouterLink>
                         </div>
                    </nav>
                    <!-- Mobile seat usage widget -->
                    <div v-if="isEmployer && seatUsage" class="mt-6 mx-2 p-3 bg-blue-50 rounded-lg border border-blue-100">
                        <div class="text-xs font-semibold text-blue-900">{{ seatUsage.plan_name || 'Active Plan' }}</div>
                        <div v-if="seatUsage.job_post_limit" class="text-xs text-blue-700 mt-1">{{ seatUsage.jobs_used }}/{{ seatUsage.job_post_limit }} Active Jobs</div>
                        <div v-else class="text-xs text-blue-700 mt-1">Unlimited Jobs</div>
                        <div v-if="seatUsage.job_post_limit" class="w-full bg-blue-200 h-1 mt-2 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full transition-all" :style="{ width: seatPercent + '%' }"></div>
                        </div>
                    </div>
                </Drawer>

                <!-- Main Content Area -->
                <main class="flex-1 overflow-y-auto bg-app-base relative">
                    <div class="max-w-[1600px] mx-auto p-4 md:p-8">
                        <slot />
                    </div>
                </main>
            </div>

            <!-- Overlays -->
            <Menu ref="userMenu" :model="menuItems" :popup="true" />
            <Popover ref="notificationPanel" class="w-80">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-main">Notifications</span>
                        <button @click="notificationPanel.hide()" class="text-slate-400 hover:text-slate-600 text-xs">✕</button>
                    </div>
                    <div v-if="!notifications.length" class="text-sm text-muted py-4 text-center">No new notifications.</div>
                    <ul v-else class="flex flex-col gap-2 max-h-80 overflow-auto">
                        <li v-for="n in notifications" :key="n.id" class="p-2 rounded border border-slate-200 bg-white">
                            <div class="text-sm font-medium text-slate-900">{{ n.title }}</div>
                            <div class="text-xs text-slate-600">{{ n.body }}</div>
                            <div class="flex gap-2 mt-2">
                                <Button v-if="n.url" size="small" text label="Open" @click="() => { notificationPanel.hide(); router.push(n.url); }" />
                                <Button size="small" text label="Mark read" @click="markRead([n.id])" />
                            </div>
                        </li>
                    </ul>
                    <div class="flex justify-between items-center mt-2">
                        <Button size="small" text label="Mark all read" @click="markAllRead" />
                    </div>
                </div>
            </Popover>
            <ChatbotWidget v-if="isEmployer" />
            <MarketingPopup v-if="!isAuthed" />
            <StickyMarketingCard v-if="!isAuthed" />
            <Toast position="bottom-right" />

            <!-- Persistent Trial Expired Blocking Modal -->
            <Dialog
                v-model:visible="showTrialExpiredModal"
                modal
                :closable="false"
                :closeOnEscape="false"
                :dismissableMask="false"
                :style="{ width: '520px' }"
                class="trial-expired-modal"
            >
                <template #header>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-slate-900">Trial Expired</div>
                            <div class="text-xs text-slate-500">Your 7-day free trial has ended</div>
                        </div>
                    </div>
                </template>
                <div class="space-y-4 pb-2">
                    <p class="text-sm text-slate-700">
                        You no longer have access to candidate profiles, messaging, interview scheduling, or other hiring features.
                    </p>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <div class="text-sm font-bold text-slate-900 mb-2">What's included with a subscription:</div>
                        <ul class="space-y-1.5 text-sm text-slate-600">
                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> Post and manage job listings</li>
                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> View candidate profiles &amp; resumes</li>
                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> Message and interview candidates</li>
                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> AI-powered candidate screening</li>
                            <li class="flex items-center gap-2"><i class="pi pi-check text-green-500 text-xs"></i> Analytics dashboard &amp; reporting</li>
                        </ul>
                    </div>
                    <p class="text-sm text-slate-700 font-medium text-center">
                        Choose a plan that fits your hiring needs — start from just $49.99/month.
                    </p>
                </div>
                <template #footer>
                    <Button
                        label="View Subscription Plans"
                        icon="pi pi-arrow-right"
                        iconPos="right"
                        class="w-full !rounded-xl !bg-blue-600 hover:!bg-blue-700 !text-sm"
                        @click="goToBilling"
                    />
                </template>
            </Dialog>
        </div>
    </div>
</template>

<style scoped>
/* Specific overrides if needed beyond utilities */
</style>
