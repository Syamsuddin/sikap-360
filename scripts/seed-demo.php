<?php
declare(strict_types=1);
// Demo: Kabupaten Hulu Sungai Selatan dengan lima OPD (60 pegawai).
//  DISDIKBUD (1–14): pola lama — semua pegawai lain menjadi rekan, agar data penilaian kaya (dipakai pengujian).
//  DINKES (15–19, 50), BKPSDM (20–34), DISKOMINFO (35–49), SETDA (51–60): pasangan penilai diturunkan dari struktur dengan aturan yang sama seperti assignment_generate.
//  Kepala OPD (puncak struktur) tidak dinilai; pegawai yang kelompok rekan/bawahannya < 3 (mis. 31, 41, 46) juga belum dinilai dan tampil "belum sah" di laman Struktur.
function demoSpec(): array
{
 // [nomor, nama, jabatan, unit kerja, pangkat, atasan (nomor), peran akun, kode OPD]
 return [
  [1,'Dina Puspitasari, S.Sos.','Kasubbag Umum dan Kepegawaian','Subbagian Umum dan Kepegawaian','Pembina (IV/a)',2,'admin','DISDIKBUD'],
  [2,'Ahmad Fauzi, S.STP., M.Si.','Sekretaris Dinas','Sekretariat','Pembina (IV/a)',14,'admin_opd','DISDIKBUD'],
  [3,'Rina Marlina, S.E.','Analis SDM Aparatur','Sekretariat','Penata (III/c)',2,'asn','DISDIKBUD'],
  [4,'Muhammad Rizki, S.Kom.','Pranata Komputer Ahli Muda','Sekretariat','Penata (III/c)',2,'asn','DISDIKBUD'],
  [5,'Siti Rahmah, S.Pd.','Analis SDM Aparatur','Sekretariat','Penata (III/c)',2,'asn','DISDIKBUD'],
  [6,'Budi Santoso, S.Sos.','Analis SDM Aparatur','Sekretariat','Penata (III/c)',2,'asn','DISDIKBUD'],
  [7,'Nur Aisyah, S.E.','Analis SDM Aparatur','Sekretariat','Penata (III/c)',2,'asn','DISDIKBUD'],
  [8,'Hendra Saputra, S.Kom.','Analis SDM Aparatur','Sekretariat','Penata (III/c)',2,'asn','DISDIKBUD'],
  [9,'Fitri Handayani, S.A.P.','Pelaksana','Subbagian Umum dan Kepegawaian','Penata (III/c)',1,'asn','DISDIKBUD'],
  [10,'Arif Rahman, S.Kom.','Pelaksana','Subbagian Umum dan Kepegawaian','Penata (III/c)',1,'asn','DISDIKBUD'],
  [11,'Dewi Lestari, S.E.','Pelaksana','Subbagian Umum dan Kepegawaian','Penata (III/c)',1,'asn','DISDIKBUD'],
  [12,'Rizal Maulana, S.A.P.','Pelaksana','Subbagian Umum dan Kepegawaian','Penata (III/c)',1,'asn','DISDIKBUD'],
  [13,'Nadia Putri, A.Md.','Pelaksana','Subbagian Umum dan Kepegawaian','Penata (III/c)',1,'asn','DISDIKBUD'],
  [14,'Bambang Prasetyo, M.Si.','Kepala Dinas','Pimpinan','Pembina Tk. I (IV/b)',null,'asn','DISDIKBUD'],
  [15,'drg. Lina Kartika, M.Kes.','Kepala Dinas','Pimpinan','Pembina Tk. I (IV/b)',null,'asn','DINKES'],
  [16,'Yusuf Hidayat, S.KM., M.M.','Sekretaris Dinas','Sekretariat','Pembina (IV/a)',15,'admin_opd','DINKES'],
  [17,'Ratna Sari, S.KM.','Analis Kesehatan','Sekretariat','Penata (III/c)',16,'asn','DINKES'],
  [18,'Fajar Nugroho, S.Kep.','Perawat Ahli Pertama','Sekretariat','Penata Muda Tk. I (III/b)',16,'asn','DINKES'],
  [19,'Mira Anggraini, A.Md.Keb.','Bidan Terampil','Sekretariat','Pengatur (II/c)',16,'asn','DINKES'],
  [20,'Drs. H. Syahrial Anwar, M.A.P.','Kepala Badan','Pimpinan','Pembina Tk. I (IV/b)',null,'asn','BKPSDM'],
  [21,'Hj. Norhayati, S.Sos., M.M.','Sekretaris Badan','Sekretariat','Pembina (IV/a)',20,'admin_opd','BKPSDM'],
  [22,'Rahmadi Noor, S.IP., M.Si.','Kepala Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian','Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian','Pembina (IV/a)',20,'asn','BKPSDM'],
  [23,'Ery Wahyudi, S.STP., M.A.P.','Kepala Bidang Mutasi dan Promosi','Bidang Mutasi dan Promosi','Pembina (IV/a)',20,'asn','BKPSDM'],
  [24,'Sri Wahyuni, S.Psi., M.Psi.','Kepala Bidang Pengembangan Kompetensi Aparatur','Bidang Pengembangan Kompetensi Aparatur','Pembina (IV/a)',20,'asn','BKPSDM'],
  [25,'Muhammad Yani, S.E.','Kasubbag Umum dan Kepegawaian','Sekretariat','Penata Tk. I (III/d)',21,'asn','BKPSDM'],
  [26,'Lisa Fitriani, S.E., Ak.','Kasubbag Perencanaan dan Keuangan','Sekretariat','Penata Tk. I (III/d)',21,'asn','BKPSDM'],
  [27,'Abdul Hakim, S.A.P.','Analis Kepegawaian Ahli Muda','Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian','Penata (III/c)',22,'asn','BKPSDM'],
  [28,'Wahyu Kurniawan, S.Kom.','Pranata Komputer Ahli Pertama','Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian','Penata Muda Tk. I (III/b)',22,'admin','BKPSDM'],
  [29,'Mariana Ulfah, A.Md.','Pengelola Data Kepegawaian','Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian','Penata Muda (III/a)',22,'asn','BKPSDM'],
  [30,'Rudi Hartono, S.Sos.','Analis Kepegawaian Ahli Pertama','Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian','Penata (III/c)',22,'asn','BKPSDM'],
  [31,'Nurul Huda, S.Pd., M.Pd.','Analis Pengembangan Kompetensi','Bidang Pengembangan Kompetensi Aparatur','Penata (III/c)',24,'asn','BKPSDM'],
  [32,'Zainal Abidin','Pelaksana','Sekretariat','Pengatur Tk. I (II/d)',21,'asn','BKPSDM'],
  [33,'Rina Wulandari, A.Md.','Pelaksana','Sekretariat','Pengatur Tk. I (II/d)',21,'asn','BKPSDM'],
  [34,'Taufik Hidayat','Pelaksana','Sekretariat','Pengatur (II/c)',21,'asn','BKPSDM'],
  [35,'Ir. H. Gusti Rahmat Fadillah, M.T.','Kepala Dinas','Pimpinan','Pembina Tk. I (IV/b)',null,'asn','DISKOMINFO'],
  [36,'Dra. Hj. Mahrita, M.M.','Sekretaris Dinas','Sekretariat','Pembina (IV/a)',35,'admin_opd','DISKOMINFO'],
  [37,'Andi Saputra, S.I.Kom., M.I.Kom.','Kepala Bidang Informasi dan Komunikasi Publik','Bidang Informasi dan Komunikasi Publik','Pembina (IV/a)',35,'asn','DISKOMINFO'],
  [38,'Deni Pratama, S.T., M.Kom.','Kepala Bidang Aplikasi Informatika','Bidang Aplikasi Informatika','Pembina (IV/a)',35,'asn','DISKOMINFO'],
  [39,'Herlina Sari, S.Si., M.Stat.','Kepala Bidang Persandian dan Statistik','Bidang Persandian dan Statistik','Pembina (IV/a)',35,'asn','DISKOMINFO'],
  [40,'Ahmad Rifani, S.A.P.','Kasubbag Umum dan Kepegawaian','Sekretariat','Penata Tk. I (III/d)',36,'asn','DISKOMINFO'],
  [41,'Maya Sari Dewi, S.I.Kom.','Pranata Humas Ahli Muda','Bidang Informasi dan Komunikasi Publik','Penata (III/c)',37,'asn','DISKOMINFO'],
  [42,'Rizky Ramadhan, S.Kom.','Pranata Komputer Ahli Muda','Bidang Aplikasi Informatika','Penata (III/c)',38,'asn','DISKOMINFO'],
  [43,'Fahmi Aziz, S.Kom.','Pranata Komputer Ahli Pertama','Bidang Aplikasi Informatika','Penata Muda Tk. I (III/b)',38,'asn','DISKOMINFO'],
  [44,'Indah Permatasari, S.T.','Analis Sistem Informasi dan Jaringan','Bidang Aplikasi Informatika','Penata Muda Tk. I (III/b)',38,'asn','DISKOMINFO'],
  [45,'Bayu Setiawan, S.ST.','Analis Keamanan Informasi','Bidang Aplikasi Informatika','Penata Muda Tk. I (III/b)',38,'asn','DISKOMINFO'],
  [46,'Yulia Rahmawati, S.Si.','Statistisi Ahli Pertama','Bidang Persandian dan Statistik','Penata Muda Tk. I (III/b)',39,'asn','DISKOMINFO'],
  [47,'Hendri Gunawan','Pelaksana','Sekretariat','Pengatur Tk. I (II/d)',36,'asn','DISKOMINFO'],
  [48,'Siti Nurjanah, A.Md.','Pelaksana','Sekretariat','Pengatur Tk. I (II/d)',36,'asn','DISKOMINFO'],
  [49,'Rahmat Hidayatullah','Pelaksana','Sekretariat','Pengatur (II/c)',36,'asn','DISKOMINFO'],
  [50,'Andi Firmansyah, S.Farm., Apt.','Apoteker Ahli Pertama','Sekretariat','Penata Muda Tk. I (III/b)',16,'asn','DINKES'],
  [51,'Drs. H. Muhammad Noor, M.AP.','Sekretaris Daerah','Pimpinan','Pembina Utama Muda (IV/c)',null,'asn','SETDA'],
  [52,'Hj. Siti Aminah, S.Sos., M.Si.','Asisten Administrasi Umum','Asisten Administrasi Umum','Pembina Tk. I (IV/b)',51,'asn','SETDA'],
  [53,'Rahmadi, S.IP.','Kepala Bagian Umum','Bagian Umum','Penata Tk. I (III/d)',52,'asn','SETDA'],
  [54,'Norhalimah, S.STP., M.AP.','Kepala Bagian Organisasi','Bagian Organisasi','Penata Tk. I (III/d)',52,'admin_opd','SETDA'],
  [55,'Akhmad Rifani, S.H., M.H.','Kepala Bagian Hukum','Bagian Hukum','Penata Tk. I (III/d)',52,'asn','SETDA'],
  [56,'Gusti Rina Wulandari, S.I.Kom.','Kepala Bagian Protokol dan Komunikasi Pimpinan','Bagian Protokol dan Komunikasi Pimpinan','Penata Tk. I (III/d)',52,'asn','SETDA'],
  [57,'Muhammad Ilham, S.A.P.','Analis Tata Usaha','Bagian Umum','Penata (III/c)',53,'asn','SETDA'],
  [58,'Nurul Hikmah, S.E.','Pengelola Keuangan','Bagian Umum','Penata (III/c)',53,'asn','SETDA'],
  [59,'Riduan, A.Md.','Pengadministrasi Umum','Bagian Umum','Pengatur (II/c)',53,'asn','SETDA'],
  [60,'Mahmudah, S.Sos.','Analis Tata Usaha','Bagian Umum','Penata (III/c)',53,'asn','SETDA'],
 ];
}
function demoOpds(): array
{
 return ['SETDA'=>'Sekretariat Daerah','BKPSDM'=>'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia','DISDIKBUD'=>'Dinas Pendidikan dan Kebudayaan','DISKOMINFO'=>'Dinas Komunikasi dan Informatika','DINKES'=>'Dinas Kesehatan'];
}
// Pasangan penilai dari struktur (aturan sama dengan Api::generateAssignments): atasan langsung; rekan seatasan bila ≥3; bawahan langsung bila ≥3; hanya-atasan dilewati.
function demoPairsFromStructure(array $members,array $boss): array
{
 $children=[];foreach($members as $n)if($boss[$n]!==null)$children[$boss[$n]][]=$n;
 $pairs=[];
 foreach($members as $n){
  if($boss[$n]===null)continue;$peers=array_values(array_filter($children[$boss[$n]],static fn(int $x)=>$x!==$n));$subs=$children[$n]??[];
  $rows=[[$n,$boss[$n],'atasan']];if(count($peers)>=3)foreach($peers as $r)$rows[]=[$n,$r,'rekan'];if(count($subs)>=3)foreach($subs as $r)$rows[]=[$n,$r,'bawahan'];
  if(count($rows)>1)array_push($pairs,...$rows);
 }
 return $pairs;
}
function seedDemo(PDO $db): void
{
 $db->beginTransaction();$hash=password_hash('SikapDemo2026!',PASSWORD_DEFAULT);$ids=[];$boss=[];$opdOf=[];
 $db->exec("INSERT INTO settings(name,value) VALUES('kabupaten_name','Hulu Sungai Selatan') ON DUPLICATE KEY UPDATE value=VALUES(value)");
 $opd=[];foreach(demoOpds() as $code=>$name){$s=$db->prepare('INSERT INTO opd(name,code) VALUES(?,?)');$s->execute([$name,$code]);$opd[$code]=(int)$db->lastInsertId();}
 foreach(demoSpec() as [$n,$name,$position,$unit,$grade,$bossNo,$role,$code]){
  $s=$db->prepare('INSERT INTO employees(name,nip,position,opd_id,unit,grade,email) VALUES(?,?,?,?,?,?,?)');$s->execute([$name,'DEMO-'.str_pad((string)$n,4,'0',STR_PAD_LEFT),$position,$opd[$code],$unit,$grade,'pegawai'.$n.'@example.test']);
  $ids[$n]=(int)$db->lastInsertId();$boss[$n]=$bossNo;$opdOf[$n]=$code;
  $s=$db->prepare('INSERT INTO users(employee_id,password_hash,role) VALUES(?,?,?)');$s->execute([$ids[$n],$hash,$role]);
 }
 $s=$db->prepare('UPDATE employees SET supervisor_id=? WHERE id=?');foreach($ids as $n=>$id)if($boss[$n]!==null)$s->execute([$ids[$boss[$n]],$id]);
 // Dua periode triwulan: triwulan berjalan (open) dan triwulan sebelumnya (published).
 $today=new DateTimeImmutable('today');$now=Period::quarter(...array_values(Period::at($today)));$prev=Period::quarter(...array_values(Period::at($today,-1)));
 $periods=[[$now['name'],$now['start_date'],$now['end_date'],'open'],[$prev['name'],$prev['start_date'],$prev['end_date'],'published']];
 $pairs=[];
 for($subject=1;$subject<=13;$subject++)for($rater=1;$rater<=14;$rater++){if($subject===$rater)continue;$pairs[]=[$subject,$rater,$rater===$boss[$subject]?'atasan':($rater!==14&&$boss[$rater]===$subject?'bawahan':'rekan')];}
 foreach(['DINKES','BKPSDM','DISKOMINFO','SETDA'] as $code)array_push($pairs,...demoPairsFromStructure(array_keys(array_filter($opdOf,static fn(string $c)=>$c===$code)),$boss));
 foreach($periods as [$periodName,$from,$to,$status]){
  $s=$db->prepare('INSERT INTO periods(name,start_date,end_date,status,published_at) VALUES(?,?,?,?,?)');$s->execute([$periodName,$from,$to,$status,$status==='published'?$to.' 23:59:00':null]);$pid=(int)$db->lastInsertId();
  foreach($pairs as [$subject,$rater,$role]){
   $submission=$status==='published'?'submitted':'pending';
   if($status==='open'){
    if($rater===1)$submission=$subject>=10?'submitted':(in_array($subject,[3,4],true)?'draft':'pending');
    elseif($subject===1&&($rater<=8||$rater===14))$submission='submitted';
    elseif($subject>14)$submission=$rater%3===0?'submitted':($rater%3===1?'draft':'pending'); // OPD lain: campuran agar dashboard hidup
   }
   $s=$db->prepare('INSERT INTO assignments(period_id,subject_id,rater_id,rater_role,status,feedback,submitted_at) VALUES(?,?,?,?,?,?,?)');$s->execute([$pid,$ids[$subject],$ids[$rater],$role,$submission,'',$submission==='submitted'?$from.' 12:00:00':null]);$aid=(int)$db->lastInsertId();
   $limit=$submission==='submitted'?7:($submission==='draft'?3:0);
   for($indicator=1;$indicator<=$limit;$indicator++){$s=$db->prepare('INSERT INTO answers(assignment_id,indicator_id,score) VALUES(?,?,?)');$s->execute([$aid,$indicator,($indicator+$rater)%3===0?5:4]);}
  }
 }
 $db->commit();
}
