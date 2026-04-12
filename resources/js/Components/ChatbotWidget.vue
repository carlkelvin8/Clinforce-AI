<template>
  <div class="chatbot-root">
    <!-- Floating Toggle Button with Pulse Animation -->
    <button v-if="!isOpen" @click="toggle" class="chat-toggle" :class="{ 'has-notification': hasNewMessage }">
      <div class="toggle-btn-content">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="chat-icon">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
        <span class="chat-label">AI Copilot</span>
      </div>
      <span v-if="hasNewMessage" class="notification-badge"></span>
    </button>

    <!-- Chat Window -->
    <div v-if="isOpen" class="chat-window" :class="{ 'expanded': isExpanded }">
      <!-- Enhanced Header -->
      <div class="chat-header">
        <div class="header-left">
          <div class="bot-avatar">
            <span class="avatar-icon">🧠</span>
            <span v-if="aiStatus === 'online'" class="status-dot online"></span>
            <span v-else-if="aiStatus === 'busy'" class="status-dot busy"></span>
            <span v-else class="status-dot offline"></span>
          </div>
          <div class="header-info">
            <div class="header-title">Clinforce AI Copilot</div>
            <div class="header-subtitle">{{ statusText }}</div>
          </div>
        </div>
        <div class="header-actions">
          <button class="header-btn" @click="toggleExpand" :title="isExpanded ? 'Collapse' : 'Expand'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
              <path v-if="!isExpanded" stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 4.5L4.5 9m4.5-4.5L9 9M15 15v4.5m0 0L19.5 15m-4.5 4.5L15 15M9 15v4.5m0 0L4.5 15m4.5 0L9 15M15 9V4.5m0 4.5l4.5-4.5m-4.5 0L15 9" />
            </svg>
          </button>
          <button class="header-btn" @click="clearChat" title="Clear conversation">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
          <button @click="toggle" class="close-btn" aria-label="Close chat">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px;height:20px;">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mode Selector -->
      <div class="mode-selector">
        <button 
          v-for="mode in modes" 
          :key="mode.value"
          @click="currentMode = mode.value"
          class="mode-btn"
          :class="{ active: currentMode === mode.value }"
          :title="mode.description"
        >
          <span class="mode-icon">{{ mode.icon }}</span>
          <span class="mode-label">{{ mode.label }}</span>
        </button>
      </div>

      <!-- Messages Area -->
      <div class="chat-messages" ref="messagesContainer">
        <!-- Welcome Screen -->
        <div v-if="messages.length === 0" class="welcome-screen">
          <div class="welcome-header">
            <div class="welcome-icon">✨</div>
            <h3>Welcome to Clinforce AI Copilot</h3>
            <p>Your enterprise-grade recruitment intelligence assistant</p>
          </div>
          
          <div class="capabilities">
            <div class="capability-item">
              <span class="cap-icon">🔍</span>
              <div>
                <div class="cap-title">Smart Candidate Search</div>
                <div class="cap-desc">Find perfect matches from your database</div>
              </div>
            </div>
            <div class="capability-item">
              <span class="cap-icon">📄</span>
              <div>
                <div class="cap-title">Document Analysis</div>
                <div class="cap-desc">Analyze resumes, CVs, and job descriptions</div>
              </div>
            </div>
            <div class="capability-item">
              <span class="cap-icon">🎯</span>
              <div>
                <div class="cap-title">Intelligent Matching</div>
                <div class="cap-desc">AI-powered candidate-job fit analysis</div>
              </div>
            </div>
            <div class="capability-item">
              <span class="cap-icon">💡</span>
              <div>
                <div class="cap-title">Strategic Insights</div>
                <div class="cap-desc">Data-driven recruitment recommendations</div>
              </div>
            </div>
          </div>

          <div class="quick-actions">
            <div class="quick-actions-title">Quick Actions</div>
            <div class="quick-grid">
              <button class="quick-action" @click="usePrompt('Search for registered nurses in New York with ICU experience')">
                <span class="qa-icon">👥</span>
                <span class="qa-text">Find ICU Nurses</span>
              </button>
              <button class="quick-action" @click="usePrompt('Analyze this resume and provide detailed assessment')">
                <span class="qa-icon">📊</span>
                <span class="qa-text">Analyze Resume</span>
              </button>
              <button class="quick-action" @click="usePrompt('Generate interview questions for a Clinical Research Associate position')">
                <span class="qa-icon">🎤</span>
                <span class="qa-text">Interview Prep</span>
              </button>
              <button class="quick-action" @click="usePrompt('Show me recruitment analytics for this month')">
                <span class="qa-icon">📈</span>
                <span class="qa-text">Analytics</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Message List -->
        <div v-for="(msg, idx) in messages" :key="idx" class="message-wrapper">
          <div class="message-row" :class="msg.role === 'user' ? 'user-row' : 'bot-row'">
            <!-- Bot Avatar -->
            <div v-if="msg.role === 'assistant'" class="msg-avatar">
              <span class="avatar-sm">🧠</span>
            </div>

            <!-- Message Content -->
            <div class="message-content">
              <div class="message-bubble" :class="msg.role">
                <!-- Markdown-like rendering -->
                <div class="message-text" v-html="renderMessage(msg.content)"></div>
                
                <!-- Attachments -->
                <div v-if="msg.attachments && msg.attachments.length" class="msg-attachments">
                  <div v-for="(f, i) in msg.attachments" :key="i" class="att-chip">
                    <svg class="att-ic" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M16.5 6.5v8a4.5 4.5 0 11-9 0v-9a3 3 0 116 0v8a1.5 1.5 0 11-3 0V7a.75.75 0 111.5 0v7a0 0 0 000 0 0 0 0 000 0"/>
                    </svg>
                    <span class="truncate">{{ f.name }}</span>
                  </div>
                </div>
              </div>

              <!-- Message Actions -->
              <div class="message-actions" v-if="msg.role === 'assistant'">
                <button class="action-btn" @click="copyMessage(msg.content)" title="Copy to clipboard">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  <span>{{ copiedIndex === idx ? 'Copied!' : 'Copy' }}</span>
                </button>
                <button v-if="ttsSupported" class="action-btn" @click="speak(msg.content)" title="Read aloud">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                  </svg>
                  <span>Listen</span>
                </button>
                <button class="action-btn" @click="regenerateMessage(idx)" :disabled="loading" title="Regenerate response">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  <span>Retry</span>
                </button>
              </div>
            </div>

            <!-- User Avatar -->
            <div v-if="msg.role === 'user'" class="msg-avatar">
              <span class="avatar-sm user-avatar-icon">👤</span>
            </div>
          </div>
        </div>

        <!-- Loading Indicator -->
        <div v-if="loading" class="message-wrapper">
          <div class="message-row bot-row">
            <div class="msg-avatar">
              <span class="avatar-sm">🧠</span>
            </div>
            <div class="message-content">
              <div class="message-bubble assistant loading-bubble">
                <div class="typing-indicator">
                  <div class="typing-dot"></div>
                  <div class="typing-dot"></div>
                  <div class="typing-dot"></div>
                </div>
                <div v-if="processingText" class="processing-text">{{ processingText }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="message-wrapper">
          <div class="message-row bot-row">
            <div class="msg-avatar">
              <span class="avatar-sm">⚠️</span>
            </div>
            <div class="message-content">
              <div class="message-bubble assistant error-bubble">
                <div class="error-title">Error</div>
                <div class="message-text">{{ errorMessage }}</div>
                <button class="retry-btn" @click="retryLastMessage">Try Again</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload Preview -->
      <div v-if="uploadNotice" class="notice" aria-live="polite">{{ uploadNotice }}</div>
      <div v-if="attachments.length" class="attach-preview">
        <div v-for="(f, i) in attachments" :key="i" class="attach-item">
          <div class="file-pill">
            <span class="file-icon">{{ getFileIcon(f.name) }}</span>
            <span class="truncate">{{ f.name }}</span>
            <button class="pill-x" @click="removeAttachment(i)" aria-label="Remove">×</button>
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div class="chat-input-area">
        <input ref="fileInput" type="file" class="hidden" multiple @change="handleFiles" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png" />
        
        <button class="icon-btn" @click="triggerChoose" :disabled="loading" title="Attach files">
          <svg class="icon-20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 7a5 5 0 019 0v7a3.5 3.5 0 11-7 0V8a2 2 0 114 0v6a.5.5 0 01-1 0V8a1 1 0 10-2 0v6a2.5 2.5 0 105 0V7a6.5 6.5 0 10-13 0v7a5 5 0 1010 0V9h-2v5a3 3 0 11-6 0V7z"/>
          </svg>
        </button>
        
        <textarea
          v-model="input"
          @keydown.enter.exact.prevent="send"
          @input="autoResize"
          placeholder="Ask me anything..."
          class="chat-input"
          :disabled="loading"
          rows="1"
          ref="inputRef"
        ></textarea>
        
        <button class="icon-btn" :disabled="loading || !micSupported" @click="toggleVoice" :class="{ 'mic-active': micActive }" title="Voice input">
          <svg v-if="!micActive" class="icon-20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 14a3 3 0 003-3V6a3 3 0 10-6 0v5a3 3 0 003 3z"/>
            <path d="M19 11a1 1 0 10-2 0 5 5 0 01-10 0 1 1 0 10-2 0 7 7 0 006 6.92V21H8a1 1 0 100 2h8a1 1 0 100-2h-3v-3.08A7 7 0 0019 11z"/>
          </svg>
          <svg v-else class="icon-20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 3a3 3 0 00-3 3v5a3 3 0 006 0V6a3 3 0 00-3-3z"/>
            <path d="M4.91 3.5l15.59 15.59-1.41 1.41L15 16.41V18a7 7 0 01-6-6 1 1 0 10-2 0 9 9 0 007 8.94V21H8a1 1 0 100 2h8a1 1 0 100-2h-3v-2.06l-1.94-1.94A9 9 0 004 12a1 1 0 102 0 7 7 0 011-3.61L3.5 4.91 4.91 3.5z"/>
          </svg>
        </button>
        
        <button @click="send" :disabled="(!input.trim() && attachments.length === 0) || loading" class="send-btn" aria-label="Send message">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="send-icon">
            <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount, watch } from 'vue'
