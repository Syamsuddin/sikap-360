export const indicators = [
{id:1,name:'Berorientasi Pelayanan',description:'Memberikan pelayanan prima, ramah, cekatan, solutif, dapat diandalkan, serta melakukan perbaikan secara berkelanjutan.'},
{id:2,name:'Akuntabel',description:'Melaksanakan tugas dengan jujur, bertanggung jawab, cermat, disiplin, dan berintegritas. Menggunakan kewenangan serta sumber daya secara bertanggung jawab.'},
{id:3,name:'Kompeten',description:'Terus belajar dan mengembangkan kemampuan, membantu orang lain belajar, serta melaksanakan tugas dengan kualitas terbaik.'},
{id:4,name:'Harmonis',description:'Menghargai perbedaan, peduli dan membantu orang lain, serta membangun lingkungan kerja yang kondusif.'},
{id:5,name:'Loyal',description:'Memegang teguh Pancasila dan UUD 1945, mengutamakan kepentingan bangsa dan negara, serta menjaga nama baik ASN dan instansi.'},
{id:6,name:'Adaptif',description:'Berinovasi, menyesuaikan diri menghadapi perubahan, dan bertindak proaktif dalam menyelesaikan pekerjaan.'},
{id:7,name:'Kolaboratif',description:'Memberi kesempatan berbagai pihak untuk berkontribusi, terbuka dalam bekerja sama, serta menggerakkan sumber daya untuk tujuan bersama.'}
];
export const roleLabels={atasan:'Atasan',rekan:'Rekan sejawat',bawahan:'Bawahan'};
export const targetLabels={atasan:'Bawahan',rekan:'Rekan sejawat',bawahan:'Atasan'};
// Bobot bawaan per komposisi (cermin Scoring::DEFAULT_WEIGHTS); db.settings.weights menyimpan bobot yang diubah admin, periode terpublikasi menyimpan salinannya.
export const DEFAULT_WEIGHTS={'atasan,bawahan,rekan':{atasan:60,rekan:25,bawahan:15},'atasan,rekan':{atasan:75,rekan:25},'atasan,bawahan':{atasan:85,bawahan:15}};
export function weightsFor(roles,table=DEFAULT_WEIGHTS){const key=[...new Set(roles)].sort().join(',');return table[key]??null;}
const OPDS=[{id:1,name:"Sekretariat Daerah",code:"SETDA",active:1},{id:2,name:"Badan Kepegawaian dan Pengembangan Sumber Daya Manusia",code:"BKPSDM",active:1},{id:3,name:"Dinas Pendidikan dan Kebudayaan",code:"DISDIKBUD",active:1},{id:4,name:"Dinas Komunikasi dan Informatika",code:"DISKOMINFO",active:1},{id:5,name:"Dinas Kesehatan",code:"DINKES",active:1}];
// [nomor, nama, jabatan, unit kerja, pangkat, atasan, peran, kode OPD] — cermin scripts/seed-demo.php
const SPEC=[
[1,"Dina Puspitasari, S.Sos.","Kasubbag Umum dan Kepegawaian","Subbagian Umum dan Kepegawaian","Pembina (IV/a)",2,"admin","DISDIKBUD"],
[2,"Ahmad Fauzi, S.STP., M.Si.","Sekretaris Dinas","Sekretariat","Pembina (IV/a)",14,"admin_opd","DISDIKBUD"],
[3,"Rina Marlina, S.E.","Analis SDM Aparatur","Sekretariat","Penata (III/c)",2,"asn","DISDIKBUD"],
[4,"Muhammad Rizki, S.Kom.","Pranata Komputer Ahli Muda","Sekretariat","Penata (III/c)",2,"asn","DISDIKBUD"],
[5,"Siti Rahmah, S.Pd.","Analis SDM Aparatur","Sekretariat","Penata (III/c)",2,"asn","DISDIKBUD"],
[6,"Budi Santoso, S.Sos.","Analis SDM Aparatur","Sekretariat","Penata (III/c)",2,"asn","DISDIKBUD"],
[7,"Nur Aisyah, S.E.","Analis SDM Aparatur","Sekretariat","Penata (III/c)",2,"asn","DISDIKBUD"],
[8,"Hendra Saputra, S.Kom.","Analis SDM Aparatur","Sekretariat","Penata (III/c)",2,"asn","DISDIKBUD"],
[9,"Fitri Handayani, S.A.P.","Pelaksana","Subbagian Umum dan Kepegawaian","Penata (III/c)",1,"asn","DISDIKBUD"],
[10,"Arif Rahman, S.Kom.","Pelaksana","Subbagian Umum dan Kepegawaian","Penata (III/c)",1,"asn","DISDIKBUD"],
[11,"Dewi Lestari, S.E.","Pelaksana","Subbagian Umum dan Kepegawaian","Penata (III/c)",1,"asn","DISDIKBUD"],
[12,"Rizal Maulana, S.A.P.","Pelaksana","Subbagian Umum dan Kepegawaian","Penata (III/c)",1,"asn","DISDIKBUD"],
[13,"Nadia Putri, A.Md.","Pelaksana","Subbagian Umum dan Kepegawaian","Penata (III/c)",1,"asn","DISDIKBUD"],
[14,"Bambang Prasetyo, M.Si.","Kepala Dinas","Pimpinan","Pembina Tk. I (IV/b)",null,"asn","DISDIKBUD"],
[15,"drg. Lina Kartika, M.Kes.","Kepala Dinas","Pimpinan","Pembina Tk. I (IV/b)",null,"asn","DINKES"],
[16,"Yusuf Hidayat, S.KM., M.M.","Sekretaris Dinas","Sekretariat","Pembina (IV/a)",15,"admin_opd","DINKES"],
[17,"Ratna Sari, S.KM.","Analis Kesehatan","Sekretariat","Penata (III/c)",16,"asn","DINKES"],
[18,"Fajar Nugroho, S.Kep.","Perawat Ahli Pertama","Sekretariat","Penata Muda Tk. I (III/b)",16,"asn","DINKES"],
[19,"Mira Anggraini, A.Md.Keb.","Bidan Terampil","Sekretariat","Pengatur (II/c)",16,"asn","DINKES"],
[20,"Drs. H. Syahrial Anwar, M.A.P.","Kepala Badan","Pimpinan","Pembina Tk. I (IV/b)",null,"asn","BKPSDM"],
[21,"Hj. Norhayati, S.Sos., M.M.","Sekretaris Badan","Sekretariat","Pembina (IV/a)",20,"admin_opd","BKPSDM"],
[22,"Rahmadi Noor, S.IP., M.Si.","Kepala Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian","Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian","Pembina (IV/a)",20,"asn","BKPSDM"],
[23,"Ery Wahyudi, S.STP., M.A.P.","Kepala Bidang Mutasi dan Promosi","Bidang Mutasi dan Promosi","Pembina (IV/a)",20,"asn","BKPSDM"],
[24,"Sri Wahyuni, S.Psi., M.Psi.","Kepala Bidang Pengembangan Kompetensi Aparatur","Bidang Pengembangan Kompetensi Aparatur","Pembina (IV/a)",20,"asn","BKPSDM"],
[25,"Muhammad Yani, S.E.","Kasubbag Umum dan Kepegawaian","Sekretariat","Penata Tk. I (III/d)",21,"asn","BKPSDM"],
[26,"Lisa Fitriani, S.E., Ak.","Kasubbag Perencanaan dan Keuangan","Sekretariat","Penata Tk. I (III/d)",21,"asn","BKPSDM"],
[27,"Abdul Hakim, S.A.P.","Analis Kepegawaian Ahli Muda","Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian","Penata (III/c)",22,"asn","BKPSDM"],
[28,"Wahyu Kurniawan, S.Kom.","Pranata Komputer Ahli Pertama","Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian","Penata Muda Tk. I (III/b)",22,"admin","BKPSDM"],
[29,"Mariana Ulfah, A.Md.","Pengelola Data Kepegawaian","Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian","Penata Muda (III/a)",22,"asn","BKPSDM"],
[30,"Rudi Hartono, S.Sos.","Analis Kepegawaian Ahli Pertama","Bidang Pengadaan, Pemberhentian dan Informasi Kepegawaian","Penata (III/c)",22,"asn","BKPSDM"],
[31,"Nurul Huda, S.Pd., M.Pd.","Analis Pengembangan Kompetensi","Bidang Pengembangan Kompetensi Aparatur","Penata (III/c)",24,"asn","BKPSDM"],
[32,"Zainal Abidin","Pelaksana","Sekretariat","Pengatur Tk. I (II/d)",21,"asn","BKPSDM"],
[33,"Rina Wulandari, A.Md.","Pelaksana","Sekretariat","Pengatur Tk. I (II/d)",21,"asn","BKPSDM"],
[34,"Taufik Hidayat","Pelaksana","Sekretariat","Pengatur (II/c)",21,"asn","BKPSDM"],
[35,"Ir. H. Gusti Rahmat Fadillah, M.T.","Kepala Dinas","Pimpinan","Pembina Tk. I (IV/b)",null,"asn","DISKOMINFO"],
[36,"Dra. Hj. Mahrita, M.M.","Sekretaris Dinas","Sekretariat","Pembina (IV/a)",35,"admin_opd","DISKOMINFO"],
[37,"Andi Saputra, S.I.Kom., M.I.Kom.","Kepala Bidang Informasi dan Komunikasi Publik","Bidang Informasi dan Komunikasi Publik","Pembina (IV/a)",35,"asn","DISKOMINFO"],
[38,"Deni Pratama, S.T., M.Kom.","Kepala Bidang Aplikasi Informatika","Bidang Aplikasi Informatika","Pembina (IV/a)",35,"asn","DISKOMINFO"],
[39,"Herlina Sari, S.Si., M.Stat.","Kepala Bidang Persandian dan Statistik","Bidang Persandian dan Statistik","Pembina (IV/a)",35,"asn","DISKOMINFO"],
[40,"Ahmad Rifani, S.A.P.","Kasubbag Umum dan Kepegawaian","Sekretariat","Penata Tk. I (III/d)",36,"asn","DISKOMINFO"],
[41,"Maya Sari Dewi, S.I.Kom.","Pranata Humas Ahli Muda","Bidang Informasi dan Komunikasi Publik","Penata (III/c)",37,"asn","DISKOMINFO"],
[42,"Rizky Ramadhan, S.Kom.","Pranata Komputer Ahli Muda","Bidang Aplikasi Informatika","Penata (III/c)",38,"asn","DISKOMINFO"],
[43,"Fahmi Aziz, S.Kom.","Pranata Komputer Ahli Pertama","Bidang Aplikasi Informatika","Penata Muda Tk. I (III/b)",38,"asn","DISKOMINFO"],
[44,"Indah Permatasari, S.T.","Analis Sistem Informasi dan Jaringan","Bidang Aplikasi Informatika","Penata Muda Tk. I (III/b)",38,"asn","DISKOMINFO"],
[45,"Bayu Setiawan, S.ST.","Analis Keamanan Informasi","Bidang Aplikasi Informatika","Penata Muda Tk. I (III/b)",38,"asn","DISKOMINFO"],
[46,"Yulia Rahmawati, S.Si.","Statistisi Ahli Pertama","Bidang Persandian dan Statistik","Penata Muda Tk. I (III/b)",39,"asn","DISKOMINFO"],
[47,"Hendri Gunawan","Pelaksana","Sekretariat","Pengatur Tk. I (II/d)",36,"asn","DISKOMINFO"],
[48,"Siti Nurjanah, A.Md.","Pelaksana","Sekretariat","Pengatur Tk. I (II/d)",36,"asn","DISKOMINFO"],
[49,"Rahmat Hidayatullah","Pelaksana","Sekretariat","Pengatur (II/c)",36,"asn","DISKOMINFO"],
[50,"Andi Firmansyah, S.Farm., Apt.","Apoteker Ahli Pertama","Sekretariat","Penata Muda Tk. I (III/b)",16,"asn","DINKES"],
[51,"Drs. H. Muhammad Noor, M.AP.","Sekretaris Daerah","Pimpinan","Pembina Utama Muda (IV/c)",null,"asn","SETDA"],
[52,"Hj. Siti Aminah, S.Sos., M.Si.","Asisten Administrasi Umum","Asisten Administrasi Umum","Pembina Tk. I (IV/b)",51,"asn","SETDA"],
[53,"Rahmadi, S.IP.","Kepala Bagian Umum","Bagian Umum","Penata Tk. I (III/d)",52,"asn","SETDA"],
[54,"Norhalimah, S.STP., M.AP.","Kepala Bagian Organisasi","Bagian Organisasi","Penata Tk. I (III/d)",52,"admin_opd","SETDA"],
[55,"Akhmad Rifani, S.H., M.H.","Kepala Bagian Hukum","Bagian Hukum","Penata Tk. I (III/d)",52,"asn","SETDA"],
[56,"Gusti Rina Wulandari, S.I.Kom.","Kepala Bagian Protokol dan Komunikasi Pimpinan","Bagian Protokol dan Komunikasi Pimpinan","Penata Tk. I (III/d)",52,"asn","SETDA"],
[57,"Muhammad Ilham, S.A.P.","Analis Tata Usaha","Bagian Umum","Penata (III/c)",53,"asn","SETDA"],
[58,"Nurul Hikmah, S.E.","Pengelola Keuangan","Bagian Umum","Penata (III/c)",53,"asn","SETDA"],
[59,"Riduan, A.Md.","Pengadministrasi Umum","Bagian Umum","Pengatur (II/c)",53,"asn","SETDA"],
[60,"Mahmudah, S.Sos.","Analis Tata Usaha","Bagian Umum","Penata (III/c)",53,"asn","SETDA"]
];
// Pasangan penilai dari struktur (aturan sama dengan assignment_generate): atasan; rekan bila ≥3 (pejabat: sesama pejabat seatasan; staf: staf satu unit); bawahan bila ≥3; hanya-atasan dilewati.
function pairsFromStructure(members,boss,unit){const kids={};for(const n of members)if(boss[n])(kids[boss[n]]??=[]).push(n);const out=[];for(const n of members){if(!boss[n])continue;const subs=kids[n]??[],peers=subs.length?kids[boss[n]].filter(x=>x!==n&&kids[x]):members.filter(x=>unit[x]===unit[n]&&x!==n&&!kids[x]);const rows=[[n,boss[n],'atasan']];if(peers.length>=3)peers.forEach(r=>rows.push([n,r,'rekan']));if(subs.length>=3)subs.forEach(r=>rows.push([n,r,'bawahan']));if(rows.length>1)out.push(...rows);}return out;}
function seed(){
 const employees=SPEC.map(([n,name,position,unit,grade,boss,role,code])=>{const o=OPDS.find(x=>x.code===code);return{id:n,name,nip:'DEMO-'+String(n).padStart(4,'0'),position,opd_id:o.id,opd_name:o.name,opd_code:o.code,unit,grade,email:`pegawai${n}@example.test`,supervisor_id:boss,active:1,role};});
 const periods=[{id:1,name:'Triwulan III 2026',start_date:'2026-07-01',end_date:'2026-09-30',status:'open'},{id:2,name:'Triwulan II 2026',start_date:'2026-04-01',end_date:'2026-06-30',status:'published'}];
 const assignments=[];let id=1;
 const bossMap=Object.fromEntries(SPEC.map(r=>[r[0],r[5]])),boss=n=>bossMap[n],pairs=[];
 for(let subject=1;subject<=13;subject++)for(let rater=1;rater<=14;rater++){if(subject!==rater)pairs.push([subject,rater,rater===boss(subject)?'atasan':rater!==14&&boss(rater)===subject?'bawahan':'rekan']);}
 for(const code of ['DINKES','BKPSDM','DISKOMINFO','SETDA'])pairs.push(...pairsFromStructure(SPEC.filter(r=>r[7]===code).map(r=>r[0]),bossMap,Object.fromEntries(SPEC.map(r=>[r[0],r[3]]))));
 for(const p of periods)for(const [subject,rater,role] of pairs){
  let status=p.id===2?'submitted':'pending';
  if(p.id===1&&rater===1)status=subject>=10?'submitted':subject===3||subject===4?'draft':'pending';
  else if(p.id===1&&subject===1&&(rater<=8||rater===14))status='submitted';
  else if(p.id===1&&subject>14)status=rater%3===0?'submitted':rater%3===1?'draft':'pending';
  const count=status==='submitted'?7:status==='draft'?3:0;
  assignments.push({id:id++,period_id:p.id,subject_id:subject,rater_id:rater,rater_role:role,status,answers:Object.fromEntries(indicators.slice(0,count).map(x=>[x.id,(x.id+rater)%3===0?5:4])),feedback:'',version:1});
 }
 return {employees,periods,assignments,opds:OPDS.map(o=>({...o})),settings:{kabupaten_name:'Hulu Sungai Selatan'},admin:false};
}
const KEY='sikap360_demo_v7';
function load(){try{const d=JSON.parse(localStorage.getItem(KEY));if(d?.employees?.length&&d?.periods?.length&&Array.isArray(d.assignments)&&Array.isArray(d.opds))return d;}catch{}return seed();}
let db=load();
function persist(){localStorage.setItem(KEY,JSON.stringify(db));}
const currentWeights=()=>db.settings.weights??DEFAULT_WEIGHTS,periodWeights=p=>p?.status==='published'?p.weights??DEFAULT_WEIGHTS:currentWeights();
function result(periodId,subjectId=1){
 const a=db.assignments.filter(x=>x.period_id===periodId&&x.subject_id===subjectId),received=a.filter(x=>x.status==='submitted').length;
 const period=db.periods.find(x=>x.id===periodId);
 const weights=weightsFor(a.map(x=>x.rater_role),periodWeights(period));const complete=a.length>0&&received===a.length&&!!weights;const published=period?.status==='published';
 const groups=Object.entries(weights??{}).map(([role,weight])=>({role,weight,total:a.filter(x=>x.rater_role===role).length,received:a.filter(x=>x.rater_role===role&&x.status==='submitted').length}));
 const visible=complete&&(published||db.admin);
 let dimensions=[],score=null;const rawValues=[];
 if(visible){dimensions=indicators.map(ind=>{let value=0;for(const [role,weight]of Object.entries(weights)){const rows=a.filter(x=>x.rater_role===role);value+=rows.reduce((s,r)=>s+Number(r.answers[ind.id]),0)/rows.length*weight/100;}rawValues.push(value*20);return{id:ind.id,name:ind.name,score:Math.round(value*20*100)/100};});score=rawValues.reduce((s,x)=>s+x,0)/7;}
 return{received,expected:a.length,complete,published,visible,weights,groups,score:score===null?null:Math.round(score*100)/100,dimensions};
}
function supervisorOk(employeeId,supervisorId){if(supervisorId===employeeId)return false;const s=db.employees.find(x=>x.id===supervisorId);if(!s||!s.active)return false;for(let cur=s,i=0;cur&&i<200;i++){if(cur.id===employeeId)return false;cur=db.employees.find(x=>x.id===cur.supervisor_id);}return true;}
export async function demoRequest(action,body={}){
 await new Promise(r=>setTimeout(r,110));
 const periodId=Number(body.period_id)||1;
 const opdOf=id=>db.opds.find(o=>o.id===Number(id)),withOpd=e=>({...e,opd_name:opdOf(e.opd_id)?.name??'',opd_code:opdOf(e.opd_id)?.code??''});
 if(action==='info')return{kabupaten_name:db.settings.kabupaten_name};
 if(action==='bootstrap')return{user:{...withOpd(db.employees[0]),role:db.admin?'admin':'asn'},settings:db.settings,opds:db.opds.map(o=>({...o,employee_count:db.employees.filter(e=>e.opd_id===o.id&&e.active).length})),periods:db.periods,period:(p=>({...p,weights:periodWeights(p)}))(db.periods.find(x=>x.id===periodId)??db.periods[0]),weights:currentWeights(),indicators,tasks:db.assignments.filter(x=>x.period_id===periodId&&x.rater_id===1).map(x=>({...x,employee:withOpd(db.employees.find(e=>e.id===x.subject_id))})),result:result(periodId),employees:db.admin?db.employees.map(withOpd):[],csrf:'demo'};
 if(action==='assignment_list'){const emp=id=>db.employees.find(e=>e.id===Number(id)),opd=Number(body.opd_id)||null,base=db.assignments.filter(x=>x.period_id===periodId&&(!opd||emp(x.subject_id)?.opd_id===opd)),q=String(body.q??'').toLowerCase(),rows=base.filter(x=>(!['atasan','rekan','bawahan'].includes(body.role)||x.rater_role===body.role)&&(!['pending','draft','submitted'].includes(body.status)||x.status===body.status)&&(!q||(emp(x.subject_id)?.name+' '+emp(x.rater_id)?.name).toLowerCase().includes(q))),per=10,pages=Math.max(1,Math.ceil(rows.length/per)),page=Math.min(Math.max(1,Number(body.page)||1),pages);return{rows:rows.slice((page-1)*per,page*per).map(x=>({id:x.id,subject_id:x.subject_id,rater_id:x.rater_id,rater_role:x.rater_role,status:x.status,opd_id:emp(x.subject_id)?.opd_id,opd_code:opdOf(emp(x.subject_id)?.opd_id)?.code??'',subject_name:emp(x.subject_id)?.name??'',rater_name:emp(x.rater_id)?.name??''})),total:rows.length,page,pages,per,stats:{total:base.length,submitted:base.filter(x=>x.status==='submitted').length}};}
 if(action==='demo_role'){db.admin=!db.admin;persist();return{};}
 if(action==='demo_reset'){db=seed();persist();return{};}
 if(action==='logout'){db.admin=false;persist();return{};}
 if(action==='login')return{};
 if(action==='save_draft'){
  const a=db.assignments.find(x=>x.id===Number(body.id)&&x.rater_id===1);if(!a)throw Error('Tugas tidak ditemukan.');
  if(db.periods.find(x=>x.id===a.period_id).status!=='open'||a.status==='submitted')throw Error('Penilaian sudah dikunci.');
  if(Number(body.version)!==a.version)throw Error('Draf telah berubah. Muat ulang halaman.');
  if(Object.entries(body.answers).some(([k,v])=>!indicators.some(i=>i.id===Number(k))||!Number.isInteger(v)||v<1||v>5))throw Error('Nilai harus berupa bilangan 1 sampai 5.');
  Object.assign(a,{answers:body.answers,feedback:body.feedback??'',status:'draft',version:a.version+1});persist();return{version:a.version};
 }
 if(action==='submit'){
  const ids=body.ids.map(Number),rows=db.assignments.filter(x=>ids.includes(x.id)&&x.rater_id===1&&x.period_id===periodId);
  if(!rows.length||rows.length!==ids.length)throw Error('Pilih tugas yang valid.');
  if(db.periods.find(x=>x.id===periodId).status!=='open')throw Error('Periode telah ditutup.');
  if(rows.some(x=>x.status!=='draft'||indicators.some(i=>!x.answers[i.id])))throw Error('Lengkapi tujuh indikator sebelum mengirim.');
  rows.forEach(x=>{x.status='submitted';x.version++;});persist();return{count:rows.length};
 }
 if(!db.admin)throw Error('Akses administrator diperlukan.');
 if(action==='employee_save'){
  if(!body.name?.trim()||!body.nip?.trim())throw Error('Nama dan NIP wajib diisi.');
  if(db.employees.some(e=>e.nip===body.nip&&e.id!==Number(body.id)))throw Error('NIP sudah terdaftar.');
  const {password,...safeBody}=body;safeBody.supervisor_id=Number(body.supervisor_id)||null;safeBody.opd_id=Number(body.opd_id)||db.employees.find(x=>x.id===Number(body.id))?.opd_id||1;if(!db.opds.some(o=>o.id===safeBody.opd_id&&o.active))throw Error('OPD tidak aktif atau tidak ditemukan.');
  if(!['asn','admin_opd','admin'].includes(safeBody.role??'asn'))throw Error('Peran akun tidak valid.');if(Number(body.id)===1)delete safeBody.role;
  if(safeBody.supervisor_id&&!supervisorOk(Number(body.id)||0,safeBody.supervisor_id))throw Error('Atasan tidak valid atau membentuk struktur melingkar.');
  if(safeBody.supervisor_id&&db.employees.find(x=>x.id===safeBody.supervisor_id)?.opd_id!==safeBody.opd_id)throw Error('Atasan harus berasal dari OPD yang sama.');
  if(body.id){const e=db.employees.find(x=>x.id===Number(body.id));Object.assign(e,safeBody,{id:Number(body.id)});}else db.employees.push({...safeBody,id:Math.max(...db.employees.map(e=>e.id))+1,active:1,role:'asn'});persist();return{};
 }
 if(action==='supervisor_set'){
  const e=db.employees.find(x=>x.id===Number(body.employee_id));if(!e)throw Error('Pegawai tidak ditemukan.');
  const sup=Number(body.supervisor_id)||null;if(sup&&!supervisorOk(e.id,sup))throw Error('Atasan tidak valid atau membentuk struktur melingkar.');if(sup&&db.employees.find(x=>x.id===sup)?.opd_id!==e.opd_id)throw Error('Atasan harus berasal dari OPD yang sama.');
  e.supervisor_id=sup;persist();return{};
 }
 if(action==='assignment_generate'){
  const p=db.periods.find(x=>x.id===periodId);if(p.status!=='draft')throw Error('Penilai hanya diatur pada periode draf.');
  const opdId=Number(body.opd_id)||null,active=db.employees.filter(e=>e.active&&(!opdId||e.opd_id===opdId)),kids=id=>active.filter(e=>e.supervisor_id===id);let created=0,skipped=0;const warnings=[];let next=Math.max(0,...db.assignments.map(x=>x.id))+1;
  for(const e of active){
   if(!e.supervisor_id){warnings.push(`${e.name}: tanpa atasan, tidak dinilai.`);continue;}
   const subs=kids(e.id),peers=subs.length?kids(e.supervisor_id).filter(x=>x.id!==e.id&&kids(x.id).length):active.filter(x=>x.opd_id===e.opd_id&&x.unit.trim().toLowerCase()===e.unit.trim().toLowerCase()&&x.id!==e.id&&!kids(x.id).length),pairs=[[e.supervisor_id,'atasan']],notes=[];
   if(peers.length>=3)peers.forEach(x=>pairs.push([x.id,'rekan']));else if(peers.length)notes.push(`${e.name}: rekan sejawat hanya ${peers.length} orang (minimal 3), kelompok rekan dilewati.`);
   if(subs.length>=3)subs.forEach(x=>pairs.push([x.id,'bawahan']));else if(subs.length)notes.push(`${e.name}: bawahan hanya ${subs.length} orang (minimal 3), kelompok bawahan dilewati.`);
   if(pairs.length===1){warnings.push(`${e.name}: komposisi belum sah (rekan ${peers.length}, bawahan ${subs.length}; kelompok minimal 3 orang), tidak dinilai.`);continue;}
   warnings.push(...notes);
   for(const [rater,role] of pairs){if(db.assignments.some(x=>x.period_id===periodId&&x.subject_id===e.id&&x.rater_id===rater)){skipped++;continue;}db.assignments.push({id:next++,period_id:periodId,subject_id:e.id,rater_id:rater,rater_role:role,status:'pending',answers:{},feedback:'',version:1});created++;}
  }
  persist();return{created,skipped,warnings};
 }
 if(action==='opd_save'){
  const name=(body.name??'').trim(),code=(body.code??'').trim().toUpperCase(),active=Number(body.active)?1:0;if(!name||!/^[A-Z0-9_-]{2,20}$/.test(code))throw Error('Nama atau kode OPD tidak valid.');
  if(db.opds.some(o=>(o.name===name||o.code===code)&&o.id!==Number(body.id)))throw Error('Nama atau kode OPD sudah dipakai.');
  if(body.id){const o=db.opds.find(x=>x.id===Number(body.id));if(!o)throw Error('OPD tidak ditemukan.');if(!active&&db.employees.some(e=>e.opd_id===o.id&&e.active))throw Error('OPD masih memiliki pegawai aktif.');Object.assign(o,{name,code,active});}else db.opds.push({id:Math.max(...db.opds.map(o=>o.id))+1,name,code,active});
  persist();return{};
 }
 if(action==='weights_save'){const out={};for(const [key,roles] of Object.entries(DEFAULT_WEIGHTS)){out[key]={};for(const role of Object.keys(roles)){const w=body.weights?.[key]?.[role];if(!Number.isInteger(w)||w<1||w>99)throw Error('Bobot harus bilangan bulat 1 sampai 99.');out[key][role]=w;}if(Object.values(out[key]).reduce((s,x)=>s+x,0)!==100)throw Error('Jumlah bobot tiap kondisi harus 100%.');}db.settings.weights=out;persist();return{weights:out};}
 if(action==='settings_save'){const name=(body.kabupaten_name??'').trim();if(!name||name.length>120)throw Error('Nama kabupaten wajib diisi.');db.settings.kabupaten_name=name;persist();return{};}
 if(action==='period_create'){
  const year=Number(body.year),quarter=Number(body.quarter);if(!(year>=2000&&year<=2100)||!(quarter>=1&&quarter<=4))throw Error('Tahun atau triwulan tidak valid.');
  const m=(quarter-1)*3,start=`${year}-${String(m+1).padStart(2,'0')}-01`,end=`${year}-${String(m+3).padStart(2,'0')}-${new Date(year,m+3,0).getDate()}`,name=`Triwulan ${['I','II','III','IV'][quarter-1]} ${year}`;
  if(db.periods.some(p=>p.start_date===start))throw Error(`Periode ${name} sudah ada.`);
  db.periods.push({id:Math.max(...db.periods.map(x=>x.id))+1,name,start_date:start,end_date:end,status:'draft'});persist();return{};
 }
 if(action==='period_status'){
  const p=db.periods.find(x=>x.id===periodId);const next={draft:['open'],open:['closed'],closed:['open','published'],published:[]};
  if(!next[p.status].includes(body.status))throw Error('Perubahan status tidak valid.');
  const rows=db.assignments.filter(x=>x.period_id===periodId);
  if(body.status==='open'){
   if(!rows.length)throw Error('Tambahkan penilai sebelum membuka periode.');
   for(const subject of new Set(rows.map(x=>x.subject_id))){const a=rows.filter(x=>x.subject_id===subject);if(!weightsFor(a.map(x=>x.rater_role))||a.filter(x=>x.rater_role==='atasan').length!==1)throw Error('Komposisi memerlukan tepat satu atasan dan kelompok penilai pendamping.');for(const role of ['rekan','bawahan']){const count=a.filter(x=>x.rater_role===role).length;if(count>0&&count<3)throw Error('Minimal 3 penilai rekan/bawahan per kelompok.');}}
  }
  if(body.status==='published'&&rows.some(x=>x.status!=='submitted'))throw Error('Seluruh penilaian wajib selesai sebelum publikasi.');
  p.status=body.status;if(p.status==='published')p.weights=currentWeights();persist();return{};
 }
 if(action==='assignment_create'){
  const p=db.periods.find(x=>x.id===periodId);if(p.status!=='draft')throw Error('Penilai hanya diatur pada periode draf.');
  const subject=Number(body.subject_id),rater=Number(body.rater_id);
  if(subject===rater)throw Error('Pegawai tidak boleh menilai dirinya sendiri.');if(db.employees.find(x=>x.id===subject)?.opd_id!==db.employees.find(x=>x.id===rater)?.opd_id)throw Error('Penilai dan pegawai yang dinilai harus berasal dari OPD yang sama.');
  if(db.assignments.some(x=>x.period_id===periodId&&x.subject_id===subject&&x.rater_id===rater))throw Error('Penilai sudah ditugaskan.');
  if(!['atasan','rekan','bawahan'].includes(body.rater_role))throw Error('Peran tidak valid.');
  db.assignments.push({id:Math.max(0,...db.assignments.map(x=>x.id))+1,period_id:periodId,subject_id:subject,rater_id:rater,rater_role:body.rater_role,status:'pending',answers:{},feedback:'',version:1});persist();return{};
 }
 if(action==='assignment_delete'){
  const a=db.assignments.find(x=>x.id===Number(body.id));if(!a||db.periods.find(x=>x.id===a.period_id).status!=='draft')throw Error('Tugas yang sudah aktif tidak dapat dihapus.');db.assignments=db.assignments.filter(x=>x.id!==a.id);persist();return{};
 }
 throw Error('Tindakan tidak dikenal.');
}
