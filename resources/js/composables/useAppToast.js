/**
 * Global toast helper — dispatches a custom event that AppLayout listens to.
 * Usage: import { toast } from '@/composables/useAppToast'
 *        toast.success('Saved!') / toast.error('Failed') / toast.info('Note')
 */
function emit(severity, summary, detail = '', life = 3500) {
  window.dispatchEvent(new CustomEvent('app:toast', {
    detail: { severity, summary, detail, life }
  }))
}

export const toast = {
  success: (summary, detail, life) => emit('success', summary, detail, life),
  error:   (summary, detail, life) => emit('error',   summary, detail, life ?? 5000),
  info:    (summary, detail, life) => emit('info',    summary, detail, life),
  warn:    (summary, detail, life) => emit('warn',    summary, detail, life),
}
