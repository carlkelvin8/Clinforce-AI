<template>
  <AppLayout>
    <div class="min-h-screen bg-slate-50 font-sans pb-12">
      <div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
        <div class="w-full h-[calc(100vh-10rem)] bg-white rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row overflow-hidden">
        
        <!-- LEFT SIDEBAR: Conversations List -->
        <div class="w-full md:w-80 lg:w-96 border-r border-gray-200 flex flex-col z-10 bg-white"
             :class="{ 'hidden md:flex': selected && isMobile, 'flex': !selected || !isMobile }">
          
          <!-- Sidebar Header -->
          <div class="p-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center justify-between w-full gap-3">
                <div class="flex items-center gap-3">
                  <Avatar :image="meAvatarUrl" :label="!meAvatarUrl ? meInitials : null" shape="circle" class="shadow-sm border border-gray-200 bg-blue-50 text-blue-600" />
                  <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Messages</h2>
                </div>
                <Button icon="pi pi-refresh" rounded text severity="secondary" :loading="loadingList" aria-label="Refresh" @click="loadConversations" v-tooltip.bottom="'Refresh'" />
              </div>
            </div>

            <!-- Search Bar -->
            <IconField iconPosition="left" class="w-full">
                <InputIcon class="pi pi-search text-gray-400" />
                <InputText v-model="q" placeholder="Search messages..." class="w-full !bg-gray-50 !border-gray-200 focus:!bg-white focus:!border-blue-500 !rounded-lg" size="small" />
            </IconField>
          </div>

          <!-- Conversation List -->
          <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
            <!-- Loading State -->
            <div v-if="loadingList && !conversations.length" class="flex flex-col items-center justify-center h-40 space-y-3">
              <ProgressSpinner style="width: 30px; height: 30px" strokeWidth="4" />
              <span class="text-sm text-gray-500">Loading conversations...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="listError" class="p-6 text-center">
              <Message severity="error" :closable="false">{{ listError }}</Message>
              <Button label="Try again" text class="mt-2" @click="loadConversations" />
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredConversations.length === 0" class="flex flex-col items-center justify-center h-64 text-center p-6">
              <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                <i class="pi pi-search text-2xl text-gray-400"></i>
              </div>
              <h3 class="font-medium text-gray-900 dark:text-white mb-1">No messages found</h3>
              <p class="text-gray-500 text-sm">No conversations match your search.</p>
            </div>

            <!-- List Items -->
            <template v-else>
              <div 
                v-for="c in filteredConversations" 
                :key="c.id"
                @click="selectConversation(c.id)"
                class="w-full p-3 flex gap-3 text-left rounded-xl transition-all cursor-pointer group"
                :class="{ 
                  'bg-blue-600 text-white': selectedId === c.id,
                  'hover:bg-gray-50': selectedId !== c.id
                }"
              >
                <!-- Avatar -->
                <div class="relative shrink-0">
                  <Avatar 
                    :image="conversationAvatar(c)" 
                    :label="!conversationAvatar(c) ? getInitials(conversationTitle(c)) : null" 
                    shape="circle" 
                    size="large" 
                    class="border border-gray-200 dark:border-gray-700 shadow-sm"
                    :class="{ 'ring-2 ring-blue-200': selectedId === c.id }"
                  />
                  <span v-if="!c.last_read_at && lastFrom(c) !== 'You'" class="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 rounded-full border-2 border-white dark:border-gray-900"></span>
                </div>

                <div class="flex-1 min-w-0 flex flex-col justify-center">
                  <div class="flex justify-between items-baseline mb-1">
                    <h3
                      class="font-semibold truncate pr-2 text-sm md:text-base transition-colors"
                      :class="selectedId === c.id ? 'text-white' : 'text-slate-900 group-hover:text-slate-900'"
                    >
                      {{ conversationTitle(c) }}
                    </h3>
                    <span
                      class="text-[10px] shrink-0 whitespace-nowrap font-medium transition-colors"
                      :class="selectedId === c.id ? 'text-white/80' : 'text-slate-500 group-hover:text-slate-900'"
                    >
                      {{ fmtTime(c?.last_message?.created_at || c?.last_message_at) }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center">
                    <p
                      class="text-xs md:text-sm truncate max-w-[180px] transition-colors"
                      :class="{ 
                        'text-slate-900 font-medium group-hover:text-slate-900': !c.last_read_at && lastFrom(c) !== 'You' && selectedId !== c.id, 
                        'text-slate-700 group-hover:text-slate-900': (c.last_read_at || lastFrom(c) === 'You') && selectedId !== c.id,
                        'text-white font-medium': selectedId === c.id
                      }"
                    >
                      <span
                        v-if="lastFrom(c) === 'You'"
                        class="mr-1 font-semibold transition-colors"
                        :class="selectedId === c.id ? 'text-white' : 'text-slate-900 group-hover:text-slate-900'"
                      >
                        You:
                      </span>
                      {{ snippet(c?.last_message?.body) }}
                    </p>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- RIGHT MAIN: Chat Thread -->
        <div class="flex-1 flex flex-col bg-white relative"
             :class="{ 'hidden md:flex': !selected && isMobile, 'flex': selected || !isMobile }">
          
          <!-- Welcome/Empty State -->
            <div v-if="!selected" class="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-500">
            <div class="w-32 h-32 bg-blue-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6 animate-pulse">
              <i class="pi pi-comments text-5xl text-blue-200 dark:text-gray-600"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Your Messages</h2>
              <p class="max-w-xs mx-auto mb-2 text-gray-500">
                Select a conversation from the sidebar to start chatting.
              </p>
          </div>

          <template v-else>
            <!-- Chat Header -->
            <div class="bg-white border-b border-gray-100 px-6 py-3 flex items-center justify-between shadow-sm z-20">
              <div class="flex items-center gap-4">
                <Button icon="pi pi-arrow-left" text rounded severity="secondary" class="md:hidden -ml-2" @click="selected = null; selectedId = null" />
                
                <div class="relative">
                  <Avatar :image="conversationAvatar(selected)" :label="!conversationAvatar(selected) ? getInitials(conversationTitle(selected)) : null" shape="circle" size="large" class="shadow-sm border border-gray-100" />
                  <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                </div>
                
                <div>
                  <h3 class="font-semibold text-slate-900 leading-tight text-lg md:text-xl">{{ conversationTitle(selected) }}</h3>
                  <p class="text-sm text-blue-600 font-medium flex items-center gap-1.5 mt-0.5">
                    {{ participantsLabel(selected) }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-1">
                <Button v-if="otherParticipant" icon="pi pi-user" rounded text severity="secondary" v-tooltip.bottom="'View Profile'" @click="viewProfile(otherParticipant)" />
                <Button icon="pi pi-refresh" rounded text severity="secondary" :loading="loadingThread" @click="loadThread(selected.id)" v-tooltip.bottom="'Reload messages'" />
              </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6 custom-scrollbar scroll-smooth bg-white" ref="messagesEl">
              <!-- Load older messages -->
              <div v-if="threadPagination?.has_more" class="flex justify-center pb-2">
                <button @click="loadOlderMessages"
                  :disabled="loadingOlder"
                  class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1.5 px-4 py-2 rounded-full border border-blue-200 hover:bg-blue-50 transition-colors disabled:opacity-50">
                  <i :class="['pi text-xs', loadingOlder ? 'pi-spin pi-spinner' : 'pi-chevron-up']"></i>
                  {{ loadingOlder ? 'Loading...' : 'Load older messages' }}
                </button>
              </div>
              <div v-if="loadingThread && !messages.length" class="flex justify-center py-12">
                 <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
              </div>

              <div v-if="messages.length === 0 && !loadingThread" class="flex flex-col items-center justify-center py-20 text-center opacity-70">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center mb-4">
                  <i class="pi pi-send text-2xl"></i>
                </div>
                <p class="text-gray-900 font-medium text-lg">No messages yet</p>
                <p class="text-sm text-gray-500 mt-1">Start the conversation by sending a message below.</p>
              </div>

              <template v-for="(m, i) in messages" :key="m.id">
                <div v-if="i === 0 || dayKey(m.created_at) !== dayKey(messages[i - 1]?.created_at)" class="flex justify-center my-2">
                  <span class="date-chip">{{ fmtDay(m.created_at) }}</span>
                </div>
                <div 
                  class="w-full flex group"
                  :class="{ 'justify-end': isMe(m), 'justify-start': !isMe(m) }"
                >
                  <div class="flex max-w-[85%] md:max-w-[70%] gap-3" :class="{ 'flex-row-reverse': isMe(m) }">
                    <Avatar 
                      v-if="!isMe(m) && (i === 0 || !sameUser(m, messages[i - 1]) || dayKey(m.created_at) !== dayKey(messages[i - 1]?.created_at))"
                      :image="msgAvatar(m)" 
                      :label="!msgAvatar(m) ? getInitials(who(m)) : null" 
                      shape="circle" 
                      class="self-end mb-1 shrink-0 w-8 h-8 shadow-sm border border-white"
                      :class="{ 'order-2': isMe(m) }"
                    />
                    <div class="flex flex-col" :class="{ 'items-end': isMe(m), 'items-start': !isMe(m) }">
                      <div class="px-5 py-3 shadow-sm text-[15px] leading-relaxed relative transition-all break-words whitespace-pre-wrap"
                           :class="[
                             isMe(m) 
                               ? 'bg-white hover:bg-gray-50 text-black rounded-2xl rounded-tr-sm border border-gray-200 transition-colors' 
                               : 'bg-white hover:bg-gray-50 text-black rounded-2xl rounded-tl-sm border border-gray-200 transition-colors'
                           ]">
                        {{ m.body }}
                      </div>
                      <span class="text-[10px] text-gray-400 mt-1 px-1 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                        {{ fmt(m.created_at) }}
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Composer Area -->
            <div class="bg-white border-t border-gray-100 p-4 md:p-6 z-20">
              <Message v-if="threadError" severity="error" class="mb-3" :closable="false">{{ threadError }}</Message>

              <div class="flex items-end gap-3 bg-gray-50 p-2 rounded-3xl border border-gray-200 focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400 transition-all">
                <Textarea
                  v-model="draft"
                  autoResize
                  rows="1"
                  class="flex-1 max-h-32 !bg-transparent !border-none !shadow-none focus:!ring-0 py-3 px-4 resize-none"
                  placeholder="Type a message..."
                  @keydown.enter.exact.prevent="sendMessage"
                />
                <Button 
                  icon="pi pi-send" 
                  @click="sendMessage" 
                  :disabled="sending || !draftTrimmed"
                  :loading="sending"
                  rounded
                  class="!w-10 !h-10 !shrink-0 mb-1 mr-1"
                  :severity="draftTrimmed ? 'primary' : 'secondary'"
                />
              </div>
              <p class="text-xs text-center text-gray-400 mt-3">
                Press <span class="font-medium text-gray-600">Enter</span> to send, <span class="font-medium text-gray-600">Shift + Enter</span> for new line
              </p>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>

    <!-- New Conversation Dialog -->
    <Dialog v-model:visible="openNew" modal header="New Conversation" :style="{ width: '50vw' }" :breakpoints="{ '960px': '75vw', '641px': '90vw' }" class="p-fluid">
        <div class="flex flex-col gap-5 pt-2">
            <div class="flex flex-col gap-2">
                <label for="participants" class="font-semibold text-sm text-gray-700">To:</label>
                <span class="p-input-icon-left">
                    <i class="pi pi-users" />
                    <InputText id="participants" v-model="newParticipantsRaw" placeholder="User IDs (e.g. 9004, 9006)" class="w-full" />
                </span>
                <small class="text-gray-500">Comma separated user IDs</small>
            </div>
            
            <div class="flex flex-col gap-2">
                <label for="subject" class="font-semibold text-sm text-gray-700">Subject <span class="font-normal text-gray-400">(Optional)</span></label>
                <InputText id="subject" v-model="newSubject" placeholder="e.g. Project Discussion" class="w-full" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="message" class="font-semibold text-sm text-gray-700">Message</label>
                <Textarea id="message" v-model="newFirstMessage" rows="4" placeholder="Write your first message..." class="w-full" />
            </div>

            <Message v-if="newError" severity="error" :closable="false">{{ newError }}</Message>
        </div>
        <template #footer>
            <Button label="Cancel" text severity="secondary" @click="openNew = false" />
            <Button label="Send Message" icon="pi pi-send" @click="createConversation" :loading="creating" :disabled="!canCreate" />
        </template>
    </Dialog>

    <!-- Profile Dialog -->
    <Dialog v-model:visible="showProfileDialog" modal :header="profileUser ? resolveName(profileUser) : 'Profile'" :style="{ width: '28rem' }" :dismissableMask="true">
        <div v-if="profileUser" class="flex flex-col items-center gap-6 py-4">
            <div class="relative">
              <Avatar :image="profileUser.avatar_url" :label="!profileUser.avatar_url ? getInitials(resolveName(profileUser)) : null" shape="circle" size="xlarge" class="w-28 h-28 text-3xl shadow-lg border-4 border-white" />
              <div class="absolute bottom-1 right-1 bg-green-500 w-5 h-5 rounded-full border-2 border-white"></div>
            </div>
            
            <div class="text-center">
              <h3 class="text-xl font-bold text-gray-900">{{ resolveName(profileUser) }}</h3>
              <p class="text-blue-600 font-medium capitalize mt-1">{{ profileUser.role || 'User' }}</p>
            </div>

            <div class="w-full bg-gray-50 p-5 rounded-2xl border border-gray-100 text-sm space-y-3">
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-500">Email</span>
                    <span class="font-medium text-gray-900">{{ profileUser.email || '—' }}</span>
                </div>
                
                <template v-if="profileUser.role === 'employer' && (profileUser.employer_profile || profileUser.employerProfile)">
                    <template v-for="(value, label) in getEmployerDetails(profileUser)" :key="label">
                        <div class="flex justify-between border-b border-gray-200 pb-2 last:border-0 last:pb-0">
                            <span class="text-gray-500">{{ label }}</span>
                            <span class="font-medium text-gray-900 text-right" v-html="value"></span>
                        </div>
                    </template>
                </template>
            </div>

            <Button label="Close" outlined class="w-full" @click="showProfileDialog = false" />
        </div>
    </Dialog>

  </AppLayout>
