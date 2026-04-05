import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

let token: string;

test.beforeAll(async ({ request }) => {
  token = await loginAs(request, 'employer');
});

test.describe('Billing & Plans endpoints', () => {

  test('GET /plans — public, returns plan list', async ({ request }) => {
    const res = await request.get(`${API}/plans`);
    expect(res.status()).toBe(200);
    const body = await res.json();
    const plans = body?.data || body;
    expect(Array.isArray(plans)).toBe(true);
    expect(plans.length).toBeGreaterThan(0);
  });

  test('GET /billing/currency — returns currency context', async ({ request }) => {
    const res = await request.get(`${API}/billing/currency`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(data?.currency_code).toBeTruthy();
    expect(data?.converted_prices).toBeTruthy();
  });

  test('GET /billing/countries — returns country list', async ({ request }) => {
    const res = await request.get(`${API}/billing/countries`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
  });

  test('GET /subscriptions — returns subscription list', async ({ request }) => {
    const res = await request.get(`${API}/subscriptions`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
  });

  test('GET /subscriptions/usage — returns usage stats', async ({ request }) => {
    const res = await request.get(`${API}/subscriptions/usage`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
  });

  test('GET /billing/currency — unauthenticated returns 401', async ({ request }) => {
    const res = await request.get(`${API}/billing/currency`);
    expect([401, 403]).toContain(res.status());
  });

});
