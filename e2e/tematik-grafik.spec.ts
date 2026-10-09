// e2e/tematik-grafik.spec.ts — grafik jaring laba-laba tematik pada kartu "Nilai saya" sepenuhnya dinamis: label sumbu, keterangan tema, jumlah sudut, dan
// nilai tiap titik mengikuti butir tematik periode dan hasil penilaian, bukan teks tetap. Butir diganti di tengah tes, lalu nilai diisi dua penilai.
import { test, expect, Page } from '@playwright/test';
import { ADMIN, watch, login } from './helpers';

const api = (page: Page, action: string, body: object) => page.evaluate(async ([action, body]) => {
  const boot = await (await fetch('api.php?action=bootstrap', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ period_id: (body as any).period_id ?? 0 }) })).json();
  const r = await fetch(`api.php?action=${action}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': boot.csrf }, body: JSON.stringify(body) });
  return { status: r.status, body: await r.json() };
}, [action, body] as const);
const dashboard = async (page: Page, id: number) => {
  await page.evaluate(id => sessionStorage.setItem('sikap_period', String(id)), id);
  await page.goto('/#dashboard'); await page.reload(); await expect(page.locator('.task-hub')).toBeVisible();
  const chart = page.locator('.score-card .radar').nth(1);
  return { caption: await chart.locator('figcaption').innerText(), labels: await chart.locator('.radar-lbl').allTextContents(), values: await chart.locator('.radar-val').allTextContents(), aria: await chart.locator('svg').getAttribute('aria-label') ?? '' };
};
const thematic = (theme: string, names: string[]) => ({ theme, indicators: names.map(name => ({ name, description: `Uraian perilaku ${name}.` })) });
const A = ['Disiplin Kehadiran', 'Pelaporan Jujur', 'Keterbukaan Informasi', 'Patuh Prosedur', 'Menolak Suap'];
const B = ['Ramah kepada Pengguna', 'Responsif', 'Informasi Layanan Jelas', 'Penyelesaian Tuntas', 'Inovasi Pelayanan'];

test('grafik tematik: label, tema, dan nilai mengikuti butir dan hasil penilaian', async ({ page, browser }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  // Periode draf tahun 2024 khusus tes ini (kedua project memakai DB yang sama).
  const used: string[] = (await api(page, 'bootstrap', {})).body.periods.map((p: any) => p.start_date);
  const quarter = [1, 2, 3, 4].find(q => !used.includes(`2024-${String(q * 3 - 2).padStart(2, '0')}-01`));
  expect(quarter, 'semua triwulan 2024 sudah terpakai').toBeTruthy();
  const id = Number((await api(page, 'period_create', { year: 2024, quarter })).body.id);

  // Set A: label sumbu = kata terpanjang tiap nama butir, keterangan = tema; nilai 0 karena hasil belum ada.
  expect((await api(page, 'thematic_save', { period_id: id, ...thematic('Integritas Layanan', A) })).status).toBe(200);
  let g = await dashboard(page, id);
  expect(g.labels).toEqual(['Kehadiran', 'Pelaporan', 'Keterbukaan', 'Prosedur', 'Menolak']);
  expect(g.caption).toBe('Tematik · Integritas Layanan');
  for (const name of A) expect(g.aria).toContain(name);

  // Set B menggantikan A: grafik ikut berubah tanpa perubahan kode.
  expect((await api(page, 'thematic_save', { period_id: id, ...thematic('Pelayanan Prima', B) })).status).toBe(200);
  g = await dashboard(page, id);
  expect(g.labels).toEqual(['Pengguna', 'Responsif', 'Informasi', 'Penyelesaian', 'Pelayanan']);
  expect(g.caption).toBe('Tematik · Pelayanan Prima');
  for (const name of A) expect(g.aria).not.toContain(name);

  // Nilai: administrator (pegawai 1) dinilai atasan (pegawai 2) dan rekan (pegawai 3). Keduanya memberi BerAKHLAK 4 dan tematik 5, 4, 3, 2, 1
  // sesuai urutan butir, sehingga nilai tiap titik tematik (bobot 75/25) = 100, 80, 60, 40, 20 dan rata-rata tematik 60.
  for (const [rater, role] of [[2, 'atasan'], [3, 'rekan']] as const) expect((await api(page, 'assignment_create', { period_id: id, subject_id: 1, rater_id: rater, rater_role: role })).status).toBe(200);
  expect((await api(page, 'period_status', { period_id: id, status: 'open' })).status).toBe(200);
  for (const rater of [2, 3]) {
    const ctx = await browser.newContext({ baseURL: test.info().project.use.baseURL }), p = await ctx.newPage();
    await login(p, `pegawai${rater}@example.test`);
    const boot = (await api(p, 'bootstrap', { period_id: id })).body, task = boot.tasks.find((t: any) => Number(t.subject_id) === 1);
    let n = 5; const answers = Object.fromEntries(boot.indicators.map((i: any) => [i.id, i.category === 'tematik' ? n-- : 4]));
    expect((await api(p, 'save_draft', { period_id: id, id: task.id, version: task.version, answers, feedback: '' })).status).toBe(200);
    expect((await api(p, 'submit', { period_id: id, ids: [task.id] })).status).toBe(200);
    await ctx.close();
  }
  g = await dashboard(page, id);
  expect(g.values).toEqual(['100', '80', '60', '40', '20']);
  expect(g.caption).toBe('Tematik · Pelayanan Prima · 60');
  await expect(page.locator('.score-card .radar').first().locator('figcaption')).toHaveText('BerAKHLAK · 80');
  expect(g.aria).toContain('Ramah kepada Pengguna 100');
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});
