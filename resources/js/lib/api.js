// resources/js/lib/api.js
import axios from "axios";

// Simple: baseURL is the full API endpoint
// VITE_API_URL should be: https://clinforce-ai.gghsoftware.tech/api
// Then requests like "auth/me" become: https://clinforce-ai.gghsoftware.tech/api/auth/me
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
  timeout: 20000,
  withCredentials: true,
});

const TOKEN_KEYS = ["auth_token", "CLINFORCE_TOKEN"];
const DEVICE_KEY = "CLINFORCE_DEVICE_ID";

export function setToken(token) {
  if (token) {
    localStorage.setItem("auth_token", token);
    localStorage.setItem("CLINFORCE_TOKEN", token);
  } else {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("CLINFORCE_TOKEN");
  }
}

export function getToken() {
  for (const k of TOKEN_KEYS) {
    const v = localStorage.getItem(k);
    if (v) return v;
  }
  return null;
}

export function getDeviceId() {
  let v = localStorage.getItem(DEVICE_KEY);
  if (v) return v;
  if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
    v = crypto.randomUUID();
  } else {
    v = `d_${Math.random().toString(36).slice(2)}_${Date.now()}`;
  }
  localStorage.setItem(DEVICE_KEY, v);
  return v;
}

api.interceptors.request.use((config) => {
  // Ensure URL doesn't have double /api/
  if (config.url && config.url.startsWith("/api/")) {
    config.url = config.url.substring(4); // Remove leading /api/
  }

  // Impersonation header (optional, local dev)
  const asUserId = localStorage.getItem("CLINFORCE_AS_USER_ID");
  if (asUserId) config.headers["X-User-Id"] = asUserId;

  // Bearer token (Sanctum)
  const token = getToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  else delete config.headers.Authorization;

  config.headers["X-Device-Id"] = getDeviceId();

  // ✅ IMPORTANT:
  // If sending FormData, DO NOT set Content-Type manually.
  // Axios will set multipart/form-data with the correct boundary.
  const isFormData = typeof FormData !== "undefined" && config.data instanceof FormData;
  if (isFormData) {
    delete config.headers["Content-Type"];
    delete config.headers["content-type"];
  } else {
    config.headers["Content-Type"] = "application/json";
  }

  return config;
});

api.interceptors.response.use(
  (res) => res,
  (err) => {
    err.__payload = err?.response?.data || null;

    // Global handler for subscription_required 403
    if (err?.response?.status === 403 && err?.response?.data?.error === "subscription_required") {
      const feature = err?.response?.data?.feature || "this feature";
      const message = err?.response?.data?.message || "An active subscription is required.";
      // Dispatch a custom event so any component can react
      window.dispatchEvent(new CustomEvent("subscription:required", { detail: { feature, message } }));
    }

    throw err;
  }
);

export default api;
