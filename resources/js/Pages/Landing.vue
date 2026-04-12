
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
            <a href="#pricing" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Pricing</a>
            <a href="#testimonials" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Testimonials</a>
            <a href="#faq" class="text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">FAQ</a>
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
              <!-- Employer path -->
              <RouterLink :to="{ name: 'auth.register' }"
                class="group relative flex flex-col gap-3 p-5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-xl shadow-blue-200 hover:shadow-2xl hover:-translate-y-1 active:translate-y-0 flex-1 min-w-[200px]">
                <div class="flex items-center justify-between">
                  <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                  </div>
                  <svg class="w-4 h-4 opacity-60 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </div>
                <div>
                  <div class="font-black text-base">I'm an Employer</div>
                  <div class="text-blue-200 text-xs font-medium mt-0.5">Post jobs · Screen candidates · Hire faster</div>
                </div>
                <div class="text-[10px] font-bold text-blue-300 uppercase tracking-widest">Free 7-day trial →</div>
              </RouterLink>
              <!-- Clinician path -->
              <RouterLink :to="{ name: 'auth.login' }"
                class="group relative flex flex-col gap-3 p-5 rounded-2xl bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600 text-slate-900 dark:text-white transition-all hover:shadow-xl hover:-translate-y-1 active:translate-y-0 flex-1 min-w-[200px]">
                <div class="flex items-center justify-between">
                  <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                  </div>
                  <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </div>
                <div>
                  <div class="font-black text-base">I'm a Clinician</div>
                  <div class="text-slate-500 dark:text-slate-400 text-xs font-medium mt-0.5">Browse jobs · Apply · Track applications</div>
                </div>
                <div class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">Browse open roles →</div>
              </RouterLink>
            </div>
            <p class="mt-4 text-xs text-slate-400 dark:text-slate-500 font-medium flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              No credit card required · Cancel anytime
            </p>
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

          <!-- Right Visual — Animated Demo -->
          <div class="relative lg:h-[650px] flex items-center justify-center reveal reveal-delay-200">
            <div class="absolute inset-0 bg-gradient-to-tr from-blue-200/40 to-indigo-200/40 rounded-full blur-3xl animate-pulse-slow"></div>
            <div class="relative w-full max-w-lg transform lg:rotate-[-2deg] hover:rotate-0 transition-all duration-700 hover:scale-[1.02]">
              <!-- Browser chrome -->
              <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-[2rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.15)] border border-white/60 dark:border-slate-700/60 overflow-hidden">
                <!-- Title bar -->
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-700 flex items-center gap-3 bg-slate-50/80 dark:bg-slate-900/50">
                  <div class="flex gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-400/80"></div>
                    <div class="w-3 h-3 rounded-full bg-amber-400/80"></div>
                    <div class="w-3 h-3 rounded-full bg-emerald-400/80"></div>
                  </div>
                  <div class="flex-1 bg-white dark:bg-slate-700 rounded-lg px-3 py-1 text-[10px] text-slate-400 font-mono">aiclinforce.com/employer/dashboard</div>
                </div>
                <!-- Demo content -->
                <div class="p-5 space-y-4">
                  <!-- KPI row -->
                  <div class="grid grid-cols-3 gap-3">
                    <div v-for="kpi in demoKpis" :key="kpi.label"
                      class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700">
                      <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mb-1">{{ kpi.label }}</div>
                      <div class="text-xl font-black text-slate-900 dark:text-white">{{ kpi.value }}</div>
                    </div>
                  </div>
                  <!-- Pipeline bars -->
                  <div class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-700 space-y-3">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Hiring Pipeline</div>
                    <div v-for="stage in demoStages" :key="stage.label" class="space-y-1">
                      <div class="flex justify-between text-xs font-semibold">
                        <span class="text-slate-600 dark:text-slate-400">{{ stage.label }}</span>
                        <span class="text-slate-900 dark:text-white">{{ stage.count }}</span>
                      </div>
                      <div class="h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full demo-bar" :style="{ width: stage.pct + '%', background: stage.color }"></div>
                      </div>
                    </div>
                  </div>
                  <!-- Recent activity -->
                  <div class="space-y-2">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Recent Activity</div>
                    <div v-for="(act, i) in demoActivity" :key="i"
                      class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors"
                      :class="{ 'demo-highlight': demoHighlight === i }">
                      <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black flex-shrink-0"
                        :style="{ background: act.bg, color: act.color }">{{ act.initials }}</div>
                      <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ act.name }}</div>
                        <div class="text-[10px] text-slate-500">{{ act.action }}</div>
                      </div>
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                        :style="{ background: act.tagBg, color: act.tagColor }">{{ act.tag }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <!-- AI badge floating -->
              <div class="absolute -bottom-4 -right-4 bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl px-4 py-3 shadow-xl shadow-blue-300/40 flex items-center gap-2 animate-float">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <span class="text-xs font-black">AI matched 3 candidates</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Stats Counter ─────────────────────────────────────────── -->
        <section class="mt-20 md:mt-28 reveal" ref="statsSection">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div v-for="stat in stats" :key="stat.label"
              class="text-center p-8 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-500">
              <div class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight mb-2">
                {{ stat.prefix }}{{ stat.displayed.toLocaleString() }}{{ stat.suffix }}
              </div>
              <div class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ stat.label }}</div>
            </div>
          </div>
        </section>

        <!-- ── Live Job Ticker ─────────────────────────────────────────── -->
        <section class="mt-16 reveal overflow-hidden">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Live — recently posted jobs</span>
          </div>
          <div class="relative">
            <!-- Fade edges -->
            <div class="absolute left-0 top-0 bottom-0 w-16 bg-gradient-to-r from-slate-50/80 dark:from-slate-950/80 to-transparent z-10 pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-16 bg-gradient-to-l from-slate-50/80 dark:from-slate-950/80 to-transparent z-10 pointer-events-none"></div>
            <div class="flex gap-4 ticker-track">
              <div v-for="(job, i) in [...tickerJobs, ...tickerJobs]" :key="i"
                class="flex-shrink-0 flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-slate-900 dark:text-white truncate max-w-[160px]">{{ job.title }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ job.location }}</div>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 flex-shrink-0">New</span>
              </div>
            </div>
          </div>
        </section>

        <!-- ── Trusted By ──────────────────────────────────────────────── -->
        <section class="mt-16 reveal">
          <p class="text-center text-xs font-bold text-slate-400 dark:text-slate-600 uppercase tracking-[0.25em] mb-8">Trusted by healthcare institutions across the Philippines &amp; beyond</p>
          <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12 opacity-60 dark:opacity-40 grayscale hover:grayscale-0 transition-all duration-500">
            <!-- SVG text logos — no external images needed -->
            <div v-for="org in trustedOrgs" :key="org.name"
              class="flex items-center gap-2 px-5 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:opacity-100 transition-opacity">
              <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" :style="{ background: org.color + '20' }">
                <svg class="w-4 h-4" :style="{ color: org.color }" fill="currentColor" viewBox="0 0 24 24">
                  <path :d="org.icon" />
                </svg>
              </div>
              <span class="text-sm font-black text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ org.name }}</span>
            </div>
          </div>
        </section>

        <!-- Banner Section -->
        <section class="mt-32 md:mt-48 reveal">
          <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Transforming Healthcare Staffing</h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">Industry-leading solutions for modern medical facilities and professionals.</p>
          </div>
          <div class="grid md:grid-cols-2 gap-8">
            <div class="relative overflow-hidden rounded-[2.5rem] shadow-2xl group cursor-pointer aspect-[16/9]">
              <img :src="banner1" alt="Healthcare Excellence" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" />
              <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/20 to-transparent opacity-80 group-hover:opacity-90 transition-opacity"></div>
              <div class="absolute inset-0 p-10 flex flex-col justify-end">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 backdrop-blur-md border border-white/20 text-white text-[10px] font-black uppercase tracking-[0.2em] mb-4 w-fit">Excellence</div>
                <h3 class="text-3xl font-black text-white mb-2">Clinical Excellence</h3>
                <p class="text-white/80 font-medium">Precision matching for specialized medical roles.</p>
              </div>
            </div>
            <div class="relative overflow-hidden rounded-[2.5rem] shadow-2xl group cursor-pointer aspect-[16/9]">
              <img :src="banner2" alt="Professional Staffing" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" loading="lazy" />
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
            <div v-for="t in testimonials" :key="t.name"
              class="p-8 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all group flex flex-col">
              <div class="flex gap-1 text-amber-400 mb-6">
                <i v-for="n in 5" :key="n" class="pi pi-star-fill text-xs"></i>
              </div>
              <p class="text-slate-700 dark:text-slate-300 leading-8 font-medium italic flex-1">"{{ t.quote }}"</p>
              <div class="mt-8 flex items-center gap-4">
                <img :src="t.avatar" :alt="t.name" class="w-12 h-12 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-700" loading="lazy" />
                <div>
                  <div class="text-sm font-black text-slate-900 dark:text-white">{{ t.name }}</div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ t.role }}</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ── Pricing ──────────────────────────────────────────────────── -->
        <section id="pricing" class="mt-32 md:mt-48 reveal">
          <div class="text-center max-w-2xl mx-auto mb-6">
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Simple, transparent pricing</h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">No hidden fees. Cancel anytime.</p>
          </div>
          <div class="flex justify-center mb-12">
            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl gap-1">
              <button v-for="r in [{ label: '🇵🇭 Philippines (PHP)', key: 'ph' }, { label: '🌍 International (USD)', key: 'intl' }]" :key="r.key"
                @click="pricingRegion = r.key"
                :class="['px-6 py-2.5 rounded-xl text-sm font-bold transition-all',
                  pricingRegion === r.key
                    ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200']">
                {{ r.label }}
              </button>
            </div>
          </div>
          <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <div v-for="plan in activePlans" :key="plan.name"
              class="relative flex flex-col rounded-[2rem] border p-8 transition-all duration-500 hover:-translate-y-1 hover:shadow-2xl"
              :class="plan.popular
                ? 'bg-blue-600 border-blue-500 text-white shadow-2xl shadow-blue-200 scale-[1.03]'
                : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 shadow-sm'">
              <div v-if="plan.popular" class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 bg-amber-400 text-slate-900 text-xs font-black rounded-full uppercase tracking-widest shadow-lg whitespace-nowrap">Most Popular</div>
              <div class="mb-6">
                <div class="text-xs font-black uppercase tracking-widest mb-2" :class="plan.popular ? 'text-blue-200' : 'text-blue-600'">{{ plan.duration }}</div>
                <div class="text-2xl font-black mb-1" :class="plan.popular ? 'text-white' : 'text-slate-900 dark:text-white'">{{ plan.name }}</div>
                <div class="flex items-end gap-1 mt-3">
                  <span class="text-5xl font-black" :class="plan.popular ? 'text-white' : 'text-slate-900 dark:text-white'">{{ plan.price }}</span>
                  <span class="text-sm font-bold mb-2" :class="plan.popular ? 'text-blue-200' : 'text-slate-400'">{{ plan.currency }}</span>
                </div>
              </div>
              <ul class="space-y-3 flex-1 mb-8">
                <li v-for="f in plan.features" :key="f" class="flex items-start gap-2.5 text-sm font-medium"
                  :class="plan.popular ? 'text-blue-100' : 'text-slate-600 dark:text-slate-400'">
                  <svg class="w-4 h-4 mt-0.5 flex-shrink-0" :class="plan.popular ? 'text-blue-200' : 'text-emerald-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                  {{ f }}
                </li>
              </ul>
              <RouterLink :to="{ name: 'auth.register' }"
                :class="['block text-center py-3.5 rounded-2xl font-bold text-sm transition-all active:scale-95',
                  plan.popular
                    ? 'bg-white text-blue-600 hover:bg-blue-50 shadow-lg'
                    : 'bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-100']">
                Get started
              </RouterLink>
            </div>
          </div>
          <p class="text-center text-sm text-slate-400 mt-8 font-medium">All plans include a 7-day free trial. No credit card required.</p>
        </section>

        <!-- ── FAQ ─────────────────────────────────────────────────────── -->
        <section id="faq" class="mt-32 md:mt-48 reveal">
          <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Frequently asked questions</h2>
            <p class="text-slate-600 dark:text-slate-400 text-lg font-medium">Everything you need to know before getting started.</p>
          </div>
          <div class="max-w-3xl mx-auto space-y-3">
            <div v-for="(faq, i) in faqs" :key="i"
              class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 overflow-hidden shadow-sm hover:shadow-md transition-shadow">
              <button class="w-full flex items-center justify-between px-8 py-6 text-left gap-4"
                @click="openFaq = openFaq === i ? null : i">
                <span class="font-bold text-slate-900 dark:text-white text-base">{{ faq.q }}</span>
                <i :class="['pi flex-shrink-0 transition-transform duration-300 text-slate-400', openFaq === i ? 'pi-minus' : 'pi-plus']"></i>
              </button>
              <Transition name="faq-expand">
                <div v-if="openFaq === i" class="px-8 pb-6">
                  <p class="text-slate-600 dark:text-slate-400 leading-relaxed font-medium">{{ faq.a }}</p>
                </div>
              </Transition>
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
              <!-- No credit card + security badges -->
              <div class="flex flex-col items-center gap-4 mt-4">
                <p class="text-white/60 text-sm font-medium flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  No credit card required · Cancel anytime · 7-day free trial
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                  <div v-for="badge in securityBadges" :key="badge.label"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 backdrop-blur-sm">
                    <svg class="w-3.5 h-3.5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="badge.icon" />
                    </svg>
                    <span class="text-[11px] font-bold text-white/80 uppercase tracking-wide">{{ badge.label }}</span>
                  </div>
                </div>
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
                <li><a href="#features" class="hover:text-blue-600 transition-colors">Features</a></li>
                <li><a href="#how-it-works" class="hover:text-blue-600 transition-colors">How it works</a></li>
                <li><a href="#pricing" class="hover:text-blue-600 transition-colors">Pricing</a></li>
                <li><a href="#faq" class="hover:text-blue-600 transition-colors">FAQ</a></li>
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
          <div class="pt-12 border-t border-slate-100 dark:border-slate-800 space-y-6">
            <!-- Security badges row -->
            <div class="flex flex-wrap items-center justify-center gap-3">
              <div v-for="badge in securityBadges" :key="badge.label"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="badge.icon" />
                </svg>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ badge.label }}</span>
              </div>
            </div>
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
              <div class="text-sm font-bold text-slate-400 uppercase tracking-widest">&copy; {{ year }} AI Clinforce Partners.</div>
              <div class="flex gap-8 text-sm font-bold text-slate-400 uppercase tracking-widest">
                <RouterLink :to="{ name: 'privacy' }" class="hover:text-slate-900 dark:hover:text-white transition-colors">Privacy</RouterLink>
                <RouterLink :to="{ name: 'terms' }" class="hover:text-slate-900 dark:hover:text-white transition-colors">Terms</RouterLink>
                <a href="#contact" class="hover:text-slate-900 dark:hover:text-white transition-colors">Contact</a>
              </div>
            </div>
          </div>
        </div>
      </footer>
      <!-- ── Sticky CTA Bar ──────────────────────────────────────────── -->
      <Transition name="slide-up">
        <div v-if="showStickyCta"
          class="fixed bottom-0 left-0 right-0 z-50 bg-slate-900/95 dark:bg-slate-950/95 backdrop-blur-xl border-t border-white/10 shadow-2xl">
          <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
            <div class="hidden sm:flex items-center gap-3">
              <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
              <span class="text-white/80 text-sm font-medium">Join 5,000+ healthcare professionals on ClinForce</span>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
              <RouterLink :to="{ name: 'auth.register' }"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition-all hover:-translate-y-0.5 shadow-lg shadow-blue-900/50">
                Start hiring free
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
              </RouterLink>
              <RouterLink :to="{ name: 'auth.login' }"
                class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-2.5 rounded-xl border border-white/20 text-white/80 hover:text-white hover:border-white/40 font-bold text-sm transition-all">
                Sign in
              </RouterLink>
              <button @click="showStickyCta = false" class="text-white/40 hover:text-white/80 transition-colors p-1 flex-shrink-0">
                <i class="pi pi-times text-xs"></i>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </AppLayout>
