// resources/js/router/index.js
import { createRouter, createWebHistory } from "vue-router";
import api from "@/lib/api";

// --- Auth pages ---
const Login = () => import("@/Pages/Auth/Login.vue");
const Register = () => import("@/Pages/Auth/Register.vue");
const ForgotPassword = () => import("@/Pages/Auth/ForgotPassword.vue");
const ResetPassword = () => import("@/Pages/Auth/ResetPassword.vue");
const VerifySuccess = () => import("@/Pages/Auth/VerifySuccess.vue");
const SocialCallback = () => import("@/Pages/Auth/SocialCallback.vue");
const SelectRole = () => import("@/Pages/Auth/SelectRole.vue");
const Landing = () => import("@/Pages/Landing.vue");
const Privacy = () => import("@/Pages/Privacy.vue");
const Terms = () => import("@/Pages/Terms.vue");
const NotFound = () => import("@/Pages/NotFound.vue");

// --- Staff pages ---
const EmployerDashboard = () => import("@/Pages/Employer/Dashboard.vue");
const EmployerJobs = () => import("@/Pages/Employer/Jobs/JobsIndex.vue");
const EmployerJobForm = () => import("@/Pages/Employer/Jobs/JobsForm.vue");
const EmployerJobView = () => import("@/Pages/Employer/Jobs/JobsDetails.vue");
const EmployerTalentSearch = () => import("@/Pages/Employer/TalentSearch.vue");
const EmployerMessages = () => import("@/Pages/Employer/Messages.vue");
const EmployerInterviews = () => import("@/Pages/Employer/Interviews.vue");
const EmployerInvitations = () => import("@/Pages/Employer/Invitations.vue");
const EmployerBilling = () => import("@/Pages/Employer/Billing.vue");
const EmployerPaymentMethod = () => import("@/Pages/Billing/PaymentMethod.vue");
const EmployerCandidateProfile = () => import("@/Pages/Employer/CandidateProfile.vue");
const EmployerSettings = () => import("@/Pages/Employer/Settings.vue");

// --- Applicants (staff side) ---
const ApplicantsList = () => import("@/Applicants/ApplicantsList.vue");
const ApplicantProfile = () => import("@/Applicants/ApplicantProfile.vue");

// --- Candidate pages ---
const CandidateDashboard = () => import("@/Pages/Candidate/Dashboard.vue");
const CandidateJobs = () => import("@/Pages/Candidate/Jobs.vue");
const CandidateJobView = () => import("@/Pages/Candidate/JobView.vue");
const CandidateApplications = () => import("@/Pages/Candidate/Applications.vue");
const CandidateApplicationView = () => import("@/Pages/Candidate/ApplicationView.vue");
const CandidateMyApplications = () => import("@/Pages/Candidate/MyApplications.vue");
const CandidateInterviews = () => import("@/Pages/Candidate/Interviews.vue");
const CandidateInvitations = () => import("@/Pages/Candidate/Invitations.vue");
const CandidateProfile = () => import("@/Pages/Candidate/Profile.vue");
const CandidateMessages = () => import("@/Pages/Candidate/Messages.vue");
const CandidateSettings = () => import("@/Pages/Candidate/Settings.vue");

