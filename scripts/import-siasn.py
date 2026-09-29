"""Impor pegawai dari ekspor SIASN (xlsx) ke SIKAP 360. Mengosongkan seluruh data lalu mengisinya ulang.

Pemakaian:
  python3 scripts/import-siasn.py ekspor.xlsx                     # pratinjau statistik, tanpa menulis
  python3 scripts/import-siasn.py ekspor.xlsx --apply --admin email@instansi.go.id

Opsi:
  --apply             kosongkan database (kecuali indikator dan pengaturan) lalu impor
  --admin EMAIL       email pegawai yang menjadi administrator kabupaten (wajib bersama --apply)
  --semua             sertakan guru, pegawai sekolah (SD/SMP/TK), dan Puskesmas
  --timpa-penilaian   izinkan penghapusan penugasan/jawaban penilaian yang sudah ada

Kebutuhan: Python 3 dengan pandas, openpyxl, pymysql; PHP CLI (untuk hash kata sandi). Koneksi database
dibaca dari .env (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD); environment variable menimpanya.
Login memakai email pegawai; kata sandi awal tiap akun adalah NIP-nya. Backup database terlebih dahulu.

Yang diimpor hanya kolom yang dipakai aplikasi: nama+gelar, NIP, jabatan, unit (UNOR), OPD (akar pohon UNOR),
pangkat/golongan, email, status aktif, dan atasan langsung (pejabat struktural unit atau unit induknya).
NIK, alamat, HP, NPWP, dan data pribadi lain tidak diimpor.
"""
import argparse
import datetime
import os
import re
import subprocess
from collections import Counter, defaultdict
from pathlib import Path

import pandas as pd

PEMKAB = 'PEMERINTAH KABUPATEN HULU SUNGAI SELATAN'
PLACEHOLDER_DOMAIN = 'sikap360.local'

# Nomenklatur SIASN (lama/baru/typo) -> kode OPD kanonik.
OPD_ALIASES = {
    'DINAS PENDIDIKAN DAN KEBUDAYAAN': 'DISDIKBUD',
    'DINAS PENDIDIKAN DAN KEBUDAYAAN.': 'DISDIKBUD',
    'DINAS KESEHATAN, PENGENDALIAN PENDUDUK DAN KELUARGA BERENCANA': 'DINKESP2KB',
    'DINAS KESEHATAN': 'DINKESP2KB',
    'RSUD BRIGJEND. H. HASAN BASRY KANDANGAN': 'RSUDHB',
    'SEKRETARIAT DAERAH': 'SETDA',
    'DINAS PERUMAHAN RAKYAT DAN KAWASAN PERMUKIMAN, LINGKUNGAN HIDUP, DAN PERTANAHAN': 'DISPERKIMLHP',
    'DINAS PERUMAHAN RAKYAT,KAWASAN PERMUKIMAN DAN LINGKUNGAN HIDUP': 'DISPERKIMLHP',
    'DINAS PERTANIAN, PERIKANAN DAN PANGAN': 'DISTANKANPANGAN',
    'DINAS KETAHANAN PANGAN': 'DISTANKANPANGAN',
    'SATUAN POLISI PAMONG PRAJA DAN PEMADAM KEBAKARAN': 'SATPOLPPDAMKAR',
    'INSPEKTORAT DAERAH': 'INSPEKTORAT',
    'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG': 'DPUPR',
    'DINAS PEKERJAAN UMUM DAN TATA RUANG': 'DPUPR',
    'BADAN PENGELOLAAN KEUANGAN DAN PENDAPATAN DAERAH': 'BPKPD',
    'DINAS PERHUBUNGAN': 'DISHUB',
    'SEKRETARIAT DPRD': 'SETWAN',
    'BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA': 'BKPSDM',
    'DINAS SOSIAL': 'DINSOS',
    'DINAS PERINDUSTRIAN DAN PERDAGANGAN': 'DISPERINDAG',
    'DINAS PERPUSTAKAAN DAN KEARSIPAN': 'DISPUSIP',
    'DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL': 'DISDUKCAPIL',
    'DINAS KOMUNIKASI, INFORMATIKA, STATISTIK DAN PERSANDIAN': 'DISKOMINFOSP',
    'DINAS PEMBERDAYAAN MASYARAKAT DAN DESA, PEMBERDAYAAN PEREMPUAN DAN PERLINDUNGAN ANAK': 'DPMDPPPA',
    'DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU': 'DPMPTSP',
    'DINAS KEPEMUDAAN, OLAHRAGA DAN PARIWISATA': 'DISPORAPAR',
    'DINAS TENAGA KERJA, KOPERASI, USAHA KECIL DAN MENENGAH': 'DISNAKERKUKM',
    'BADAN PENANGGULANGAN BENCANA DAERAH': 'BPBD',
    'BADAN PERENCANAAN PEMBANGUNAN, RISET DAN INOVASI DAERAH': 'BAPPERIDA',
    'BADAN KESATUAN BANGSA DAN POLITIK': 'BAKESBANGPOL',
}
KECAMATAN = ['KANDANGAN', 'PADANG BATUNG', 'ANGKINANG', 'SUNGAI RAYA', 'SIMPUR', 'DAHA SELATAN',
             'DAHA UTARA', 'LOKSADO', 'DAHA BARAT', 'TELAGA LANGSAT', 'KALUMPANG']
