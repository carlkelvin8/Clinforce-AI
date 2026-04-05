import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          // Vendor: vue core
          if (id.includes('node_modules/vue/') || id.includes('node_modules/@vue/')) {
            return 'vendor-vue'
          }
          // Vendor: vue-router
          if (id.includes('node_modules/vue-router')) {
            return 'vendor-router'
          }
          // Vendor: primevue components
          if (id.includes('node_modules/primevue') || id.includes('node_modules/primeicons') || id.includes('node_modules/@primeuix') || id.includes('node_modules/@primevue')) {
            return 'vendor-primevue'
          }
          // Vendor: chart.js
          if (id.includes('node_modules/chart.js')) {
            return 'vendor-charts'
          }
          // Vendor: sweetalert2
          if (id.includes('node_modules/sweetalert2')) {
            return 'vendor-swal'
          }
          // Vendor: everything else in node_modules
          if (id.includes('node_modules/')) {
            return 'vendor-misc'
          }
          // Admin pages — separate chunk, only loaded when visiting /admin
          if (id.includes('/Pages/Admin/')) {
            return 'pages-admin'
          }
          // Candidate pages
          if (id.includes('/Pages/Candidate/')) {
            return 'pages-candidate'
          }
          // Employer pages
          if (id.includes('/Pages/Employer/') || id.includes('/Applicants/')) {
            return 'pages-employer'
          }
          // Auth pages
          if (id.includes('/Pages/Auth/')) {
            return 'pages-auth'
          }
        },
      },
    },
    // Raise the warning threshold since we're splitting intentionally
    chunkSizeWarningLimit: 800,
  },
})