<template>
  <AppLayout :guestFull="true">
    <div class="relative min-h-screen overflow-hidden font-sans">
      <!-- Background Elements -->
      <div class="absolute inset-0 -z-10 bg-gradient-to-b from-slate-50 via-white to-white"></div>
      <div class="absolute -top-40 -right-40 w-[600px] h-[600px] rounded-full bg-blue-100 blur-[120px] opacity-50"></div>
      <div class="absolute top-20 -left-40 w-[500px] h-[500px] rounded-full bg-indigo-100 blur-[100px] opacity-50"></div>

      <!-- Navigation -->
      <header class="max-w-7xl mx-auto px-6 py-6 relative z-20">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-600 text-white grid place-content-center shadow-lg shadow-blue-200">
              <span class="font-bold text-lg">AC</span>
            </div>
            <div class="text-slate-900 font-bold text-lg tracking-tight">AI Clinforce Partners</div>
          </div>
          <!-- Desktop Nav -->
          <nav class="hidden sm:flex items-center gap-6">
            <a href="#features" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Features</a>
            <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">How it works</a>
            <a href="#testimonials" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">Testimonials</a>
            <RouterLink class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors" :to="{ name: 'auth.login' }">Log in</RouterLink>
            <RouterLink class="inline-flex items-center px-5 py-2.5 rounded-full bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-all shadow-lg shadow-slate-200" :to="{ name: 'auth.register' }">
              Get started
            </RouterLink>
          </nav>
          <!-- Mobile Trigger -->
          <button class="sm:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 text-slate-700 bg-white" @click="mobileOpen = !mobileOpen" aria-label="Open menu">
            <i class="pi pi-bars"></i>
          </button>
        </div>
        <!-- Mobile Menu -->
        <div v-if="mobileOpen" class="sm:hidden absolute right-6 top-16 bg-white border border-slate-200 rounded-xl shadow-lg w-56 p-2">
          <a href="#features" @click="mobileOpen = false" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">Features</a>
          <a href="#how-it-works" @click="mobileOpen = false" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">How it works</a>
          <a href="#testimonials" @click="mobileOpen = false" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">Testimonials</a>
          <RouterLink :to="{ name: 'auth.login' }" @click="mobileOpen = false" class="block px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">Log in</RouterLink>
          <RouterLink :to="{ name: 'auth.register' }" @click="mobileOpen = false" class="block px-3 py-2 rounded-lg text-sm bg-slate-900 text-white text-center mt-1">Get started</RouterLink>
        </div>
      </header>

      <main class="max-w-7xl mx-auto px-6 pt-16 pb-24 md:pt-24 md:pb-32">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
          <!-- Left Content -->
          <div class="max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold uppercase tracking-wide mb-6">
              <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
              New Platform Live
            </div>
            <h1 class="text-5xl md:text-6xl font-bold tracking-tight text-slate-900 leading-[1.1]">
              The modern way to <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">hire clinical talent.</span>
            </h1>
            <p class="mt-6 text-slate-600 text-xl leading-relaxed max-w-lg">
              Streamline your healthcare recruitment. Connect with qualified professionals, automate compliance, and fill shifts faster than ever.
            </p>
            
            <div class="mt-8 flex flex-wrap items-center gap-4">
              <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-blue-600 text-white font-semibold text-lg hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 hover:shadow-2xl hover:-translate-y-0.5">
                Start hiring now
              </RouterLink>
              <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center justify-center px-8 py-4 rounded-2xl border border-slate-200 bg-white text-slate-700 font-semibold text-lg hover:bg-slate-50 hover:border-slate-300 transition-all">
                I'm a clinician
              </RouterLink>
            </div>

            <div class="mt-10 flex items-center gap-6 text-sm font-medium text-slate-500">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Verified Professionals
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    HIPAA Compliant
                </div>
            </div>
          </div>

          <!-- Right Visual -->
          <div class="relative lg:h-[600px] flex items-center justify-center">
            <div class="absolute inset-0 bg-gradient-to-tr from-blue-100/50 to-indigo-100/50 rounded-full blur-3xl"></div>
            
            <!-- Dashboard Mockup Card -->
            <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden transform rotate-[-2deg] hover:rotate-0 transition-transform duration-500">
              <!-- Mockup Header -->
              <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 backdrop-blur">
                <div class="flex items-center gap-3">
                   <div class="w-3 h-3 rounded-full bg-red-400"></div>
                   <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                   <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
                </div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dashboard Preview</div>
              </div>
              
              <!-- Mockup Content -->
              <div class="p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-slate-500">Total Candidates</div>
                        <div class="text-3xl font-bold text-slate-900">1,248</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">Active Jobs</span>
                        <span class="text-blue-600 font-bold">12</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 75%"></div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="text-xs font-semibold text-slate-500 uppercase mb-3">Recent Activity</div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">JS</div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-slate-900">James Smith</div>
                                <div class="text-xs text-slate-500">Applied for Senior Nurse</div>
                            </div>
                            <span class="text-xs text-slate-400">2m</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-bold">AL</div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-slate-900">Ana Lee</div>
                                <div class="text-xs text-slate-500">Interview Scheduled</div>
                            </div>
                            <span class="text-xs text-slate-400">1h</span>
                        </div>
                    </div>
                </div>
              </div>
            </div>
            
            <!-- Floating Card -->
            <div class="absolute -bottom-6 -right-6 bg-white p-4 rounded-2xl shadow-xl border border-slate-100 animate-bounce" style="animation-duration: 3s;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-900">Offer Accepted</div>
                        <div class="text-xs text-slate-500">Just now</div>
                    </div>
                </div>
            </div>
          </div>
        </div>

        <section id="features" class="mt-24 md:mt-32">
          <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-3xl font-bold text-slate-900">Everything you need to hire</h2>
            <p class="mt-4 text-slate-600">Powerful tools designed for the modern healthcare workforce.</p>
          </div>
          
          <div class="grid md:grid-cols-3 gap-8">
            <div class="group p-8 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
              <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
              </div>
              <h3 class="text-xl font-bold text-slate-900 mb-3">Job Posting</h3>
              <p class="text-slate-500 leading-relaxed">Create and distribute detailed job listings to thousands of qualified clinicians instantly.</p>
            </div>
            
            <div class="group p-8 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
              <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
              </div>
              <h3 class="text-xl font-bold text-slate-900 mb-3">Smart Matching</h3>
              <p class="text-slate-500 leading-relaxed">Our AI automatically matches the best candidates to your specific requirements and shifts.</p>
            </div>
            
            <div class="group p-8 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
              <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
              </div>
              <h3 class="text-xl font-bold text-slate-900 mb-3">Easy Scheduling</h3>
              <p class="text-slate-500 leading-relaxed">Coordinate interviews and shifts effortlessly with our integrated calendar system.</p>
            </div>
          </div>
        </section>
        <section id="how-it-works" class="mt-24 md:mt-32">
          <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-3xl font-bold text-slate-900">How it works</h2>
            <p class="mt-4 text-slate-600">Simple steps to fill roles or find your next opportunity.</p>
          </div>
          <div class="grid md:grid-cols-3 gap-6">
            <div class="p-8 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="w-10 h-10 rounded-xl bg-blue-600 text-white grid place-content-center font-bold mb-4">1</div>
              <div class="text-xl font-bold text-slate-900 mb-2">Post or Sign Up</div>
              <div class="text-slate-600">Employers post roles. Clinicians create profiles and set preferences.</div>
            </div>
            <div class="p-8 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white grid place-content-center font-bold mb-4">2</div>
              <div class="text-xl font-bold text-slate-900 mb-2">Smart Match</div>
              <div class="text-slate-600">Our AI highlights top matches based on skills, location, and shift.</div>
            </div>
            <div class="p-8 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white grid place-content-center font-bold mb-4">3</div>
              <div class="text-xl font-bold text-slate-900 mb-2">Interview & Hire</div>
              <div class="text-slate-600">Schedule interviews, manage offers, and confirm shifts seamlessly.</div>
            </div>
          </div>
        </section>
        <section id="audiences" class="mt-24 md:mt-32">
          <div class="grid md:grid-cols-2 gap-6">
            <div class="p-8 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="text-sm font-semibold text-blue-600 mb-2">For Employers</div>
              <div class="text-2xl font-bold text-slate-900 mb-4">Hire faster with better visibility</div>
              <ul class="space-y-2 text-slate-600">
                <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">•</span><span>AI shortlists to reduce time-to-hire</span></li>
                <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">•</span><span>Centralized job and application tracking</span></li>
                <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">•</span><span>Integrated interview scheduling</span></li>
              </ul>
              <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center mt-6 px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition-colors">Get started</RouterLink>
            </div>
            <div class="p-8 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="text-sm font-semibold text-indigo-600 mb-2">For Clinicians</div>
              <div class="text-2xl font-bold text-slate-900 mb-4">Find roles that fit your life</div>
              <ul class="space-y-2 text-slate-600">
                <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">•</span><span>Personalized recommendations</span></li>
                <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">•</span><span>Easy applications and updates</span></li>
                <li class="flex items-start gap-2"><span class="text-emerald-600 mt-1">•</span><span>Message employers directly</span></li>
              </ul>
              <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center mt-6 px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50">Browse jobs</RouterLink>
            </div>
          </div>
        </section>
        <section id="testimonials" class="mt-24 md:mt-32">
          <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-3xl font-bold text-slate-900">Trusted by teams and clinicians</h2>
            <p class="mt-4 text-slate-600">Real stories from our community.</p>
          </div>
          <div class="grid md:grid-cols-3 gap-6">
            <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="text-slate-700 leading-7">“We filled critical shifts in days, not weeks.”</div>
              <div class="mt-4 text-sm font-semibold text-slate-900">HR Manager, Regional Hospital</div>
            </div>
            <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="text-slate-700 leading-7">“The interview scheduling saved so much back-and-forth.”</div>
              <div class="mt-4 text-sm font-semibold text-slate-900">Clinic Director</div>
            </div>
            <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm">
              <div class="text-slate-700 leading-7">“I found a role that matches my preferred shifts.”</div>
              <div class="mt-4 text-sm font-semibold text-slate-900">Registered Nurse</div>
            </div>
          </div>
        </section>
        <section id="cta" class="mt-24 md:mt-32">
          <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 text-white p-10 md:p-12">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative">
              <div class="max-w-2xl">
                <h3 class="text-2xl md:text-3xl font-bold">Start hiring or find your next role today</h3>
                <p class="text-white/90 mt-2">Join a growing network of healthcare employers and professionals.</p>
              </div>
              <div class="flex gap-3">
                <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center px-6 py-3 rounded-xl bg-white text-slate-900 font-semibold hover:bg-slate-100">Create account</RouterLink>
                <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center px-6 py-3 rounded-xl bg-white/10 text-white font-semibold border border-white/20 hover:bg-white/20">Sign in</RouterLink>
              </div>
            </div>
          </div>
        </section>
      </main>

      <footer class="border-t border-slate-100 bg-slate-50/50">
        <div class="max-w-7xl mx-auto px-6 py-12 flex flex-col md:flex-row items-center justify-between gap-6">
          <div class="flex items-center gap-2">
             <div class="w-6 h-6 rounded bg-slate-200 grid place-content-center text-xs font-bold text-slate-600">AC</div>
             <span class="text-sm font-semibold text-slate-600">AI Clinforce Partners</span>
          </div>
          <div class="text-sm text-slate-500">
            © {{ year }} All rights reserved.
          </div>
          <div class="flex gap-6">
            <a href="#" class="text-slate-400 hover:text-slate-600 transition-colors">Privacy</a>
            <a href="#" class="text-slate-400 hover:text-slate-600 transition-colors">Terms</a>
            <a href="#" class="text-slate-400 hover:text-slate-600 transition-colors">Support</a>
          </div>
        </div>
      </footer>
    </div>
  </AppLayout>
</template>

<script setup>
import { RouterLink } from "vue-router";
import { ref } from "vue";
import AppLayout from "@/Components/AppLayout.vue";
const year = new Date().getFullYear();
const mobileOpen = ref(false);
</script>

<style scoped>
</style>