for k in KECAMATAN:
    OPD_ALIASES['KECAMATAN ' + k] = 'KEC-' + k.replace(' ', '')

# Bidang yang di SIASN tergantung langsung ke Pemkab (salah induk) -> OPD sebenarnya.
ORPHAN_UNITS = {'BIDANG PENAGIHAN DAN PENGAWASAN': 'BPKPD', 'BIDANG KEBUDAYAAN': 'DISDIKBUD'}

OPD_NAMES = {
    'DISDIKBUD': 'Dinas Pendidikan dan Kebudayaan',
    'DINKESP2KB': 'Dinas Kesehatan, Pengendalian Penduduk dan Keluarga Berencana',
    'RSUDHB': 'RSUD Brigjend. H. Hasan Basry Kandangan',
    'SETDA': 'Sekretariat Daerah',
    'DISPERKIMLHP': 'Dinas Perumahan Rakyat dan Kawasan Permukiman, Lingkungan Hidup, dan Pertanahan',
    'DISTANKANPANGAN': 'Dinas Pertanian, Perikanan dan Pangan',
    'SATPOLPPDAMKAR': 'Satuan Polisi Pamong Praja dan Pemadam Kebakaran',
    'INSPEKTORAT': 'Inspektorat Daerah',
    'DPUPR': 'Dinas Pekerjaan Umum dan Penataan Ruang',
    'BPKPD': 'Badan Pengelolaan Keuangan dan Pendapatan Daerah',
    'DISHUB': 'Dinas Perhubungan',
    'SETWAN': 'Sekretariat DPRD',
    'BKPSDM': 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia',
    'DINSOS': 'Dinas Sosial',
    'DISPERINDAG': 'Dinas Perindustrian dan Perdagangan',
    'DISPUSIP': 'Dinas Perpustakaan dan Kearsipan',
    'DISDUKCAPIL': 'Dinas Kependudukan dan Pencatatan Sipil',
    'DISKOMINFOSP': 'Dinas Komunikasi, Informatika, Statistik dan Persandian',
    'DPMDPPPA': 'Dinas Pemberdayaan Masyarakat dan Desa, Pemberdayaan Perempuan dan Perlindungan Anak',
    'DPMPTSP': 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu',
    'DISPORAPAR': 'Dinas Kepemudaan, Olahraga dan Pariwisata',
    'DISNAKERKUKM': 'Dinas Tenaga Kerja, Koperasi, Usaha Kecil dan Menengah',
    'BPBD': 'Badan Penanggulangan Bencana Daerah',
    'BAPPERIDA': 'Badan Perencanaan Pembangunan, Riset dan Inovasi Daerah',
    'BAKESBANGPOL': 'Badan Kesatuan Bangsa dan Politik',
    'BELUM-TERPETAKAN': 'Unit Organisasi Belum Terpetakan',
}
for k in KECAMATAN:
    OPD_NAMES['KEC-' + k.replace(' ', '')] = 'Kecamatan ' + k.title()

