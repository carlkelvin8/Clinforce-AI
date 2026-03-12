          <template>
  <div class="chatbot-root">
    <!-- Floating Toggle Button -->
    <button v-if="!isOpen" @click="toggle" class="chat-toggle">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="chat-icon">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
      </svg>
      <span class="chat-label">AI Assistant</span>
    </button>

    <!-- Chat Window -->
    <div v-if="isOpen" class="chat-window">
      <div class="chat-header">
        <div class="header-title">
          <span class="bot-icon">🧠</span>
          <span>Clinforce Copilot</span>
        </div>
        <div class="header-actions">
          <button class="header-btn" :disabled="!micSupported" @click="toggleVoice" :aria-pressed="micActive" aria-label="Voice input">
            <svg v-if="!micActive" xmlns="http://www.w3.org/2000/svg" class="mi" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14a3 3 0 003-3V6a3 3 0 10-6 0v5a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V21H8a1 1 0 100 2h8a1 1 0 100-2h-3v-3.08A7 7 0 0019 11z"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="mi" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a3 3 0 00-3 3v5a3 3 0 006 0V6a3 3 0 00-3-3z"/><path d="M4.91 3.5l15.59 15.59-1.41 1.41L15 16.41V18a7 7 0 01-6-6 1 1 0 10-2 0 9 9 0 007 8.94V21H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.06l-1.94-1.94A9 9 0 004 12a1 1 0 102 0 7 7 0 011-3.61L3.5 4.91 4.91 3.5z"/></svg>
          </button>
          <button @click="toggle" class="close-btn" aria-label="Close chat">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px;height:20px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="chat-messages" ref="messagesContainer">
        <div v-if="micNotice" class="notice" aria-live="polite">{{ micNotice }}</div>
        <div v-if="messages.length === 0" class="welcome-msg">
          <p>👋 Hi! I can help analyze candidates, summarize roles, and answer questions.</p>
          <div class="quick-prompts">
            <button class="chip" @click="usePrompt('Summarize this job post')">Summarize this job post</button>
            <button class="chip" @click="usePrompt('Find applicants with ICU experience')">Find ICU applicants</button>
            <button class="chip" @click="usePrompt('Draft a message to a candidate')">Draft a message</button>
          </div>
        </div>

        <div 
          v-for="(msg, idx) in messages" 
          :key="idx" 
          class="message-row"
          :class="msg.role === 'user' ? 'user-row' : 'bot-row'"
        >
          <div class="message-bubble" :class="msg.role">
            <div class="message-meta" v-if="msg.role !== 'user'">
              <button class="speak-btn" v-if="ttsSupported" @click="speak(msg.content)" aria-label="Read aloud">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="speak-icon" fill="currentColor"><path d="M12 3v18l7-6h3V9h-3l-7-6zM2 9v6h4v-6H2z"/></svg>
              </button>
            </div>
            <div class="message-text">{{ msg.content }}</div>
            <div v-if="msg.attachments && msg.attachments.length" class="msg-attachments">
              <div v-for="(f,i) in msg.attachments" :key="i" class="att-chip">
                <svg class="att-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 6.5v8a4.5 4.5 0 11-9 0v-9a3 3 0 116 0v8a1.5 1.5 0 11-3 0V7a.75.75 0 111.5 0v7a0 0 0 000 0 0 0 0 000 0"/></svg>
                <span class="truncate">{{ f.name }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-if="loading" class="message-row bot-row">
          <div class="message-bubble assistant loading-bubble">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
          </div>
        </div>
      </div>

      <div v-if="uploadNotice" class="notice" aria-live="polite">{{ uploadNotice }}</div>
      <div v-if="attachments.length" class="attach-preview">
        <div v-for="(f, i) in attachments" :key="i" class="attach-item">
          <div class="file-pill">
            <svg class="pill-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M8 6a4 4 0 015.657 0l3.536 3.536a4 4 0 11-5.657 5.657l-1.414-1.414"/></svg>
            <span class="truncate">{{ f.name }}</span>
            <button class="pill-x" @click="removeAttachment(i)" aria-label="Remove">×</button>
          </div>
        </div>
      </div>

      <div class="chat-input-area">
        <button class="icon-btn" @click="triggerChoose" :disabled="loading" aria-label="Attach files">
          <svg class="icon-24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7a5 5 0 019 0v7a3.5 3.5 0 11-7 0V8a2 2 0 114 0v6a.5.5 0 01-1 0V8a1 1 0 10-2 0v6a2.5 2.5 0 105 0V7a6.5 6.5 0 10-13 0v7a5 5 0 1010 0V9h-2v5a3 3 0 11-6 0V7z"/></svg>
        </button>
        <input ref="fileInput" type="file" class="hidden" multiple @change="handleFiles" accept="image/*,.pdf,.doc,.docx,.txt" />
        <input 
          v-model="input" 
          @keydown.enter="send"
          placeholder="Ask anything…" 
          class="chat-input"
          :disabled="loading"
        />
        <button class="icon-btn" :disabled="!micSupported || loading" @click="toggleVoice" :aria-pressed="micActive" aria-label="Voice input">
          <svg v-if="!micActive" class="icon-24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14a3 3 0 003-3V6a3 3 0 10-6 0v5a3 3 0 003 3z"/><path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V21H8a1 1 0 100 2h8a1 1 0 100-2h-3v-3.08A7 7 0 0019 11z"/></svg>
          <svg v-else class="icon-24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a3 3 0 00-3 3v5a3 3 0 006 0V6a3 3 0 00-3-3z"/><path d="M4.91 3.5l15.59 15.59-1.41 1.41L15 16.41V18a7 7 0 01-6-6 1 1 0 10-2 0 9 9 0 007 8.94V21H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.06l-1.94-1.94A9 9 0 004 12a1 1 0 102 0 7 7 0 011-3.61L3.5 4.91 4.91 3.5z"/></svg>
        </button>
        <button @click="send" :disabled="(!input.trim() && attachments.length===0) || loading" class="send-btn" aria-label="Send message">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="send-icon">
            <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'

const isOpen = ref(false)
const input = ref('')
const loading = ref(false)
const messages = ref([])
const messagesContainer = ref(null)
const attachments = ref([])
const fileInput = ref(null)
const micSupported = ref(false)
const micActive = ref(false)
const ttsSupported = ref(false)
let recognition = null
const micNotice = ref('')
const uploadNotice = ref('')
const currentUserId = ref(null)

function getToken() {
  return localStorage.getItem("auth_token") || localStorage.getItem("CLINFORCE_TOKEN");
}

function getUser() {
  try {
    return JSON.parse(localStorage.getItem("auth_user"));
  } catch {
    return null;
  }
}

function loadHistory() {
  const user = getUser();
  const newUserId = user?.id || 'guest';
  
  // If user changed, clear history
  if (currentUserId.value && currentUserId.value !== newUserId) {
    messages.value = [];
  }
  
  currentUserId.value = newUserId;
  const key = `clinforce_chat_history_${newUserId}`;
  
  const saved = localStorage.getItem(key);
  if (saved) {
    try {
      messages.value = JSON.parse(saved);
    } catch (e) {
      console.error('Failed to load chat history', e);
      messages.value = [];
    }
  } else {
    messages.value = [];
  }
}

function saveHistory() {
  const key = `clinforce_chat_history_${currentUserId.value}`;
  localStorage.setItem(key, JSON.stringify(messages.value));
}

onMounted(() => {
  loadHistory();
  // Listen for auth changes to switch history
  window.addEventListener('auth:changed', loadHistory);

  const SR = window.SpeechRecognition || window.webkitSpeechRecognition
  const secure = window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1'
  micSupported.value = secure && Boolean(SR)
  if (!secure) {
    micNotice.value = 'Microphone requires HTTPS or localhost.'
  }
  ttsSupported.value = 'speechSynthesis' in window
  window.addEventListener('chatbot:toggle', toggle)
  window.addEventListener('chatbot:open', () => { isOpen.value = true })
})

onBeforeUnmount(() => {
  window.removeEventListener('auth:changed', loadHistory)
  window.removeEventListener('chatbot:toggle', toggle)
  window.removeEventListener('chatbot:open', () => { isOpen.value = true })
})

watch(messages, () => {
  saveHistory()
  scrollToBottom()
}, { deep: true })

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    scrollToBottom()
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

function usePrompt(text) {
  input.value = text
  nextTick(() => {
    const el = document.activeElement
    if (el && el.blur) el.blur()
  })
}

function triggerChoose() {
  if (fileInput.value) fileInput.value.click()
}

function handleFiles(e) {
  const files = Array.from(e.target.files || [])
  files.forEach(f => attachments.value.push(f))
  if (fileInput.value) fileInput.value.value = ''
}

function removeAttachment(i) {
  attachments.value.splice(i, 1)
}

function speak(text) {
  if (!ttsSupported.value) return
  try {
    const u = new SpeechSynthesisUtterance(text)
    window.speechSynthesis.speak(u)
  } catch {}
}

function toggleVoice() {
  if (!micSupported.value) return
  if (!recognition) {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition
    recognition = new SR()
    recognition.continuous = false
    recognition.lang = 'en-US'
    recognition.interimResults = false
    recognition.onstart = () => { micActive.value = true }
    recognition.onresult = (e) => {
      const transcript = Array.from(e.results).map(r => r[0].transcript).join(' ')
      input.value = (input.value ? input.value + ' ' : '') + transcript
    }
    recognition.onend = () => { micActive.value = false }
    recognition.onerror = (ev) => {
      micActive.value = false
      micNotice.value = 'Microphone blocked. Please allow mic access in your browser.'
      console.warn('Speech error', ev)
    }
  }
  if (micActive.value) {
    try { recognition.stop() } catch {}
    micActive.value = false
  } else {
    try { recognition.start() } catch (e) {
      micActive.value = false
      micNotice.value = 'Failed to start microphone. Check permissions.'
    }
  }
}

async function send() {
  const text = input.value.trim()
  if (!text && attachments.value.length === 0) return

  messages.value.push({
    role: 'user',
    content: text || '',
    attachments: attachments.value.map(f => ({ name: f.name, size: f.size, type: f.type }))
  })
  input.value = ''
  loading.value = true
  scrollToBottom()

  try {
    const token = getToken()
    const apiMessages = messages.value.map(m => ({
      role: m.role,
      content: m.content,
      attachments: m.attachments || []
    }))

    let res
    if (attachments.value.length) {
      try {
        const fd = new FormData()
        fd.append('messages', JSON.stringify(apiMessages))
        attachments.value.forEach(f => fd.append('files[]', f, f.name))
        const headers = { 'Accept': 'application/json' }
        if (token) headers['Authorization'] = `Bearer ${token}`
        res = await axios.post('/api/chatbot', fd, { headers })
        uploadNotice.value = ''
      } catch (e) {
        const status = e?.response?.status
        const shouldFallback = status === 400 || status === 404 || status === 415 || status === 422 || !status
        if (shouldFallback) {
          const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' }
          if (token) headers['Authorization'] = `Bearer ${token}`
          res = await axios.post('/api/chatbot', { messages: apiMessages }, { headers })
          uploadNotice.value = 'Server did not accept file uploads. Sent text only.'
        } else {
          throw e
        }
      }
    } else {
      const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' }
      if (token) headers['Authorization'] = `Bearer ${token}`
      res = await axios.post('/api/chatbot', { messages: apiMessages }, { headers })
      uploadNotice.value = ''
    }
    
    const botMsg = res.data
    messages.value.push({ role: botMsg.role, content: botMsg.content, attachments: botMsg.attachments || [] })

  } catch (err) {
    console.error(err)
    const errorMsg = err.response?.data?.error || 'Sorry, I encountered an error. Please try again later.'
    messages.value.push({ 
      role: 'assistant', 
      content: errorMsg
    })
  } finally {
    loading.value = false
    attachments.value = []
    scrollToBottom()
  }
}
</script>

<style scoped>
.chatbot-root {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  /* font-family removed to use global Inter font */
}

/* Custom Scrollbar */
.chat-messages::-webkit-scrollbar {
  width: 6px;
}
.chat-messages::-webkit-scrollbar-track {
  background: transparent;
}
.chat-messages::-webkit-scrollbar-thumb {
  background-color: #d1d5db;
  border-radius: 999px;
}
.chat-messages::-webkit-scrollbar-thumb:hover {
  background-color: #9ca3af;
}

/* Toggle Button */
.chat-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border: 1px solid rgba(99,102,241,0.35);
  padding: 12px 20px;
  border-radius: 999px;
  cursor: pointer;
  box-shadow: 0 18px 42px rgba(99,102,241,0.22);
  transition: transform 0.2s, background 0.2s, box-shadow 0.2s;
}
.chat-toggle:hover {
  background: linear-gradient(180deg, #4338ca 0%, #6d28d9 100%);
  transform: translateY(-2px);
  box-shadow: 0 22px 55px rgba(99,102,241,0.28);
}
.chat-icon {
  width: 20px;
  height: 20px;
}
.chat-label {
  font-weight: 900;
  font-size: 14px;
  letter-spacing: 0.02em;
}

/* Chat Window */
.chat-window {
  width: 360px;
  height: 500px;
  background: white;
  border-radius: 18px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Header */
.chat-header {
  background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,0.18);
}
.header-title {
  font-weight: 900;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  letter-spacing: 0.01em;
}
.header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}
.header-btn {
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.2);
  color: white;
  padding: 8px;
  border-radius: 10px;
  cursor: pointer;
}
.header-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.mi {
  width: 18px;
  height: 18px;
}
.bot-icon { font-size: 20px; }
.close-btn {
  background: transparent;
  border: none;
  color: rgba(255,255,255,0.9);
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
  transition: color 0.2s;
}
.close-btn:hover { color: white; }

