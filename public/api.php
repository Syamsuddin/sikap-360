<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: same-origin');
require dirname(__DIR__).'/app/Database.php';require dirname(__DIR__).'/app/Scoring.php';require dirname(__DIR__).'/app/Period.php';require dirname(__DIR__).'/app/Api.php';
$config=require dirname(__DIR__).'/config/app.php';date_default_timezone_set($config['timezone']);
try{
 if($_SERVER['REQUEST_METHOD']!=='POST')throw new ApiError('Gunakan metode POST.',405);
 if(!str_starts_with(strtolower($_SERVER['CONTENT_TYPE']??''),'application/json'))throw new ApiError('Content-Type harus application/json.',415);
 if((int)($_SERVER['CONTENT_LENGTH']??0)>65536)throw new ApiError('Permintaan terlalu besar.',413);
 $expected=parse_url($config['app_url']);$origin=isset($_SERVER['HTTP_ORIGIN'])?parse_url($_SERVER['HTTP_ORIGIN']):null;
 if($origin!==null && (!is_array($origin)||($origin['host']??'')!==($expected['host']??'')||($origin['scheme']??'')!==($expected['scheme']??'')||($origin['port']??null)!==($expected['port']??null)))throw new ApiError('Origin tidak diizinkan.',403);
 ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.gc_maxlifetime',(string)$config['session_ttl']);session_name('SIKAPSESSID');session_set_cookie_params(['httponly'=>true,'secure'=>$config['secure_cookie'],'samesite'=>'Strict','path'=>'/']);session_start();
 $raw=file_get_contents('php://input',false,null,0,65537);if(strlen($raw)>65536)throw new ApiError('Permintaan terlalu besar.',413);
 $body=json_decode($raw,true,32,JSON_THROW_ON_ERROR);if(!is_array($body))throw new ApiError('Format permintaan tidak valid.');
 $api=new Api(Database::connect($config),$config);$result=$api->handle((string)($_GET['action']??''),$body);echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
}catch(ApiError $e){http_response_code($e->status);echo json_encode(['error'=>$e->getMessage()]);}
catch(DomainException $e){http_response_code(422);echo json_encode(['error'=>$e->getMessage()]);}
catch(JsonException $e){http_response_code(400);echo json_encode(['error'=>'Format JSON tidak valid.']);}
catch(PDOException $e){error_log('SIKAP database: '.$e->getCode().'/'.($e->errorInfo[1]??0));[$status,$message]=match(true){$e->getCode()==='23000'=>[409,'Data duplikat atau relasi data tidak valid.'],($e->errorInfo[1]??0)===3819=>[422,'Data tidak memenuhi aturan validasi.'],default=>[503,'Database belum tersedia. Periksa konfigurasi dan instalasi.']};http_response_code($status);echo json_encode(['error'=>$message]);}
catch(Throwable $e){error_log('SIKAP error: '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'Terjadi kesalahan server. Hubungi administrator.']);}