# Setda: SIASN meratakan Bagian/Sub Bagian langsung ke Sekretariat Daerah; pulihkan jenjangnya.
SETDA_PARENT = {
    'BAGIAN PEMERINTAHAN': 'ASISTEN PEMERINTAHAN DAN KESEJAHTERAAN RAKYAT',
    'BAGIAN HUKUM': 'ASISTEN PEMERINTAHAN DAN KESEJAHTERAAN RAKYAT',
    'BAGIAN KESEJAHTERAAN RAKYAT': 'ASISTEN PEMERINTAHAN DAN KESEJAHTERAAN RAKYAT',
    'BAGIAN PEREKONOMIAN DAN ADMINISTRASI PEMBANGUNAN': 'ASISTEN PEREKONOMIAN DAN PEMBANGUNAN',
    'BAGIAN PENGADAAN BARANG DAN JASA': 'ASISTEN PEREKONOMIAN DAN PEMBANGUNAN',
    'BAGIAN ORGANISASI': 'ASISTEN ADMINISTRASI UMUM',
    'BAGIAN UMUM': 'ASISTEN ADMINISTRASI UMUM',
    'BAGIAN PROTOKOL DAN KOMUNIKASI PIMPINAN': 'ASISTEN ADMINISTRASI UMUM',
    'BAGIAN PERENCANAAN DAN KEUANGAN': 'ASISTEN ADMINISTRASI UMUM',
    'SUB BAGIAN RUMAH TANGGA DAN PERLENGKAPAN': 'BAGIAN UMUM',
    'SUBBAGIAN PERLENGKAPAN': 'BAGIAN UMUM',
    'SUBBAGIAN RUMAH TANGGA': 'BAGIAN UMUM',
    'SUB BAGIAN TATA USAHA PIMPINAN STAF AHLI DAN KEPEGAWAIAN': 'BAGIAN UMUM',
    'SUB BAGIAN KOMUNIKASI DAN DOKUMENTASI PIMPINAN': 'BAGIAN PROTOKOL DAN KOMUNIKASI PIMPINAN',
    'SUB BAGIAN PROTOKOL': 'BAGIAN PROTOKOL DAN KOMUNIKASI PIMPINAN',
    'SUB BAGIAN DOKUMENTASI DAN INFORMASI': 'BAGIAN PROTOKOL DAN KOMUNIKASI PIMPINAN',
    'SUB BAGIAN PEREKONOMIAN DAN SUMBER DAYA ALAM': 'BAGIAN PEREKONOMIAN DAN ADMINISTRASI PEMBANGUNAN',
    'SUB BAGIAN ADMINISTRASI PEMBANGUNAN DAN PELAPORAN': 'BAGIAN PEREKONOMIAN DAN ADMINISTRASI PEMBANGUNAN',
    'SUB BAGIAN PEMBINAAN BUMD DAN BLUD': 'BAGIAN PEREKONOMIAN DAN ADMINISTRASI PEMBANGUNAN',
    'SUB BAGIAN PERUNDANG-UNDANGAN': 'BAGIAN HUKUM',
    'SUB BAGIAN BANTUAN HUKUM': 'BAGIAN HUKUM',
    'SUB BAGIAN ADMINISTRASI KEWILAYAHAN': 'BAGIAN PEMERINTAHAN',
    'SUB BAGIAN ADMINISTRASI PEMERINTAHAN DAN OTONOMI DAERAH': 'BAGIAN PEMERINTAHAN',
    'SUB BAGIAN BINA MENTAL SPIRITUAL': 'BAGIAN KESEJAHTERAAN RAKYAT',
    'SUB BAGIAN KESEJAHTERAAN SOSIAL': 'BAGIAN KESEJAHTERAAN RAKYAT',
    'SUB BAGIAN KESEJAHTERAAN MASYARAKAT': 'BAGIAN KESEJAHTERAAN RAKYAT',
    'SUB BAGIAN PELAYANAN PUBLIK DAN TATA LAKSANA': 'BAGIAN ORGANISASI',
    'SUB BAGIAN KINERJA DAN REFORMASI BIROKRASI': 'BAGIAN ORGANISASI',
    'SUB BAGIAN KELEMBAGAAN DAN ANALISIS JABATAN': 'BAGIAN ORGANISASI',
    'SUB BAGIAN PENGELOLAAN LAYANAN PENGADAAN SECARA ELEKTRONIK': 'BAGIAN PENGADAAN BARANG DAN JASA',
    'SUB BAGIAN PEMBINAAN DAN ADVOKASI PENGADAAN BARANG DAN JASA': 'BAGIAN PENGADAAN BARANG DAN JASA',
    'SUB BAGIAN PENGELOLAAN PENGADAAN BARANG DAN JASA': 'BAGIAN PENGADAAN BARANG DAN JASA',
    'SUBBAGIAN PERENCANAAN': 'BAGIAN PERENCANAAN DAN KEUANGAN',
    'SUBBAGIAN KEUANGAN': 'BAGIAN PERENCANAAN DAN KEUANGAN',
    'SUBBAGIAN PELAPORAN': 'BAGIAN PERENCANAAN DAN KEUANGAN',
    'SUB BAGIAN PERENCANAAN DAN KEUANGAN': 'BAGIAN PERENCANAAN DAN KEUANGAN',
}
# RSUD: Bagian/Sub Bagian di bawah Wadir Administrasi, Bidang di bawah Wadir Pelayanan.
RSUD_WADIR_ADM = 'WAKIL DIREKTUR ADMINISTRASI, KEUANGAN DAN DIKLAT'
RSUD_WADIR_YAN = 'WAKIL DIREKTUR PELAYANAN'