</template>

<script setup>
import { computed, nextTick, onMounted, onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import { me } from '@/lib/auth'

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import Avatar from 'primevue/avatar';
import ProgressSpinner from 'primevue/progressspinner';
import Message from 'primevue/message';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Divider from 'primevue/divider';
import Tooltip from 'primevue/tooltip';

const vTooltip = Tooltip;
const router = useRouter()
const meName = ref('ME')
const meId = ref(null)

// Mobile responsiveness state
const isMobile = ref(window.innerWidth < 768)
window.addEventListener('resize', () => {
  isMobile.value = window.innerWidth < 768
})

const loadingList = ref(false)
const loadingThread = ref(false)
const loadingOlder = ref(false)
const threadPagination = ref(null)
const listError = ref('')
const threadError = ref('')

const conversations = ref([])
const selectedId = ref(null)
const selected = ref(null)
const messages = ref([])

const draft = ref('')
const sending = ref(false)

const messagesEl = ref(null)

// search
const q = ref('')

// New conversation UI
const openNew = ref(false)
const newParticipantsRaw = ref('')
const newSubject = ref('')
const newFirstMessage = ref('')
const creating = ref(false)
const newError = ref('')

// Profile Dialog
const showProfileDialog = ref(false)
const profileUser = ref(null)

let pollInterval = null

function unwrap(resData) {
  return resData?.data ?? resData
}
function asArray(payload) {
  const body = unwrap(payload)
  if (Array.isArray(body)) return body
  if (Array.isArray(body?.data)) return body.data
  return []
}

function fmt(v) {
  if (!v) return ''
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return String(v)
  return d.toLocaleString([], { hour: '2-digit', minute: '2-digit', month: 'short', day: 'numeric' })
}
function fmtTime(v) {
  if (!v) return ''
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return ''
  // If today, show time, else date
  const now = new Date()
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}
function dayKey(v) {
  const d = new Date(v || 0)
  if (Number.isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}
function fmtDay(v) {
  const d = new Date(v || 0)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
function snippet(s) {
  const t = String(s || '').trim()
  if (!t) return ''
  return t.length > 60 ? t.slice(0, 60) + '...' : t
}

function getInitials(name) {
  const s = String(name || '').trim()
  if (!s) return '?'
  const parts = s.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return s.slice(0, 2).toUpperCase()
}

function conversationAvatar(c) {
  const parts = c?.participants || []
  // Find first user who isn't me
  const other = parts.find(p => p?.user && Number(p.user.id) !== Number(meId.value))
  return other?.user?.avatar_url || null
}

function msgAvatar(m) {
  return m?.sender?.avatar_url || null
}

function isMe(m) {
  const sid = m?.sender_user_id ?? m?.sender?.id ?? null
  return meId.value != null && Number(sid) === Number(meId.value)
}
function sameUser(a, b) {
  if (!a || !b) return false
  const aid = a?.sender_user_id ?? a?.sender?.id ?? null
  const bid = b?.sender_user_id ?? b?.sender?.id ?? null
  if (aid != null && bid != null) return Number(aid) === Number(bid)
  return isMe(a) === isMe(b)
}

function resolveName(u) {
  if (!u) return 'Unknown'
  
  // 1. Try profiles (handle snake_case and camelCase)
  const app = u.applicant_profile || u.applicantProfile
  const emp = u.employer_profile || u.employerProfile
  const agy = u.agency_profile || u.agencyProfile
  
  if (u.role === 'applicant' && app) {
    if (app.public_display_name) return app.public_display_name
    if (app.first_name || app.last_name) {
      return [app.first_name, app.last_name].filter(Boolean).join(' ')
    }
  }
  
  if (u.role === 'employer' && emp) {
    return 'Confidential Employer'
  }
  
  if (u.role === 'agency' && agy) {
    if (agy.agency_name) return agy.agency_name
  }
  
  // 2. Check direct name, but ignore if it looks like an email
  if (u.name && !String(u.name).includes('@')) {
    return u.name
  }
  
  // 3. Fallback to capitalized role
  if (u.role) {
    return u.role.charAt(0).toUpperCase() + u.role.slice(1)
  }
  
  return 'User'
}

function who(m) {
  if (isMe(m)) return 'You'
  return resolveName(m?.sender)
}

function participantsLabel(c) {
  const parts = c?.participants || []
  const others = parts
    .map(p => p?.user)
    .filter(u => u && Number(u.id) !== Number(meId.value))
    .map(u => resolveName(u))
  if (others.length === 0) return 'Just you'
  return others.join(', ')
}

function conversationTitle(c) {
  if (c?.subject) return c.subject
  const label = participantsLabel(c)
  return label === 'Just you' ? `Conversation #${c?.id}` : label
}

function lastFrom(c) {
  const m = c?.last_message
  if (!m) return '—'
  const sid = m?.sender_user_id ?? m?.sender?.id
  if (meId.value != null && Number(sid) === Number(meId.value)) return 'You'
  return resolveName(m?.sender)
}

const otherParticipant = computed(() => {
  if (!selected.value) return null
  const parts = selected.value.participants || []
  // Find first user who isn't me
  const other = parts.find(p => p?.user && Number(p.user.id) !== Number(meId.value))
  return other?.user || null
})

function viewProfile(user) {
  if (!user) return
  profileUser.value = user
  showProfileDialog.value = true
}

function getEmployerDetails(user) {
    const details = {}
    const ep = user.employer_profile || user.employerProfile
    if (ep) details['Company'] = 'Confidential Employer'
    if (ep.website) details['Website'] = `<a href="${ep.website}" target="_blank" class="text-blue-600 hover:underline">${ep.website}</a>`
    if (ep.location || ep.city) details['Location'] = ep.location || ep.city
    return details
}

const meAvatarUrl = ref(null)
const meInitials = ref('ME')

async function fetchMe() {
  try {
    const user = await me() // Uses lib/auth.js which updates localStorage
    meId.value = user?.id ?? null
    // Avoid showing email in the header
    const rawName = user?.name || ''
    meName.value = (rawName && !rawName.includes('@')) ? rawName : 'User'
    meAvatarUrl.value = user?.avatar_url
    meInitials.value = getInitials(meName.value)
  } catch {
    meName.value = 'ME'
  }
}

async function loadConversations() {
  loadingList.value = true
  listError.value = ''
  try {
    const res = await api.get('/conversations')
    conversations.value = asArray(res.data)
  } catch (err) {
    console.error(err)
    listError.value = 'Failed to load conversations.'
  } finally {
    loadingList.value = false
  }
}

const filteredConversations = computed(() => {
  if (!q.value) return conversations.value
  const term = q.value.toLowerCase()
  return conversations.value.filter(c => {
    const title = conversationTitle(c).toLowerCase()
    return title.includes(term)
  })
})

function selectConversation(id) {
  if (selectedId.value === id) return
  selectedId.value = id
  const c = conversations.value.find(x => x.id === id)
  selected.value = c || null
  messages.value = [] // clear previous
  if (id) {
    loadThread(id)
  }
}

async function loadThread(id) {
  if (!id) return
  loadingThread.value = true
  threadError.value = ''
  try {
    const res = await api.get(`/conversations/${id}`)
    const body = unwrap(res.data)

    // Handle both old shape (conversation.messages) and new paginated shape
    const c = body?.conversation ?? body
    const rawMsgs = body?.messages ?? asArray(c?.messages || [])

    // Update local list item if needed
    const idx = conversations.value.findIndex(x => x.id === id)
    if (idx !== -1) conversations.value[idx] = c
    selected.value = c

    // Sort messages oldest-first for display
    const msgs = [...rawMsgs].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    messages.value = msgs

    // Store pagination info for load-more
    if (body?.pagination) {
      threadPagination.value = body.pagination
    }

    scrollToBottom()

    const lastMsg = msgs[msgs.length - 1]
    if (lastMsg && !isMe(lastMsg)) markRead(c.id, lastMsg.id)

  } catch (err) {
    console.error(err)
    threadError.value = 'Failed to load messages.'
  } finally {
    loadingThread.value = false
  }
}

async function loadOlderMessages() {
  if (!selectedId.value || !threadPagination.value?.has_more || loadingOlder.value) return
  loadingOlder.value = true
  try {
    const nextPage = (threadPagination.value.current_page || 1) + 1
    const res = await api.get(`/conversations/${selectedId.value}`, { params: { page: nextPage } })
    const body = unwrap(res.data)
    const rawMsgs = body?.messages ?? []
    const older = [...rawMsgs].sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    // Prepend older messages
    messages.value = [...older, ...messages.value]
    if (body?.pagination) threadPagination.value = body.pagination
  } catch {} finally { loadingOlder.value = false }
}

async function markRead(convId, msgId) {
    try {
        await api.post(`/conversations/${convId}/read`, { last_read_message_id: msgId })
        // Update local state: finding the conversation and setting its last_read_at (if we tracked it locally perfectly)
        // For now, we rely on reload or just ignore visual update until next poll
        const idx = conversations.value.findIndex(x => x.id === convId)
        if (idx !== -1) {
            // Optimistically update
            conversations.value[idx].last_read_at = new Date().toISOString()
        }
    } catch (e) {
        console.error("Mark read failed", e)
    }
}

const draftTrimmed = computed(() => draft.value.trim().length > 0)

async function sendMessage() {
  if (!draftTrimmed.value) return
  if (!selectedId.value) return
  
  const body = draft.value
  draft.value = '' // clear immediately
  sending.value = true
  
  try {
    const res = await api.post(`/conversations/${selectedId.value}/messages`, {
      body
    })
    const newMsg = unwrap(res.data)
    messages.value.push(newMsg)
    scrollToBottom()
    
    // Update conversation list snippet
    const idx = conversations.value.findIndex(x => x.id === selectedId.value)
    if (idx !== -1) {
      conversations.value[idx].last_message = newMsg
      // Move to top?
      const c = conversations.value[idx]
      conversations.value.splice(idx, 1)
      conversations.value.unshift(c)
    }
    
  } catch (err) {
    console.error(err)
    threadError.value = 'Failed to send message.'
    draft.value = body // restore
  } finally {
    sending.value = false
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesEl.value) {
      messagesEl.value.scrollTop = messagesEl.value.scrollHeight
    }
  })
}

const canCreate = computed(() => {
    return newParticipantsRaw.value.trim().length > 0 && newFirstMessage.value.trim().length > 0
})

async function createConversation() {
    if (!canCreate.value) return
    creating.value = true
    newError.value = ''
    
    try {
        const pIds = newParticipantsRaw.value.split(',').map(x => x.trim()).filter(Boolean)
        
        const res = await api.post('/conversations', {
            participant_user_ids: pIds,
            subject: newSubject.value,
            first_message: newFirstMessage.value
        })
        
        const c = unwrap(res.data)
        conversations.value.unshift(c)
        openNew.value = false
        
        // Select it
        selectConversation(c.id)
        
        // Reset form
        newParticipantsRaw.value = ''
        newSubject.value = ''
        newFirstMessage.value = ''
        
    } catch (err) {
        console.error(err)
        newError.value = err.response?.data?.message || 'Failed to create conversation.'
    } finally {
        creating.value = false
    }
}

onMounted(async () => {
  await fetchMe()
  await loadConversations()
  
  // Simple polling for new messages every 15s
  pollInterval = setInterval(() => {
      // If we are viewing a thread, reload it
      if (selectedId.value) {
          loadThread(selectedId.value)
      }
      // And always reload list to see new conversations or updates
      loadConversations()
  }, 15000)
})

onBeforeUnmount(() => {
    if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.date-chip {
  font-size: 0.75rem;
  line-height: 1rem;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  background-color: #f3f4f6;
  color: #4b5563;
  border: 1px solid #e5e7eb;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 20px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(107, 114, 128, 0.8);
}
</style>