/* Messages */
.chat-messages {
  flex: 1;
  padding: 16px;
  overflow-y: auto;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.welcome-msg {
  text-align: center;
  color: #6b7280;
  font-size: 13px;
  margin-top: 40px;
  font-weight: 500;
}
.quick-prompts {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-top: 12px;
  flex-wrap: wrap;
}
.chip {
  background: white;
  border: 1px solid #e5e7eb;
  color: #111827;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  cursor: pointer;
}

.message-row {
  display: flex;
}
.user-row { justify-content: flex-end; }
.bot-row { justify-content: flex-start; }

.message-bubble {
  max-width: 85%;
  padding: 10px 14px;
  border-radius: 14px;
  font-size: 14px;
  line-height: 1.5;
  white-space: pre-wrap;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.message-bubble.user {
  background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border-bottom-right-radius: 2px;
  border: 1px solid rgba(255,255,255,0.2);
}
.message-bubble.assistant {
  background: white;
  color: #111827;
  border: 1px solid #e5e7eb;
  border-bottom-left-radius: 2px;
}
.message-meta {
  display: flex;
  justify-content: flex-end;
}
.speak-btn {
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
}
.speak-icon { width: 16px; height: 16px; }
.message-text { white-space: pre-wrap; }
.msg-attachments {
  display: flex;
  gap: 6px;
  margin-top: 8px;
  flex-wrap: wrap;
}
.att-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #e5e7eb;
  padding: 6px 8px;
  border-radius: 10px;
  background: #fff;
  max-width: 180px;
}
.att-ic { width: 14px; height: 14px; color: #6b7280; }
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Loading dots */
.loading-bubble {
  padding: 12px 16px;
  display: flex;
  gap: 4px;
  align-items: center;
  justify-content: center;
  min-width: 48px;
}
.typing-dot {
  width: 5px;
  height: 5px;
  background-color: #6b7280;
  border-radius: 50%;
  animation: typing 1.4s infinite ease-in-out both;
}
.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
  0%, 80%, 100% { transform: scale(0); }
  40% { transform: scale(1); }
}

/* Input Area */
.chat-input-area {
  padding: 16px;
  border-top: 1px solid #e5e7eb;
  background: white;
  display: flex;
  gap: 8px;
  align-items: center;
}
.chat-input {
  flex: 1;
  border: 1px solid #d1d5db;
  border-radius: 999px;
  padding: 10px 16px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.chat-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
}
.icon-btn {
  background: white;
  border: 1px solid #e5e7eb;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.icon-24 { width: 20px; height: 20px; }
.send-btn {
  background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 100%);
  color: white;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s, background 0.2s;
  box-shadow: 0 4px 12px rgba(99,102,241,0.25);
}
.send-btn:hover:not(:disabled) {
  background: linear-gradient(180deg, #4338ca 0%, #6d28d9 100%);
  transform: scale(1.05);
}
.send-btn:disabled {
  background: #e5e7eb;
  color: #9ca3af;
  cursor: not-allowed;
  box-shadow: none;
}
.send-icon {
  width: 16px;
  height: 16px;
  margin-left: 2px;
}
.attach-preview {
  padding: 8px 16px 0 16px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.notice {
  background: #fff7ed;
  color: #9a3412;
  border: 1px solid #ffedd5;
  font-size: 12px;
  padding: 8px 12px;
  border-radius: 10px;
  margin: 8px 16px;
}
.file-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #eef2ff;
  border: 1px solid #e0e7ff;
  color: #3730a3;
  padding: 6px 10px;
  border-radius: 999px;
}
.pill-ic { width: 16px; height: 16px; }
.pill-x {
  background: transparent;
  border: none;
  color: #4338ca;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
}
</style>