</template>

<script setup>
import { RouterLink } from "vue-router";
import { ref, computed, onMounted, onBeforeUnmount, reactive } from "vue";
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
const showStickyCta = ref(false);

// ── Sticky CTA — show after scrolling past hero ──────────────────────
function handleScroll() {
  showStickyCta.value = window.scrollY > 600;
}

// ── Animated demo data ───────────────────────────────────────────────
const demoKpis = [
  { label: 'Candidates', value: '1,248' },
  { label: 'Active Jobs', value: '12' },
  { label: 'Hired', value: '34' },
];
const demoStages = [
  { label: 'Applied',     count: 48, pct: 100, color: '#3b82f6' },
  { label: 'Shortlisted', count: 22, pct: 46,  color: '#6366f1' },
  { label: 'Interview',   count: 11, pct: 23,  color: '#f59e0b' },
  { label: 'Hired',       count: 4,  pct: 8,   color: '#10b981' },
];
const demoActivity = [
  { initials: 'JS', name: 'James S.', action: 'Applied for Senior ICU Nurse', tag: 'New', bg: '#ede9fe', color: '#7c3aed', tagBg: '#eff6ff', tagColor: '#2563eb' },
  { initials: 'AL', name: 'Ana L.',   action: 'Interview confirmed',           tag: '✓',   bg: '#d1fae5', color: '#059669', tagBg: '#d1fae5', tagColor: '#059669' },
  { initials: 'MR', name: 'Maria R.', action: 'AI score: 92% match',           tag: 'AI',  bg: '#fef3c7', color: '#d97706', tagBg: '#fef3c7', tagColor: '#d97706' },
];
const demoHighlight = ref(0);

