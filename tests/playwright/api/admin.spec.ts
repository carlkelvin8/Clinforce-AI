import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

let adminToken: string;
let employerToken: string;

test.beforeAll(async ({ request }) => {
  [adminToken, employerToken] = await Promise.all([
    loginAs(request, 'admin'),
    loginAs(request, 'employer'),
  ]);
});

test.describe('Admin endpoints', () => {

  test('GET /admin/stats — admin only', async ({ request }) => {
    const res = await request.get(`${API}/admin/stats`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(data?.total_users).toBeDefined();
  });

  test('GET /admin/stats — non-admin returns 403', async ({ request }) => {
    const res = await request.get(`${API}/admin/stats`, { headers: authHeaders(employerToken) });
    expect(res.status()).toBe(403);
  });

  test('GET /admin/users — returns paginated users', async ({ request }) => {
    const res = await request.get(`${API}/admin/users`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(data?.data || data).toBeTruthy();
  });

  test('GET /admin/users?q=test — search works', async ({ request }) => {
    const res = await request.get(`${API}/admin/users?q=test`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/jobs — returns jobs list', async ({ request }) => {
    const res = await request.get(`${API}/admin/jobs`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/subscriptions — returns subscriptions', async ({ request }) => {
    const res = await request.get(`${API}/admin/subscriptions`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/analytics — returns analytics data', async ({ request }) => {
    const res = await request.get(`${API}/admin/analytics`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(data?.revenue).toBeDefined();
  });

  test('GET /admin/mrr — returns MRR stats', async ({ request }) => {
    const res = await request.get(`${API}/admin/mrr`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/system-status — returns system info', async ({ request }) => {
    const res = await request.get(`${API}/admin/system-status`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(data?.database?.ok).toBe(true);
  });

  test('GET /admin/queue-monitor — returns failed jobs', async ({ request }) => {
    const res = await request.get(`${API}/admin/queue-monitor`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/feature-flags — returns flags', async ({ request }) => {
    const res = await request.get(`${API}/admin/feature-flags`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/maintenance — returns maintenance status', async ({ request }) => {
    const res = await request.get(`${API}/admin/maintenance`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/funnel — returns application funnel', async ({ request }) => {
    const res = await request.get(`${API}/admin/funnel`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/db-table-sizes — returns table sizes', async ({ request }) => {
    const res = await request.get(`${API}/admin/db-table-sizes`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
  });

  test('GET /admin/search?q=test — global search', async ({ request }) => {
    const res = await request.get(`${API}/admin/search?q=test`, { headers: authHeaders(adminToken) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(data?.users).toBeDefined();
  });

});