import api from '@/lib/api'

const isOpen = ref(false)
const input = ref('')
const loading = ref(false)
const messages = ref([])
const messagesContainer = ref(null)
const attachments = ref([])
const fileInput = ref(null)
const inputRef = ref(null)
const micSupported = ref(false)
const micActive = ref(false)
const ttsSupported = ref(false)
const isExpanded = ref(false)
const hasNewMessage = ref(false)
const errorMessage = ref('')
const processingText = ref('')
const copiedIndex = ref(-1)
const currentMode = ref('chat')
let recognition = null
const uploadNotice = ref('')
const currentUserId = ref(null)
const aiStatus = ref('offline')
const lastFailedMessage = ref(null)

const modes = [
  { value: 'chat', label: 'Chat', icon: '💬', description: 'General conversation' },
  { value: 'analysis', label: 'Analyze', icon: '📊', description: 'Deep document analysis' },
  { value: 'interview', label: 'Interview', icon: '🎤', description: 'Generate interview questions' },
  { value: 'matching', label: 'Match', icon: '🎯', description: 'Candidate-job matching' },
]

function getToken() {
  return localStorage.getItem("auth_token") || localStorage.getItem("CLINFORCE_TOKEN")
}

function getUser() {
  try {
    return JSON.parse(localStorage.getItem("auth_user"))
  } catch {
    return null
  }
}