# Satuan kerja yang dikepalai pejabat fungsional (kepala sekolah/Puskesmas tidak tercatat di SIASN):
# bila tanpa kepala, atasan tidak dieskalasi ke kepala OPD (menghindari ribuan bawahan langsung).
FUNCTIONAL_UNIT = re.compile(r'^(SD ?N(EGERI)?|SMP ?N(EGERI)?|TK ?N(EGERI)?|SMA ?N(EGERI)?|PUSKESMAS)\b')
TEACHER = re.compile(r'^(GURU|TENAGA PENDIDIK)\b', re.I)  # bukan "Kepala Seksi ... Tenaga Pendidik ..."

PANGKAT = {
    'I/a': 'Juru Muda', 'I/b': 'Juru Muda Tk. I', 'I/c': 'Juru', 'I/d': 'Juru Tk. I',
    'II/a': 'Pengatur Muda', 'II/b': 'Pengatur Muda Tk. I', 'II/c': 'Pengatur', 'II/d': 'Pengatur Tk. I',
    'III/a': 'Penata Muda', 'III/b': 'Penata Muda Tk. I', 'III/c': 'Penata', 'III/d': 'Penata Tk. I',
    'IV/a': 'Pembina', 'IV/b': 'Pembina Tk. I', 'IV/c': 'Pembina Utama Muda', 'IV/d': 'Pembina Utama Madya',
    'IV/e': 'Pembina Utama',
}
ESELON_RANK = {'II.a': 1, 'II.b': 2, 'III.a': 3, 'III.b': 4, 'IV.a': 5, 'IV.b': 6}
EMAIL_RE = re.compile(r'^[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,}$')


def clean(v):
    if v is None or (isinstance(v, float) and pd.isna(v)):
        return ''
    return re.sub(r'\s+', ' ', str(v)).strip()


