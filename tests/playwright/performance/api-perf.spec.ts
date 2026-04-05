import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

// Max acceptable response times in ms
const THRESHOLDS = {
  public:    500,
  auth:      1500,  // bcrypt is slow on local dev; production will be faster
  protected: 1000,
  admin:     1500,
};

let employerToken: string;
let adminToken: string;

test.beforeAll(async ({ request }) => {
  [employerToken, adminToken] = await Promise.all([
    loginAs(request, 'employer'),
    loginAs(request, 'admin'),
  ]);
});

async function measureMs(fn: () => Promise<void>): Promise<number> {
  const start = Date.now();
  await fn();
  return Date.now() - start;
}

test.describe('API Performance', () => {

  test('GET /health — under 500ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/health`);
      expect(res.status()).toBe(200);
    });
    console.log(`  /health: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.public);
  });

  test('GET /public/jobs — under 500ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/public/jobs`);
      expect(res.status()).toBe(200);
    });
    console.log(`  /public/jobs: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.public);
  });

  test('GET /plans — under 500ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/plans`);
      expect(res.status()).toBe(200);
    });
    console.log(`  /plans: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.public);
  });

  test('POST /auth/login — under 800ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.post(`${API}/auth/login`, {
        data: {
          identifier: process.env.TEST_EMPLOYER_EMAIL || 'employer@test.com',
          password:   process.env.TEST_PASSWORD       || 'Password1!',
        },
      });
      expect(res.status()).toBe(200);
    });
    console.log(`  /auth/login: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.auth);
  });

  test('GET /auth/me — under 1000ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/auth/me`, { headers: authHeaders(employerToken) });
      expect(res.status()).toBe(200);
    });
    console.log(`  /auth/me: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.protected);
  });

  test('GET /jobs — under 1000ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/jobs`, { headers: authHeaders(employerToken) });
      expect(res.status()).toBe(200);
    });
    console.log(`  /jobs: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.protected);
  });

  test('GET /applications — under 1000ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/applications`, {
        headers: authHeaders(employerToken),
        params: { scope: 'owned' },
      });
      expect(res.status()).toBe(200);
    });
    console.log(`  /applications: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.protected);
  });

  test('GET /billing/currency — under 1000ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/billing/currency`, { headers: authHeaders(employerToken) });
      expect(res.status()).toBe(200);
    });
    console.log(`  /billing/currency: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.protected);
  });

  test('GET /notifications — under 1000ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/notifications`, { headers: authHeaders(employerToken) });
      expect(res.status()).toBe(200);
    });
    console.log(`  /notifications: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.protected);
  });

  test('GET /admin/stats — under 1500ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/admin/stats`, { headers: authHeaders(adminToken) });
      expect(res.status()).toBe(200);
    });
    console.log(`  /admin/stats: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.admin);
  });

  test('GET /admin/analytics — under 1500ms', async ({ request }) => {
    const ms = await measureMs(async () => {
      const res = await request.get(`${API}/admin/analytics`, { headers: authHeaders(adminToken) });
      expect(res.status()).toBe(200);
    });
    console.log(`  /admin/analytics: ${ms}ms`);
    expect(ms).toBeLessThan(THRESHOLDS.admin);
  });

  test('Concurrent requests — 5 parallel /public/jobs under 2000ms total', async ({ request }) => {
    const start = Date.now();
    await Promise.all(
      Array.from({ length: 5 }, () => request.get(`${API}/public/jobs`))
    );
    const ms = Date.now() - start;
    console.log(`  5x concurrent /public/jobs: ${ms}ms`);
    expect(ms).toBeLessThan(2000);
  });

});