function loadHistory() {
  const user = getUser()
  const newUserId = user?.id || 'guest'

  if (currentUserId.value && currentUserId.value !== newUserId) {
    messages.value = []
  }

  currentUserId.value = newUserId
  const key = `clinforce_chat_history_v2_${newUserId}`

  const saved = localStorage.getItem(key)
  if (saved) {
    try {
      messages.value = JSON.parse(saved)
    } catch (e) {
      console.error('Failed to load chat history', e)
      messages.value = []
    }
  } else {
    messages.value = []
  }
}

function saveHistory() {
  const key = `clinforce_chat_history_v2_${currentUserId.value}`
  localStorage.setItem(key, JSON.stringify(messages.value))
}

function clearChat() {
  if (confirm('Clear this conversation?')) {
    messages.value = []
    localStorage.removeItem(`clinforce_chat_history_v2_${currentUserId.value}`)
  }
}

onMounted(() => {
  loadHistory()
  window.addEventListener('auth:changed', loadHistory)

  const SR = window.SpeechRecognition || window.webkitSpeechRecognition
  const secure = window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1'
  micSupported.value = secure && Boolean(SR)
  ttsSupported.value = 'speechSynthesis' in window
  
  window.addEventListener('chatbot:toggle', toggle)
  window.addEventListener('chatbot:open', () => { isOpen.value = true })
  
  // Check AI service health
  checkAiHealth()
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

async function checkAiHealth() {
  try {
    const response = await api.get('/chatbot/health')
    aiStatus.value = response.data.available ? 'online' : 'offline'
  } catch {
    aiStatus.value = 'offline'
  }
}

const statusText = computed(() => {
  const statusMap = {
    online: 'Ready to assist',
    busy: 'Processing request...',
    offline: 'Service unavailable'
  }
  return statusMap[aiStatus.value] || 'Initializing...'
})

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    hasNewMessage.value = false
    scrollToBottom()
  }
}