// ── Live job ticker ──────────────────────────────────────────────────
const tickerJobs = ref([
  { title: 'Senior ICU Nurse', location: 'Manila, PH' },
  { title: 'ER Physician', location: 'Cebu, PH' },
  { title: 'Physical Therapist', location: 'New York, US' },
  { title: 'Registered Nurse', location: 'Quezon City, PH' },
  { title: 'Medical Technologist', location: 'Dubai, UAE' },
  { title: 'Occupational Therapist', location: 'London, UK' },
  { title: 'Radiologic Technologist', location: 'Davao, PH' },
  { title: 'Clinical Pharmacist', location: 'Singapore' },
]);

async function loadTickerJobs() {
  try {
    const { get } = await import('@/lib/http');
    const res = await get('/public/jobs', { params: { per_page: 8 } });
    const items = res.data?.data?.data || res.data?.data || [];
    if (items.length >= 4) {
      tickerJobs.value = items.map(j => ({
        title: j.title,
        location: [j.city, j.country_code].filter(Boolean).join(', ') || '—',
      }));
    }
  } catch {}
}

// ── Stats counter ────────────────────────────────────────────────────
const statsSection = ref(null);
const stats = reactive([
  { label: 'Clinicians registered', target: 5000, displayed: 0, suffix: '+', prefix: '' },
  { label: 'Healthcare employers', target: 200,  displayed: 0, suffix: '+', prefix: '' },
  { label: 'Avg. time to hire',    target: 48,   displayed: 0, suffix: 'h', prefix: '' },
  { label: 'Interviews scheduled', target: 1200, displayed: 0, suffix: '+', prefix: '' },
]);

