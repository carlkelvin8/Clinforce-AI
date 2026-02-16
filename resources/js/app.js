import './bootstrap'
import { createApp } from 'vue'
import 'chart.js/auto'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import router from './router'
import App from './App.vue'

import 'primeicons/primeicons.css'

const app = createApp(App)

app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.app-dark',
      cssLayer: false
    }
  }
})
app.use(ConfirmationService)
app.use(ToastService)

app.use(router)
app.mount('#app')
