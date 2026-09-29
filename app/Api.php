<?php
declare(strict_types=1);
final class ApiError extends RuntimeException
{
 public function __construct(string $message, public readonly int $status=422) { parent::__construct($message); }
}
final class Api
{
 private ?array $user=null;
 public function __construct(private PDO $db, private array $config) {}
 private function all(string $sql,array $params=[]): array { $s=$this->db->prepare($sql);$s->execute($params);return $s->fetchAll(); }
 private function one(string $sql,array $params=[]): ?array {return $this->all($sql,$params)[0]??null;}
 private function run(string $sql,array $params=[]): void {$s=$this->db->prepare($sql);$s->execute($params);}
 private function audit(string $action,string $entity,?int $id=null,array $meta=[]): void {$this->run('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,metadata) VALUES(?,?,?,?,?)',[$this->user['user_id']??null,$action,$entity,$id,json_encode($meta,JSON_THROW_ON_ERROR)]);}
 private function transaction(callable $fn): mixed { $this->db->beginTransaction();try{$r=$fn();$this->db->commit();return $r;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;} }
 private function auth(): array
 {
  if(empty($_SESSION['user_id']) || time()-($_SESSION['last_seen']??0)>$this->config['session_ttl']) {$_SESSION=[];throw new ApiError('Silakan masuk untuk melanjutkan.',401);}
  $u=$this->one('SELECT e.*,u.id AS user_id,u.role,u.auth_version,o.name AS opd_name,o.code AS opd_code FROM users u JOIN employees e ON e.id=u.employee_id JOIN opd o ON o.id=e.opd_id WHERE u.id=? AND e.active=1',[$_SESSION['user_id']]);
  if(!$u || $u['auth_version']!==($_SESSION['auth_version']??null)){$_SESSION=[];throw new ApiError('Sesi berakhir. Silakan masuk kembali.',401);}
  $_SESSION['last_seen']=time();$this->user=$u;return $u;
 }
 // IP klien: X-Forwarded-For hanya dipercaya bila permintaan datang dari proxy yang terdaftar di TRUSTED_PROXIES.
 private function clientIp(): string
 {
  $remote=$_SERVER['REMOTE_ADDR']??'unknown';
  if(!in_array($remote,$this->config['trusted_proxies'],true)||empty($_SERVER['HTTP_X_FORWARDED_FOR']))return $remote;
  $first=trim(explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
  return filter_var($first,FILTER_VALIDATE_IP)?:$remote;
 }
 // Peran: admin = administrator kabupaten (semua OPD); admin_opd = administrator satu OPD (lingkup opd_id miliknya); asn = pegawai biasa.
 private function admin(): void {if(!in_array($this->user['role']??'',['admin','admin_opd'],true))throw new ApiError('Akses administrator diperlukan.',403);}
 private function superadmin(): void {if(($this->user['role']??'')!=='admin')throw new ApiError('Akses administrator kabupaten diperlukan.',403);}
 private function scope(): ?int {return ($this->user['role']??'')==='admin_opd'?(int)$this->user['opd_id']:null;}
 // Pegawai harus berada dalam lingkup OPD admin yang sedang masuk; mengembalikan opd_id pegawai.
 private function inScope(int $employeeId,bool $lock=false): int
 {
  $e=$this->one('SELECT opd_id FROM employees WHERE id=?'.($lock?' FOR UPDATE':''),[$employeeId]);if(!$e)throw new ApiError('Pegawai tidak ditemukan.',404);
  if($this->scope()!==null&&(int)$e['opd_id']!==$this->scope())throw new ApiError('Pegawai berada di luar OPD Anda.',403);return (int)$e['opd_id'];
 }
 private function settings(): array {$out=[];foreach($this->all('SELECT name,value FROM settings') as $r)$out[$r['name']]=$r['value'];return $out+['kabupaten_name'=>'Hulu Sungai Selatan'];}
 // Bobot tersimpan sebagai JSON; kosong atau rusak kembali ke bobot bawaan.
 private function weightTable(?string $json): array {if($json===null)return Scoring::DEFAULT_WEIGHTS;try{return Scoring::normalizeWeights(json_decode($json,true,8,JSON_THROW_ON_ERROR));}catch(JsonException|DomainException){return Scoring::DEFAULT_WEIGHTS;}}
 // Bobot melekat pada tiap periode (periods.weights): dapat diubah selama draf dan terkunci sejak periode dibuka. NULL (periode sebelum v1.8) berarti bobot bawaan.
 private function periodWeights(array $p): array {return $this->weightTable($p['weights']??null);}
 // Periode baru menyalin bobot periode terbaru menurut urutan daftar periode; tanpa periode, bobot bawaan.
 private function latestWeights(): array {return $this->weightTable($this->one('SELECT weights FROM periods ORDER BY start_date DESC,id DESC LIMIT 1')['weights']??null);}
 private function requiredText(array $body,string $key,int $max=160): string {$v=trim(is_string($body[$key]??null)?$body[$key]:'');if($v===''||mb_strlen($v)>$max)throw new ApiError('Kolom '.$key.' wajib diisi dan maksimal '.$max.' karakter.');return $v;}
 private function period(int $id,bool $lock=false): array {$p=$this->one('SELECT * FROM periods WHERE id=?'.($lock?' FOR UPDATE':''),[$id]);if(!$p)throw new ApiError('Periode tidak ditemukan.',404);return $p;}
 // Status periode yang menentukan: selama berstatus open, penilaian dapat disimpan dan dikirim di luar rentang tanggal; administrator menghentikannya dengan menutup periode.
 private function openPeriod(array $period): void {if($period['status']!=='open')throw new ApiError('Periode tidak sedang menerima penilaian.',409);}
 private function indicators(): array {return $this->all('SELECT * FROM indicators ORDER BY id');}
 private function answersFor(int $id): array {$out=[];foreach($this->all('SELECT indicator_id,score FROM answers WHERE assignment_id=?',[$id]) as $a)$out[(int)$a['indicator_id']]=(int)$a['score'];return $out;}
 private function scoringRows(int $periodId,int $subjectId): array {$rows=$this->all('SELECT id,rater_role,status FROM assignments WHERE period_id=? AND subject_id=?',[$periodId,$subjectId]);foreach($rows as &$r)$r['answers']=$this->answersFor((int)$r['id']);return $rows;}
 public function handle(string $action,array $body): array
 {
  if($action==='login')return $this->login($body);
  if($action==='info')return ['kabupaten_name'=>$this->settings()['kabupaten_name']];
  $this->auth();
  if($action!=='bootstrap'&&!hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??''))throw new ApiError('Token keamanan tidak valid. Muat ulang halaman.',403);
  if($action==='bootstrap')return $this->bootstrap((int)($body['period_id']??0));
  if($action==='logout')return $this->logout();
  if($action==='password_change')return $this->changePassword($body);
  if($action==='save_draft')return $this->saveDraft($body);
  if($action==='submit')return $this->submit($body);
  $this->admin();
  if(in_array($action,['period_create','period_status','opd_save','settings_save','weights_save'],true))$this->superadmin();
  return match($action){'assignment_list'=>$this->listAssignments($body),'employee_save'=>$this->saveEmployee($body),'period_create'=>$this->createPeriod($body),'period_status'=>$this->periodStatus($body),'assignment_create'=>$this->createAssignment($body),'assignment_delete'=>$this->deleteAssignment($body),'supervisor_set'=>$this->setSupervisor($body),'assignment_generate'=>$this->generateAssignments($body),'opd_save'=>$this->saveOpd($body),'settings_save'=>$this->saveSettings($body),'weights_save'=>$this->saveWeights($body),default=>throw new ApiError('Tindakan tidak ditemukan.',404)};
 }
 private function login(array $body): array
 {
  // Username berupa NIP atau email: berisi "@" dicari sebagai email, selain itu sebagai NIP (spasi diabaikan). Kunci "email" tetap diterima untuk klien lama.
  $raw=$body['username']??$body['email']??null;$username=trim(is_string($raw)?$raw:'');$password=is_string($body['password']??null)?$body['password']:'';
  $byEmail=str_contains($username,'@');$username=$byEmail?strtolower($username):preg_replace('/\s+/','',$username);
  if($username===''||strlen($username)>160||strlen($password)>1024)throw new ApiError('NIP/email atau kata sandi salah.',401);
  $account=hash('sha256',$username);$ip=hash('sha256',$this->clientIp());
  $this->run('DELETE FROM login_attempts WHERE attempted_at<DATE_SUB(NOW(),INTERVAL 1 DAY)');
  $attempts=$this->one('SELECT SUM(account_hash=?) AS by_account,SUM(ip_hash=?) AS by_ip FROM login_attempts WHERE attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)',[$account,$ip]);
  if((int)$attempts['by_account']>=$this->config['login_limit_account']||(int)$attempts['by_ip']>=$this->config['login_limit_ip'])throw new ApiError('Terlalu banyak percobaan. Coba lagi dalam 15 menit.',429);
  $this->run('INSERT INTO login_attempts(account_hash,ip_hash) VALUES(?,?)',[$account,$ip]);
  $u=$this->one('SELECT u.* FROM users u JOIN employees e ON e.id=u.employee_id WHERE e.'.($byEmail?'email':'nip').'=? AND e.active=1',[$username]);
  $dummy='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
  if(!password_verify($password,$u['password_hash']??$dummy)||!$u)throw new ApiError('NIP/email atau kata sandi salah.',401);
  session_regenerate_id(true);$_SESSION=['user_id'=>(int)$u['id'],'auth_version'=>(int)$u['auth_version'],'last_seen'=>time(),'csrf'=>bin2hex(random_bytes(32))];
  $this->user=['user_id'=>(int)$u['id']];$this->audit('login','user',(int)$u['id']);
  $this->run('DELETE FROM login_attempts WHERE account_hash=?',[$account]);return ['ok'=>true];
 }
 // Keluar: catat audit, kosongkan dan hancurkan sesi, lalu hapus cookie sesi di browser agar ID lama tidak terkirim lagi.
 private function logout(): array
 {
  $this->audit('logout','user',(int)$this->user['user_id']);
  $_SESSION=[];
  if(ini_get('session.use_cookies'))setcookie(session_name(),'',['expires'=>time()-86400,'path'=>'/','httponly'=>true,'secure'=>$this->config['secure_cookie'],'samesite'=>'Strict']);
  session_destroy();return ['ok'=>true];
 }
 private function bootstrap(int $periodId): array
 {
  $periods=$this->all('SELECT * FROM periods ORDER BY start_date DESC,id DESC');if(!$periods)throw new ApiError('Belum ada periode. Jalankan instalasi awal.',409);
  if(!$periodId||!array_filter($periods,static fn($p)=>(int)$p['id']===$periodId)){$default=null;foreach(['open','closed','published'] as $status){foreach($periods as $p)if($p['status']===$status){$default=$p;break 2;}}$periodId=(int)($default??$periods[0])['id'];}
  $p=$this->period($periodId);$ind=$this->indicators();$tasks=$this->all('SELECT id,period_id,subject_id,rater_role,status,version,feedback FROM assignments WHERE period_id=? AND rater_id=? ORDER BY id',[$periodId,$this->user['id']]);
  foreach($tasks as &$t){$t['answers']=(object)$this->answersFor((int)$t['id']);$t['employee']=$this->one('SELECT e.id,e.name,e.nip,e.position,e.unit,e.grade,o.name AS opd_name FROM employees e JOIN opd o ON o.id=e.opd_id WHERE e.id=?',[$t['subject_id']]);}
  $p['weights']=$this->periodWeights($p);$periods=array_map(static function(array $x){unset($x['weights']);return $x;},$periods);
  $result=Scoring::calculate($this->scoringRows($periodId,(int)$this->user['id']),$ind,$p['weights']);$result['published']=$p['status']==='published';$result['visible']=$result['complete']&&($result['published']||$this->user['role']==='admin');
  if(!$result['visible']){$result['score']=null;$result['dimensions']=[];}
  $user=$this->user;unset($user['auth_version'],$user['user_id']);
  $isAdmin=in_array($this->user['role'],['admin','admin_opd'],true);$scope=$this->scope();
  // Nama/jabatan/OPD atasan ikut dikirim agar atasan lintas OPD (mis. Bupati bagi kepala OPD) tetap tampil di lingkup admin OPD.
  $employees=$isAdmin?$this->all('SELECT e.*,u.role,o.name AS opd_name,o.code AS opd_code,s.name AS supervisor_name,s.position AS supervisor_position,s.opd_id AS supervisor_opd_id FROM employees e JOIN users u ON u.employee_id=e.id JOIN opd o ON o.id=e.opd_id LEFT JOIN employees s ON s.id=e.supervisor_id'.($scope!==null?' WHERE e.opd_id=?':'').' ORDER BY o.name,e.name',$scope!==null?[$scope]:[]):[];
  return ['user'=>$user,'settings'=>$this->settings(),'opds'=>$this->all('SELECT o.*,(SELECT COUNT(*) FROM employees e WHERE e.opd_id=o.id AND e.active=1) AS employee_count FROM opd o ORDER BY o.name'),'periods'=>$periods,'period'=>$p,'indicators'=>$ind,'tasks'=>$tasks,'result'=>$result,'csrf'=>$_SESSION['csrf'],'employees'=>$employees];
 }
 private function changePassword(array $b): array
 {
  $password=$this->requiredText($b,'password',128);if(strlen($password)<12)throw new ApiError('Kata sandi minimal 12 karakter.');
  $u=$this->one('SELECT password_hash FROM users WHERE id=?',[$this->user['user_id']]);if(!password_verify((string)($b['current_password']??''),$u['password_hash']))throw new ApiError('Kata sandi saat ini salah.',403);
  $this->run('UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE id=?',[password_hash($password,PASSWORD_DEFAULT),$this->user['user_id']]);$_SESSION['auth_version']++;session_regenerate_id(true);$this->audit('password_changed','user',(int)$this->user['user_id']);return ['ok'=>true];
 }
 private function saveDraft(array $b): array
 {
  $id=(int)($b['id']??0);$a=$this->one('SELECT period_id FROM assignments WHERE id=? AND rater_id=?',[$id,$this->user['id']]);if(!$a)throw new ApiError('Penugasan tidak ditemukan.',404);
  return $this->transaction(function()use($a,$b,$id){
   $this->openPeriod($this->period((int)$a['period_id'],true));
   $row=$this->one('SELECT * FROM assignments WHERE id=? AND rater_id=? FOR UPDATE',[$id,$this->user['id']]);if(!$row)throw new ApiError('Penugasan tidak ditemukan.',404);
   if($row['status']==='submitted')throw new ApiError('Penilaian telah dikirim dan dikunci.',409);
   if((int)($b['version']??0)!==(int)$row['version'])throw new ApiError('Draf telah berubah dari sesi lain. Muat ulang halaman.',409);
   if(!is_array($b['answers']??null))throw new ApiError('Jawaban tidak valid.');$answers=$b['answers'];Scoring::validateAnswers($answers,array_map('intval',array_column($this->indicators(),'id')),false);
   $feedback=is_string($b['feedback']??null)?trim($b['feedback']):'';if(mb_strlen($feedback)>1000)throw new ApiError('Catatan maksimal 1.000 karakter.');
   $this->run('DELETE FROM answers WHERE assignment_id=?',[$id]);foreach($answers as $ind=>$score)$this->run('INSERT INTO answers(assignment_id,indicator_id,score) VALUES(?,?,?)',[$id,(int)$ind,$score]);
   $this->run("UPDATE assignments SET status='draft',feedback=?,version=version+1 WHERE id=?",[$feedback,$id]);$this->audit('draft_saved','assignment',$id,['answered'=>count($answers)]);return ['version'=>(int)$row['version']+1];
  });
 }
 private function submit(array $b): array
 {
  $periodId=(int)($b['period_id']??0);if(!is_array($b['ids']??null)||!count($b['ids'])||count($b['ids'])>500)throw new ApiError('Pilih 1 sampai 500 penilaian.');
  $ids=array_map('intval',$b['ids']);if(count($ids)!==count(array_unique($ids)))throw new ApiError('Penugasan duplikat.');sort($ids);
  return $this->transaction(function()use($periodId,$ids){
   $this->openPeriod($this->period($periodId,true));$indIds=array_map('intval',array_column($this->indicators(),'id'));
   foreach($ids as $id){$a=$this->one('SELECT * FROM assignments WHERE id=? AND period_id=? AND rater_id=? FOR UPDATE',[$id,$periodId,$this->user['id']]);if(!$a)throw new ApiError('Penugasan tidak ditemukan atau bukan milik Anda.',403);if($a['status']!=='draft')throw new ApiError('Hanya draf yang dapat dikirim.',409);Scoring::validateAnswers($this->answersFor($id),$indIds,true);}
   foreach($ids as $id){$this->run("UPDATE assignments SET status='submitted',submitted_at=NOW(),version=version+1 WHERE id=?",[$id]);$this->audit('assessment_submitted','assignment',$id);}
   return ['count'=>count($ids)];
  });
 }
 private function saveEmployee(array $b): array
 {
  $id=(int)($b['id']??0);$fields=[];foreach(['name','nip','position','unit','grade','email'] as $key)$fields[$key]=$this->requiredText($b,$key,$key==='nip'?18:($key==='grade'?80:160));
  // NIP 18 digit; pejabat non-ASN (mis. Bupati) memakai kode huruf besar seperti BUPATI-HSS yang juga dipakai untuk masuk. Kode DEMO-0001 milik data demo.
  if(!preg_match('/^\d{18}$/D',$fields['nip'])&&!preg_match('/^[A-Z][A-Z0-9-]{3,17}$/D',$fields['nip']))throw new ApiError('NIP harus 18 digit, atau kode huruf besar untuk pejabat non-ASN (mis. BUPATI-HSS).');
  $fields['email']=strtolower($fields['email']);if(!filter_var($fields['email'],FILTER_VALIDATE_EMAIL))throw new ApiError('Email tidak valid.');
  // Kata sandi awal pegawai baru = NIP bila dikosongkan; saat edit, kosong berarti tidak diganti.
  $password=is_string($b['password']??null)?$b['password']:'';if(!$id&&$password==='')$password=$fields['nip'];if((!$id||$password!=='')&&(strlen($password)<12||strlen($password)>128))throw new ApiError('Kata sandi harus 12 sampai 128 karakter.');
  $supervisor=(int)($b['supervisor_id']??0)?:null;
  // OPD: admin kabupaten memilih bebas; admin OPD selalu OPD-nya sendiri. Peran hanya diatur admin kabupaten (dan tidak untuk akunnya sendiri).
  $opdId=$this->scope()??(int)($b['opd_id']??0);if(!$this->one('SELECT id FROM opd WHERE id=? AND active=1',[$opdId]))throw new ApiError('OPD tidak aktif atau tidak ditemukan.',422);
  $role=$this->scope()===null&&isset($b['role'])?$b['role']:null;if($role!==null&&!in_array($role,['asn','admin_opd','admin'],true))throw new ApiError('Peran akun tidak valid.');
  return $this->transaction(function()use($id,$fields,$password,$supervisor,$opdId,$role){
   if($id){
    $old=$this->one('SELECT opd_id FROM employees WHERE id=? FOR UPDATE',[$id]);if(!$old)throw new ApiError('Pegawai tidak ditemukan.',404);$this->inScope($id);
    if((int)$old['opd_id']!==$opdId&&$this->one('SELECT id FROM employees WHERE supervisor_id=? LIMIT 1',[$id]))throw new ApiError('Pegawai masih memiliki bawahan; pindahkan bawahannya terlebih dahulu sebelum mengganti OPD.',409);
    $this->validateSupervisor($id,$supervisor,$opdId);$this->run('UPDATE employees SET name=?,nip=?,position=?,unit=?,grade=?,email=?,opd_id=?,supervisor_id=? WHERE id=?',[...array_values($fields),$opdId,$supervisor,$id]);
    if($password!==''){$this->run('UPDATE users SET password_hash=?,auth_version=auth_version+1 WHERE employee_id=?',[password_hash($password,PASSWORD_DEFAULT),$id]);if($id===(int)$this->user['id'])$_SESSION['auth_version']++;}
    if($role!==null&&$id!==(int)$this->user['id'])$this->run('UPDATE users SET role=? WHERE employee_id=?',[$role,$id]);
   }else{$this->validateSupervisor(0,$supervisor,$opdId);$this->run('INSERT INTO employees(name,nip,position,unit,grade,email,opd_id,supervisor_id) VALUES(?,?,?,?,?,?,?,?)',[...array_values($fields),$opdId,$supervisor]);$id=(int)$this->db->lastInsertId();$this->run('INSERT INTO users(employee_id,password_hash,role) VALUES(?,?,?)',[$id,password_hash($password,PASSWORD_DEFAULT),$role??'asn']);}
   $this->audit('employee_saved','employee',$id,['opd_id'=>$opdId,'role'=>$role]);return ['id'=>$id];
  });
 }
 // Atasan langsung: harus pegawai aktif lain dan tidak boleh membentuk siklus (ditelusuri ke atas sampai akar).
 // Atasan lintas OPD (mis. Bupati bagi kepala OPD) hanya dapat ditetapkan atau dilepas admin kabupaten; admin OPD hanya dapat mempertahankannya.
 private function validateSupervisor(int $employeeId,?int $supervisorId,int $opdId): void
 {
  $current=$employeeId?$this->one('SELECT s.id,s.opd_id FROM employees e JOIN employees s ON s.id=e.supervisor_id WHERE e.id=?',[$employeeId]):null;$keep=$current&&(int)$current['id']===$supervisorId;
  if($this->scope()!==null&&$current&&!$keep&&(int)$current['opd_id']!==$opdId)throw new ApiError('Atasan dari OPD lain hanya dapat diubah administrator kabupaten.');
  if($supervisorId===null)return;if($supervisorId===$employeeId)throw new ApiError('Pegawai tidak dapat menjadi atasan dirinya sendiri.');
  $sup=$this->one('SELECT opd_id FROM employees WHERE id=? AND active=1',[$supervisorId]);if(!$sup)throw new ApiError('Atasan tidak aktif atau tidak ditemukan.',404);
  if((int)$sup['opd_id']!==$opdId&&$this->scope()!==null&&!$keep)throw new ApiError('Atasan dari OPD lain hanya dapat ditetapkan administrator kabupaten.');
  for($cur=$supervisorId,$depth=0;$cur!==null&&$depth<200;$depth++){if($cur===$employeeId)throw new ApiError('Struktur melingkar: atasan yang dipilih berada di bawah pegawai ini.',409);$row=$this->one('SELECT supervisor_id FROM employees WHERE id=?',[$cur]);$cur=isset($row['supervisor_id'])?(int)$row['supervisor_id']:null;}
 }
 private function setSupervisor(array $b): array
 {
  $id=(int)($b['employee_id']??0);$supervisor=(int)($b['supervisor_id']??0)?:null;
  return $this->transaction(function()use($id,$supervisor){
   $e=$this->one('SELECT supervisor_id,opd_id FROM employees WHERE id=? FOR UPDATE',[$id]);if(!$e)throw new ApiError('Pegawai tidak ditemukan.',404);$this->inScope($id);
   $this->validateSupervisor($id,$supervisor,(int)$e['opd_id']);$this->run('UPDATE employees SET supervisor_id=? WHERE id=?',[$supervisor,$id]);
   $this->audit('supervisor_changed','employee',$id,['from'=>isset($e['supervisor_id'])?(int)$e['supervisor_id']:null,'to'=>$supervisor]);return ['ok'=>true];
  });
 }
 // Turunkan penugasan periode draf dari struktur: atasan langsung → atasan, bawahan langsung → bawahan (min. 3), rekan (min. 3):
 // pejabat (punya bawahan) → sesama pejabat dengan atasan yang sama, juga lintas OPD (kepala OPD di bawah Bupati); staf → staf lain satu unit (UNOR) dalam OPD yang sama.
 // Pegawai tanpa atasan dilewati; pasangan yang sudah ada tidak diduplikasi. Kelompok rekan/bawahan berjumlah 1–2 dilewati agar komposisi tetap sah.
 private function listAssignments(array $b): array
 {
  $periodId=(int)($b['period_id']??0);$this->period($periodId);
  $opdId=$this->scope()??((int)($b['opd_id']??0)?:null);
  $where='a.period_id=?';$params=[$periodId];if($opdId!==null){$where.=' AND s.opd_id=?';$params[]=$opdId;}
  $from=' FROM assignments a JOIN employees s ON s.id=a.subject_id JOIN employees r ON r.id=a.rater_id JOIN opd o ON o.id=s.opd_id WHERE ';
  $stats=$this->one('SELECT COUNT(*) AS total,COALESCE(SUM(a.status=\'submitted\'),0) AS submitted FROM assignments a JOIN employees s ON s.id=a.subject_id WHERE '.$where,$params);
  $role=(string)($b['role']??'');if(in_array($role,['atasan','rekan','bawahan'],true)){$where.=' AND a.rater_role=?';$params[]=$role;}
  $status=(string)($b['status']??'');if(in_array($status,['pending','draft','submitted'],true)){$where.=' AND a.status=?';$params[]=$status;}
  $q=trim((string)($b['q']??''));if($q!==''){$like='%'.addcslashes(mb_substr($q,0,80),'%_\\').'%';$where.=' AND (s.name LIKE ? OR r.name LIKE ?)';array_push($params,$like,$like);}
  $total=(int)$this->one('SELECT COUNT(*) AS c'.$from.$where,$params)['c'];
  $per=10;$pages=max(1,(int)ceil($total/$per));$page=min(max(1,(int)($b['page']??1)),$pages);
  $rows=$this->all('SELECT a.id,a.subject_id,a.rater_id,a.rater_role,a.status,s.opd_id,o.code AS opd_code,s.name AS subject_name,r.name AS rater_name'.$from.$where.' ORDER BY s.opd_id,a.subject_id,a.rater_role,a.id LIMIT '.$per.' OFFSET '.(($page-1)*$per),$params);
  return ['rows'=>$rows,'total'=>$total,'page'=>$page,'pages'=>$pages,'per'=>$per,'stats'=>['total'=>(int)$stats['total'],'submitted'=>(int)$stats['submitted']]];
 }
 private function generateAssignments(array $b): array
 {
  $periodId=(int)($b['period_id']??0);$opdId=$this->scope()??((int)($b['opd_id']??0)?:null);
  return $this->transaction(function()use($periodId,$opdId){
   if($this->period($periodId,true)['status']!=='draft')throw new ApiError('Distribusi penilai dikunci setelah periode dibuka.',409);
   if($opdId!==null&&!$this->one('SELECT id FROM opd WHERE id=?',[$opdId]))throw new ApiError('OPD tidak ditemukan.',404);
   // Struktur dibaca dari seluruh OPD agar atasan dan rekan lintas OPD (Bupati dan sesama kepala OPD) ikut terhitung; yang dinilai tetap hanya pegawai OPD terpilih.
   $employees=$this->all('SELECT e.id,CONCAT(o.code," · ",e.name) AS name,e.supervisor_id,e.opd_id,e.unit FROM employees e JOIN opd o ON o.id=e.opd_id WHERE e.active=1 ORDER BY o.name,e.name');$children=[];$units=[];$unitKey=static fn(array $e)=>$e['opd_id'].'|'.mb_strtolower(trim($e['unit']));foreach($employees as $e)if($e['supervisor_id']!==null)$children[(int)$e['supervisor_id']][]=(int)$e['id'];foreach($employees as $e)if(!isset($children[(int)$e['id']]))$units[$unitKey($e)][]=(int)$e['id'];
   $existing=[];foreach($this->all('SELECT subject_id,rater_id FROM assignments WHERE period_id=?',[$periodId]) as $a)$existing[$a['subject_id'].'-'.$a['rater_id']]=true;
   $created=0;$skipped=0;$warnings=[];
   foreach($employees as $e){
    if($opdId!==null&&(int)$e['opd_id']!==$opdId)continue;
    $subject=(int)$e['id'];if($e['supervisor_id']===null){$warnings[]=$e['name'].': tanpa atasan, tidak dinilai.';continue;}
    $boss=(int)$e['supervisor_id'];$subs=$children[$subject]??[];$peers=$subs?array_values(array_filter($children[$boss],static fn(int $x)=>$x!==$subject&&isset($children[$x]))):array_values(array_diff($units[$unitKey($e)],[$subject]));
    $pairs=[[$boss,'atasan']];$notes=[];
    if(count($peers)>=3)foreach($peers as $r)$pairs[]=[$r,'rekan'];elseif($peers)$notes[]=$e['name'].': rekan sejawat hanya '.count($peers).' orang (minimal 3), kelompok rekan dilewati.';
    if(count($subs)>=3)foreach($subs as $r)$pairs[]=[$r,'bawahan'];elseif($subs)$notes[]=$e['name'].': bawahan hanya '.count($subs).' orang (minimal 3), kelompok bawahan dilewati.';
    // Atasan saja bukan komposisi yang sah (Kondisi 1–3 memerlukan rekan dan/atau bawahan), jadi pegawai ini dilewati seluruhnya.
    if(count($pairs)===1){$warnings[]=$e['name'].': komposisi belum sah (rekan '.count($peers).', bawahan '.count($subs).'; kelompok minimal 3 orang), tidak dinilai.';continue;}
    array_push($warnings,...$notes);
    foreach($pairs as [$rater,$role]){if(isset($existing[$subject.'-'.$rater])){$skipped++;continue;}$this->run('INSERT INTO assignments(period_id,subject_id,rater_id,rater_role) VALUES(?,?,?,?)',[$periodId,$subject,$rater,$role]);$existing[$subject.'-'.$rater]=true;$created++;}
   }
   $this->audit('assignments_generated','period',$periodId,['created'=>$created,'skipped'=>$skipped,'opd_id'=>$opdId]);return ['created'=>$created,'skipped'=>$skipped,'warnings'=>$warnings];
  });
 }
 private function createPeriod(array $b): array
 {
  // Periode selalu satu triwulan kalender; nama dan rentang tanggal diturunkan dari tahun + triwulan.
  if(!is_numeric($b['year']??null)||!is_numeric($b['quarter']??null))throw new ApiError('Tahun dan triwulan wajib diisi.');
  try{$p=Period::quarter((int)$b['year'],(int)$b['quarter']);}catch(DomainException $e){throw new ApiError($e->getMessage());}
  return $this->transaction(function()use($p){
   if($this->one('SELECT id FROM periods WHERE start_date=? AND end_date=? FOR UPDATE',[$p['start_date'],$p['end_date']]))throw new ApiError('Periode '.$p['name'].' sudah ada.',409);
   $this->run('INSERT INTO periods(name,start_date,end_date,weights) VALUES(?,?,?,?)',[$p['name'],$p['start_date'],$p['end_date'],json_encode($this->latestWeights(),JSON_THROW_ON_ERROR)]);$id=(int)$this->db->lastInsertId();$this->audit('period_created','period',$id,['year'=>(int)substr($p['start_date'],0,4),'name'=>$p['name']]);return ['id'=>$id];
  });
 }
 private function periodStatus(array $b): array
 {
  $id=(int)($b['period_id']??0);$status=$b['status']??'';
  return $this->transaction(function()use($id,$status){
   $p=$this->period($id,true);$allowed=['draft'=>['open'],'open'=>['closed'],'closed'=>['open','published'],'published'=>[]];if(!in_array($status,$allowed[$p['status']],true))throw new ApiError('Transisi status tidak diizinkan.',409);
   $rows=$this->all('SELECT id,subject_id,rater_role,status FROM assignments WHERE period_id=? ORDER BY id FOR UPDATE',[$id]);
   if($status==='open'||$status==='published'){
    if(!$rows)throw new ApiError('Tambahkan penugasan sebelum membuka periode.');
    $subjects=[];foreach($rows as $row)$subjects[$row['subject_id']][]=$row;
    foreach($subjects as $subject=>$assignments){Scoring::validateComposition($assignments);if($status==='published'){if(!Scoring::calculate($this->scoringRows($id,(int)$subject),$this->indicators())['complete'])throw new ApiError('Seluruh penugasan wajib selesai sebelum publikasi.',409);}}
   }
   // Bobot periode tidak disentuh perubahan status; periode lama tanpa bobot mendapat bobot bawaan secara tertulis saat dibuka.
   $this->run('UPDATE periods SET status=?,published_at=?,weights=COALESCE(weights,?) WHERE id=?',[$status,$status==='published'?date('Y-m-d H:i:s'):null,json_encode($this->periodWeights($p),JSON_THROW_ON_ERROR),$id]);$this->audit('period_status_changed','period',$id,['from'=>$p['status'],'to'=>$status]);return ['ok'=>true];
  });
 }
 private function createAssignment(array $b): array
 {
  $periodId=(int)($b['period_id']??0);$subject=(int)($b['subject_id']??0);$rater=(int)($b['rater_id']??0);$role=$b['rater_role']??'';
  if(!$subject||!$rater||$subject===$rater)throw new ApiError('Pegawai dan penilai harus berbeda.');if(!in_array($role,['atasan','rekan','bawahan'],true))throw new ApiError('Peran penilai tidak valid.');
  return $this->transaction(function()use($periodId,$subject,$rater,$role){if($this->period($periodId,true)['status']!=='draft')throw new ApiError('Distribusi penilai dikunci setelah periode dibuka.',409);$opd=[];foreach([$subject,$rater] as $id){if(!$this->one('SELECT id FROM employees WHERE id=? AND active=1',[$id]))throw new ApiError('Pegawai tidak aktif atau tidak ditemukan.',404);$opd[]=$this->inScope($id);}if($opd[0]!==$opd[1])throw new ApiError('Penilai dan pegawai yang dinilai harus berasal dari OPD yang sama.');$this->run('INSERT INTO assignments(period_id,subject_id,rater_id,rater_role) VALUES(?,?,?,?)',[$periodId,$subject,$rater,$role]);$id=(int)$this->db->lastInsertId();$this->audit('assignment_created','assignment',$id);return ['id'=>$id];});
 }
 private function deleteAssignment(array $b): array
 {
  $id=(int)($b['id']??0);$a=$this->one('SELECT period_id,subject_id FROM assignments WHERE id=?',[$id]);if(!$a)throw new ApiError('Penugasan tidak ditemukan.',404);$this->inScope((int)$a['subject_id']);
  return $this->transaction(function()use($a,$id){if($this->period((int)$a['period_id'],true)['status']!=='draft')throw new ApiError('Distribusi penilai dikunci setelah periode dibuka.',409);$this->run('DELETE FROM assignments WHERE id=?',[$id]);$this->audit('assignment_deleted','assignment',$id);return ['ok'=>true];});
 }
 private function saveOpd(array $b): array
 {
  $id=(int)($b['id']??0);$name=$this->requiredText($b,'name',160);$code=strtoupper($this->requiredText($b,'code',20));$active=(int)(bool)($b['active']??1);
  if(!preg_match('/^[A-Z0-9_-]{2,20}$/D',$code))throw new ApiError('Kode OPD 2–20 karakter huruf, angka, garis bawah, atau strip.');
  return $this->transaction(function()use($id,$name,$code,$active){
   if($id){if(!$this->one('SELECT id FROM opd WHERE id=? FOR UPDATE',[$id]))throw new ApiError('OPD tidak ditemukan.',404);if(!$active&&$this->one('SELECT id FROM employees WHERE opd_id=? AND active=1 LIMIT 1',[$id]))throw new ApiError('OPD masih memiliki pegawai aktif; nonaktifkan atau pindahkan pegawainya terlebih dahulu.',409);$this->run('UPDATE opd SET name=?,code=?,active=? WHERE id=?',[$name,$code,$active,$id]);}
   else{$this->run('INSERT INTO opd(name,code,active) VALUES(?,?,?)',[$name,$code,$active]);$id=(int)$this->db->lastInsertId();}
   $this->audit('opd_saved','opd',$id,['code'=>$code,'active'=>$active]);return ['id'=>$id];
  });
 }
 private function saveSettings(array $b): array
 {
  $name=$this->requiredText($b,'kabupaten_name',120);
  $this->run('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',['kabupaten_name',$name]);$this->audit('settings_saved','settings',null,['kabupaten_name'=>$name]);return ['ok'=>true];
 }
 // Bobot hanya berlaku untuk periode terpilih dan hanya dapat diubah selama draf; sejak dibuka, bobot terkunci agar aturan tidak berubah saat penilaian berjalan atau setelah nilai terlihat.
 private function saveWeights(array $b): array
 {
  $periodId=(int)($b['period_id']??0);$weights=Scoring::normalizeWeights($b['weights']??null);
  return $this->transaction(function()use($periodId,$weights){
   $p=$this->period($periodId,true);if($p['status']!=='draft')throw new ApiError('Bobot '.$p['name'].' terkunci sejak periode dibuka.',409);
   $old=$this->periodWeights($p);$this->run('UPDATE periods SET weights=? WHERE id=?',[json_encode($weights,JSON_THROW_ON_ERROR),$periodId]);
   $this->audit('weights_saved','period',$periodId,['from'=>$old,'to'=>$weights]);return ['weights'=>$weights];
  });
 }
}
