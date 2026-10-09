// e2e/periode-aksi.spec.ts — laman Periode & penilai: tombol aksi per baris sesuai status (administrator kabupaten), peringatan saat membuka periode
// ketika periode lain masih aktif, hapus periode draf tanpa penugasan lewat dialog konfirmasi, dan admin OPD hanya dapat memilih periode.
import { test, expect, Page } from '@playwright/test';
import { ADMIN, watch, login, bootstrap } from './helpers';

const api = (page: Page, action: string, body: object) => page.evaluate(async ([action, body]) => {
  const boot = await (await fetch('api.php?action=bootstrap', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })).json();
  const r = await fetch(`api.php?action=${action}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': boot.csrf }, body: JSON.stringify(body) });
  return { status: r.status, body: await r.json() };
}, [action, body] as const);

test('periode: aksi per baris, peringatan periode aktif lain, dan hapus draf kosong', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  // Periode draf tahun 2023 khusus tes ini (kedua project memakai DB yang sama).
  const boot = await bootstrap(page), used: string[] = boot.periods.map((p: any) => p.start_date);
  const quarter = [1, 2, 3, 4].find(q => !used.includes(`2023-${String(q * 3 - 2).padStart(2, '0')}-01`));
  expect(quarter, 'semua triwulan 2023 sudah terpakai').toBeTruthy();
  const created = await api(page, 'period_create', { year: 2023, quarter }); expect(created.status).toBe(200);
  const name = `Triwulan ${['I', 'II', 'III', 'IV'][quarter! - 1]} 2023`, active = boot.periods.find((p: any) => p.status === 'open');
  await page.goto('/#periods'); await page.reload(); await expect(page.locator('#main-content')).toContainText('Daftar periode');
  await page.locator('select[data-table="periods"][data-filter="year"]').selectOption('2023');
  const row = page.locator('tr', { hasText: name });
  await expect(row.locator('[data-action="period-status"][data-status="open"]')).toHaveText('Buka');
  await expect(row.locator('[data-action="period-delete"]')).toBeVisible();
  await expect(row).toContainText('Belum ada penugasan');

  // Membuka saat periode lain aktif: dialog memperingatkan, lalu dibatalkan tanpa mengubah status.
  await row.locator('[data-action="period-status"]').click();
  await expect(page.locator('#dialog-title')).toHaveText(`Buka ${name}?`);
  await expect(page.locator('.modal-warning')).toContainText(`${active.name}`);
  await expect(page.locator('.modal-warning')).toContainText('masih aktif');
  await page.locator('#dialog [data-action="close-modal"]').click();
  expect((await bootstrap(page)).periods.find((p: any) => p.name === name).status).toBe('draft');

  // Memilih draf menampilkan tautan ke bobot dan tematik di bagian periode terpilih; draf terpilih lalu dihapus dan aplikasi kembali ke periode bawaan.
  await row.locator('[data-action="change-period"]').click();
  await expect(page.locator('a[href="#method"]', { hasText: 'Atur bobot & tematik' })).toBeVisible();
  await expect(row).toContainText('Terpilih');
  // Hapus draf kosong lewat dialog konfirmasi.
  await row.locator('[data-action="period-delete"]').click();
  await expect(page.locator('#dialog-title')).toHaveText(`Hapus ${name}?`);
  const [res] = await Promise.all([page.waitForResponse(r => r.url().includes('action=period_delete')), page.locator('[data-action="confirm-period-delete"]').click()]);
  expect(res.status(), await res.text()).toBe(200);
  await expect(page.locator('#notifications')).toContainText('Periode dihapus.');
  const after = await bootstrap(page); expect(after.periods.some((p: any) => p.name === name)).toBe(false); expect(after.period.status).toBe('open');

  // Periode aktif menampilkan Tutup dan tidak dapat dihapus dari tabel.
  await page.locator('select[data-table="periods"][data-filter="year"]').selectOption('all');
  await page.locator('select[data-table="periods"][data-filter="status"]').selectOption('open');
  const open = page.locator('tr', { hasText: active.name });
  await expect(open.locator('[data-action="period-status"][data-status="closed"]')).toHaveText('Tutup');
  await expect(open.locator('[data-action="period-delete"]')).toHaveCount(0);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('periode: admin OPD hanya dapat memilih periode', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, 'pegawai2@example.test');
  await page.goto('/#periods'); await expect(page.locator('#main-content')).toContainText('Daftar periode');
  await expect(page.locator('[data-action="period-status"], [data-action="period-delete"]')).toHaveCount(0);
  await expect(page.locator('[data-action="change-period"]').first()).toBeVisible();
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});
