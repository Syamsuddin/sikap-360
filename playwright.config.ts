import { defineConfig, devices } from '@playwright/test';
// QA E2E SIKAP 360. Server: DB_DATABASE=sikap360_test APP_URL=http://127.0.0.1:8765 php -S 127.0.0.1:8765 -t public
export default defineConfig({
  testDir: './e2e', timeout: 30000, retries: 0, workers: 1,
  use: { baseURL: process.env.QA_BASE ?? 'http://127.0.0.1:8765' },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'hp-android', use: { ...devices['Pixel 5'] } },
  ],
});
