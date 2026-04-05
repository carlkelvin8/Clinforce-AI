import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

let token: string;

test.beforeAll(async ({ request }) => {
  token = await loginAs(request, 'employer');
});

test.describe('Interviews endpoints', () => {

  test('GET /interviews — returns list', async ({ request }) => {
    const res = await request.get(`${API}/interviews`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
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
