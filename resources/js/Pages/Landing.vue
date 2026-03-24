<template>
  <AppLayout :guestFull="true">
    <div class="relative min-h-screen overflow-hidden font-sans bg-slate-50/30 dark:bg-slate-950">
      <!-- Marketing Announcement Bar -->
      <div v-if="showAnnouncement" class="relative z-50 bg-gradient-to-r from-blue-600 via-indigo-700 to-violet-800 text-white px-6 py-3 overflow-hidden shadow-lg animate-fade-in-down">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-6 text-sm font-bold uppercase tracking-widest">
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded bg-white/20 backdrop-blur-md text-[10px]">NEW</span>
            <span class="hidden sm:inline">AI-Powered Screening Tools are now available!</span>
            <span class="sm:hidden">AI Screening is here!</span>
          </div>
          <RouterLink :to="{ name: 'auth.register' }" class="px-4 py-1.5 rounded-full bg-white text-blue-700 hover:bg-slate-100 transition-colors shadow-sm">Learn More</RouterLink>
          <button @click="showAnnouncement = false" class="absolute right-6 p-2 hover:bg-white/10 rounded-full transition-colors">
            <i class="pi pi-times text-xs"></i>
          </button>
        </div>
      </div>

      <!-- Background Elements -->
      <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-blue-50 via-white to-white dark:from-slate-900 dark:via-slate-950 dark:to-slate-950"></div>
      <div class="absolute -top-40 -right-40 w-[600px] h-[600px] rounded-full bg-blue-100/40 blur-[120px] opacity-60 animate-pulse-slow"></div>
      <div class="absolute top-20 -left-40 w-[500px] h-[500px] rounded-full bg-indigo-100/40 blur-[100px] opacity-60 animate-pulse-slow" style="animation-delay: 2s"></div>

      <!-- Navigation -->
      <header class="max-w-7xl mx-auto px-6 py-1 relative z-20">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3 group cursor-pointer">
            <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-44 w-auto group-hover:scale-105 transition-transform duration-300" />
          </div>
          <!-- Desktop Nav -->
          <nav class="hidden md:flex items-center gap-8">
            <a href="#features" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Features</a>
            <a href="#how-it-works" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">How it works</a>
            <a href="#testimonials" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Testimonials</a>
            <a href="#contact" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Contact</a>
            <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-2"></div>
            <DarkModeToggle />
            <RouterLink class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors" :to="{ name: 'auth.login' }">Log in</RouterLink>
            <RouterLink class="inline-flex items-center px-6 py-2.5 rounded-full bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-bold hover:bg-blue-600 dark:hover:bg-blue-100 hover:shadow-xl hover:shadow-blue-200 transition-all duration-300 transform hover:-translate-y-0.5" :to="{ name: 'auth.register' }">
              Get started
            </RouterLink>
          </nav>
          <!-- Mobile Trigger -->
          <button class="md:hidden inline-flex items-center justify-center w-11 h-11 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" @click="mobileOpen = !mobileOpen" aria-label="Open menu">
            <i :class="mobileOpen ? 'pi pi-times' : 'pi pi-bars'" class="text-lg"></i>
          </button>
        </div>
        <!-- Mobile Menu -->
        <Transition name="fade-slide">
          <div v-if="mobileOpen" class="md:hidden absolute left-6 right-6 top-20 bg-white/90 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl p-4 z-50">
            <div class="flex flex-col gap-2">
              <a href="#features" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 transition-colors">Features</a>
              <a href="#how-it-works" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 transition-colors">How it works</a>
              <a href="#testimonials" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 transition-colors">Testimonials</a>
              <a href="#contact" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 transition-colors">Contact</a>
              <div class="h-px bg-slate-100 dark:bg-slate-700 my-2"></div>
              <div class="flex items-center justify-between px-4 py-2">
                <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Dark mode</span>
                <DarkModeToggle />
              </div>
              <RouterLink :to="{ name: 'auth.login' }" @click="mobileOpen = false" class="px-4 py-3 rounded-xl text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-slate-800 hover:text-blue-600 transition-colors">Log in</RouterLink>
              <RouterLink :to="{ name: 'auth.register' }" @click="mobileOpen = false" class="px-4 py-4 rounded-xl text-base font-bold bg-blue-600 text-white text-center shadow-lg shadow-blue-200 active:scale-95 transition-all">Get started</RouterLink>
            </div>
          </div>
        </Transition>
      </header>

      <main class="max-w-7xl mx-auto px-6 pt-0 pb-24 md:pt-0 md:pb-32">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
          <!-- Left Content -->
          <div class="max-w-2xl reveal">

            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.05] mb-8">
              The modern way to <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600">hire clinical talent.</span>
            </h1>
            <p class="text-slate-600 dark:text-slate-400 text-xl md:text-2xl leading-relaxed max-w-lg mb-10 font-medium">
              Streamline your healthcare recruitment. Connect with qualified professionals, automate compliance, and fill shifts faster than ever.
            </p>
            <div class="flex flex-wrap items-center gap-5">
              <RouterLink :to="{ name: 'auth.register' }" class="group inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-blue-600 text-white font-bold text-lg hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 hover:shadow-2xl hover:-translate-y-1 active:translate-y-0">
                Start hiring now
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
              </RouterLink>
              <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center justify-center px-8 py-4 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-lg hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition-all hover:-translate-y-1 active:translate-y-0">
                I'm a clinician
              </RouterLink>
            </div>
            <div class="mt-12 flex flex-wrap items-center gap-8 text-sm font-bold text-slate-500 uppercase tracking-wide">
              <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-full bg-emerald-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                Verified Professionals
              </div>
              <div class="flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-full bg-emerald-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                HIPAA Compliant
              </div>
            </div>
          </div>

          <!-- Right Visual -->
          <div class="relative lg:h-[650px] flex items-center justify-center reveal reveal-delay-200">
            <div class="absolute inset-0 bg-gradient-to-tr from-blue-200/40 to-indigo-200/40 rounded-full blur-3xl animate-pulse-slow"></div>
            <!-- Dashboard Mockup Card -->
            <div class="relative w-full max-w-lg bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-[2.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.1)] border border-white/50 dark:border-slate-700/50 overflow-hidden transform lg:rotate-[-3deg] hover:rotate-0 transition-all duration-700 hover:scale-[1.02]">
              <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-white/50 dark:bg-slate-800/50">
                <div class="flex items-center gap-2">
                  <div class="w-3.5 h-3.5 rounded-full bg-red-400/80 shadow-inner"></div>
                  <div class="w-3.5 h-3.5 rounded-full bg-amber-400/80 shadow-inner"></div>
                  <div class="w-3.5 h-3.5 rounded-full bg-emerald-400/80 shadow-inner"></div>
                </div>
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Dashboard Preview</div>
              </div>
              <div class="p-8 space-y-8">
                <div class="flex items-center justify-between">
                  <div>
                    <div class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-1">Total Candidates</div>
                    <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">1,248</div>
                  </div>
                  <div class="w-16 h-16 rounded-[1.25rem] bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-blue-200">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                  </div>
                </div>
                <div class="space-y-4">
                  <div class="flex items-center justify-between text-sm font-bold">
                    <span class="text-slate-700 dark:text-slate-300">Active Jobs</span>
                    <span class="text-blue-600">75% Complete</span>
                  </div>
                  <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-full rounded-full transition-all duration-1000" style="width: 75%"></div>
                  </div>
                </div>
                <div class="p-6 bg-slate-50/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-700">
                  <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-5">Recent Activity</div>
                  <div class="space-y-5">
                    <div class="flex items-center gap-4 group cursor-default">
                      <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-black shadow-sm group-hover:scale-110 transition-transform">JS</div>
                      <div class="flex-1">
                        <div class="text-sm font-bold text-slate-900 dark:text-white">James Smith</div>
                        <div class="text-xs font-medium text-slate-500">Applied for Senior Nurse</div>
                      </div>
                      <span class="text-[10px] font-bold text-slate-400">2m ago</span>
                    </div>
                    <div class="flex items-center gap-4 group cursor-default">
                      <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs font-black shadow-sm group-hover:scale-110 transition-transform">AL</div>
                      <div class="flex-1">
                        <div class="text-sm font-bold text-slate-900 dark:text-white">Ana Lee</div>
                        <div class="text-xs font-medium text-slate-500">Interview Scheduled</div>
                      </div>
                      <span class="text-[10px] font-bold text-slate-400">1h ago</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Floating Elements -->


          </div>
        </div>

        <!-- Banner Section -->
        <section class="mt-32 md:mt-48 reveal">
          <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Transforming Healthcare Staffing</h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">Industry-leading solutions for modern medical facilities and professionals.</p>
          </div>
          <div class="grid md:grid-cols-2 gap-8">
            <div class="relative overflow-hidden rounded-[2.5rem] shadow-2xl group cursor-pointer aspect-[16/9]">
              <img :src="banner1" alt="Healthcare Excellence" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/20 to-transparent opacity-80 group-hover:opacity-90 transition-opacity"></div>
              <div class="absolute inset-0 p-10 flex flex-col justify-end">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 backdrop-blur-md border border-white/20 text-white text-[10px] font-black uppercase tracking-[0.2em] mb-4 w-fit">Excellence</div>
                <h3 class="text-3xl font-black text-white mb-2">Clinical Excellence</h3>
                <p class="text-white/80 font-medium">Precision matching for specialized medical roles.</p>
              </div>
            </div>
            <div class="relative overflow-hidden rounded-[2.5rem] shadow-2xl group cursor-pointer aspect-[16/9]">
              <img :src="banner2" alt="Professional Staffing" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/90 via-indigo-900/20 to-transparent opacity-80 group-hover:opacity-90 transition-opacity"></div>
              <div class="absolute inset-0 p-10 flex flex-col justify-end">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 backdrop-blur-md border border-white/20 text-white text-[10px] font-black uppercase tracking-[0.2em] mb-4 w-fit">Reliability</div>
                <h3 class="text-3xl font-black text-white mb-2">Professional Staffing</h3>
                <p class="text-white/80 font-medium">Scalable solutions for healthcare institutions.</p>
              </div>
            </div>
          </div>
        </section>

        <section id="features" class="mt-32 md:mt-48 reveal">
          <div class="text-center max-w-2xl mx-auto mb-20">
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Everything you need to hire</h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">Powerful tools designed for the modern healthcare workforce.</p>
          </div>
          <div class="grid md:grid-cols-3 gap-10">
            <div class="group p-10 rounded-[2rem] bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500">
              <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-8 group-hover:bg-blue-600 group-hover:text-white group-hover:rotate-6 transition-all duration-500 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
              </div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Job Posting</h3>
              <p class="text-slate-500 dark:text-slate-400 leading-relaxed font-medium">Create and distribute detailed job listings to thousands of qualified clinicians instantly.</p>
            </div>
            <div class="group p-10 rounded-[2rem] bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500">
              <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-8 group-hover:bg-indigo-600 group-hover:text-white group-hover:-rotate-6 transition-all duration-500 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
              </div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Smart Matching</h3>
              <p class="text-slate-500 dark:text-slate-400 leading-relaxed font-medium">Our AI automatically matches the best candidates to your specific requirements and shifts.</p>
            </div>
            <div class="group p-10 rounded-[2rem] bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500">
              <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-8 group-hover:bg-emerald-600 group-hover:text-white group-hover:rotate-6 transition-all duration-500 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
              </div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Easy Scheduling</h3>
              <p class="text-slate-500 dark:text-slate-400 leading-relaxed font-medium">Coordinate interviews and shifts effortlessly with our integrated calendar system.</p>
            </div>
          </div>
        </section>

        <section id="how-it-works" class="mt-32 md:mt-48 reveal">
          <div class="text-center max-w-2xl mx-auto mb-20">
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">How it works</h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">Simple steps to fill roles or find your next opportunity.</p>
          </div>
          <div class="grid md:grid-cols-3 gap-8">
            <div class="relative p-10 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-shadow group">
              <div class="absolute -top-5 -left-5 w-12 h-12 rounded-2xl bg-slate-900 text-white grid place-content-center font-black shadow-xl group-hover:scale-110 transition-transform">1</div>
              <div class="text-2xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Post or Sign Up</div>
              <div class="text-slate-600 dark:text-slate-400 font-medium leading-relaxed">Employers post roles. Clinicians create profiles and set preferences.</div>
            </div>
            <div class="relative p-10 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-shadow group">
              <div class="absolute -top-5 -left-5 w-12 h-12 rounded-2xl bg-blue-600 text-white grid place-content-center font-black shadow-xl group-hover:scale-110 transition-transform">2</div>
              <div class="text-2xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Smart Match</div>
              <div class="text-slate-600 dark:text-slate-400 font-medium leading-relaxed">Our AI highlights top matches based on skills, location, and shift.</div>
            </div>
            <div class="relative p-10 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-shadow group">
              <div class="absolute -top-5 -left-5 w-12 h-12 rounded-2xl bg-indigo-600 text-white grid place-content-center font-black shadow-xl group-hover:scale-110 transition-transform">3</div>
              <div class="text-2xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Interview &amp; Hire</div>
              <div class="text-slate-600 dark:text-slate-400 font-medium leading-relaxed">Schedule interviews, manage offers, and confirm shifts seamlessly.</div>
            </div>
          </div>
        </section>

        <section id="audiences" class="mt-32 md:mt-48 reveal">
          <div class="grid md:grid-cols-2 gap-10">
            <div class="p-10 bg-white dark:bg-slate-800 rounded-[2.5rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-2xl transition-all duration-500">
              <div class="text-sm font-black text-blue-600 uppercase tracking-widest mb-4">For Employers</div>
              <div class="text-3xl font-black text-slate-900 dark:text-white mb-6 tracking-tight">Hire faster with better visibility</div>
              <ul class="space-y-4 text-slate-600 dark:text-slate-400 mb-10">
                <li class="flex items-start gap-3">
                  <div class="mt-1 w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </div>
                  <span class="font-medium">AI shortlists to reduce time-to-hire</span>
                </li>
                <li class="flex items-start gap-3">
                  <div class="mt-1 w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </div>
                  <span class="font-medium">Centralized job and application tracking</span>
                </li>
                <li class="flex items-start gap-3">
                  <div class="mt-1 w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </div>
                  <span class="font-medium">Integrated interview scheduling</span>
                </li>
              </ul>
              <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center px-8 py-4 rounded-2xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 active:scale-95">Get started</RouterLink>
            </div>
            <div class="p-10 bg-slate-900 rounded-[2.5rem] text-white shadow-2xl hover:shadow-indigo-200/50 transition-all duration-500">
              <div class="text-sm font-black text-indigo-400 uppercase tracking-widest mb-4">For Clinicians</div>
              <div class="text-3xl font-black text-white mb-6 tracking-tight">Find roles that fit your life</div>
              <ul class="space-y-4 text-white/80 mb-10">
                <li class="flex items-start gap-3">
                  <div class="mt-1 w-5 h-5 rounded-full bg-white/10 text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </div>
                  <span class="font-medium">Personalized recommendations</span>
                </li>
                <li class="flex items-start gap-3">
                  <div class="mt-1 w-5 h-5 rounded-full bg-white/10 text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </div>
                  <span class="font-medium">Easy applications and updates</span>
                </li>
                <li class="flex items-start gap-3">
                  <div class="mt-1 w-5 h-5 rounded-full bg-white/10 text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                  </div>
                  <span class="font-medium">Message employers directly</span>
                </li>
              </ul>
              <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center px-8 py-4 rounded-2xl bg-white text-slate-900 font-bold hover:bg-slate-100 transition-all active:scale-95">Browse jobs</RouterLink>
            </div>
          </div>
        </section>

        <section id="testimonials" class="mt-32 md:mt-48 reveal">
          <div class="text-center max-w-2xl mx-auto mb-20">
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Trusted by teams and clinicians</h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">Real stories from our community.</p>
          </div>
          <div class="grid md:grid-cols-3 gap-8">
            <div class="p-8 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all group">
              <div class="flex gap-1 text-amber-400 mb-6 group-hover:scale-105 transition-transform origin-left">
                <i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i>
              </div>
              <div class="text-slate-700 dark:text-slate-300 leading-8 font-medium italic">"We filled critical shifts in days, not weeks. The platform is intuitive and incredibly efficient."</div>
              <div class="mt-8 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 grid place-content-center font-black text-xs">HR</div>
                <div>
                  <div class="text-sm font-black text-slate-900 dark:text-white">HR Manager</div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Regional Hospital</div>
                </div>
              </div>
            </div>
            <div class="p-8 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all group">
              <div class="flex gap-1 text-amber-400 mb-6 group-hover:scale-105 transition-transform origin-left">
                <i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i>
              </div>
              <div class="text-slate-700 dark:text-slate-300 leading-8 font-medium italic">"The interview scheduling saved so much back-and-forth. Our workflow has never been smoother."</div>
              <div class="mt-8 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 grid place-content-center font-black text-xs">CD</div>
                <div>
                  <div class="text-sm font-black text-slate-900 dark:text-white">Clinic Director</div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">HealthCare Plus</div>
                </div>
              </div>
            </div>
            <div class="p-8 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all group">
              <div class="flex gap-1 text-amber-400 mb-6 group-hover:scale-105 transition-transform origin-left">
                <i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i><i class="pi pi-star-fill text-xs"></i>
              </div>
              <div class="text-slate-700 dark:text-slate-300 leading-8 font-medium italic">"I found a role that matches my preferred shifts perfectly. The AI matching is spot on!"</div>
              <div class="mt-8 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 grid place-content-center font-black text-xs">RN</div>
                <div>
                  <div class="text-sm font-black text-slate-900 dark:text-white">Registered Nurse</div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Independent Contractor</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="cta" class="mt-32 md:mt-48 reveal">
          <div class="relative overflow-hidden rounded-[3rem] bg-gradient-to-br from-blue-600 via-indigo-700 to-violet-800 text-white p-12 md:p-24 shadow-2xl">
            <div class="absolute -right-20 -top-20 w-[30rem] h-[30rem] bg-white/10 rounded-full blur-[100px] animate-pulse-slow"></div>
            <div class="absolute -left-20 -bottom-20 w-[25rem] h-[25rem] bg-indigo-400/20 rounded-full blur-[80px] animate-pulse-slow" style="animation-delay: 2s"></div>
            <div class="flex flex-col items-center text-center relative z-10">
              <h3 class="text-4xl md:text-6xl font-black mb-8 tracking-tight max-w-4xl leading-[1.1]">Ready to revolutionize your hiring process?</h3>
              <p class="text-white/80 text-xl md:text-2xl font-medium mb-12 max-w-2xl leading-relaxed">Join thousands of healthcare professionals and employers already using Clinforce.</p>
              <div class="flex flex-col sm:flex-row gap-6 w-full sm:w-auto">
                <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center justify-center px-10 py-5 rounded-2xl bg-white text-slate-900 font-black text-lg hover:bg-slate-100 transition-all hover:-translate-y-1 active:scale-95 shadow-2xl">Create account</RouterLink>
                <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center justify-center px-10 py-5 rounded-2xl bg-white/10 text-white font-black text-lg border-2 border-white/20 hover:bg-white/20 transition-all hover:-translate-y-1 active:scale-95 backdrop-blur-md">Sign in</RouterLink>
              </div>
            </div>
          </div>
        </section>

        <section id="contact" class="mt-32 md:mt-48 reveal">
          <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
              <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-6">Get in touch</h2>
              <p class="text-slate-600 dark:text-slate-400 text-lg font-medium mb-10 leading-relaxed">Have questions about our platform? Our team is here to help you find the right talent or opportunity.</p>
              <div class="space-y-8">
                <div class="flex items-start gap-5">
                  <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center flex-shrink-0 shadow-inner"><i class="pi pi-envelope text-xl"></i></div>
                  <div>
                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-1">Email Us</div>
                    <a href="mailto:aiclinforce@gmail.com" class="text-lg font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600 transition-colors">aiclinforce@gmail.com</a>
                  </div>
                </div>
                <div class="flex items-start gap-5">
                  <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center flex-shrink-0 shadow-inner"><i class="pi pi-phone text-xl"></i></div>
                  <div>
                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-1">Call Us</div>
                    <a href="tel:+1234567890" class="text-lg font-medium text-slate-600 dark:text-slate-400 hover:text-blue-600 transition-colors">+1 (234) 567-890</a>
                  </div>
                </div>
                <div class="flex items-start gap-5">
                  <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center flex-shrink-0 shadow-inner"><i class="pi pi-map-marker text-xl"></i></div>
                  <div>
                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-1">Visit Us</div>
                    <address class="text-lg font-medium text-slate-600 dark:text-slate-400 not-italic">123 Healthcare Way, Medical District, NY 10001</address>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-10 border border-slate-100 dark:border-slate-700 shadow-2xl relative">
              <div v-if="contactSuccess" class="absolute inset-0 bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm rounded-[2.5rem] z-10 flex flex-col items-center justify-center p-10 text-center animate-fade-in">
                <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-6 scale-110"><i class="pi pi-check text-4xl"></i></div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4">Message Sent!</h3>
                <p class="text-slate-600 dark:text-slate-400 font-medium mb-8">Thank you for reaching out. We've received your message and will get back to you within 24 hours.</p>
                <button @click="contactSuccess = false" class="px-8 py-3 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold hover:bg-slate-800 dark:hover:bg-slate-100 transition-all">Send another message</button>
              </div>
              <form @submit.prevent="submitContact" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <label class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest ml-1">Your Name</label>
                    <input v-model="contactForm.name" type="text" required placeholder="John Doe" class="w-full px-5 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:placeholder-slate-500 border-2 border-transparent dark:border-slate-700 focus:border-blue-600 focus:bg-white dark:focus:bg-slate-900 outline-none transition-all font-medium text-slate-700" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest ml-1">Email Address</label>
                    <input v-model="contactForm.email" type="email" required placeholder="john@example.com" class="w-full px-5 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:placeholder-slate-500 border-2 border-transparent dark:border-slate-700 focus:border-blue-600 focus:bg-white dark:focus:bg-slate-900 outline-none transition-all font-medium text-slate-700" />
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest ml-1">Subject</label>
                  <input v-model="contactForm.subject" type="text" required placeholder="How can we help?" class="w-full px-5 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:placeholder-slate-500 border-2 border-transparent dark:border-slate-700 focus:border-blue-600 focus:bg-white dark:focus:bg-slate-900 outline-none transition-all font-medium text-slate-700" />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest ml-1">Message</label>
                  <textarea v-model="contactForm.message" required rows="4" placeholder="Your message here..." class="w-full px-5 py-4 rounded-2xl bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:placeholder-slate-500 border-2 border-transparent dark:border-slate-700 focus:border-blue-600 focus:bg-white dark:focus:bg-slate-900 outline-none transition-all font-medium text-slate-700 resize-none"></textarea>
                </div>
                <button type="submit" :disabled="contactLoading" class="w-full py-5 rounded-2xl bg-blue-600 text-white font-black text-lg hover:bg-blue-700 transition-all shadow-xl shadow-blue-100 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                  <i v-if="contactLoading" class="pi pi-spin pi-spinner"></i>
                  {{ contactLoading ? 'Sending...' : 'Send Message' }}
                </button>
              </form>
            </div>
          </div>
        </section>
      </main>

      <footer class="border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-6">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-12 mb-16">
            <div class="col-span-2">
              <div class="flex items-center gap-3 mb-6">
                <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-64 w-auto" />
              </div>
              <p class="text-slate-500 dark:text-slate-400 font-medium max-w-sm leading-relaxed mb-8">The next generation of healthcare staffing solutions, powered by intelligent matching and seamless workflows.</p>
              <div class="flex gap-4">
                <a href="#" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-blue-50 dark:hover:bg-slate-700 hover:text-blue-600 transition-all"><i class="pi pi-twitter"></i></a>
                <a href="#" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-blue-50 dark:hover:bg-slate-700 hover:text-blue-600 transition-all"><i class="pi pi-linkedin"></i></a>
                <a href="https://www.facebook.com/profile.php?id=61552278210549" target="_blank" class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-blue-50 dark:hover:bg-slate-700 hover:text-blue-600 transition-all"><i class="pi pi-facebook"></i></a>
              </div>
            </div>
            <div>
              <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-6">Platform</h4>
              <ul class="space-y-4 text-slate-500 dark:text-slate-400 font-medium">
                <li><a href="#" class="hover:text-blue-600 transition-colors">Features</a></li>
                <li><a href="#" class="hover:text-blue-600 transition-colors">How it works</a></li>
                <li><a href="#" class="hover:text-blue-600 transition-colors">Pricing</a></li>
              </ul>
            </div>
            <div>
              <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest mb-6">Company</h4>
              <ul class="space-y-4 text-slate-500 dark:text-slate-400 font-medium">
                <li><a href="#" class="hover:text-blue-600 transition-colors">About Us</a></li>
                <li><a href="#" class="hover:text-blue-600 transition-colors">Careers</a></li>
                <li><a href="#contact" class="hover:text-blue-600 transition-colors">Contact</a></li>
              </ul>
            </div>
          </div>
          <div class="pt-12 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-sm font-bold text-slate-400 uppercase tracking-widest">&copy; {{ year }} AI Clinforce Partners.</div>
            <div class="flex gap-8 text-sm font-bold text-slate-400 uppercase tracking-widest">
              <a href="#" class="hover:text-slate-900 dark:hover:text-white transition-colors">Privacy</a>
              <a href="#" class="hover:text-slate-900 dark:hover:text-white transition-colors">Terms</a>
              <a href="#" class="hover:text-slate-900 dark:hover:text-white transition-colors">Cookie Policy</a>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </AppLayout>