def key(s):
    """Kunci pembanding nama unit: huruf besar, spasi tunggal, tanpa titik di ujung."""
    return clean(s).upper().rstrip('.').strip()


ALIAS_KEYS = {key(alias): code for alias, code in OPD_ALIASES.items()}


def opd_of(name):
    return ALIAS_KEYS.get(key(name))


def full_name(r):
    depan, nama, belakang = clean(r['GELAR DEPAN']), clean(r['NAMA']), clean(r['GELAR BELAKANG']).lstrip(', ').strip()
    s = (depan + ' ' if depan else '') + nama
    return s + (', ' + belakang if belakang else '')


def grade(r):
    gol, src = clean(r['GOL AKHIR NAMA']), clean(r['Source File'])
    if src.startswith('pppk_paruh'):
        return 'PPPK Paruh Waktu'
    if src.startswith('pppk'):
        return f'PPPK Golongan {gol}' if gol else 'PPPK'
    return f'{PANGKAT[gol]} ({gol})' if gol in PANGKAT else gol


def fix_email(e):
    e = re.sub(r'\s+', '', clean(e)).lower()
    e = re.sub(r'@([a-z0-9\-]+)@\1\.', r'@\1.', e)  # nama@gmail@gmail.com -> nama@gmail.com
    return e if EMAIL_RE.match(e) and len(e) <= 160 else ''