function animateCounter(stat) {
  const duration = 1800;
  const steps = 60;
  const increment = stat.target / steps;
  let current = 0;
  const timer = setInterval(() => {
    current = Math.min(current + increment, stat.target);
    stat.displayed = Math.floor(current);
    if (current >= stat.target) clearInterval(timer);
  }, duration / steps);
}

// ── Testimonials with avatars ────────────────────────────────────────
const testimonials = [
  {
    quote: 'We filled critical shifts in days, not weeks. The platform is intuitive and incredibly efficient.',
    name: 'Maria Santos',
    role: 'HR Manager · Regional Hospital',
    avatar: 'https://api.dicebear.com/9.x/personas/svg?seed=Maria&backgroundColor=b6e3f4',
  },
  {
    quote: 'The interview scheduling saved so much back-and-forth. Our workflow has never been smoother.',
    name: 'Dr. James Reyes',
    role: 'Clinic Director · HealthCare Plus',
    avatar: 'https://api.dicebear.com/9.x/personas/svg?seed=James&backgroundColor=c0aede',
  },
  {
    quote: 'I found a role that matches my preferred shifts perfectly. The AI matching is spot on!',
    name: 'Ana Lim, RN',
    role: 'Registered Nurse · Independent',
    avatar: 'https://api.dicebear.com/9.x/personas/svg?seed=Ana&backgroundColor=d1d4f9',
  },
];

