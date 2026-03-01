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
import ChatbotWidget from '@/Components/ChatbotWidget.vue';
import api from '@/lib/api';

const router = useRouter();
const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);
const userMenu = ref();
const notificationPanel = ref();
const notifications = ref([]);
const unreadCount = ref(0);
let sse = null;

const props = defineProps({
  guestFull: { type: Boolean, default: false },
});

// Auth Logic
const user = ref({});

function loadUser() {
    try {
        user.value = JSON.parse(localStorage.getItem('auth_user') || '{}');
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

onMounted(async () => {
    loadUser();
    window.addEventListener('auth:changed', loadUser);
    if (isAuthed.value) {
        try {
            await me();
        } catch {}
        initNotifications();
    }
    document.documentElement.classList.remove('app-dark');
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
        const origin = window.location.origin;
        const ev = new EventSource(`${origin}/api/notifications/stream`);
        ev.addEventListener('notification', (e) => {
            try {
                const payload = JSON.parse(e.data);
                notifications.value.unshift(payload);
                unreadCount.value++;
            } catch {}
        });
        ev.onerror = () => {};
        sse = ev;
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
const menuItems = [
    { label: 'Profile', icon: 'pi pi-user', command: () => router.push(isEmployer.value ? { name: 'employer.settings' } : { name: 'candidate.profile' }) },
    { label: 'Account', icon: 'pi pi-cog', command: () => router.push(isEmployer.value ? { name: 'employer.settings' } : { name: 'candidate.settings' }) },
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
            { label: 'Jobs', icon: 'pi pi-briefcase', to: { name: 'employer.jobs' } },
            { label: 'Candidates', icon: 'pi pi-users', to: { name: 'applicants.list' } },
            { label: 'Interviews', icon: 'pi pi-calendar', to: { name: 'employer.interviews' } },
            { label: 'Invitations', icon: 'pi pi-send', to: { name: 'employer.invitations' } },
            { label: 'Sourcing', icon: 'pi pi-search', to: { name: 'employer.talentsearch' } },
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

const candidateNavGroups = [
    {
        title: 'Menu',
        items: [
            { label: 'Dashboard', icon: 'pi pi-th-large', to: { name: 'candidate.dashboard' } },
            { label: 'Find Jobs', icon: 'pi pi-search', to: { name: 'candidate.jobs' } },
            { label: 'Applications', icon: 'pi pi-file', to: { name: 'candidate.myapplications' } },
            { label: 'Interviews', icon: 'pi pi-calendar', to: { name: 'candidate.interviews' } },
            { label: 'Invitations', icon: 'pi pi-envelope', to: { name: 'candidate.invitations' } },
            { label: 'Messages', icon: 'pi pi-comments', to: { name: 'candidate.messages' } },
        ]
    },
    {
        title: 'Account',
        items: [
            { label: 'Profile', icon: 'pi pi-user', to: { name: 'candidate.profile' } },
            { label: 'Account', icon: 'pi pi-cog', to: { name: 'candidate.settings' } },
        ]
    }
];

const navGroups = computed(() => isEmployer.value ? employerNavGroups : candidateNavGroups);

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
                    <div class="flex items-center gap-2"></div>
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
                    <Button icon="pi pi-question-circle" text rounded severity="secondary" class="text-gray-500" />
                    <OverlayBadge :value="unreadCount" severity="danger" v-if="unreadCount > 0">
                        <Button icon="pi pi-bell" text rounded severity="secondary" @click="(e) => notificationPanel.toggle(e)" class="text-gray-500" />
                    </OverlayBadge>
                    <Button v-else icon="pi pi-bell" text rounded severity="secondary" @click="(e) => notificationPanel.toggle(e)" class="text-gray-500" />
                    
                    <div class="h-6 w-px bg-gray-200 mx-1 hidden sm:block"></div>
                    
                    <div class="flex items-center gap-2 cursor-pointer p-1 rounded-full hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100" @click="(e) => userMenu.toggle(e)">
                        <Avatar :image="user.avatar_url || user.avatar" :label="!(user.avatar_url || user.avatar) ? userInitials : null" shape="circle" class="bg-blue-100 text-blue-700 font-bold border border-blue-200" />
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
                         <div v-if="!sidebarCollapsed && isEmployer" class="p-3 bg-blue-50 rounded-lg border border-blue-100 mb-2">
                            <div class="text-xs font-semibold text-blue-900">Pro Plan</div>
                            <div class="text-xs text-blue-700 mt-1">2/5 Seats Used</div>
                            <div class="w-full bg-blue-200 h-1 mt-2 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-full w-2/5"></div>
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
                    <span class="font-bold text-main">Notifications</span>
                    <div v-if="!notifications.length" class="text-sm text-muted">No notifications.</div>
                    <ul v-else class="flex flex-col gap-2 max-h-80 overflow-auto">
                        <li v-for="n in notifications" :key="n.id" class="p-2 rounded border border-slate-200 bg-white">
                            <div class="text-sm font-medium text-slate-900">{{ n.title }}</div>
                            <div class="text-xs text-slate-600">{{ n.body }}</div>
                            <div class="flex gap-2 mt-2">
                                <Button v-if="n.url" size="small" text label="Open" @click="$router.push(n.url)" />
                                <Button size="small" text label="Mark read" @click="markRead([n.id])" />
                            </div>
                        </li>
                    </ul>
                    <div class="flex justify-between items-center mt-2">
                        <Button size="small" text label="Mark all read" @click="markAllRead" />
                        <RouterLink :to="isEmployer ? {name:'employer.dashboard'} : {name:'candidate.dashboard'}" class="text-xs text-blue-600">View more</RouterLink>
                    </div>
                </div>
            </Popover>
            <ChatbotWidget v-if="isEmployer" />
        </div>
    </div>
</template>

<style scoped>
/* Specific overrides if needed beyond utilities */
</style>