function toggleExpand() {
  isExpanded.value = !isExpanded.value
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
    if (inputRef.value) {
      inputRef.value.focus()
    }
  })
}

function triggerChoose() {
  if (fileInput.value) fileInput.value.click()
}

function handleFiles(e) {
  const files = Array.from(e.target.files || [])
  const maxSize = 10 * 1024 * 1024 // 10MB
  
  files.forEach(f => {
    if (f.size > maxSize) {
      uploadNotice.value = `File "${f.name}" exceeds 10MB limit`
      return
    }
    attachments.value.push(f)
  })
  
  if (fileInput.value) fileInput.value.value = ''
}

function removeAttachment(i) {
  attachments.value.splice(i, 1)
}

function getFileIcon(filename) {
  const ext = filename.split('.').pop().toLowerCase()
  const icons = {
    pdf: '📄',
    doc: '📝',
    docx: '📝',
    txt: '📃',
    jpg: '🖼️',
    jpeg: '🖼️',
    png: '🖼️',
  }
  return icons[ext] || '📎'
}

function speak(text) {
  if (!ttsSupported.value) return
  try {
    const cleanText = text.replace(/[#*_~`]/g, '')
    const u = new SpeechSynthesisUtterance(cleanText)
    u.rate = 0.9
    u.pitch = 1
    window.speechSynthesis.cancel()
    window.speechSynthesis.speak(u)
  } catch (e) {
    console.error('TTS error:', e)
  }
}

function toggleVoice() {
  if (!micSupported.value) return
  
  if (!recognition) {
    const SR = window.SpeechRecognition || window.webkitSpeechRecognition
    recognition = new SR()
    recognition.continuous = false
    recognition.lang = 'en-US'
    recognition.interimResults = false
    
    recognition.onstart = () => { 
      micActive.value = true 
    }
    
    recognition.onresult = (e) => {
      const transcript = Array.from(e.results).map(r => r[0].transcript).join(' ')
      input.value = (input.value ? input.value + ' ' : '') + transcript
    }
    
    recognition.onend = () => { 
      micActive.value = false 
    }
    
    recognition.onerror = (ev) => {
      micActive.value = false
      console.warn('Speech recognition error:', ev.error)
    }
  }
  
  if (micActive.value) {
    recognition.stop()
    micActive.value = false
  } else {
    try {
      recognition.start()
    } catch (e) {
      console.error('Failed to start microphone:', e)
    }
  }
}

function autoResize() {
  nextTick(() => {
    if (inputRef.value) {
      inputRef.value.style.height = 'auto'
      inputRef.value.style.height = Math.min(inputRef.value.scrollHeight, 120) + 'px'
    }
  })
}

function renderMessage(content) {
  if (!content) return ''
  
  return content
    // Bold
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    // Italic
    .replace(/\*(.+?)\*/g, '<em>$1</em>')
    // Headers
    .replace(/^## (.+)$/gm, '<h4>$1</h4>')
    .replace(/^# (.+)$/gm, '<h3>$1</h3>')
    // Bullet points
    .replace(/^- (.+)$/gm, '• $1')
    // Numbered lists
    .replace(/^\d+\. (.+)$/gm, '$1')
    // Line breaks
    .replace(/\n/g, '<br>')
}

async function copyMessage(content) {
  try {
    await navigator.clipboard.writeText(content)
    const idx = messages.value.findIndex(m => m.content === content)
    copiedIndex.value = idx
    setTimeout(() => {
      copiedIndex.value = -1
    }, 2000)
  } catch (e) {
    console.error('Failed to copy:', e)
  }
}

async function regenerateMessage(index) {
  const botMsg = messages.value[index]
  if (!botMsg || botMsg.role !== 'assistant') return
  
  // Find the user message before this
  const userMsgIndex = index - 1
  if (userMsgIndex < 0 || messages.value[userMsgIndex].role !== 'user') return
  
  const userMsg = messages.value[userMsgIndex]
  
  // Remove the old bot message
  messages.value.splice(index, 1)
  
  // Resend
  loading.value = true
  errorMessage.value = ''
  
  try {
    const apiMessages = messages.value.slice(0, -1).map(m => ({
      role: m.role,
      content: m.content,
    }))
    
    const res = await api.post('/chatbot', { 
      messages: [...apiMessages, { role: 'user', content: userMsg.content }],
      mode: currentMode.value,
    })
    
    messages.value.push({ 
      role: res.data.role, 
      content: res.data.content 
    })
  } catch (err) {
    errorMessage.value = err.response?.data?.error || 'Failed to regenerate response'
  } finally {
    loading.value = false
  }
}

async function retryLastMessage() {
  if (!lastFailedMessage.value) return
  
  const tempMsg = lastFailedMessage.value
  lastFailedMessage.value = null
  errorMessage.value = ''
  
  input.value = tempMsg
  await send()
}

async function send() {
  const text = input.value.trim()
  if (!text && attachments.value.length === 0) return

  const userMessage = {
    role: 'user',
    content: text || '[Files uploaded]',
    attachments: attachments.value.map(f => ({ name: f.name, size: f.size, type: f.type }))
  }
  
  messages.value.push(userMessage)
  const currentInput = text
  const currentAttachments = [...attachments.value]
  
  input.value = ''
  loading.value = true
  errorMessage.value = ''
  aiStatus.value = 'busy'
  processingText.value = 'Analyzing your request...'
  
  scrollToBottom()

  try {
    const apiMessages = messages.value.map(m => ({
      role: m.role,
      content: m.content,
    }))

    let res
    if (currentAttachments.length) {
      try {
        const fd = new FormData()
        fd.append('messages', JSON.stringify(apiMessages))
        fd.append('mode', currentMode.value)
        currentAttachments.forEach(f => fd.append('files[]', f, f.name))
        res = await api.post('/chatbot', fd)
        uploadNotice.value = ''
      } catch (e) {
        const status = e?.response?.status
        const shouldFallback = status === 400 || status === 404 || status === 415 || status === 422 || !status
        if (shouldFallback) {
          res = await api.post('/chatbot', { messages: apiMessages, mode: currentMode.value })
          uploadNotice.value = 'Files could not be uploaded. Processing text only.'
        } else {
          throw e
        }
      }
    } else {
      res = await api.post('/chatbot', { 
        messages: apiMessages, 
        mode: currentMode.value,
      })
      uploadNotice.value = ''
    }

    const botMsg = res.data
    messages.value.push({ 
      role: botMsg.role, 
      content: botMsg.content,
      metadata: botMsg.metadata 
    })
    
    aiStatus.value = 'online'
    hasNewMessage.value = !isOpen.value

  } catch (err) {
    console.error('Chat error:', err)
    aiStatus.value = 'online'
    
    const errorData = err.response?.data
    errorMessage.value = errorData?.error || 'Sorry, I encountered an error. Please try again later.'
    lastFailedMessage.value = currentInput
    
    // Auto-retry once for server errors
    if (err.response?.status >= 500) {
      setTimeout(() => {
        if (errorMessage.value) {
          retryLastMessage()
        }
      }, 2000)
    }
  } finally {
    loading.value = false
    attachments.value = []
    processingText.value = ''
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
}

/* Toggle Button */
.chat-toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 14px 24px;
  border-radius: 50px;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  font-weight: 600;
  font-size: 15px;
}

.chat-toggle:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
}

.chat-toggle.has-notification {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4); }
  50% { box-shadow: 0 8px 32px rgba(102, 126, 234, 0.7); }
}

.toggle-btn-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.chat-icon {
  width: 22px;
  height: 22px;
}

.notification-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid white;
}

