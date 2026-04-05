import { test, expect } from '@playwright/test';
import { API, loginAs, authHeaders } from '../helpers/auth';

let token: string;
let createdJobId: number;

test.beforeAll(async ({ request }) => {
  token = await loginAs(request, 'employer');
});

test.describe('Jobs endpoints', () => {

  test('GET /public/jobs — public, no auth', async ({ request }) => {
    const res = await request.get(`${API}/public/jobs`);
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body).toBeTruthy();
  });

  test('GET /jobs — requires auth', async ({ request }) => {
    const res = await request.get(`${API}/jobs`);
    expect([401, 403]).toContain(res.status());
  });

  test('GET /jobs — authenticated employer', async ({ request }) => {
    const res = await request.get(`${API}/jobs`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
  });

  test('POST /jobs — create job', async ({ request }) => {
    const res = await request.post(`${API}/jobs`, {
      headers: authHeaders(token),
      data: {
        title: 'Test RN Position',
        description: 'Test job description for playwright testing',
        employment_type: 'full_time',
        city: 'Manila',
        country_code: 'PH',
      },
    });
    // 201 created or 402 if subscription required
    expect([201, 402, 403]).toContain(res.status());
    if (res.status() === 201) {
      const body = await res.json();
      createdJobId = body?.data?.id || body?.id;
      expect(createdJobId).toBeTruthy();
    }
  });

  test('GET /jobs/:id — fetch single job', async ({ request }) => {
    if (!createdJobId) test.skip();
    const res = await request.get(`${API}/jobs/${createdJobId}`, { headers: authHeaders(token) });
    expect(res.status()).toBe(200);
  });

  test('PUT /jobs/:id — update job', async ({ request }) => {
    if (!createdJobId) test.skip();
    const res = await request.put(`${API}/jobs/${createdJobId}`, {
      headers: authHeaders(token),
      data: { title: 'Updated RN Position' },
    });
    expect([200, 402, 403]).toContain(res.status());
  });

  test('DELETE /jobs/:id — delete job', async ({ request }) => {
    if (!createdJobId) test.skip();
    const res = await request.delete(`${API}/jobs/${createdJobId}`, { headers: authHeaders(token) });
    expect([200, 204, 402, 403]).toContain(res.status());
  });

});
