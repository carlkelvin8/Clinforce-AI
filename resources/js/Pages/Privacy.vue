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
                <i class="pi pi-shield text-xl"></i>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-slate-900">Privacy Policy</h1>
                <p class="text-sm text-slate-500">Last updated: {{ lastUpdated }}</p>
              </div>
            </div>
            <p class="text-slate-600 leading-relaxed">
              At AI Clinforce Partners, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, and safeguard your data.
            </p>
          </div>
        </div>

        <!-- Content Sections -->
        <div class="space-y-6">
          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-info-circle text-blue-600"></i>
                <span>Information We Collect</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4 text-slate-700">
                <div>
                  <h3 class="font-semibold text-slate-900 mb-2">Personal Information</h3>
                  <ul class="list-disc list-inside space-y-1 text-sm">
                    <li>Full name and contact information (email, phone number)</li>
                    <li>Professional credentials and certifications</li>
                    <li>Employment history and work experience</li>
                    <li>Educational background</li>
                    <li>Resume and supporting documents</li>
                  </ul>
                </div>
                <div>
                  <h3 class="font-semibold text-slate-900 mb-2">Usage Information</h3>
                  <ul class="list-disc list-inside space-y-1 text-sm">
                    <li>IP address and device information</li>
                    <li>Browser type and operating system</li>
                    <li>Pages visited and features used</li>
                    <li>Time and date of access</li>
                  </ul>
                </div>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-cog text-blue-600"></i>
                <span>How We Use Your Information</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 space-y-2 text-sm">
                <p>We use your information to:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                  <li>Provide and improve our staffing platform services</li>
                  <li>Match healthcare professionals with suitable job opportunities</li>
                  <li>Facilitate communication between employers and candidates</li>
                  <li>Process applications and manage hiring workflows</li>
                  <li>Verify credentials and conduct background checks</li>
                  <li>Send important notifications and updates</li>
                  <li>Analyze platform usage to enhance user experience</li>
                  <li>Comply with legal obligations and prevent fraud</li>
                </ul>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-share-alt text-blue-600"></i>
                <span>Information Sharing</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 space-y-3 text-sm">
                <p>We may share your information with:</p>
                <div class="space-y-2">
                  <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                    <p class="font-semibold text-slate-900 mb-1">Employers & Healthcare Facilities</p>
                    <p class="text-slate-600">When you apply for jobs or grant document access, relevant information is shared with potential employers.</p>
                  </div>
                  <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                    <p class="font-semibold text-slate-900 mb-1">Service Providers</p>
                    <p class="text-slate-600">Third-party services that help us operate the platform (payment processing, email delivery, cloud storage).</p>
                  </div>
                  <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                    <p class="font-semibold text-slate-900 mb-1">Legal Requirements</p>
                    <p class="text-slate-600">When required by law, court order, or to protect our rights and safety.</p>
                  </div>
                </div>
                <p class="text-slate-600 italic mt-3">We never sell your personal information to third parties.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-lock text-blue-600"></i>
                <span>Data Security</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 space-y-2 text-sm">
                <p>We implement industry-standard security measures to protect your data:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                  <li>Encryption of data in transit and at rest</li>
                  <li>Secure authentication and access controls</li>
                  <li>Regular security audits and monitoring</li>
                  <li>Compliance with healthcare data protection standards</li>
                  <li>Employee training on data privacy and security</li>
                </ul>
                <p class="text-slate-600 mt-3">While we strive to protect your information, no method of transmission over the internet is 100% secure.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-user text-blue-600"></i>
                <span>Your Rights</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 space-y-2 text-sm">
                <p>You have the right to:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                  <li>Access and review your personal information</li>
                  <li>Update or correct inaccurate data</li>
                  <li>Request deletion of your account and data</li>
                  <li>Opt-out of marketing communications</li>
                  <li>Export your data in a portable format</li>
                  <li>Withdraw consent for data processing</li>
                </ul>
                <p class="text-slate-600 mt-3">To exercise these rights, contact us at <a href="mailto:privacy@aiclinforce.com" class="text-blue-600 hover:underline">privacy@aiclinforce.com</a></p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-clock text-blue-600"></i>
                <span>Data Retention</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm">
                <p>We retain your personal information for as long as necessary to:</p>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-2">
                  <li>Provide our services and maintain your account</li>
                  <li>Comply with legal and regulatory requirements</li>
                  <li>Resolve disputes and enforce our agreements</li>
                </ul>
                <p class="text-slate-600 mt-3">After account deletion, we may retain certain information for legal compliance and fraud prevention purposes.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-globe text-blue-600"></i>
                <span>Cookies & Tracking</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm">
                <p>We use cookies and similar technologies to:</p>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-2">
                  <li>Maintain your login session</li>
                  <li>Remember your preferences</li>
                  <li>Analyze platform usage and performance</li>
                  <li>Provide personalized content</li>
                </ul>
                <p class="text-slate-600 mt-3">You can control cookies through your browser settings, but some features may not function properly if disabled.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-users text-blue-600"></i>
                <span>Children's Privacy</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm">
                <p>Our platform is not intended for individuals under 18 years of age. We do not knowingly collect personal information from children. If you believe we have inadvertently collected such information, please contact us immediately.</p>
              </div>
            </template>
          </Card>

          <Card class="!border-0 !shadow-sm">
            <template #title>
              <div class="flex items-center gap-2 text-lg">
                <i class="pi pi-refresh text-blue-600"></i>
                <span>Changes to This Policy</span>
              </div>
            </template>
            <template #content>
              <div class="text-slate-700 text-sm">
                <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by:</p>
                <ul class="list-disc list-inside space-y-1 ml-2 mt-2">
                  <li>Posting the updated policy on our platform</li>
                  <li>Sending an email notification to registered users</li>
                  <li>Displaying a prominent notice on the platform</li>
                </ul>
                <p class="text-slate-600 mt-3">Your continued use of the platform after changes constitutes acceptance of the updated policy.</p>
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
                <p>If you have questions or concerns about this Privacy Policy or our data practices, please contact us:</p>
                <div class="grid md:grid-cols-2 gap-4">
                  <div class="bg-white rounded-lg p-4 border border-slate-200">
                    <p class="font-semibold text-slate-900 mb-2">Email</p>
                    <a href="mailto:privacy@aiclinforce.com" class="text-blue-600 hover:underline">privacy@aiclinforce.com</a>
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
