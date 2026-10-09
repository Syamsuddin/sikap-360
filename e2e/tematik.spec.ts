// e2e/tematik.spec.ts — indikator tematik: administrator kabupaten mengisi tema dan lima butir di laman Metode selama periode draf,
// lalu penilai mengisi 12 indikator (7 BerAKHLAK + 5 tematik) yang tampil berkelompok, dan formulir tematik terkunci sejak periode dibuka.
import { test, expect, Page } from '@playwright/test';
import { USER, ADMIN, watch, login, bootstrap } from './helpers';

// Panggilan API dengan token CSRF sesi yang sedang masuk (untuk menyiapkan data tanpa melewati banyak layar).
const api = (page: Page, action: string, body: object) => page.evaluate(async ([action, body]) => {
  const boot = await (await fetch('api.php?action=bootstrap', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' })).json();
  const r = await fetch(`api.php?action=${action}`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': boot.csrf }, body: JSON.stringify(body) });
  return { status: r.status, body: await r.json() };
}, [action, body] as const);
const usePeriod = async (page: Page, id: number, hash: string) => {
  await page.evaluate(id => sessionStorage.setItem('sikap_period', String(id)), id);
  await page.goto('/' + hash); await page.reload(); await page.waitForLoadState('networkidle');
};

test('tematik: admin mengisi lima indikator di laman Metode, penilai mengisi 12 indikator berkelompok', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, ADMIN);
  // Periode draf tahun 2025 (lebih lama dari periode aktif) agar tidak menjadi periode bawaan spec lain; kedua project memakai DB yang sama.
  const used: string[] = (await bootstrap(page)).periods.map((p: any) => p.start_date);
  const quarter = [1, 2, 3, 4].find(q => !used.includes(`2025-${String(q * 3 - 2).padStart(2, '0')}-01`));
  expect(quarter, 'semua triwulan 2025 sudah terpakai').toBeTruthy();
  const created = await api(page, 'period_create', { year: 2025, quarter }); expect(created.status).toBe(200); const id = Number(created.body.id);
  await usePeriod(page, id, '#method');
  const form = page.locator('#thematic-form'); await expect(form).toBeVisible();
  await form.locator('input[name="theme"]').fill('Pemberantasan Korupsi');
  for (let n = 1; n <= 5; n++) { await form.locator(`input[name="name-${n}"]`).fill(`Tematik E2E ${n}`); await form.locator(`textarea[name="description-${n}"]`).fill(`Uraian perilaku tematik ${n}.`); }
  const [res] = await Promise.all([page.waitForResponse(r => r.url().includes('action=thematic_save')), form.locator('button[type=submit]').click()]);
  expect(res.status(), await res.text()).toBe(200);
  await expect(page.locator('#notifications')).toContainText('Indikator tematik');
  await expect(page.locator('#thematic-form input[name="name-5"]')).toHaveValue('Tematik E2E 5');
  expect(page.url()).not.toContain('?');

  // Pegawai 4 dinilai atasan (pegawai 2) dan rekan (pegawai 3, akun uji ASN), lalu periode dibuka: formulir tematik berganti daftar terkunci.
  for (const [rater, role] of [[2, 'atasan'], [3, 'rekan']] as const) expect((await api(page, 'assignment_create', { period_id: id, subject_id: 4, rater_id: rater, rater_role: role })).status).toBe(200);
  expect((await api(page, 'period_status', { period_id: id, status: 'open' })).status).toBe(200);
  await usePeriod(page, id, '#method');
  await expect(page.locator('#thematic-form')).toHaveCount(0);
  await expect(page.locator('#main-content')).toContainText('Tematik E2E 3');
  await expect(page.locator('#main-content')).toContainText('terkunci sejak periode dibuka');
  expect((await api(page, 'logout', {})).status).toBe(200);

  await login(page, USER);
  await usePeriod(page, id, '#assessments');
  await page.locator('a[href^="#evaluate/"]').first().click();
  await expect(page.locator('#evaluation-form')).toBeVisible();
  await expect(page.locator('.question-group')).toHaveCount(2);
  await expect(page.locator('.question-group').nth(1)).toContainText('Pemberantasan Korupsi');
  const cards = page.locator('.question-card'); await expect(cards).toHaveCount(12);
  await expect(page.locator('#answer-count')).toHaveText('0 / 12 terjawab');
  for (let i = 0; i < 7; i++) await cards.nth(i).locator('input[value="4"]').check({ force: true });
  await page.locator('button[data-action="submit-one"]').click();
  await expect(page.locator('#notifications')).toContainText('Lengkapi seluruh 12 indikator sebelum mengirim.');
  for (let i = 7; i < 12; i++) await cards.nth(i).locator('input[value="5"]').check({ force: true });
  await expect(page.locator('#answer-count')).toHaveText('12 / 12 terjawab');
  const [draft] = await Promise.all([page.waitForResponse(r => r.url().includes('action=save_draft')), page.locator('button[data-action="save-draft"]').click()]);
  expect(draft.status(), await draft.text()).toBe(200);
  await page.locator('button[data-action="submit-one"]').click();
  const [sent] = await Promise.all([page.waitForResponse(r => r.url().includes('action=submit')), page.locator('[data-action="confirm-submit"]').click()]);
  expect(sent.status(), await sent.text()).toBe(200);
  await expect(page.locator('#notifications')).toContainText('1 penilaian berhasil dikirim.');
  // Dashboard periode bertematik: kartu Nilai saya memuat grafik jaring laba-laba kedua untuk lima indikator tematik, dengan label sumbu yang berbeda-beda.
  await page.goto('/#dashboard'); await expect(page.locator('.task-hub')).toBeVisible();
  await expect(page.locator('.score-card .radar')).toHaveCount(2);
  await expect(page.locator('.score-card figcaption').nth(1)).toContainText('Tematik · Pemberantasan Korupsi');
  const labels = await page.locator('.score-card .radar').nth(1).locator('.radar-lbl').allTextContents();
  expect(labels.length).toBe(5); expect(new Set(labels).size, labels.join(', ')).toBe(5);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});
