import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

let token: string;

test.beforeAll(async ({ request }) => {
  token = await loginAs(request, 'employer');
});

test.describe('Notifications endpoints', () => {

  test('GET /notifications — returns paginated list', async ({ request }) => {
    const res = await request.get(`${API}/notifications`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body).toBeTruthy();
  });

  test('GET /notifications/unread-count — returns count', async ({ request }) => {
    const res = await request.get(`${API}/notifications/unread-count`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
    const body = await res.json();
    const data = body?.data || body;
    expect(typeof data?.count).toBe('number');
  });

  test('POST /notifications/read-all — marks all read', async ({ request }) => {
    const res = await request.post(`${API}/notifications/read-all`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
  });

  test('GET /notifications/preferences — returns preferences', async ({ request }) => {
    const res = await request.get(`${API}/notifications/preferences`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
  });

  test('GET /notifications — unauthenticated returns 401', async ({ request }) => {
    const res = await request.get(`${API}/notifications`);
    expect([401, 403]).toContain(res.status());
  });

});
