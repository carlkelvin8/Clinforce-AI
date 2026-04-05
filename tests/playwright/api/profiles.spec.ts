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

test.describe('Profile endpoints', () => {

  test('GET /me — employer profile', async ({ request }) => {
    const res = await request.get(`${API}/me`, { headers: authHeaders(employerToken) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(data?.role).toBeTruthy();
  });

  test('GET /me — applicant profile', async ({ request }) => {
    const res = await request.get(`${API}/me`, { headers: authHeaders(applicantToken) });
    expect(res.status()).toBe(200);
  });

  test('PUT /me/applicant — update applicant profile', async ({ request }) => {
    const res = await request.put(`${API}/me/applicant`, {
      headers: authHeaders(applicantToken),
      data: { headline: 'Registered Nurse | Playwright Test' },
    });
    expect([200, 422]).toContain(res.status());
  });

  test('PUT /me/employer — update employer profile', async ({ request }) => {
    const res = await request.put(`${API}/me/employer`, {
      headers: authHeaders(employerToken),
      data: { business_name: 'Test Hospital PW' },
    });
    expect([200, 422]).toContain(res.status());
  });

  test('GET /me — unauthenticated returns 401', async ({ request }) => {
    const res = await request.get(`${API}/me`);
    expect([401, 403]).toContain(res.status());
  });

});