/* Chat Window */
.chat-window {
  width: 400px;
  height: 600px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-window.expanded {
  width: 600px;
  height: calc(100vh - 100px);
}

@keyframes slideUp {
  from { 
    opacity: 0; 
    transform: translateY(20px) scale(0.95); 
  }
  to { 
    opacity: 1; 
    transform: translateY(0) scale(1); 
  }
}

/* Header */
.chat-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.bot-avatar {
  position: relative;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-icon {
  font-size: 24px;
}

.status-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid white;
}

.status-dot.online {
  background: #10b981;
}

.status-dot.busy {
  background: #f59e0b;
  animation: blink 1s infinite;
}

.status-dot.offline {
  background: #6b7280;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.header-info {
  flex: 1;
}

.header-title {
  font-weight: 700;
  font-size: 16px;
  letter-spacing: 0.01em;
}

.header-subtitle {
  font-size: 12px;
  opacity: 0.9;
  margin-top: 2px;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.header-btn {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}

.header-btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

.close-btn {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

/* Mode Selector */
.mode-selector {
  display: flex;
  gap: 4px;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  overflow-x: auto;
}

.mode-btn {
  flex: 1;
  min-width: 70px;
  padding: 8px 12px;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.mode-btn:hover {
  border-color: #667eea;
  background: #f0f1ff;
}

.mode-btn.active {
  border-color: #667eea;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.mode-icon {
  font-size: 18px;
}

.mode-label {
  font-size: 11px;
  font-weight: 600;
}

/* Messages */
.chat-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background: #fafafa;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Welcome Screen */
.welcome-screen {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.welcome-header {
  text-align: center;
  padding: 20px 0;
}

.welcome-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.welcome-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
  margin: 0 0 8px 0;
}

.welcome-header p {
  font-size: 13px;
  color: #6b7280;
  margin: 0;
}

.capabilities {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.capability-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.cap-icon {
  font-size: 24px;
}

.cap-title {
  font-size: 14px;
  font-weight: 600;
  color: #111827;
}

.cap-desc {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.quick-actions {
  margin-top: 8px;
}

.quick-actions-title {
  font-size: 14px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 12px;
  text-align: center;
}

.quick-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.quick-action {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-action:hover {
  border-color: #667eea;
  background: #f0f1ff;
  transform: translateY(-2px);
}

.qa-icon {
  font-size: 24px;
}

.qa-text {
  font-size: 12px;
  font-weight: 600;
  color: #111827;
}

/* Messages */
.message-wrapper {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.message-row {
  display: flex;
  gap: 8px;
  align-items: flex-start;
}

.user-row {
  flex-direction: row-reverse;
}

.msg-avatar {
  flex-shrink: 0;
}

.avatar-sm {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

.user-avatar-icon {
  background: #e5e7eb;
}

.message-content {
  flex: 1;
  max-width: 85%;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.message-bubble {
  padding: 12px 16px;
  border-radius: 16px;
  font-size: 14px;
  line-height: 1.6;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.message-bubble.user {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom-right-radius: 4px;
}

.message-bubble.assistant {
  background: white;
  color: #111827;
  border: 1px solid #e5e7eb;
  border-bottom-left-radius: 4px;
}

.message-text {
  white-space: pre-wrap;
  word-wrap: break-word;
}

.message-text h3, .message-text h4 {
  margin: 12px 0 8px 0;
  font-weight: 700;
}

.message-text h3 {
  font-size: 15px;
}

.message-text h4 {
  font-size: 14px;
}

.message-actions {
  display: flex;
  gap: 8px;
  padding: 0 4px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  background: transparent;
  border: none;
  color: #6b7280;
  font-size: 11px;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f3f4f6;
  color: #111827;
}

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
  padding: 6px 10px;
  border-radius: 10px;
  background: #f9fafb;
  font-size: 12px;
}

.att-ic {
  width: 14px;
  height: 14px;
  color: #6b7280;
}

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 150px;
}

/* Loading */
.loading-bubble {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  min-width: 60px;
}

.typing-indicator {
  display: flex;
  gap: 4px;
  align-items: center;
}

.typing-dot {
  width: 6px;
  height: 6px;
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

.processing-text {
  font-size: 12px;
  color: #6b7280;
  font-style: italic;
}

/* Error */
.error-bubble {
  border-color: #fca5a5;
  background: #fef2f2;
}

.error-title {
  font-weight: 700;
  color: #dc2626;
  margin-bottom: 8px;
  font-size: 14px;
}

.retry-btn {
  margin-top: 12px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.retry-btn:hover {
  transform: scale(1.05);
}

/* Input Area */
.chat-input-area {
  padding: 16px;
  border-top: 1px solid #e5e7eb;
  background: white;
  display: flex;
  gap: 8px;
  align-items: flex-end;
}

.chat-input {
  flex: 1;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 14px;
  outline: none;
  resize: none;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
  max-height: 120px;
}

.chat-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.chat-input:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}

.icon-btn {
  background: white;
  border: 1px solid #e5e7eb;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.icon-btn:hover:not(:disabled) {
  border-color: #667eea;
  background: #f0f1ff;
}

.icon-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.icon-btn.mic-active {
  border-color: #10b981;
  background: #d1fae5;
  animation: pulse-ring 1.5s infinite;
}

@keyframes pulse-ring {
  0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
  70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
  100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.icon-20 {
  width: 20px;
  height: 20px;
}

.send-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  flex-shrink: 0;
}

.send-btn:hover:not(:disabled) {
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.send-btn:disabled {
  background: #e5e7eb;
  color: #9ca3af;
  cursor: not-allowed;
  box-shadow: none;
}

.send-icon {
  width: 18px;
  height: 18px;
  margin-left: 2px;
}

/* Upload Preview */
.attach-preview {
  padding: 8px 16px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}

.notice {
  background: #fff7ed;
  color: #9a3412;
  border: 1px solid #ffedd5;
  font-size: 12px;
  padding: 8px 16px;
}

.file-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #eef2ff;
  border: 1px solid #e0e7ff;
  color: #3730a3;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
}

.file-icon {
  font-size: 14px;
}

.pill-x {
  background: transparent;
  border: none;
  color: #4338ca;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
  padding: 0 2px;
}

.hidden {
  display: none;
}

/* Scrollbar */
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

/* Responsive */
@media (max-width: 640px) {
  .chat-window {
    width: calc(100vw - 32px);
    height: calc(100vh - 100px);
    border-radius: 16px;
  }
  
  .chat-window.expanded {
    width: calc(100vw - 32px);
    height: calc(100vh - 100px);
  }
}
</style>
