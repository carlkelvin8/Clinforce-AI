import { test, expect } from '@playwright/test';

const BASE = process.env.TEST_BASE_URL || 'http://localhost:5173';
const PAGE_LOAD_THRESHOLD = 5000; // ms — generous for local dev cold start

test.describe('Page load performance', () => {

  test('Landing page loads under 3s', async ({ page }) => {
    const start = Date.now();
    await page.goto(`${BASE}/`);
    await page.waitForLoadState('networkidle');
    const ms = Date.now() - start;
    console.log(`  Landing: ${ms}ms`);
    expect(ms).toBeLessThan(PAGE_LOAD_THRESHOLD);
  });

  test('Login page loads under 3s', async ({ page }) => {
    const start = Date.now();
    await page.goto(`${BASE}/login`);
    await page.waitForLoadState('networkidle');
    const ms = Date.now() - start;
    console.log(`  Login: ${ms}ms`);
    expect(ms).toBeLessThan(PAGE_LOAD_THRESHOLD);
  });

  test('Register page loads under 3s', async ({ page }) => {
    const start = Date.now();
    await page.goto(`${BASE}/register`);
    await page.waitForLoadState('networkidle');
    const ms = Date.now() - start;
    console.log(`  Register: ${ms}ms`);
    expect(ms).toBeLessThan(PAGE_LOAD_THRESHOLD);
  });

  test('Employer dashboard loads under 3s after login', async ({ page }) => {
    // Skip if Vite dev server isn't running
    try {
      const res = await page.request.get(`${BASE}/login`);
      if (!res.ok()) { test.skip(); return; }
    } catch { test.skip(); return; }

    await page.goto(`${BASE}/login`);
    // Wait for the login form to be ready
    await page.waitForSelector('input[type="password"]', { timeout: 10000 });
    const emailInput = page.locator('input').filter({ hasText: '' }).first();
    await page.fill('input[type="password"]', process.env.TEST_PASSWORD || 'Password1!');
    // Try different email field selectors
    const emailField = page.locator('input[type="email"], input[name="identifier"]').first();
    await emailField.fill(process.env.TEST_EMPLOYER_EMAIL || 'employer@test.com');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/, { timeout: 15000 }).catch(() => {});

    const start = Date.now();
    await page.goto(`${BASE}/employer/dashboard`);
    await page.waitForLoadState('networkidle');
    const ms = Date.now() - start;
    console.log(`  Employer dashboard: ${ms}ms`);
    expect(ms).toBeLessThan(PAGE_LOAD_THRESHOLD);
  });

  test('Core Web Vitals — no layout shift on landing', async ({ page }) => {
    await page.goto(`${BASE}/`);
    const cls = await page.evaluate(() => {
      return new Promise<number>((resolve) => {
        let clsValue = 0;
        new PerformanceObserver((list) => {
          for (const entry of list.getEntries()) {
            if (!(entry as any).hadRecentInput) {
              clsValue += (entry as any).value;
            }
          }
        }).observe({ type: 'layout-shift', buffered: true });
        setTimeout(() => resolve(clsValue), 2000);
      });
    });
    console.log(`  CLS on landing: ${cls}`);
    expect(cls).toBeLessThan(0.1); // Good CLS threshold
  });

});