def build(path, include_functional):
    df = pd.read_excel(path, dtype=str)
    stats = Counter()

    # ---- Pohon UNOR: node = (kode OPD, kunci unit, node induk); akar OPD = ('ROOT', kode) ----
    raw = {}
    for n in df['UNOR NAMA'].dropna().unique():
        seg, _, par = n.partition(' - ')
        raw[n] = (seg, par)
    by_seg = defaultdict(list)
    for n, (seg, _) in raw.items():
        by_seg[key(seg)].append(n)

    def resolve(n, depth=0):
        """UNOR NAMA -> (kode OPD, node); node None berarti akar OPD."""
        seg, par = raw[n]
        if key(par) == PEMKAB:
            if opd_of(seg):
                return opd_of(seg), None
            if key(seg) in ORPHAN_UNITS:
                return ORPHAN_UNITS[key(seg)], (ORPHAN_UNITS[key(seg)], key(seg), None)
            raise ValueError('Akar UNOR tak dikenal: ' + n)
        pseg = par.split(' - ')[0]
        if opd_of(pseg):
            return opd_of(pseg), (opd_of(pseg), key(seg), None)
        cands = [c for c in by_seg.get(key(pseg), []) if c == par] or by_seg.get(key(pseg), [])
        if not cands or depth > 10:
            raise ValueError('Induk UNOR tak ditemukan: ' + n)
        code, parent_node = resolve(sorted(cands)[0], depth + 1)
        return code, (code, key(seg), parent_node)

    unor_node = {n: resolve(n) for n in raw}
    parent = {}
    for code, node in unor_node.values():
        if node is not None:
            parent[node] = ('ROOT', code) if node[2] is None else node[2]

    # Pulihkan jenjang yang diratakan SIASN.
    root_children = defaultdict(dict)
    for node, p in parent.items():
        if p == ('ROOT', node[0]):
            root_children[node[0]][node[1]] = node
    for node, p in list(parent.items()):
        code, ukey = node[0], node[1]
        if p != ('ROOT', code):
            continue
        kids = root_children[code]
        if code == 'SETDA' and SETDA_PARENT.get(ukey) in kids:
            parent[node] = kids[SETDA_PARENT[ukey]]
            stats['jenjang_setda'] += 1
        elif code == 'RSUDHB' and RSUD_WADIR_ADM in kids and re.match(r'^(BAGIAN|SUB ?BAG)', ukey):
            parent[node] = kids[RSUD_WADIR_ADM]
            stats['jenjang_rsud'] += 1
        elif code == 'RSUDHB' and RSUD_WADIR_YAN in kids and ukey.startswith('BIDANG'):
            parent[node] = kids[RSUD_WADIR_YAN]
            stats['jenjang_rsud'] += 1
        elif re.match(r'^SUB ?BAG', ukey):
            m = re.match(r'^SUB ?BAG(IAN)? TATA USAHA (.+)$', ukey)
            if m and m.group(2) in kids:  # Subbag TU UPT X -> UPT X
                parent[node] = kids[m.group(2)]
                stats['jenjang_subbag_upt'] += 1
                continue
            sek = [k for k in kids if k == 'SEKRETARIAT' or k.startswith('SEKRETARIAT KECAMATAN')]
            if sek:
                parent[node] = kids[sek[0]]
                stats['jenjang_subbag_sekretariat'] += 1

    # ---- Pegawai ----
    emails = df['EMAIL'].map(fix_email)
    filled = emails[emails != '']
    shared = set(filled[filled.duplicated(keep=False)])  # email dipakai bersama: tidak dipakai siapa pun
    gov = df['EMAIL GOV'].map(fix_email)
    rows = []
    for i, r in df.iterrows():
        nip = clean(r['NIP BARU']).lstrip("'")
        if not re.fullmatch(r'\d{18}', nip):
            raise ValueError(f'NIP tidak valid pada baris {i + 2}: {nip}')
        n = r['UNOR NAMA'] if isinstance(r['UNOR NAMA'], str) else None
        if n:
            code, node = unor_node[n]
            node = node or ('ROOT', code)
            unit = clean(raw[n][0]).rstrip('.').strip()
        else:
            code = 'DISDIKBUD' if TEACHER.search(clean(r['JABATAN NAMA'])) else 'BELUM-TERPETAKAN'
            node, unit = None, 'UNOR belum terisi di SIASN'
            stats['tanpa_unor'] += 1
        email = emails[i] if emails[i] not in shared else ''
        if not email:
            email = gov[i] if gov[i] and gov[i] not in shared else f'{nip}@{PLACEHOLDER_DOMAIN}'
            stats['email_pengganti'] += 1
        kedudukan = clean(r['KEDUDUKAN HUKUM NAMA'])
        jabatan = clean(r['JABATAN NAMA'])
        # Dokter tugas belajar dititipkan di BKPSDM dengan kedudukan "Aktif" tetapi jabatan "PNS TUGAS BELAJAR"; keduanya tidak dinilai.
        study_leave = kedudukan == 'Tugas Belajar' or jabatan.upper() == 'PNS TUGAS BELAJAR'
        rows.append({
            'nip': nip, 'name': full_name(r)[:160], 'position': (jabatan or '-')[:160],
            'opd': code, 'node': node, 'unit': (unit or '-')[:160], 'grade': grade(r)[:80], 'email': email,
            'active': 0 if study_leave or kedudukan == 'Pemberhentian Sementara' else 1,
            'structural': clean(r['JENIS JABATAN NAMA']) == 'Jabatan Struktural', 'eselon': clean(r['ESELON NAMA']),
        })
    seen = Counter(r['email'] for r in rows)  # email pengganti bisa bentrok dengan email pribadi pegawai lain
    for r in rows:
        if seen[r['email']] > 1:
            r['email'] = f"{r['nip']}@{PLACEHOLDER_DOMAIN}"
    if len({r['email'] for r in rows}) != len(rows):
        raise ValueError('Email pegawai tidak unik.')

    # ---- Pengecualian guru/sekolah/Puskesmas (sebelum kepala & atasan dihitung) ----
    def in_functional_unit(nd):
        while nd is not None and nd[0] != 'ROOT':
            if FUNCTIONAL_UNIT.match(nd[1] or ''):
                return True
            nd = parent.get(nd)
        return False

    if not include_functional:
        kept = []
        for r in rows:
            if in_functional_unit(r['node']):
                stats['dikecualikan_sekolah_puskesmas'] += 1
            elif TEACHER.search(r['position']):
                stats['dikecualikan_guru_lain'] += 1
            else:
                kept.append(r)
        rows = kept

    # ---- Kepala unit & atasan langsung ----
    heads = defaultdict(list)
    for idx, r in enumerate(rows):
        if r['structural'] and r['active'] and r['node'] is not None:
            heads[r['node']].append(idx)
    head = {}
    for nd, idxs in heads.items():
        idxs.sort(key=lambda i: (ESELON_RANK.get(rows[i]['eselon'], 9), rows[i]['nip']))
        head[nd] = idxs[0]

    for idx, r in enumerate(rows):
        r['supervisor'] = None
        if r['node'] is None or not r['active']:
            continue
        cur = parent.get(r['node']) if head.get(r['node']) == idx else r['node']
        while cur is not None:
            h = head.get(cur)
            if h is not None and h != idx:
                r['supervisor'] = h
                break
            if cur[0] != 'ROOT' and FUNCTIONAL_UNIT.match(cur[1] or ''):
                break  # satuan tanpa kepala tercatat: jangan naik ke kepala OPD
            cur = None if cur[0] == 'ROOT' else parent.get(cur)
    return rows, stats


