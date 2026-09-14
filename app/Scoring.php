<?php
declare(strict_types=1);
final class Scoring
{
 public static function weights(array $roles): ?array
 {
  $roles = array_values(array_unique($roles)); sort($roles);
  return match(implode(',', $roles)) {
   'atasan,bawahan,rekan' => ['atasan'=>60,'rekan'=>25,'bawahan'=>15],
   'atasan,rekan' => ['atasan'=>75,'rekan'=>25],
   'atasan,bawahan' => ['atasan'=>85,'bawahan'=>15],
   default => null,
  };
 }
 public static function validateComposition(array $rows): void
 {
  if (self::weights(array_column($rows, 'rater_role')) === null) throw new DomainException('Komposisi wajib: atasan + rekan + bawahan, atasan + rekan, atau atasan + bawahan.');
  $counts = array_count_values(array_column($rows, 'rater_role'));
  if (($counts['atasan'] ?? 0) !== 1) throw new DomainException('Setiap pegawai harus memiliki tepat satu penilai atasan.');
  foreach (['rekan', 'bawahan'] as $role) if (isset($counts[$role]) && $counts[$role] < 3) throw new DomainException('Kelompok ' . $role . ' memerlukan minimal tiga orang penilai.');
 }
 public static function validateAnswers(array $answers, array $indicatorIds, bool $complete): void
 {
  foreach ($answers as $id=>$score) {
   if (!is_int($id) || !in_array($id, $indicatorIds, true) || !is_int($score) || $score < 1 || $score > 5) throw new DomainException('Indikator atau nilai tidak valid. Gunakan bilangan bulat 1 sampai 5.');
  }
  if ($complete && (count($answers) !== count($indicatorIds) || array_diff($indicatorIds, array_map('intval', array_keys($answers))))) throw new DomainException('Lengkapi seluruh tujuh indikator sebelum mengirim.');
 }
 public static function calculate(array $rows, array $indicators): array
 {
  $weights = self::weights(array_column($rows, 'rater_role'));
  $received = count(array_filter($rows, static fn($r)=>$r['status']==='submitted'));
  $groups=[];
  foreach($weights ?? [] as $role=>$weight) {
   $group = array_values(array_filter($rows, static fn($r)=>$r['rater_role']===$role));
   $groups[]=['role'=>$role,'weight'=>$weight,'total'=>count($group),'received'=>count(array_filter($group, static fn($r)=>$r['status']==='submitted'))];
  }
  $validComposition = true;
  try { self::validateComposition($rows); } catch (DomainException) { $validComposition = false; }
  $complete = count($rows)>0 && $received===count($rows) && $weights!==null && $validComposition;
  $ids=array_map('intval',array_column($indicators,'id'));
  foreach($rows as $row) if($row['status']==='submitted') {
   try { self::validateAnswers($row['answers'], $ids, true); } catch(DomainException) { $complete=false; }
  }
  $dimensions=[]; $sum=0.0;
  if($complete) foreach($indicators as $indicator) {
   $value=0.0;
   foreach($weights as $role=>$weight) {
    $group=array_values(array_filter($rows,static fn($r)=>$r['rater_role']===$role));
    $mean=array_sum(array_map(static fn($r)=>(int)$r['answers'][$indicator['id']],$group))/count($group);
    $value += $mean*$weight/100;
   }
   $sum += $value*20;
   $dimensions[]=['id'=>(int)$indicator['id'],'name'=>$indicator['name'],'score'=>round($value*20,2)];
  }
  return ['received'=>$received,'expected'=>count($rows),'complete'=>$complete,'weights'=>$weights,'groups'=>$groups,'score'=>$complete?round($sum/count($indicators),2):null,'dimensions'=>$dimensions];
 }
}
