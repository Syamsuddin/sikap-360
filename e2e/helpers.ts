// e2e/helpers.ts — akun uji dan langkah bersama untuk spec Playwright SIKAP 360.
import { expect, Page } from '@playwright/test';
export const USER = process.env.QA_USER ?? 'pegawai3@example.test', ADMIN = 'pegawai1@example.test', PASS = process.env.QA_PASS ?? 'SikapDemo2026!';

export function watch(page: Page, errs: string[]) {
  page.on('pageerror', e => errs.push(`JS ${page.url()}: ${e.message}`));
  // 401 pada cek sesi awal (bootstrap sebelum login) adalah perilaku normal SPA, bukan error.
  page.on('console', m => { if (m.type() === 'error' && !/status of 401/.test(m.text())) errs.push(`console ${page.url()}: ${m.text()}`); });
  page.on('response', r => { if (r.status() >= 500) errs.push(`${r.status()} ${r.url()}`); });
}
export async function login(page: Page, username: string) {
  await page.goto('/#login');
  await page.locator('#login-form input[name=username]').fill(username);
  await page.locator('#login-form input[type=password]').fill(PASS);
  await Promise.all([page.waitForResponse(r => r.url().includes('action=bootstrap') && r.status() === 200), page.locator('#login-form button[type=submit]').click()]);
  await expect(page.locator('#main-content')).toBeVisible();
}
// Buka tugas pertama yang belum terkirim lalu isi ketujuh indikator dengan nilai 4; mengembalikan hash formulirnya.
export async function openEvaluation(page: Page) {
  await page.goto('/#assessments'); await page.waitForLoadState('networkidle');
  const link = page.locator('a[href^="#evaluate/"]', { hasText: /Nilai|Lanjutkan/ }).first(); await expect(link).toBeVisible();
  const href = (await link.getAttribute('href'))!; await link.click();
  await expect(page.locator('#evaluation-form')).toBeVisible();
  for (let i = 1; i <= 7; i++) await page.locator(`input[name="indicator-${i}"][value="4"]`).check({ force: true });
  return href;
}
export const bootstrap = (page: Page) => page.evaluate(async () => (await fetch('api.php?action=bootstrap', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })).json());
