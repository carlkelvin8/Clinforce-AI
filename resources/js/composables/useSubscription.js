import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";

const _hasSubscription = ref(null); // null = unknown, true/false = known

export function useSubscription() {
  const router = useRouter();

  function loadFromUser() {
    try {
      const raw = localStorage.getItem("auth_user");
      const user = raw ? JSON.parse(raw) : null;
      // If the user object carries subscription info use it, otherwise unknown
      if (user?.has_subscription !== undefined) {
        _hasSubscription.value = !!user.has_subscription;
      }
    } catch { /* ignore */ }
  }

  loadFromUser();

  const isSubscribed = computed(() => _hasSubscription.value === true);

  /**
   * Call this before a gated action. Returns true if allowed, false if blocked.
   * Shows a SweetAlert prompt to redirect to billing if not subscribed.
   */
  async function requireSubscription(featureLabel = "this feature") {
    // If we don't know yet, optimistically allow (backend will gate anyway)
    if (_hasSubscription.value === null) return true;
    if (_hasSubscription.value === true) return true;

    const result = await Swal.fire({
      icon: "warning",
      title: "Subscription Required",
      html: `<p class="text-gray-700">You need an active subscription to use <strong>${featureLabel}</strong>.</p>`,
      confirmButtonText: "View Plans",
      showCancelButton: true,
      cancelButtonText: "Not now",
      confirmButtonColor: "#2563eb",
    });

    if (result.isConfirmed) {
      router.push({ name: "employer.billing" });
    }

    return false;
  }

  /** Call after login / subscription change to refresh the cached value. */
  function setSubscribed(val) {
    _hasSubscription.value = !!val;
  }

  return { isSubscribed, requireSubscription, setSubscribed, loadFromUser };
}
