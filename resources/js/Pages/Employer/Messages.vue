<script setup>
import { computed, onBeforeUnmount, onMounted, ref, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import { logout } from "@/lib/auth";
import api from "@/lib/api";
import Swal from "sweetalert2";

// PrimeVue Components
import Button from "primevue/button";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Dialog from "primevue/dialog";
import AutoComplete from "primevue/autocomplete";
import Avatar from "primevue/avatar";
import Badge from "primevue/badge";
import Menu from "primevue/menu";

/** ===== Sidebar ===== */
const route = useRoute();
const router = useRouter();

const currentUser = ref(null);

/** ============================
 *  Conversations (wired)
 * ============================ */
const loading = ref(false);
const threadLoading = ref(false);
const error = ref("");
const q = ref("");

const conversations = ref([]);
const activeId = ref(null);
const active = ref(null);
const threadBodyRef = ref(null);
let pollInterval = null;

function unwrap(resData) {
  return resData?.data ?? resData;
}
function unwrapArray(resData) {
  const body = unwrap(resData);
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  return [];
}
function fmtTime(v) {
  if (!v) return "";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  // If today, show time
  const now = new Date();
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
function initialsFrom(nameOrEmail) {
  const s = String(nameOrEmail || "C").trim();
  if (!s) return "C";
  const parts = s.split(/\s+/).filter(Boolean);
  const a = parts[0]?.[0] || "C";
  const b = parts.length > 1 ? parts[1]?.[0] : (parts[0]?.[1] || "");
  return (a + b).toUpperCase();
}

function convSnippet(c) {
  const m = c?.last_message || c?.lastMessage || null;
  return m?.body || c?.last_message_body || "";
}

function convTime(c) {
  const m = c?.last_message || c?.lastMessage || null;
  const ts = m?.created_at || c?.updated_at || c?.created_at || null;
  return fmtTime(ts);
}

function scrollToBottom() {
  nextTick(() => {
    if (threadBodyRef.value) {
      threadBodyRef.value.scrollTop = threadBodyRef.value.scrollHeight + 999;
    }
  });
}

async function loadConversations(silent = false) {
  if (!silent) loading.value = true;
  error.value = "";
  
  // Safety timeout
  const safetyTimeout = setTimeout(() => {
    if (loading.value) {
       loading.value = false;
       if (!conversations.value.length) error.value = "Request timed out.";
    }
  }, 15000);

  try {
    const res = await api.get("/conversations");
    clearTimeout(safetyTimeout);
    const list = unwrapArray(res?.data);
    conversations.value = list;

    if (!activeId.value && list[0]?.id && !silent) {
      activeId.value = list[0].id;
      await loadConversation(activeId.value, silent);
    }
  } catch (e) {
      clearTimeout(safetyTimeout);
      try {
           const res2 = await api.get("/conversations");
           conversations.value = unwrapArray(res2?.data);
      } catch (e2) {
           if (!silent) error.value = e?.response?.data?.message || "Failed to load conversations";
      }
  } finally {
    clearTimeout(safetyTimeout);
    if (!silent) loading.value = false;
  }
}

async function loadConversation(id, silent = false) {
  if (!id) return;
  if (!silent) threadLoading.value = true;
  error.value = "";
  try {
    let res;
    try {
      res = await api.get(`/api/conversations/${id}`);
    } catch {
      res = await api.get(`/conversations/${id}`);
    }
    const body = unwrap(res?.data);
    
    const oldLen = active.value?.messages?.length || 0;
    active.value = body;
    const newLen = body.messages?.length || 0;

    if (!silent || newLen > oldLen) {
      scrollToBottom();
    }
  } catch (e) {
    if (!silent) error.value = e?.response?.data?.message || "Failed to load conversation";
    if (!silent) active.value = null;
  } finally {
    if (!silent) threadLoading.value = false;
  }
}

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase();
  if (!term) return conversations.value;
  return conversations.value.filter((c) => {
    const hay = `${c.title || ""} ${c.last_message?.body || ""} ${c.last_message_body || ""}`.toLowerCase();
    return hay.includes(term);
  });
});