def report(rows, stats):
    print('Pegawai:', len(rows), '| aktif:', sum(r['active'] for r in rows))
    print('Catatan:', dict(stats))
    by_opd = Counter(r['opd'] for r in rows)
    sup = Counter(r['opd'] for r in rows if r['supervisor'] is not None)
    print(f"{'OPD':18s} {'pegawai':>7s} {'beratasan':>9s}")
    for code, n in by_opd.most_common():
        print(f'{code:18s} {n:7d} {sup[code]:9d}')
    print('Total beratasan:', sum(sup.values()))
    kids = Counter(r['supervisor'] for r in rows if r['supervisor'] is not None)
    print('Bawahan langsung terbanyak:')
    for idx, n in kids.most_common(5):
        print(f"  {n:4d}  {rows[idx]['position'][:60]} ({rows[idx]['opd']})")
    tops = [r for r in rows if r['structural'] and r['active'] and r['supervisor'] is None]
    print('Pejabat struktural tanpa atasan (puncak OPD atau induk kosong):', len(tops))


def db_config():
    env_file = Path(__file__).resolve().parent.parent / '.env'
    local = {}
    if env_file.is_file():
        for line in env_file.read_text().splitlines():
            m = re.match(r'^\s*([A-Z_]+)\s*=\s*(.*?)\s*$', line)
            if m:
                local[m.group(1)] = m.group(2).strip('"\'')
    env = lambda k, d='': os.environ.get(k, local.get(k, d))  # noqa: E731
    return {'host': env('DB_HOST', '127.0.0.1'), 'port': int(env('DB_PORT', '3306')), 'database': env('DB_DATABASE', 'sikap360'),
            'user': env('DB_USERNAME', 'sikap'), 'password': env('DB_PASSWORD')}


def nip_hashes(nips):
    """Hash kata sandi awal (= NIP) lewat satu proses PHP agar formatnya sama dengan password_verify aplikasi."""
    out = subprocess.run(['php', '-r', 'while(($l=fgets(STDIN))!==false)echo password_hash(trim($l),PASSWORD_DEFAULT),"\\n";'],
                         input='\n'.join(nips) + '\n', capture_output=True, text=True, check=True).stdout.split()
    if len(out) != len(nips) or not all(h.startswith('$2y$') for h in out):
        raise SystemExit('Gagal membuat hash kata sandi melalui PHP.')
    return out


