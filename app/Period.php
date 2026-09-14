<?php
declare(strict_types=1);
// Periode penilaian selalu satu triwulan kalender: I (Jan–Mar), II (Apr–Jun), III (Jul–Sep), IV (Okt–Des).
final class Period
{
 public const ROMAN=[1=>'I',2=>'II',3=>'III',4=>'IV'];
 /** @return array{name:string,start_date:string,end_date:string} */
 public static function quarter(int $year,int $quarter): array
 {
  if($year<2000||$year>2100)throw new DomainException('Tahun harus antara 2000 dan 2100.');
  if($quarter<1||$quarter>4)throw new DomainException('Triwulan harus 1 sampai 4.');
  $start=new DateTimeImmutable(sprintf('%04d-%02d-01',$year,($quarter-1)*3+1));
  return ['name'=>'Triwulan '.self::ROMAN[$quarter].' '.$year,'start_date'=>$start->format('Y-m-d'),'end_date'=>$start->modify('+3 months -1 day')->format('Y-m-d')];
 }
 /** Triwulan yang memuat tanggal tertentu, digeser $offset triwulan (negatif = mundur). @return array{year:int,quarter:int} */
 public static function at(DateTimeImmutable $date,int $offset=0): array
 {
  $index=(int)$date->format('Y')*4+intdiv((int)$date->format('n')-1,3)+$offset;
  return ['year'=>intdiv($index,4),'quarter'=>$index%4+1];
 }
}
