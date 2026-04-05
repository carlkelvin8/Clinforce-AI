import { test, expect } from '@playwright/test';

test.describe('Authentication and Profile', () => {
  test('should login successfully', async ({ page }) => {
    await page.goto('/login');
    
    // Fill in the login form
    await page.fill('#identifier', 'admin@demo.com');
    await page.fill('#password input', 'Password1!');
    
    // Click login button
    await page.click('button[type="submit"]');
    
    // Check if redirected to admin or dashboard
    await expect(page).toHaveURL(/.*(admin|dashboard)/);
  });

  test('should view candidate profile', async ({ page }) => {
    // Login first
    await page.goto('/login');
    await page.fill('#identifier', 'admin@demo.com');
    await page.fill('#password input', 'Password1!');
    await page.click('button[type="submit"]');
    
    // Wait for navigation
    await page.waitForURL(/.*(admin|dashboard)/);

    // Navigate to profile
    await page.goto('/candidate/profile');
    
    // Verify name is displayed
    await expect(page.locator('h1')).toBeVisible();
  });
});
