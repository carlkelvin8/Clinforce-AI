<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="mb-8">
          <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 mb-6">
            <i class="pi pi-arrow-left"></i>
            <span>Back to Register</span>
          </RouterLink>
          
          <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 grid place-content-center text-white shadow-lg">
                <i class="pi pi-file-edit text-xl"></i>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-slate-900">Terms of Service</h1>
                <p class="text-sm text-slate-500">Last updated: {{ lastUpdated }}</p>
              </div>
            </div>
            <p class="text-slate-600 leading-relaxed">
              Welcome to AI Clinforce Partners. By accessing or using our platform, you agree to be bound by these Terms of Service. Please read them carefully.
            </p>
          </div>
        </div>

        <!-- Content Sections -->
        <div class="space-y-6">
          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-check-circle text-blue-600"></i>
                <span>Acceptance of Terms</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-2">
                <p>By creating an account and using AI Clinforce Partners, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service and our Privacy Policy.</p>
                <p>If you do not agree to these terms, you may not access or use our services.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-users text-blue-600"></i>
                <span>User Accounts</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-3">
                <div>
                  <h3 class="font-semibold text-slate-900 mb-2">Account Registration</h3>
                  <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>You must be at least 18 years old to create an account</li>
                    <li>You must provide accurate and complete information</li>
                    <li>You are responsible for maintaining account security</li>
                    <li>You may not share your account credentials</li>
                  </ul>
                </div>
                <div>
                  <h3 class="font-semibold text-slate-900 mb-2">Account Types</h3>
                  <ul class="list-disc list-inside space-y-1 ml-2">
                    <li><span class="font-medium">Healthcare Professionals:</span> Must provide valid credentials and certifications</li>
                    <li><span class="font-medium">Employers:</span> Must represent a legitimate healthcare organization</li>
                  </ul>
                </div>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-briefcase text-blue-600"></i>
                <span>Platform Services</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-2">
                <p>AI Clinforce Partners provides:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                  <li>Job posting and application management for employers</li>
                  <li>Job search and application tools for healthcare professionals</li>
                  <li>Messaging and interview scheduling features</li>
                  <li>Credential verification services</li>
                  <li>AI-powered candidate matching</li>
                </ul>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-credit-card text-blue-600"></i>
                <span>Subscription & Payments</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-3">
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                  <p class="font-semibold text-slate-900 mb-1">Subscription Plans</p>
                  <p class="text-slate-600">Employers require an active subscription to access messaging, interviews, and hiring features.</p>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                  <p class="font-semibold text-slate-900 mb-1">Document Access</p>
                  <p class="text-slate-600">Separate one-time payments are required to access candidate resumes and documents.</p>
                </div>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-3">
                  <li>All fees are non-refundable unless required by law</li>
                  <li>Subscriptions auto-renew unless cancelled</li>
                  <li>You may cancel your subscription at any time</li>
                  <li>Prices are subject to change with notice</li>
                </ul>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-ban text-blue-600"></i>
                <span>Prohibited Conduct</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm">
                <p class="mb-2">You agree not to:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                  <li>Provide false or misleading information</li>
                  <li>Impersonate another person or entity</li>
                  <li>Post discriminatory or offensive content</li>
                  <li>Harass, abuse, or threaten other users</li>
                  <li>Scrape or collect data without permission</li>
                  <li>Attempt to breach platform security</li>
                  <li>Use the platform for illegal purposes</li>
                  <li>Spam or send unsolicited communications</li>
                </ul>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-file text-blue-600"></i>
                <span>Content & Intellectual Property</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-3">
                <div>
                  <h3 class="font-semibold text-slate-900 mb-2">Your Content</h3>
                  <p>You retain ownership of content you submit (resumes, job postings, messages). By submitting content, you grant us a license to use, display, and process it to provide our services.</p>
                </div>
                <div>
                  <h3 class="font-semibold text-slate-900 mb-2">Our Platform</h3>
                  <p>All platform features, design, code, and branding are owned by AI Clinforce Partners and protected by intellectual property laws.</p>
                </div>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-exclamation-triangle text-blue-600"></i>
                <span>Disclaimers & Limitations</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-2">
                <p class="font-semibold text-slate-900">Service "As Is"</p>
                <p>Our platform is provided "as is" without warranties of any kind. We do not guarantee:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                  <li>Uninterrupted or error-free service</li>
                  <li>Accuracy of user-submitted information</li>
                  <li>Successful job placements or hires</li>
                  <li>Compatibility with all devices or browsers</li>
                </ul>
                <p class="mt-3 font-semibold text-slate-900">Limitation of Liability</p>
                <p>We are not liable for indirect, incidental, or consequential damages arising from your use of the platform.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-times-circle text-blue-600"></i>
                <span>Termination</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-2">
                <p>We reserve the right to suspend or terminate your account if you:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                  <li>Violate these Terms of Service</li>
                  <li>Engage in fraudulent activity</li>
                  <li>Fail to pay required fees</li>
                  <li>Pose a security or legal risk</li>
                </ul>
                <p class="mt-3">You may delete your account at any time through your settings.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-book text-blue-600"></i>
                <span>Governing Law</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm">
                <p>These Terms of Service are governed by the laws of the jurisdiction where AI Clinforce Partners operates. Any disputes will be resolved through binding arbitration or in the courts of that jurisdiction.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-refresh text-blue-600"></i>
                <span>Changes to Terms</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm">
                <p>We may update these Terms of Service from time to time. We will notify you of significant changes by email or through the platform. Your continued use after changes constitutes acceptance of the updated terms.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm bg-gradient-to-br from-blue-50 to-indigo-50">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-envelope text-blue-600"></i>
                <span>Contact Us</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm space-y-3">
                <p>If you have questions about these Terms of Service, please contact us:</p>
                <div class="grid md:grid-cols-2 gap-4">
                  <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <p class="font-semibold text-slate-900 mb-2">Email</p>
                    <a href="mailto:legal@aiclinforce.com" class="text-blue-600 hover:underline">legal@aiclinforce.com</a>
                  </div>
                  <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <p class="font-semibold text-slate-900 mb-2">Support</p>
                    <a href="mailto:support@aiclinforce.com" class="text-blue-600 hover:underline">support@aiclinforce.com</a>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Footer -->
        <div class="mt-12 text-center">
          <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all">
            <i class="pi pi-arrow-left mr-2"></i>
            Back to Registration
          </RouterLink>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import Card from 'primevue/card'

const lastUpdated = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
})
</script>

<style scoped>
/* Custom styles if needed */
</style>
