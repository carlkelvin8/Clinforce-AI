import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

let employerToken: string;
let applicantToken: string;

test.beforeAll(async ({ request }) => {
  [employerToken, applicantToken] = await Promise.all([
    loginAs(request, 'employer'),
    loginAs(request, 'applicant'),
  ]);
});

test.describe('Applications endpoints', () => {

  test('GET /applications — employer sees own applications', async ({ request }) => {
    const res = await request.get(`${API}/applications`, {
      headers: authHeaders(employerToken),
      params: { scope: 'owned' },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body).toBeTruthy();
  });

  test('GET /applications — applicant sees own applications', async ({ request }) => {
    const res = await request.get(`${API}/applications`, {
      headers: authHeaders(applicantToken),
      params: { scope: 'mine' },
    });
    expect(res.status()).toBe(200);
  });

  test('GET /applications — unauthenticated returns 401', async ({ request }) => {
    const res = await request.get(`${API}/applications`);
    expect([401, 403]).toContain(res.status());
  });

  test('GET /applications/:id — returns 404 for nonexistent', async ({ request }) => {
    const res = await request.get(`${API}/applications/999999999`, {
      headers: authHeaders(employerToken),
    });
    expect([404, 403]).toContain(res.status());
  });

  test('POST /applications/bulk-action — requires auth', async ({ request }) => {
    const res = await request.post(`${API}/applications/bulk-action`, {
      data: { application_ids: [], action: 'shortlist' },
    });
    expect([401, 403]).toContain(res.status());
  });

});
