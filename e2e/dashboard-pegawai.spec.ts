// e2e/dashboard-pegawai.spec.ts — dashboard pegawai satu layar: kartu "Penilaian saya" memuat angka yang sama dengan daftar tugas, tombol lanjut menuju tugas
// berikutnya (draf lebih dulu), dan kartu Penilaian saya serta Nilai saya muat tanpa gulir pada laptop 1366×768; di ponsel kartu tugas utuh pada layar pertama.
import { test, expect } from '@playwright/test';
import { USER, watch, login, bootstrap } from './helpers';

test('dashboard pegawai: kartu Penilaian saya cocok dengan tugas dan muat satu layar', async ({ page, isMobile }) => {
  const errs: string[] = []; watch(page, errs);
  if (!isMobile) await page.setViewportSize({ width: 1366, height: 768 });
  await login(page, USER);
  await page.goto('/#dashboard'); await expect(page.locator('.task-hub')).toBeVisible();
  const tasks: any[] = (await bootstrap(page)).tasks, n = (s: string) => tasks.filter(t => t.status === s).length, rest = tasks.length - n('submitted');
  await expect(page.locator('.task-hub-info > p')).toHaveText(`${rest} dari ${tasks.length} penilaian belum terkirim`);
  await expect(page.locator('.hub-chips')).toContainText(`Terkirim ${n('submitted')}`);
  await expect(page.locator('.hub-chips')).toContainText(`Draf ${n('draft')}`);
  await expect(page.locator('.hub-chips')).toContainText(`Belum dinilai ${n('pending')}`);
  const next = tasks.find(t => t.status === 'draft') ?? tasks.find(t => t.status === 'pending');
  await expect(page.locator('.hub-primary')).toHaveAttribute('href', `#evaluate/${next.id}`);
  await expect(page.locator('.task-hub .card-foot')).toContainText(`Lihat semua ${tasks.length} pegawai`);
  const box = await page.evaluate(() => ({ hub: document.querySelector('.task-hub')!.getBoundingClientRect().bottom, score: document.querySelector('.score-card')!.getBoundingClientRect().bottom, view: innerHeight, x: document.documentElement.scrollWidth - document.documentElement.clientWidth }));
  expect(box.x, 'tanpa gulir horizontal').toBe(0);
  expect(box.hub, 'kartu Penilaian saya utuh di layar pertama').toBeLessThanOrEqual(box.view);
  if (!isMobile) expect(box.score, 'kartu Nilai saya muat di layar pertama').toBeLessThanOrEqual(box.view);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});
