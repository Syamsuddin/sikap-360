// e2e/pegawai-status.spec.ts — Data pegawai: administrator menonaktifkan dan mengaktifkan kembali pegawai lewat dialog konfirmasi; akun sendiri tidak memiliki
// tombol status, dan admin OPD tidak dapat mengubah status administrator kabupaten.
import { test, expect } from '@playwright/test';
import { ADMIN, watch, login, bootstrap } from './helpers';

test('pegawai: nonaktifkan dan aktifkan kembali lewat dialog konfirmasi', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  const boot = await bootstrap(page), me = boot.user, target = boot.employees.find((e: any) => e.nip === 'DEMO-0013');
  expect(target && Number(target.active)).toBe(1);
  await page.goto('/#employees'); await expect(page.locator('#main-content')).toContainText('Data pegawai');
  await page.locator('#search-employees').fill('DEMO-0013');
  const row = page.locator('tr', { hasText: target.name });
  await row.locator('[data-action="employee-status"]').click();
  await expect(page.locator('#dialog-title')).toHaveText(`Nonaktifkan ${target.name}?`);
  const [off] = await Promise.all([page.waitForResponse(r => r.url().includes('action=employee_status')), page.locator('[data-action="confirm-employee-status"]').click()]);
  expect(off.status(), await off.text()).toBe(200);
  await expect(page.locator('#notifications')).toContainText(`${target.name} dinonaktifkan.`);
  await expect(row).toContainText('Nonaktif'); await expect(row.locator('[data-action="employee-status"]')).toContainText('Aktifkan');
  expect(Number((await bootstrap(page)).employees.find((e: any) => e.id === target.id).active)).toBe(0);
  await row.locator('[data-action="employee-status"]').click();
  await expect(page.locator('#dialog-title')).toHaveText(`Aktifkan ${target.name}?`);
  const [on] = await Promise.all([page.waitForResponse(r => r.url().includes('action=employee_status')), page.locator('[data-action="confirm-employee-status"]').click()]);
  expect(on.status(), await on.text()).toBe(200);
  await expect(row.locator('[data-action="employee-status"]')).toContainText('Nonaktifkan');
  // Akun sendiri tidak memiliki tombol status.
  await page.locator('#search-employees').fill(me.nip);
  await expect(page.locator('tr', { hasText: me.name }).locator('[data-action="employee-status"]')).toHaveCount(0);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('pegawai: admin OPD tidak dapat mengubah status administrator kabupaten', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, 'pegawai2@example.test');
  const admin = (await bootstrap(page)).employees.find((e: any) => e.role === 'admin');
  await page.goto('/#employees'); await page.locator('#search-employees').fill(admin.nip);
  const row = page.locator('tr', { hasText: admin.name });
  await expect(row).toBeVisible();
  await expect(row.locator('[data-action="employee-status"]')).toHaveCount(0);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});