const otherParticipant = computed(() => {
  if (!active.value || !active.value.participants || !currentUser.value) return null;
  const p = active.value.participants.find(part => Number(part.user_id) !== Number(currentUser.value.id));
  return p ? p.user : null;
});

function viewProfile() {
  if (otherParticipant.value?.id) {
    router.push({ name: 'employer.candidates.view', params: { id: otherParticipant.value.id } });
  } else {
    Swal.fire({
      icon: 'info',
      title: 'Profile unavailable',
      text: 'Cannot identify the candidate profile for this conversation.',
    });
  }
}

function scheduleInterview() {
  if (otherParticipant.value?.id) {
    router.push({ name: 'employer.interviews', query: { candidate_id: otherParticipant.value.id } });
  } else {
    Swal.fire({
      icon: 'info',
      title: 'Candidate not found',
      text: 'Cannot identify the candidate for scheduling an interview.',
    });
  }
}

async function pickConversation(id) {
  activeId.value = id;
  await loadConversation(id);
}

/** ===== Composer ===== */
const draft = ref("");
const sending = ref(false);
const offerFile = ref(null);
const offerFileInput = ref(null);

function onPickOfferFile(ev) {
  const f = ev?.target?.files?.[0] || null;
  offerFile.value = f;
  if (offerFileInput.value) {
    offerFileInput.value.value = "";
  }
}

function clearOfferFile() {
  offerFile.value = null;
  if (offerFileInput.value) {
    offerFileInput.value.value = "";
  }
}

function triggerOfferFile() {
  if (offerFileInput.value) {
    offerFileInput.value.click();
  }
}

async function send() {
  if (!active.value) return;
  const t = draft.value.trim();
  if (!t && !offerFile.value) return;

  sending.value = true;
  try {
    let bodyToSend = t;

    if (offerFile.value) {
      const fd = new FormData();
      fd.append("doc_type", "other");
      fd.append("file", offerFile.value);

      const docRes = await api.post("/documents", fd);
      const doc = unwrap(docRes.data);

      const name = doc?.file_name || offerFile.value.name || "Document";
      const url = doc?.file_url || "";
      const attachmentText = url
        ? `Job offer document: ${name} — ${url}`
        : `Job offer document: ${name}`;

      bodyToSend = bodyToSend
        ? `${bodyToSend}\n\n${attachmentText}`
        : attachmentText;
    }

    try {
        await api.post(`/api/conversations/${active.value.id}/messages`, { body: bodyToSend });
    } catch {
        await api.post(`/conversations/${active.value.id}/messages`, { body: bodyToSend });
    }
    draft.value = "";
    clearOfferFile();
    await loadConversation(active.value.id);
    await loadConversations();
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      e?.__payload?.message ||
      e?.message ||
      "Failed to send message";
  } finally {
    sending.value = false;
  }
}

/** ===== New conversation modal (eligible candidates) ===== */
const newOpen = ref(false);
const newLoading = ref(false);

const eligibleLoading = ref(false);
const eligibleError = ref("");
const invitedCandidates = ref([]);
const appliedCandidates = ref([]);

function normalizeApplicationsPayload(resData) {
  const root = resData?.data ?? resData;
  if (root?.data?.data && Array.isArray(root.data.data)) return root.data.data;
  if (Array.isArray(root?.data)) return root.data;
  if (Array.isArray(root)) return root;
  if (root?.data?.data && Array.isArray(root.data.data)) return root.data.data;
  return [];
}

