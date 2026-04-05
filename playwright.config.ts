import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/playwright',
  fullyParallel: false,
  retries: 1,
  workers: 1,
  reporter: [['html', { outputFolder: 'tests/playwright/report' }], ['list']],
  timeout: 30_000,

  use: {
    baseURL: process.env.TEST_BASE_URL || 'http://localhost:8000',
    extraHTTPHeaders: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
  },

  projects: [
    { name: 'api', testMatch: '**/api/**/*.spec.ts' },
    { name: 'e2e', testMatch: '**/e2e/**/*.spec.ts', use: { ...devices['Desktop Chrome'] } },
    { name: 'performance', testMatch: '**/performance/**/*.spec.ts', use: { ...devices['Desktop Chrome'] } },
  ],
});
