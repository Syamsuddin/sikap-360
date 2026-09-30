// e2e/qa-crawl.spec.ts — crawl menu + 3 alur di browser nyata. Gagal bila ada error JS, console error, atau 5xx.
import { test, expect } from '@playwright/test';
import { USER, ADMIN, watch, login, openEvaluation } from './helpers';
const PAGES = ['#dashboard', '#assessments', '#results', '#method', '#guide', '#employees', '#structure', '#periods', '#opd'];

test('crawl: admin membuka semua menu tanpa error JS/console/5xx', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  for (const p of PAGES) { await page.goto('/' + p); await page.waitForLoadState('networkidle'); await expect(page.locator('#main-content')).toContainText(/\S/); }
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('alur ASN: buka tugas, isi 7 indikator, simpan draf, muat ulang tetap tersimpan', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, USER);
  const href = await openEvaluation(page);
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