async function loadEligibleRecipients() {
  eligibleLoading.value = true;
  eligibleError.value = "";
  invitedCandidates.value = [];
  appliedCandidates.value = [];

  try {
    const [invRes, appsRes] = await Promise.all([
      api.get("/invitations"),
      api.get("/applications", { params: { scope: "owned" } }),
    ]);

    const invRoot = invRes.data?.data || invRes.data || [];
    const invRaw = Array.isArray(invRoot) ? invRoot : invRoot.data || [];

    invitedCandidates.value = invRaw
      .map((inv) => {
        const userId =
          inv?.candidate_id ||
          inv?.candidate?.id ||
          inv?.candidate_profile?.user_id ||
          null;
        if (!userId) return null;

        const profile = inv?.candidate_profile || {};
        const baseName = `${profile.first_name || ""} ${profile.last_name || ""}`.trim();
        const name =
          baseName ||
          inv?.candidate?.name ||
          `Candidate #${userId}`;

        const locationParts = [];
        if (profile.city) locationParts.push(profile.city);
        if (profile.country_code) locationParts.push(profile.country_code);

        return {
          id: userId,
          name,
          headline: profile.headline || "",
          location: locationParts.join(", "),
          kind: "invited",
        };
      })
      .filter(Boolean);

    const appsList = normalizeApplicationsPayload(appsRes.data);

    appliedCandidates.value = appsList
      .map((app) => {
        const userId =
          app?.applicant_user_id ||
          app?.applicant_id ||
          app?.applicant?.id ||
          app?.user_id ||
          null;
        if (!userId) return null;

        const name =
          app?.applicant?.name ||
          app?.applicant_full_name ||
          app?.applicant_name ||
          `Candidate #${userId}`;

        const jobTitle = app?.job?.title || app?.job_title || "";

        return {
          id: userId,
          name,
          jobTitle,
          status: app?.status || "",
          kind: "applied",
        };
      })
      .filter(Boolean);
  } catch (e) {
    eligibleError.value =
      e?.response?.data?.message ||
      e?.message ||
      "Failed to load eligible candidates.";
  } finally {
    eligibleLoading.value = false;
  }
}

function openNewConversation() {
  newOpen.value = true;
  eligibleError.value = "";
  invitedCandidates.value = [];
  appliedCandidates.value = [];
  loadEligibleRecipients();
}

