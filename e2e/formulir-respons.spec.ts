// e2e/formulir-respons.spec.ts — formulir modal tersimpan lewat API (bukan terkirim sebagai GET biasa), dan tombol
// memberi umpan balik selama server lambat, tidak merespons, atau menjawab dengan halaman galat nginx.
import { test, expect, Page, Request } from '@playwright/test';
import { USER, ADMIN, watch, login, openEvaluation, bootstrap } from './helpers';

// Formulir yang lolos dari handler JS terkirim sebagai GET ke URL halaman dengan seluruh isian di query string.
function watchNativeSubmit(page: Page, errs: string[]) {
  page.on('request', (r: Request) => { if (r.isNavigationRequest() && new URL(r.url()).search) errs.push(`formulir terkirim sebagai GET: ${r.url()}`); });
}
const onAction = (action: string) => (u: URL) => u.pathname.endsWith('/api.php') && u.searchParams.get('action') === action;

test('formulir pegawai: Simpan pegawai tersimpan lewat API tanpa mengubah kolom lain', async ({ page }) => {
  const errs: string[] = []; watch(page, errs); watchNativeSubmit(page, errs);
  await login(page, ADMIN);
  const before = (await bootstrap(page)).employees.find((e: any) => e.email === USER);
  expect(before, `pegawai ${USER} tidak ditemukan`).toBeTruthy();
  // Formulir pegawai memuat <input name="id">: inilah kasus yang dulu lolos dari handler submit.
  const saveUnit = async (unit: string) => {
    await page.goto('/#employees'); await page.waitForLoadState('networkidle');
    await page.locator('#search-employees').fill(before.nip);
    await page.locator(`[data-action="employee-edit"][data-id="${before.id}"]`).click();
    await expect(page.locator('#employee-form')).toBeVisible();
    await page.locator('#employee-form input[name="unit"]').fill(unit);
    const [res] = await Promise.all([page.waitForResponse(r => r.url().includes('action=employee_save')), page.locator('#employee-form button[type=submit]').click()]);
    expect(res.status(), await res.text()).toBe(200);
    await expect(page.locator('#notifications')).toContainText('Perubahan berhasil disimpan.');
    expect(page.url()).not.toContain('?');
  };
  await saveUnit(`${before.unit} E2E`);
  const changed = (await bootstrap(page)).employees.find((e: any) => Number(e.id) === Number(before.id));
  expect(changed.unit).toBe(`${before.unit} E2E`);
  for (const k of ['name', 'nip', 'email', 'position', 'grade', 'opd_id', 'supervisor_id', 'role', 'active']) expect(changed[k], k).toEqual(before[k]);
  await saveUnit(before.unit);
  expect((await bootstrap(page)).employees.find((e: any) => Number(e.id) === Number(before.id)).unit).toBe(before.unit);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('formulir OPD: Simpan OPD tersimpan lewat API', async ({ page, isMobile }) => {
  test.fixme(isMobile, 'Laman OPD melebar hingga ±820 px di layar ponsel sehingga modal OPD dan tombol simpannya berada di luar layar.');
  const errs: string[] = []; watch(page, errs); watchNativeSubmit(page, errs);
  await login(page, ADMIN);
  const before = (await bootstrap(page)).opds;
  await page.goto('/#opd'); await page.waitForLoadState('networkidle');
  await page.locator('[data-action="opd-edit"]').first().click();
  await expect(page.locator('#opd-form')).toBeVisible();
  const [res] = await Promise.all([page.waitForResponse(r => r.url().includes('action=opd_save')), page.locator('#opd-form button[type=submit]').click()]);
  expect(res.status(), await res.text()).toBe(200);
  await expect(page.locator('#notifications')).toContainText('Perubahan berhasil disimpan.');
  expect(page.url()).not.toContain('?');
  expect((await bootstrap(page)).opds).toEqual(before);
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('simpan draf: tombol menampilkan status proses selama server belum menjawab', async ({ page }) => {
  const errs: string[] = []; watch(page, errs);
  await login(page, USER); await openEvaluation(page);
  let release!: () => void; const held = new Promise<void>(r => { release = r; });
  await page.route(onAction('save_draft'), async route => { await held; await route.continue(); });
  const btn = page.locator('button[data-action="save-draft"]');
  await btn.click();
  await expect(btn).toHaveAttribute('aria-busy', 'true');
  await expect(btn).toContainText('Menyimpan…');
  await expect(btn).toBeDisabled();
  await expect(page.locator('input[name="indicator-1"]').first()).toBeDisabled();
  release();
  await expect(page.locator('#notifications')).toContainText('Draf penilaian tersimpan.');
  await expect(btn).not.toHaveAttribute('aria-busy');
  await expect(btn).toHaveText('Simpan draf');
  await expect(btn).toBeEnabled();
  expect([...new Set(errs)], errs.join('\n')).toEqual([]);
});

test('server tidak merespons: pemberitahuan lambat setelah 10 detik, dibatalkan setelah 65 detik', async ({ page }) => {
  const jsErrs: string[] = []; page.on('pageerror', e => jsErrs.push(e.message));
  await page.clock.install();
  await login(page, USER); await openEvaluation(page);
  await page.route(onAction('save_draft'), () => { /* tidak pernah dijawab: meniru server yang hang */ });
  const btn = page.locator('button[data-action="save-draft"]'), notes = page.locator('#notifications');
  await btn.click();
  await expect(btn).toHaveAttribute('aria-busy', 'true');
  await page.clock.fastForward(10_000);
  await expect(notes).toContainText('Server lambat merespons');
  await expect(btn).toBeDisabled();
  await page.clock.fastForward(55_000);
  await expect(notes).toContainText('Server tidak merespons. Periksa koneksi, lalu coba lagi.');
  await expect(btn).toHaveText('Simpan draf');
  await expect(btn).toBeEnabled();
  expect(jsErrs).toEqual([]);
});

test('galat server dan jaringan tampil sebagai pesan yang dapat dibaca', async ({ page }) => {
  const jsErrs: string[] = []; page.on('pageerror', e => jsErrs.push(e.message));
  await login(page, USER); await openEvaluation(page);
  const btn = page.locator('button[data-action="save-draft"]'), notes = page.locator('#notifications'), saveDraft = onAction('save_draft');
  // Halaman 504 bawaan nginx berupa HTML, bukan JSON.
  await page.route(saveDraft, r => r.fulfill({ status: 504, contentType: 'text/html', body: '<html><body><center><h1>504 Gateway Time-out</h1></center></body></html>' }));
  await btn.click();
  await expect(notes).toContainText('Server sedang bermasalah (HTTP 504). Coba lagi beberapa saat.');
  await expect(btn).toBeEnabled();
  await page.unroute(saveDraft);
  await page.route(saveDraft, r => r.abort('internetdisconnected'));
  await btn.click();
  await expect(notes).toContainText('Tidak dapat terhubung ke server. Periksa koneksi internet, lalu coba lagi.');
  await expect(btn).toHaveText('Simpan draf');
  await expect(btn).toBeEnabled();
  expect(jsErrs).toEqual([]);
});