// ---------- helpers ----------
function safeParse(raw) {
  try {
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function getToken() {
  return localStorage.getItem("auth_token") || localStorage.getItem("CLINFORCE_TOKEN");
}

function getUser() {
  return safeParse(localStorage.getItem("auth_user"));
}

function setUser(user) {
  if (user) localStorage.setItem("auth_user", JSON.stringify(user));
  else localStorage.removeItem("auth_user");
}

function clearAuth() {
  localStorage.removeItem("auth_token");
  localStorage.removeItem("CLINFORCE_TOKEN");
  localStorage.removeItem("auth_user");
  window.dispatchEvent(new Event("auth:changed"));
}

function isStaff(user) {
  return user?.role === "admin" || user?.role === "employer" || user?.role === "agency";
}

function homeRouteFor(user) {
  return isStaff(user) ? { name: "employer.dashboard" } : { name: "candidate.dashboard" };
}

// ---------- auth bootstrap ----------
const authState = { inflight: null };

async function ensureUserLoaded() {
  const token = getToken();
  if (!token) return null;

  const cached = getUser();
  if (cached) return cached;

  if (authState.inflight) return authState.inflight;

  authState.inflight = (async () => {
    try {
      const res = await api.get("/auth/me");
      const user = res?.data?.data?.user || res?.data?.user || res?.data?.data || null;
      if (user) setUser(user);
      window.dispatchEvent(new Event("auth:changed"));
      return user;
    } catch (e) {
      const status = e?.response?.status;
      if (status === 401 || status === 419) clearAuth();
      return null;
    } finally {
      authState.inflight = null;
    }
  })();

  return authState.inflight;
}

// ---------- routes ----------
const staffMeta = { requiresAuth: true, roles: ["admin", "employer", "agency"] };
const candidateMeta = { requiresAuth: true, roles: ["applicant"] };

const routes = [
  { path: "/", name: "landing", component: Landing },

  // Auth
  { path: "/login", name: "auth.login", component: Login, meta: { guestOnly: true } },
  { path: "/register", name: "auth.register", component: Register, meta: { guestOnly: true } },
  { path: "/forgot-password", name: "auth.forgot-password", component: ForgotPassword, meta: { guestOnly: true } },
  { path: "/password/reset/:token", name: "auth.reset-password", component: ResetPassword, meta: { guestOnly: true } },
  { path: "/privacy", name: "privacy", component: Privacy },
  { path: "/terms", name: "terms", component: Terms },
  { path: "/verify/success", name: "auth.verify.success", component: VerifySuccess, meta: { guestOnly: true } },
  { path: "/auth/social/callback", name: "auth.social.callback", component: SocialCallback, meta: { guestOnly: true } },
  { path: "/auth/select-role", name: "auth.select-role", component: SelectRole, meta: { guestOnly: true } },

  // Staff dashboard
  { path: "/employer/dashboard", name: "employer.dashboard", component: EmployerDashboard, meta: staffMeta },

  // Jobs
  { path: "/employer/jobs", name: "employer.jobs", component: EmployerJobs, meta: staffMeta },
  { path: "/employer/jobs/create", name: "employer.jobs.create", component: EmployerJobForm, meta: staffMeta },
  { path: "/employer/jobs/new", name: "employer.jobs.new", redirect: { name: "employer.jobs.create" }, meta: staffMeta },
  { path: "/employer/jobs/:id", name: "employer.jobs.view", component: EmployerJobView, props: true, meta: staffMeta },
  { path: "/employer/jobs/:id/edit", name: "employer.jobs.edit", component: EmployerJobForm, props: true, meta: staffMeta },

  // Other staff pages
  { path: "/employer/talent-search", name: "employer.talentsearch", component: EmployerTalentSearch, meta: staffMeta },
  { path: "/employer/messages", name: "employer.messages", component: EmployerMessages, meta: staffMeta },
  { path: "/employer/interviews", name: "employer.interviews", component: EmployerInterviews, meta: staffMeta },
  { path: "/employer/invitations", name: "employer.invitations", component: EmployerInvitations, meta: staffMeta },
  { path: "/employer/billing", name: "employer.billing", component: EmployerBilling, meta: staffMeta },
  { path: "/employer/payment-method", name: "employer.payment-method", component: EmployerPaymentMethod, meta: staffMeta },
  { path: "/employer/settings", name: "employer.settings", component: EmployerSettings, meta: staffMeta },
  { path: "/employer/candidates/:id", name: "employer.candidates.view", component: EmployerCandidateProfile, props: true, meta: staffMeta },

  // Applicants (staff side)
  { path: "/applicants", name: "applicants.list", component: ApplicantsList, meta: staffMeta },
  { path: "/applicants/:id", name: "applicants.view", component: ApplicantProfile, props: true, meta: staffMeta },

  // Alias
  { path: "/employer/applicants", name: "employer.applicants", redirect: { name: "applicants.list" }, meta: staffMeta },
  {
    path: "/employer/applicants/:id",
    name: "employer.applicants.view",
    redirect: (to) => ({ name: "applicants.view", params: { id: to.params.id } }),
    meta: staffMeta,
  },

  // Candidate
  { path: "/candidate/dashboard", name: "candidate.dashboard", component: CandidateDashboard, meta: candidateMeta },
  { path: "/candidate/jobs", name: "candidate.jobs", component: CandidateJobs, meta: candidateMeta },
  { path: "/candidate/jobs/:id", name: "candidate.jobs.view", component: CandidateJobView, props: true, meta: candidateMeta },

  { path: "/candidate/my-applications", name: "candidate.myapplications", component: CandidateMyApplications, meta: candidateMeta },

  { path: "/candidate/applications", name: "candidate.applications", component: CandidateApplications, meta: candidateMeta },
  { path: "/candidate/applications/:id", name: "candidate.applications.view", component: CandidateApplicationView, props: true, meta: candidateMeta },

  { path: "/candidate/interviews", name: "candidate.interviews", component: CandidateInterviews, meta: candidateMeta },
  { path: "/candidate/invitations", name: "candidate.invitations", component: CandidateInvitations, meta: candidateMeta },
  { path: "/candidate/profile", name: "candidate.profile", component: CandidateProfile, meta: candidateMeta },
  { path: "/candidate/messages", name: "candidate.messages", component: CandidateMessages, meta: candidateMeta },
  { path: "/candidate/settings", name: "candidate.settings", component: CandidateSettings, meta: candidateMeta },

  { path: "/:pathMatch(.*)*", name: "notfound", component: NotFound },
];

const router = createRouter({
  // ✅ important for Vite base path / subfolder deployments
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

// ---------- guard ----------
router.beforeEach(async (to) => {
  const token = getToken();

  if (to.meta?.requiresAuth && !token) {
    return { name: "auth.login", query: { redirect: to.fullPath } };
  }

  const user = token ? await ensureUserLoaded() : null;

  if (to.meta?.guestOnly && token) {
    return homeRouteFor(user);
  }

  const roles = to.meta?.roles;
  if (to.meta?.requiresAuth && roles?.length) {
    if (!user) return true;
    if (!roles.includes(user.role)) return homeRouteFor(user);
  }

  return true;
});

export default router;
