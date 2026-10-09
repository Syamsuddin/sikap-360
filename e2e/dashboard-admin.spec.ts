// e2e/dashboard-admin.spec.ts — Dashboard admin: hanya administrator kabupaten; kartu progres kabupaten cocok dengan API, tabel per OPD bisa dicari dan difilter.
import { test, expect } from '@playwright/test';
import { USER, ADMIN, watch, login, bootstrap } from './helpers';

test('dashboard admin: kartu progres dan tabel per OPD untuk administrator kabupaten', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  await expect(page.locator('a[href="#admin"]')).toHaveCount(1);
  const [res] = await Promise.all([page.waitForResponse(r => r.url().includes('action=admin_dashboard')), page.goto('/#admin')]);
  expect(res.status(), await res.text()).toBe(200);
  const d = await res.json(), s = d.summary;
  await expect(page.locator('.stat-card')).toHaveCount(4);
  const pct = (n: number, t: number) => (t ? Math.floor(n / t * 1000) / 10 : 0).toLocaleString('id-ID');
  await expect(page.locator('.stat-card').first()).toContainText(`${pct(s.submitted, s.total)}%`);
  await expect(page.locator('.stat-card').nth(1)).toContainText(String(s.submitted));
  const opds = (await bootstrap(page)).opds.length;
  await expect(page.locator('.admin-opd-table tbody tr')).toHaveCount(Math.min(10, opds));
  await expect(page.locator('.card-foot').last()).toContainText(`dari ${opds} OPD`);
  await page.locator('#search-admin-opd').fill('kesehatan');
  await expect(page.locator('.admin-opd-table tbody tr')).toHaveCount(1);
  await expect(page.locator('.admin-opd-table tbody tr').first()).toContainText('Dinas Kesehatan');
  await page.locator('#search-admin-opd').fill('');
  await page.locator('select[data-filter="state"]').selectOption('none');
  await expect(page.locator('#main-content')).toContainText('Tidak ada OPD ditemukan');
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('dashboard admin: ASN tidak melihat menu dan laman ditolak', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, USER);
  await expect(page.locator('a[href="#admin"]')).toHaveCount(0);
  await page.goto('/#admin');
  await expect(page.locator('#main-content')).toContainText('Akses dibatasi');
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});
