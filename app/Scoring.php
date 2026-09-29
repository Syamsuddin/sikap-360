<?php
declare(strict_types=1);
final class Scoring
{
 // Tabel bobot per komposisi, dikunci dengan peran terurut. Angkanya dapat diubah administrator kabupaten (settings.scoring_weights); kunci dan perannya tetap.
 public const DEFAULT_WEIGHTS = [
  'atasan,bawahan,rekan' => ['atasan'=>60,'rekan'=>25,'bawahan'=>15],
  'atasan,rekan' => ['atasan'=>75,'rekan'=>25],
  'atasan,bawahan' => ['atasan'=>85,'bawahan'=>15],
 ];
 private const CONDITION_NAMES = ['atasan,bawahan,rekan'=>'Kondisi 1 (tiga peran)','atasan,rekan'=>'Kondisi 2 (tanpa bawahan)','atasan,bawahan'=>'Kondisi 3 (tanpa rekan sejawat)'];
 public static function weights(array $roles, array $table = self::DEFAULT_WEIGHTS): ?array
 {
  $roles = array_values(array_unique($roles)); sort($roles);
  return $table[implode(',', $roles)] ?? null;
 }
 // Tabel bobot kiriman admin atau hasil baca database: tiga kondisi bawaan lengkap, tiap bobot bilangan bulat 1–99, jumlah per kondisi 100. Urutan mengikuti bawaan.
 public static function normalizeWeights(mixed $table): array
 {
  if (!is_array($table)) throw new DomainException('Format bobot tidak valid.');
  $out = [];
  foreach (self::DEFAULT_WEIGHTS as $key=>$roles) {
   $name = self::CONDITION_NAMES[$key];
   if (!is_array($table[$key] ?? null)) throw new DomainException('Bobot ' . $name . ' wajib diisi.');
   foreach (array_keys($roles) as $role) {
    $weight = $table[$key][$role] ?? null;
    if (!is_int($weight) || $weight < 1 || $weight > 99) throw new DomainException('Bobot ' . $role . ' pada ' . $name . ' harus bilangan bulat 1 sampai 99.');
    $out[$key][$role] = $weight;
   }
   if (array_sum($out[$key]) !== 100) throw new DomainException('Jumlah bobot ' . $name . ' harus 100%, saat ini ' . array_sum($out[$key]) . '%.');
  }
  return $out;
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
 public static function calculate(array $rows, array $indicators, array $table = self::DEFAULT_WEIGHTS): array
 {
  $weights = self::weights(array_column($rows, 'rater_role'), $table);
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
