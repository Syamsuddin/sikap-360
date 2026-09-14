<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/Database.php';require dirname(__DIR__).'/app/Period.php';
$config=require dirname(__DIR__).'/config/app.php';date_default_timezone_set($config['timezone']);
try{
 $db=Database::connect($config);
 $db->exec(file_get_contents(dirname(__DIR__).'/database/schema.sql'));
 if((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn()>0)throw new RuntimeException('Database sudah berisi akun. Instalasi tidak menimpa data lama.');
 $demo=in_array('--demo',$argv,true);
 if($demo){require __DIR__.'/seed-demo.php';seedDemo($db);echo "Instalasi demo selesai.\nAdmin: pegawai1@example.test\nASN: pegawai3@example.test\nKata sandi demo: SikapDemo2026!\nJangan gunakan data/akun demo untuk produksi.\n";exit;}
 $email=getenv('ADMIN_EMAIL')?:'';$password=getenv('ADMIN_PASSWORD')?:'';$name=getenv('ADMIN_NAME')?:'Administrator SIKAP';$nip=getenv('ADMIN_NIP')?:'';$kabupaten=trim(getenv('KABUPATEN_NAME')?:'')?:'Hulu Sungai Selatan';$opdName=trim(getenv('ADMIN_OPD')?:'')?:'Sekretariat Daerah';
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12||strlen($password)>128||!preg_match('/^\d{18}$/D',$nip))throw new RuntimeException('Isi ADMIN_EMAIL, ADMIN_PASSWORD (12–128 karakter), ADMIN_NIP (18 digit), dan ADMIN_NAME sebagai environment variable. Opsional: KABUPATEN_NAME (default Hulu Sungai Selatan), ADMIN_OPD (default Sekretariat Daerah).');
 $db->beginTransaction();
 $s=$db->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');$s->execute(['kabupaten_name',$kabupaten]);
 $s=$db->prepare('INSERT INTO opd(name,code) VALUES(?,?)');$s->execute([$opdName,'SETDA']);$opdId=(int)$db->lastInsertId();
 $s=$db->prepare('INSERT INTO employees(name,nip,position,opd_id,unit,grade,email) VALUES(?,?,?,?,?,?,?)');$s->execute([$name,$nip,'Administrator',$opdId,'Sekretariat','',''.$email]);
 $s=$db->prepare("INSERT INTO users(employee_id,password_hash,role) VALUES(?,?,'admin')");$s->execute([(int)$db->lastInsertId(),password_hash($password,PASSWORD_DEFAULT)]);
 $s=$db->prepare('INSERT INTO periods(name,start_date,end_date) VALUES(?,?,?)');$q=Period::quarter(...array_values(Period::at(new DateTimeImmutable('today'))));$s->execute([$q['name'],$q['start_date'],$q['end_date']]);
 $db->commit();echo "Instalasi selesai. Masuk menggunakan akun admin yang Anda tetapkan.\n";
}catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();fwrite(STDERR,'Instalasi gagal: '.$e->getMessage()."\n");exit(1);}
