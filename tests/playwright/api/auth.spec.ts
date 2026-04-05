import { test, expect } from '@playwright/test';
import { API } from '../helpers/auth';

test.describe('Auth endpoints', () => {

  test('GET /health returns ok', async ({ request }) => {
    const res = await request.get(`${API}/health`);
    expect(res.status()).toBe(200);
  });

  test('POST /auth/login — valid credentials', async ({ request }) => {
    const res = await request.post(`${API}/auth/login`, {
      data: {
        identifier: process.env.TEST_EMPLOYER_EMAIL || 'employer@test.com',
        password:   process.env.TEST_PASSWORD       || 'Password1!',
      },
    });
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body?.data?.token).toBeTruthy();
    expect(body?.data?.user?.role).toBe('employer');
  });

  test('POST /auth/login — wrong password returns 401', async ({ request }) => {
    const res = await request.post(`${API}/auth/login`, {
      data: { identifier: 'employer@test.com', password: 'wrongpassword' },
    });
    expect(res.status()).toBe(401);
  });

  test('POST /auth/login — missing fields returns 422', async ({ request }) => {
    const res = await request.post(`${API}/auth/login`, { data: {} });
    expect(res.status()).toBe(422);
  });

  test('GET /auth/me — unauthenticated returns 401', async ({ request }) => {
    const res = await request.get(`${API}/auth/me`);
    expect([401, 403]).toContain(res.status());
  });

  test('GET /auth/me — authenticated returns user', async ({ request }) => {
    const loginRes = await request.post(`${API}/auth/login`, {
      data: {
        identifier: process.env.TEST_EMPLOYER_EMAIL || 'employer@test.com',
        password:   process.env.TEST_PASSWORD       || 'Password1!',
      },
    });
    const { data } = await loginRes.json();
    const meRes = await request.get(`${API}/auth/me`, {
      headers: { Authorization: `Bearer ${data.token}` },
    });
    expect(meRes.status()).toBe(200);
    const me = await meRes.json();
    expect(me?.data?.user?.id).toBeTruthy();
  });

  test('POST /auth/logout — invalidates token', async ({ request }) => {
    const loginRes = await request.post(`${API}/auth/login`, {
      data: {
        identifier: process.env.TEST_EMPLOYER_EMAIL || 'employer@test.com',
        password:   process.env.TEST_PASSWORD       || 'Password1!',
      },
    });
    const { data } = await loginRes.json();
    const logoutRes = await request.post(`${API}/auth/logout`, {
      headers: { Authorization: `Bearer ${data.token}` },
    });
    expect(logoutRes.status()).toBe(200);
  });

});
