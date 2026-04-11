import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

let token: string;

test.beforeAll(async ({ request }) => {
  token = await loginAs(request, 'employer');
});

test.describe('Interviews endpoints', () => {

  test('GET /interviews — returns list or 402 if trial/subscription required', async ({ request }) => {
    const res = await request.get(`${API}/interviews`, { headers: authHeaders(token) });
    // 200 = active trial/subscription, 402 = trial expired or no subscription
    expect([200, 402, 403]).toContain(res.status());
  });

  test('GET /interviews — unauthenticated returns 401', async ({ request }) => {
    const res = await request.get(`${API}/interviews`);
    expect([401, 403]).toContain(res.status());
  });

  test('GET /interviews/999999 — nonexistent returns 404', async ({ request }) => {
    const res = await request.get(`${API}/interviews/999999`, { headers: authHeaders(token) });
    expect([404, 403]).toContain(res.status());
  });

});