</template>

<script setup>
import { RouterLink } from "vue-router";
import { ref, onMounted, reactive } from "vue";
import AppLayout from "@/Components/AppLayout.vue";
import DarkModeToggle from "@/Components/DarkModeToggle.vue";
import banner1 from "../../assets/banner.png";
import banner2 from "../../assets/banner2.png";
import { post } from "../lib/http";
import { useDarkMode } from "@/composables/useDarkMode";

const { initDarkMode } = useDarkMode();

const year = new Date().getFullYear();
const mobileOpen = ref(false);
const showAnnouncement = ref(true);

const contactForm = reactive({ name: '', email: '', subject: '', message: '' });
const contactLoading = ref(false);
const contactSuccess = ref(false);

const submitContact = async () => {
  contactLoading.value = true;
  try {
    await post('/contact', contactForm);
    contactSuccess.value = true;
    contactForm.name = '';
    contactForm.email = '';
    contactForm.subject = '';
    contactForm.message = '';
  } catch (error) {
    console.error('Failed to send message:', error);
    alert('Failed to send message. Please try again later.');
  } finally {
    contactLoading.value = false;
  }
};

onMounted(() => {
  initDarkMode();
  showAnnouncement.value = true;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) entry.target.classList.add('reveal-visible');
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
});
</script>

<style scoped>
.reveal { opacity: 0; transform: translateY(30px); transition: all 1s cubic-bezier(0.22, 1, 0.36, 1); }
.reveal-visible { opacity: 1; transform: translateY(0); }
.reveal-delay-200 { transition-delay: 0.2s; }
.reveal-delay-400 { transition-delay: 0.4s; }
.animate-pulse-slow { animation: pulse 8s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
@keyframes pulse { 0%, 100% { opacity: 0.4; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.05); } }
.animate-float { animation: float 6s ease-in-out infinite; }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease-out; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-10px); }
.animate-fade-in-down { animation: fadeInDown 1s both; }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.5s ease-out both; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: #f8fafc; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 5px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
