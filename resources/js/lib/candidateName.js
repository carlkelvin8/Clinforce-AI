/**
 * Format a candidate name showing first name + last name initial only.
 * e.g. "Carl Manahan" → "Carl M."
 *
 * Accepts various data shapes from the API.
 */
export function formatCandidateName(source, fallback = 'Candidate') {
  // source can be: application row, applicant profile object, or user object
  const profile =
    source?.applicant_profile ||
    source?.applicantProfile ||
    source?.candidate_profile ||
    source?.candidateProfile ||
    (source?.applicant?.applicant_profile) ||
    (source?.applicant?.applicantProfile) ||
    null

  const first = (profile?.first_name || source?.first_name || '').trim()
  const last  = (profile?.last_name  || source?.last_name  || '').trim()

  if (first) {
    return last ? `${first} ${last[0].toUpperCase()}.` : first
  }

  // fallback chains
  const display =
    profile?.public_display_name ||
    source?.public_display_name ||
    source?.name ||
    source?.full_name ||
    null

  if (display) {
    const parts = display.trim().split(/\s+/)
    if (parts.length >= 2) {
      return `${parts[0]} ${parts[parts.length - 1][0].toUpperCase()}.`
    }
    return display
  }

  return fallback
}

/**
 * Build name from an application row (has nested applicant/user).
 */
export function formatApplicationName(app, fallback = 'Candidate') {
  const u = app?.applicant || app?.user || null
  const p = u?.applicant_profile || u?.applicantProfile || null

  const first = (p?.first_name || '').trim()
  const last  = (p?.last_name  || '').trim()

  if (first) {
    return last ? `${first} ${last[0].toUpperCase()}.` : first
  }

  return (
    p?.public_display_name ||
    u?.name ||
    u?.full_name ||
    (app?.applicant_user_id ? `Candidate #${app.applicant_user_id}` : fallback)
  )
}