// ── Pricing ──────────────────────────────────────────────────────────
const pricingRegion = ref('ph');

const pricingData = {
  ph: [
    {
      name: 'Starter',
      duration: '3 Months',
      price: '₱2,999.99',
      currency: '/ 3 months',
      popular: false,
      features: ['Up to 20 job posts', 'AI candidate screening', 'Analytics dashboard', 'Email & chat support'],
    },
    {
      name: 'Growth',
      duration: '6 Months',
      price: '₱4,999.99',
      currency: '/ 6 months',
      popular: true,
      features: ['Up to 50 job posts', 'AI candidate screening', 'Advanced analytics', 'Priority support', 'Talent search'],
    },
    {
      name: 'Pro',
      duration: '1 Year',
      price: '₱7,999.99',
      currency: '/ year',
      popular: false,
      features: ['Unlimited job posts', 'AI candidate screening', 'Advanced analytics', 'Dedicated support', 'Custom branding'],
    },
  ],
  intl: [
    {
      name: 'Starter',
      duration: '3 Months',
      price: '$49.99',
      currency: '/ 3 months',
      popular: false,
      features: ['Up to 20 job posts', 'AI candidate screening', 'Analytics dashboard', 'Email & chat support'],
    },
    {
      name: 'Growth',
      duration: '6 Months',
      price: '$79.99',
      currency: '/ 6 months',
      popular: true,
      features: ['Up to 50 job posts', 'AI candidate screening', 'Advanced analytics', 'Priority support', 'Talent search'],
    },
    {
      name: 'Pro',
      duration: '1 Year',
      price: '$129.99',
      currency: '/ year',
      popular: false,
      features: ['Unlimited job posts', 'AI candidate screening', 'Advanced analytics', 'Dedicated support', 'Custom branding'],
    },
  ],
};

