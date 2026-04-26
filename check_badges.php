<?php
$files = [
    'Siswa/Resources/RekapAbsensis/Pages/ViewRekapAbsensiPertemuan.php',
    'Siswa/Resources/RekapAbsensis/Pages/ViewRekapAbsensiHari.php',
    'Siswa/Resources/RekapAbsensis/Pages/ListRekapAbsensis.php',
    'Siswa/Resources/PresensiDetails/Tables/PresensiDetailsTable.php',
    'Siswa/Resources/PresensiDetails/Pages/ViewPresensiHari.php',
    'Resources/RekapAbsensis/Tables/RekapAbsensisTable.php',
    'Resources/RekapAbsensis/Pages/ViewRekapAbsensiSiswa.php',
    'Resources/RekapAbsensis/Pages/ViewRekapAbsensiPelajaran.php',
    'Resources/RekapAbsensis/Pages/ViewRekapAbsensiKelas.php',
    'Resources/RekapAbsensis/Pages/ViewRekapAbsensiHari.php',
    'Resources/Jadwals/Pages/ViewJadwalKelas.php',
    'Guru/Pages/RekapPerSiswaHari.php',
    'Guru/Pages/RekapWaliKelasDetail.php',
    'Guru/Pages/RekapWaliKelasSiswa.php',
    'Guru/Pages/RekapWaliKelasPertemuan.php',
    'Guru/Pages/RekapWaliKelas.php',
    'Guru/Pages/RekapPerSiswaDetail.php',
    'Guru/Pages/RekapPerSiswa.php',
    'Guru/Resources/PresensiSesis/Pages/ViewPresensiKelas.php',
    'Guru/Resources/PresensiSesis/Pages/ListPresensiHari.php',
    'Guru/Resources/PresensiSesis/Pages/DetailPresensiSesi.php'
];
foreach ($files as $file) {
    $path = "c:/xampp/htdocs/Absensi_SMK-AlHafidz/app/Filament/" . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        preg_match_all("/(TextColumn::make\([^)]+\)[\s\S]*?->badge\(\)[\s\S]*?)(?=TextColumn::make|,|\])/", $content, $matches);
        if(!empty($matches[0])) {
            echo "\n=== $file ===\n";
            foreach($matches[0] as $match) {
                 if (str_contains(strtolower($match), 'hadir') || str_contains(strtolower($match), 'status')) {
                     echo $match . "\n---\n";
                 }
            }
        }
    }
}