async function startConversationFor(candidate) {
  if (!candidate?.id) return;
  if (newLoading.value) return;

  try {
    const name = candidate.name || "there";
    const jobTitle = candidate.jobTitle || null;

    newLoading.value = true;

    Swal.fire({
      title: "Creating conversation…",
      text: "Please wait while we set things up.",
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    const firstMessage = jobTitle
      ? `Hi ${name}, thank you for applying for ${jobTitle}. I’d like to connect with you about this role.`
      : `Hi ${name}, I’d like to connect with you about a potential opportunity.`;

    const subject = jobTitle
      ? `Regarding your application for ${jobTitle}`
      : `Message to ${name}`;

    const payload = {
      participant_user_ids: [candidate.id],
      subject,
      first_message: firstMessage,
    };

    try {
      await api.post("/api/conversations", payload);
    } catch (err) {
      try {
        await api.post("/conversations", payload);
      } catch (err2) {
        throw err2 || err;
      }
    }

    Swal.close();

    await Swal.fire({
      icon: "success",
      title: "Conversation started",
      text: `You can now continue chatting with ${name} in your message list.`,
      timer: 2000,
      showConfirmButton: false,
    });

    newOpen.value = false;
    await loadConversations();
  } catch (e) {
    const status = e?.response?.status;
    const msg =
      e?.response?.data?.message ||
      e?.__payload?.message ||
      e?.message ||
      "Failed to start conversation.";

    if (status === 403) {
      Swal.close();
      await Swal.fire({
        icon: "error",
        title: "Cannot start conversation",
        text:
          msg ||
          "You can only message candidates you invited or who applied to your jobs.",
      });
    } else {
      Swal.close();
      await Swal.fire({
        icon: "error",
        title: "Message failed",
        text: msg,
      });
    }
  } finally {
    newLoading.value = false;
  }
}

/** ===== Display helpers for list ===== */
function getOtherParticipant(c) {
  if (!c.participants || !currentUser.value) return null;
  // c.participants is array of { user_id, user: {...} }
  const p = c.participants.find(part => Number(part.user_id) !== Number(currentUser.value.id));
  return p ? p.user : null;
}

function convName(c) {
  const other = getOtherParticipant(c);
  if (other?.name) return other.name;
  return c.title || c.subject || c.display_name || `Conversation #${c.id}`;
}

function convAvatar(c) {
    const other = getOtherParticipant(c);
    if (other?.avatar_url) return other.avatar_url;
    return null; // Fallback to label
}

function convInitials(c) {
  const other = getOtherParticipant(c);
  if (other?.name) return initialsFrom(other.name);
  return initialsFrom(convName(c));
}

function isMine(m) {
  const uid = currentUser.value?.id ?? null;
  const senderId =
    m?.sender_user_id ??
    m?.sender?.id ??
    m?.user_id ??
    m?.from_id ??
    null;
  if (uid != null && senderId != null) {
    return Number(senderId) === Number(uid);
  }
  return !!(m?.is_mine || m?.from === "me");
}
function dayKey(ts) {
  const d = new Date(ts || 0);
  if (!Number.isFinite(d.getTime())) return "";
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}
function fmtDay(ts) {
  const d = new Date(ts || 0);
  if (!Number.isFinite(d.getTime())) return "";
  return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" });
}
function sameUser(a, b) {
  if (!a || !b) return false;
  const am = isMine(a);
  const bm = isMine(b);
  if (am !== bm) return false;
  const as = a?.sender_user_id ?? a?.sender?.id ?? null;
  const bs = b?.sender_user_id ?? b?.sender?.id ?? null;
  if (as != null && bs != null) return Number(as) === Number(bs);
  return am === bm;
}
function messageText(m) {
  const raw = m?.body ?? m?.text ?? "";
  return String(raw).replace(/\s+/g, " ").trim();
}

onMounted(async () => {
  try {
    const u = localStorage.getItem("auth_user");
    if (u) currentUser.value = JSON.parse(u);
  } catch(e) {}

  await loadConversations();

  // Auto-refresh every 5 seconds
  pollInterval = setInterval(() => {
    // Refresh conversation list
    if (!loading.value) loadConversations(true);
    // Refresh active thread if one is selected
    if (activeId.value && !threadLoading.value) loadConversation(activeId.value, true);
  }, 5000);
});

onBeforeUnmount(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
<template>
  <AppLayout>
    <div class="min-h-screen bg-slate-50 font-sans pb-12">
      <div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
        <div class="w-full h-[calc(100vh-10rem)] bg-white rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row overflow-hidden">
          <!-- LEFT: Sidebar -->
          <!-- RIGHT: Chat -->
        </div>
      </div>
    </div>

    <!-- New Conversation Dialog + Chatbot -->
  </AppLayout>
</template><div
  class="flex-1 flex flex-col bg-white min-w-0 relative"
  :class="{ 'hidden md:flex': !activeId }"
>
  <!-- v-if="!active" empty state -->
  <!-- v-else active chat header, messages list, composer -->
</div>  <AppLayout>
    <div class="min-h-screen bg-slate-50 font-sans pb-12">
      <div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
        <div class="w-full h-[calc(100vh-10rem)] bg-white rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row overflow-hidden">
          <!-- LEFT: Sidebar Conversation List -->
          <div
            class="w-full md:w-80 lg:w-96 flex flex-col border-r border-gray-200 bg-white"
            :class="{ 'hidden md:flex': activeId }"
          >
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-200 bg-white">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900">Messages</h2>
                <Button
                  icon="pi pi-plus"
                  size="small"
                  rounded
                  @click="openNewConversation"
                  class="w-8 h-8 p-0"
                />
              </div>
              <div class="relative">
                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input
                  v-model="q"
                  type="text"
                  placeholder="Search messages..."
                  class="w-full pl-9 pr-3 py-2 bg-gray-100 border-none rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 text-gray-900 placeholder-gray-500"
                />
              </div>
            </div>

            <!-- Conversation List -->
            <div class="flex-1 overflow-y-auto">
              <div v-if="loading && !conversations.length" class="p-8 text-center text-gray-500">
                <i class="pi pi-spin pi-spinner text-xl mb-2"></i>
                <div>Loading conversations...</div>
              </div>

              <div v-else-if="!filtered.length" class="p-8 text-center text-gray-500">
                <div class="text-sm">No messages found</div>
              </div>

              <div v-else class="flex flex-col">
                <div
                  v-for="c in filtered"
                  :key="c.id"
                  @click="pickConversation(c.id)"
                  class="p-4 cursor-pointer transition-colors hover:bg-white border-b border-gray-100 relative group"
                  :class="[activeId === c.id ? 'bg-white border-l-4 border-l-blue-600 shadow-sm' : 'border-l-4 border-l-transparent']"
                >
                  <div class="flex gap-3">
                    <Avatar
                      v-if="convAvatar(c)"
                      :image="convAvatar(c)"
                      shape="circle"
                      class="flex-shrink-0 w-10 h-10 border border-gray-100"
                    />
                    <Avatar
                      v-else
                      :label="convInitials(c)"
                      shape="circle"
                      class="flex-shrink-0 w-10 h-10 bg-gray-200 text-gray-600 font-bold border border-gray-100"
                    />
                    <div class="flex-1 min-w-0">
                      <div class="flex justify-between items-baseline mb-1">
                        <span
                          class="font-semibold text-gray-900 truncate pr-2"
                          :class="{'text-blue-700': activeId === c.id}"
                        >
                          {{ convName(c) }}
                        </span>
                        <span class="text-xs text-gray-400 whitespace-nowrap">
                          {{ convTime(c) }}
                        </span>
                      </div>
                      <div class="text-sm text-gray-500 truncate">
                        {{ convSnippet(c) || "—" }}
                      </div>
                      <div v-if="c.subject" class="text-xs text-blue-600 mt-1 truncate font-medium">
                        {{ c.subject }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT: Chat Area -->
          <div
            class="flex-1 flex flex-col bg-white min-w-0 relative"
            :class="{ 'hidden md:flex': !activeId }"
          >
            <!-- Empty State -->
            <div
              v-if="!active"
              class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-white"
            >
              <div class="w-16 h-16 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mb-4">
                <i class="pi pi-comments text-2xl"></i>
              </div>
              <h3 class="text-xl font-bold text-gray-900 mb-2">Your Conversations</h3>
              <p class="text-gray-500 max-w-xs mx-auto mb-6">
                Select a candidate from the list to view history or start a new conversation.
              </p>
              <Button label="New Message" icon="pi pi-plus" @click="openNewConversation" />
            </div>

            <!-- Active Chat -->
            <div v-else class="flex flex-col h-full">
              <!-- Chat Header -->
              <div
                class="flex items-center justify-between px-4 md:px-6 py-3 border-b border-slate-100 bg-white/95 backdrop-blur-sm flex-shrink-0 z-10"
              >
                <div class="flex items-center gap-3">
                  <Button
                    icon="pi pi-arrow-left"
                    text
                    rounded
                    class="md:hidden -ml-2 text-gray-500"
                    @click="activeId = null; active = null"
                  />

                  <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                      {{ convName(active) }}
                      <span
                        v-if="otherParticipant"
                        class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100"
                      >
                        Candidate
                      </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                      {{ active.subject || "No subject" }}
                    </div>
                  </div>
                </div>

                <div class="flex gap-2">
                  <Button
                    label="Profile"
                    icon="pi pi-user"
                    size="small"
                    outlined
                    class="hidden sm:flex !border-slate-200 !text-slate-700 hover:!bg-slate-50 focus-visible:!ring-2 focus-visible:!ring-blue-200 rounded-full px-3"
                    @click="viewProfile"
                  />
                  <Button
                    label="Interview"
                    icon="pi pi-calendar"
                    size="small"
                    severity="secondary"
                    class="hidden sm:flex !border-slate-200 !text-slate-700 hover:!bg-slate-50 focus-visible:!ring-2 focus-visible:!ring-blue-200 rounded-full px-3"
                    @click="scheduleInterview"
                  />
                  <Button
                    icon="pi pi-ellipsis-v"
                    text
                    rounded
                    size="small"
                    class="text-gray-400 hover:!bg-slate-50 focus-visible:!ring-2 focus-visible:!ring-blue-200"
                  />
                </div>
              </div>

              <!-- Messages List -->
              <div
                class="messages-scroll flex-1 overflow-y-auto px-3 md:px-6 py-4 md:py-6 bg-slate-50 scroll-smooth"
                ref="threadBodyRef"
              >
                <div
                  v-if="threadLoading && !(active.messages || []).length"
                  class="flex justify-center py-10"
                >
                  <i class="pi pi-spin pi-spinner text-2xl text-gray-400"></i>
                </div>

                <div
                  v-else-if="!(active.messages || []).length"
                  class="flex flex-col items-center justify-center py-16 text-center text-gray-500"
                >
                  <div class="w-16 h-16 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center mb-4">
                    <i class="pi pi-send text-2xl text-blue-400"></i>
                  </div>
                  <p class="text-base font-semibold text-slate-900 mb-1">No messages yet</p>
                  <p class="text-sm text-slate-500">Start the conversation by sending a message below.</p>
                </div>

                <div v-else>
                  <template
                    v-for="(m, idx) in active.messages"
                    :key="m.id || idx"
                  >
                    <div
                      v-if="
                        idx === 0 ||
                        dayKey(m.created_at || m.at) !==
                          dayKey(active.messages[idx - 1]?.created_at || active.messages[idx - 1]?.at)
                      "
                      class="flex justify-center my-2"
                    >
                      <span
                        class="text-xs px-3 py-1 rounded-full bg-white text-slate-600 border border-slate-200 shadow-sm"
                      >
                        {{ fmtDay(m.created_at || m.at) }}
                      </span>
                    </div>
                    <div
                      class="msg-row"
                      :class="[
                        isMine(m) ? 'mine' : 'theirs',
                        idx > 0 &&
                        sameUser(m, active.messages[idx - 1]) &&
                        dayKey(m.created_at || m.at) ===
                          dayKey(
                            active.messages[idx - 1]?.created_at ||
                              active.messages[idx - 1]?.at
                          )
                          ? 'mt-2'
                          : 'mt-4'
                      ]"
                    >
                      <div
                        class="max-w-[90%] md:max-w-[70%] flex items-end gap-2 group"
                      >
                        <Avatar
                          v-if="
                            !isMine(m) &&
                            (idx === 0 ||
                              !sameUser(m, active.messages[idx - 1]) ||
                              dayKey(m.created_at || m.at) !==
                                dayKey(
                                  active.messages[idx - 1]?.created_at ||
                                    active.messages[idx - 1]?.at
                                ))
                          "
                          :label="initialsFrom(active?.title || convName(active))"
                          shape="circle"
                          class="w-8 h-8 flex-shrink-0 bg-gray-200 text-gray-600 text-xs"
                        />
                        <div
                          class="flex flex-col"
                          :class="{ 'items-end': isMine(m), 'items-start': !isMine(m) }"
                        >
                          <div
                            class="msg-bubble shadow-sm text-[16px] leading-relaxed"
                          >
                            <span class="msg-text">
                              {{ messageText(m) }}
                            </span>
                          </div>
                          <span
                            v-if="
                              idx === active.messages.length - 1 ||
                              !sameUser(m, active.messages[idx + 1]) ||
                              dayKey(m.created_at || m.at) !==
                                dayKey(
                                  active.messages[idx + 1]?.created_at ||
                                    active.messages[idx + 1]?.at
                                )
                            "
                            class="text-[11px] text-slate-400 mt-1 px-1"
                          >
                            {{ fmtTime(m.created_at || m.at) }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Composer -->
              <div class="p-3 md:p-4 bg-white/95 border-t border-slate-100 flex-shrink-0 backdrop-blur-sm">
                <div class="max-w-4xl mx-auto">
                  <div class="flex items-end gap-3 bg-slate-50 p-2.5 rounded-2xl border border-slate-200 focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400 transition-all">
                    <input
                      ref="offerFileInput"
                      type="file"
                      class="hidden"
                      accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                      @change="onPickOfferFile"
                    />
                    <Button
                      icon="pi pi-paperclip"
                      text
                      rounded
                      class="!w-9 !h-9 !shrink-0 mb-0.5 text-slate-500"
                      @click="triggerOfferFile"
                    />
                    <Textarea
                      v-model="draft"
                      autoResize
                      rows="1"
                      placeholder="Type a message..."
                      class="flex-1 max-h-32 !bg-transparent !border-none !shadow-none focus:!ring-0 py-2.5 px-3 text-sm leading-relaxed resize-none"
                      @keydown.enter.exact.prevent="send"
                    />
                    <Button
                      icon="pi pi-send"
                      @click="send"
                      :disabled="sending || !draft.trim()"
                      rounded
                      :loading="sending"
                      class="!w-10 !h-10 !shrink-0 mb-0.5 !bg-blue-600 !border-blue-600 hover:!bg-blue-700 shadow-md shadow-blue-200"
                    />
                  </div>
                  <div
                    v-if="offerFile"
                    class="mt-2 flex items-center justify-between text-[11px] text-gray-500"
                  >
                    <span class="truncate">
                      Attached: {{ offerFile.name }}
                    </span>
                    <button
                      type="button"
                      class="ml-3 text-blue-600 hover:underline flex-shrink-0"
                      @click="clearOfferFile"
                    >
                      Remove
                    </button>
                  </div>
                  <div class="text-center mt-2">
                    <span class="text-[11px] text-gray-400">
                      Press <span class="font-medium text-gray-600">Enter</span> to send
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Conversation Dialog -->
    <Dialog
      v-model:visible="newOpen"
      modal
      header="New Message"
      class="w-full max-w-4xl"
      :pt="{
        root: { class: 'rounded-2xl overflow-hidden shadow-xl' },
        header: { class: 'border-b border-gray-100 pb-4 bg-white/90 backdrop-blur-sm px-6' },
        content: { class: 'pt-0 pb-5 px-6 bg-slate-50' }
      }"
    >
      <div class="space-y-5">
        <div class="pt-4">
          <p class="text-xs font-medium uppercase tracking-wide text-blue-600">
            Start a new conversation
          </p>
          <p class="text-sm text-gray-500 mt-1">
            Select an invited candidate or an applicant to start messaging.
          </p>
        </div>

        <div
          v-if="eligibleError"
          class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-sm text-red-700"
        >
          {{ eligibleError }}
        </div>

        <div
          class="rounded-2xl border border-slate-200 bg-white shadow-sm px-5 py-5 space-y-5"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">
                  Invited candidates
                </h3>
                <span
                  v-if="invitedCandidates.length"
                  class="text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700"
                >
                  {{ invitedCandidates.length }}
                </span>
              </div>

              <div
                v-if="eligibleLoading && !invitedCandidates.length"
                class="text-xs text-gray-400 py-6"
              >
                Loading invited candidates…
              </div>
              <div
                v-else-if="!eligibleLoading && !invitedCandidates.length"
                class="text-xs text-gray-400 py-6"
              >
                No invited candidates yet.
              </div>
              <div v-else class="space-y-2.5 max-h-80 overflow-y-auto pr-1.5">
                <button
                  v-for="cand in invitedCandidates"
                  :key="`inv-${cand.id}`"
                  type="button"
                  class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-2xl hover:bg-slate-50 border border-transparent hover:border-slate-200 text-left transition"
                  @click="startConversationFor(cand)"
                >
                  <Avatar
                    :label="initialsFrom(cand.name)"
                    class="w-9 h-9 bg-indigo-50 text-indigo-600 border border-indigo-100 text-xs font-semibold"
                    shape="circle"
                  />
                  <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-900 truncate">
                      {{ cand.name }}
                    </div>
                    <div
                      v-if="cand.headline"
                        class="text-xs text-gray-500 truncate mt-0.5"
                    >
                      {{ cand.headline }}
                    </div>
                    <div
                      v-if="cand.location"
                        class="text-[11px] text-gray-400 truncate mt-0.5"
                    >
                      {{ cand.location }}
                    </div>
                  </div>
                  <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                    Invited
                  </span>
                </button>
              </div>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">
                  Applicants
                </h3>
                <span
                  v-if="appliedCandidates.length"
                  class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700"
                >
                  {{ appliedCandidates.length }}
                </span>
              </div>

              <div
                v-if="eligibleLoading && !appliedCandidates.length"
                class="text-xs text-gray-400 py-6"
              >
                Loading applicants…
              </div>
              <div
                v-else-if="!eligibleLoading && !appliedCandidates.length"
                class="text-xs text-gray-400 py-6"
              >
                No applicants found for your jobs yet.
              </div>
              <div v-else class="space-y-2.5 max-h-80 overflow-y-auto pr-1.5">
                <button
                  v-for="cand in appliedCandidates"
                  :key="`app-${cand.id}-${cand.jobTitle}`"
                  type="button"
                  class="w-full flex items-center gap-3.5 px-3 py-2.5 rounded-2xl hover:bg-slate-50 border border-transparent hover:border-slate-200 text-left transition"
                  @click="startConversationFor(cand)"
                >
                  <Avatar
                    :label="initialsFrom(cand.name)"
                    class="w-9 h-9 bg-emerald-50 text-emerald-600 border border-emerald-100 text-xs font-semibold"
                    shape="circle"
                  />
                  <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-gray-900 truncate">
                      {{ cand.name }}
                    </div>
                    <div
                      v-if="cand.jobTitle"
                        class="text-xs text-gray-500 truncate mt-0.5"
                    >
                      Applied for {{ cand.jobTitle }}
                    </div>
                    <div
                      v-if="cand.status"
                        class="text-[11px] text-gray-400 truncate mt-0.5"
                    >
                      Status: {{ cand.status }}
                    </div>
                  </div>
                  <span class="pi pi-angle-right text-gray-300 text-xs"></span>
                </button>
              </div>
            </div>
          </div>

          <p class="text-[11px] text-gray-400 mt-1.5">
            We only list candidates you invited or whose applications belong to your job posts.
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end items-center pt-4 border-t border-gray-100 mt-0 px-6 pb-3">
          <Button
            label="Close"
            text
            severity="secondary"
            @click="newOpen = false"
            class="!px-4 !rounded-full"
          />
        </div>
      </template>
    </Dialog>

  </AppLayout>
</template>

<style scoped>
/* Ensure SweetAlert is always above PrimeVue dialogs/overlays */
:global(.swal2-container) {
  z-index: 2147483647 !important;
}

/* Custom Scrollbar for message list */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}

.messages-scroll {
  display: flex;
  flex-direction: column;
}

.msg-row {
  display: flex;
  width: 100%;
}

.msg-row.mine {
  justify-content: flex-end;
}

.msg-row.theirs {
  justify-content: flex-start;
}

.msg-bubble {
  max-width: 100%;
  padding: 10px 14px;
  border-radius: 16px;
  display: inline-block;
  word-break: break-word;
  white-space: normal;
  writing-mode: horizontal-tb;
  transform: none;
}

.msg-text {
  display: inline;
  white-space: normal !important;
  word-break: break-word;
  overflow-wrap: anywhere;
  writing-mode: horizontal-tb !important;
}

.msg-row.mine .msg-bubble {
  background: var(--primary, #2563eb);
  color: #ffffff;
  border-top-right-radius: 6px;
}

.msg-row.theirs .msg-bubble {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.1);
  color: #0f172a;
  border-top-left-radius: 6px;
}
</style>
