import { test, expect } from '@playwright/test';

const BASE = process.env.TEST_BASE_URL || 'http://localhost:5173';

test.describe('Login page E2E', () => {

  test('Login page loads', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await expect(page.locator('input[type="password"], input[name="password"]').first()).toBeVisible({ timeout: 10000 });
  });

  test('Shows error on wrong credentials', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.fill('input[type="email"], input[name="identifier"], input[placeholder*="email" i]', 'wrong@test.com');
    await page.fill('input[type="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=/invalid|incorrect|failed/i').first()).toBeVisible({ timeout: 8000 });
  });

  test('Successful login redirects to dashboard', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.fill('input[type="email"], input[name="identifier"], input[placeholder*="email" i]',
      process.env.TEST_EMPLOYER_EMAIL || 'employer@test.com');
    await page.fill('input[type="password"]', process.env.TEST_PASSWORD || 'Password1!');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|employer/, { timeout: 10000 });
    expect(page.url()).toMatch(/dashboard|employer/);
  });

});
