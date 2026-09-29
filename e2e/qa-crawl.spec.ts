// e2e/qa-crawl.spec.ts — crawl menu + 3 alur di browser nyata. Gagal bila ada error JS, console error, atau 5xx.
import { test, expect, Page } from '@playwright/test';
const USER = process.env.QA_USER ?? 'pegawai3@example.test', ADMIN = 'pegawai1@example.test', PASS = process.env.QA_PASS ?? 'SikapDemo2026!';
const PAGES = ['#dashboard', '#assessments', '#results', '#guide', '#employees', '#structure', '#periods', '#opd'];

function watch(page: Page, errs: string[]) {
  page.on('pageerror', e => errs.push(`JS ${page.url()}: ${e.message}`));
  // 401 pada cek sesi awal (bootstrap sebelum login) adalah perilaku normal SPA, bukan error.
  page.on('console', m => { if (m.type() === 'error' && !/status of 401/.test(m.text())) errs.push(`console ${page.url()}: ${m.text()}`); });
  page.on('response', r => { if (r.status() >= 500) errs.push(`${r.status()} ${r.url()}`); });
}
async function login(page: Page, username: string) {
  await page.goto('/#login');
  await page.locator('#login-form input[name=username]').fill(username);
  await page.locator('#login-form input[type=password]').fill(PASS);
  await Promise.all([page.waitForResponse(r => r.url().includes('action=bootstrap') && r.status() === 200), page.locator('#login-form button[type=submit]').click()]);
  await expect(page.locator('#main-content')).toBeVisible();
}

test('crawl: admin membuka semua menu tanpa error JS/console/5xx', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  for (const p of PAGES) { await page.goto('/' + p); await page.waitForLoadState('networkidle'); await expect(page.locator('#main-content')).toContainText(/\S/); }
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('alur ASN: buka tugas, isi 7 indikator, simpan draf, muat ulang tetap tersimpan', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, USER);
  await page.goto('/#assessments'); await page.waitForLoadState('networkidle');
  const link = page.locator('a[href^="#evaluate/"]', { hasText: /Nilai|Lanjutkan/ }).first(); await expect(link).toBeVisible();
  const href = await link.getAttribute('href'); await link.click();
  await expect(page.locator('#evaluation-form')).toBeVisible();
  for (let i = 1; i <= 7; i++) await page.locator(`input[name="indicator-${i}"][value="4"]`).check({ force: true });
  await page.locator('#feedback').fill('E2E draf');
  const [res] = await Promise.all([page.waitForResponse(r => r.url().includes('action=save_draft')), page.locator('button[data-action="save-draft"]').click()]);
  expect(res.status(), await res.text()).toBe(200);
  await page.reload(); await page.goto('/' + href); await page.waitForLoadState('networkidle');
  await expect(page.locator('#evaluation-form')).toBeVisible();
  for (let i = 1; i <= 7; i++) await expect(page.locator(`input[name="indicator-${i}"][value="4"]`)).toBeChecked();
  await expect(page.locator('#feedback')).toHaveValue('E2E draf');
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('alur keluar: setelah logout, tombol Back tidak menampilkan data terlindungi', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, USER);
  await page.goto('/#assessments'); await page.waitForLoadState('networkidle');
  await expect(page.locator('a[href^="#evaluate/"]').first()).toBeVisible();
  await page.locator('details.profile-menu summary').first().click();
  const [out] = await Promise.all([page.waitForResponse(r => r.url().includes('action=logout')), page.locator('[data-action="logout"]').first().click()]);
  expect(out.status()).toBe(200);
  await expect(page.locator('#login-form')).toBeVisible();
  await page.goBack(); await page.waitForLoadState('networkidle');
  await expect(page.locator('#login-form')).toBeVisible();
  await expect(page.locator('a[href^="#evaluate/"]')).toHaveCount(0);
  const boot = await page.evaluate(async () => (await fetch('api.php?action=bootstrap', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })).status);
  expect(boot).toBe(401);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('alur admin: buat periode baru dari formulir', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  await page.goto('/#periods'); await page.waitForLoadState('networkidle');
  await page.locator('[data-action="period-add"]').click();
  await expect(page.locator('#period-form')).toBeVisible();
  // Kedua project (chromium, hp-android) memakai DB yang sama: pilih triwulan 2028 pertama yang belum ada.
  await page.locator('#period-form select[name="year"]').selectOption('2028');
  let expected = '';
  for (const q of ['1', '2', '3', '4']) {
    await page.locator('#period-form select[name="quarter"]').selectOption(q);
    const preview = await page.locator('#period-preview').innerText();
    if (!preview.includes('sudah ada')) { expected = `Triwulan ${['I', 'II', 'III', 'IV'][Number(q) - 1]} 2028`; expect(preview).toContain(expected); break; }
  }
  expect(expected, 'semua triwulan 2028 sudah terpakai').not.toBe('');
  await expect(page.locator('#period-form button[type=submit]')).toBeEnabled();
  const [res] = await Promise.all([page.waitForResponse(r => r.url().includes('action=period_create')), page.locator('#period-form button[type=submit]').click()]);
  expect(res.status(), await res.text()).toBe(200);
  await page.waitForLoadState('networkidle');
  const names: string[] = await page.evaluate(async () => (await (await fetch('api.php?action=bootstrap', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })).json()).periods.map((p: any) => p.name));
  expect(names).toContain(expected);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});