def apply(rows, admin_email, overwrite_assessments):
    import pymysql
    admins = [i for i, r in enumerate(rows) if r['email'] == admin_email.lower()]
    if len(admins) != 1:
        raise SystemExit(f'--admin {admin_email}: harus cocok dengan tepat satu pegawai yang diimpor (ditemukan {len(admins)}).')
    print(f'Membuat hash kata sandi awal (NIP) untuk {len(rows)} akun...')
    pw_hash = nip_hashes([r['nip'] for r in rows])
    conn = pymysql.connect(**db_config(), charset='utf8mb4', autocommit=False)
    cur = conn.cursor()
    cur.execute('SELECT COUNT(*) FROM assignments')
    existing = cur.fetchone()[0]
    if existing and not overwrite_assessments:
        raise SystemExit(f'Database berisi {existing} penugasan penilaian. Tambahkan --timpa-penilaian bila memang ingin menghapusnya.')
    cur.execute('SET FOREIGN_KEY_CHECKS=0')
    for t in ['answers', 'assignments', 'audit_logs', 'login_attempts', 'users', 'employees', 'opd', 'periods']:
        cur.execute(f'TRUNCATE TABLE {t}')
    cur.execute('SET FOREIGN_KEY_CHECKS=1')
    try:
        cur.execute("INSERT INTO settings(name,value) VALUES('kabupaten_name','Hulu Sungai Selatan') "
                    'ON DUPLICATE KEY UPDATE value=VALUES(value)')
        codes = sorted({r['opd'] for r in rows}, key=lambda c: OPD_NAMES[c])
        opd_id = {c: i + 1 for i, c in enumerate(codes)}
        cur.executemany('INSERT INTO opd(id,name,code) VALUES(%s,%s,%s)', [(opd_id[c], OPD_NAMES[c], c) for c in codes])
        order = sorted(range(len(rows)), key=lambda i: (OPD_NAMES[rows[i]['opd']], rows[i]['unit'], rows[i]['name']))
        emp_id = {idx: n + 1 for n, idx in enumerate(order)}
        cur.executemany(
            'INSERT INTO employees(id,name,nip,position,opd_id,unit,grade,email,active) VALUES(%s,%s,%s,%s,%s,%s,%s,%s,%s)',
            [(emp_id[i], rows[i]['name'], rows[i]['nip'], rows[i]['position'], opd_id[rows[i]['opd']], rows[i]['unit'],
              rows[i]['grade'], rows[i]['email'], rows[i]['active']) for i in order])
        cur.executemany('UPDATE employees SET supervisor_id=%s WHERE id=%s',
                        [(emp_id[r['supervisor']], emp_id[i]) for i, r in enumerate(rows) if r['supervisor'] is not None])
        cur.executemany('INSERT INTO users(employee_id,password_hash,role) VALUES(%s,%s,%s)',
                        [(emp_id[i], pw_hash[i], 'admin' if i == admins[0] else 'asn') for i in order])
        # Satu periode draf triwulan berjalan (seperti scripts/install.php) agar aplikasi bisa dibuka.
        today = datetime.date.today()
        q = (today.month - 1) // 3
        start = datetime.date(today.year, q * 3 + 1, 1)
        end = (datetime.date(today.year + 1, 1, 1) if q == 3 else datetime.date(today.year, q * 3 + 4, 1)) - datetime.timedelta(days=1)
        cur.execute('INSERT INTO periods(name,start_date,end_date) VALUES(%s,%s,%s)',
                    (f"Triwulan {['I', 'II', 'III', 'IV'][q]} {today.year}", start, end))
        conn.commit()
    except Exception:
        conn.rollback()
        raise
    finally:
        conn.close()


if __name__ == '__main__':
    ap = argparse.ArgumentParser(description='Impor pegawai dari ekspor SIASN (xlsx) ke SIKAP 360.')
    ap.add_argument('xlsx')
    ap.add_argument('--apply', action='store_true')
    ap.add_argument('--admin')
    ap.add_argument('--semua', action='store_true')
    ap.add_argument('--timpa-penilaian', action='store_true')
    args = ap.parse_args()
    if args.apply and not args.admin:
        raise SystemExit('Untuk --apply isi --admin EMAIL.')
    rows, stats = build(args.xlsx, args.semua)
    report(rows, stats)
    if args.apply:
        apply(rows, args.admin, args.timpa_penilaian)
        print('Impor selesai. Login: email pegawai, kata sandi awal: NIP; minta pegawai menggantinya setelah masuk.')