const activePlans = computed(() => pricingData[pricingRegion.value]);

// ── FAQ ──────────────────────────────────────────────────────────────
const openFaq = ref(null);
const faqs = [
  {
    q: 'Is there a free trial?',
    a: 'Yes — every new employer account gets a 7-day free trial with up to 5 job posts. No credit card required to start.',
  },
  {
    q: 'How does AI screening work?',
    a: 'When a candidate applies, our AI analyzes their profile, resume, and cover letter against your job requirements and generates a match score with a summary. You can review it before deciding who to shortlist.',
  },
  {
    q: 'Can I cancel my subscription?',
    a: 'Yes, you can cancel at any time from your billing settings. Your access continues until the end of the current billing period.',
  },
  {
    q: 'Is candidate contact information visible immediately?',
    a: 'Messaging, interviews, and hiring workflows are unlocked with a subscription. Full contact details and resume downloads require a separate one-time document access payment per candidate — this protects candidate privacy.',
  },
  {
    q: 'What currencies are supported?',
    a: 'Philippine employers are billed in PHP (₱). International employers are billed in USD ($). Currency is set automatically based on your billing country.',
  },
  {
    q: 'Is the platform HIPAA compliant?',
    a: 'We follow healthcare data best practices including encrypted storage, access controls, and audit logging. For specific compliance requirements, please contact our team.',
  },
];

// ── Trusted orgs ─────────────────────────────────────────────────────
const trustedOrgs = [
  { name: 'St. Luke\'s Medical',  color: '#2563eb', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
  { name: 'Makati Medical',       color: '#7c3aed', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
  { name: 'Philippine Heart',     color: '#dc2626', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
  { name: 'HealthFirst Agency',   color: '#059669', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
  { name: 'MedStaff PH',          color: '#d97706', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
  { name: 'ClinCare Network',     color: '#0891b2', icon: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9' },
];

// ── Security badges ───────────────────────────────────────────────────
const securityBadges = [
  { label: 'HIPAA Compliant',    icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
  { label: 'SSL Encrypted',      icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
  { label: 'Data Protected',     icon: 'M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
  { label: '99.9% Uptime',       icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
];
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
    alert('Failed to send message. Please try again later.');
  } finally {
    contactLoading.value = false;
  }
};

onMounted(() => {
  initDarkMode();
  showAnnouncement.value = true;

  // Scroll listener for sticky CTA
  window.addEventListener('scroll', handleScroll, { passive: true });

  // Intersection observer for reveal animations
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) entry.target.classList.add('reveal-visible');
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  // Stats counter — trigger when stats section enters viewport
  const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        stats.forEach(s => animateCounter(s));
        statsObserver.disconnect();
      }
    });
  }, { threshold: 0.3 });
  if (statsSection.value) statsObserver.observe(statsSection.value);

  // Demo highlight cycle
  setInterval(() => {
    demoHighlight.value = (demoHighlight.value + 1) % demoActivity.length;
  }, 2000);

  // Load real jobs for ticker
  loadTickerJobs();
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll);
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
.faq-expand-enter-active, .faq-expand-leave-active { transition: all 0.3s ease; overflow: hidden; }
.faq-expand-enter-from, .faq-expand-leave-to { opacity: 0; max-height: 0; }
.faq-expand-enter-to, .faq-expand-leave-from { opacity: 1; max-height: 300px; }
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(100%); }
.ticker-track {
  animation: ticker 30s linear infinite;
  width: max-content;
}
.ticker-track:hover { animation-play-state: paused; }
@keyframes ticker {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}
.demo-bar { transition: width 1.5s cubic-bezier(0.22, 1, 0.36, 1); }
.demo-highlight { background: rgba(59,130,246,0.06); border-radius: 0.75rem; }
::-webkit-scrollbar { width: 10px; }
::-webkit-scrollbar-track { background: #f8fafc; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 5px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>
