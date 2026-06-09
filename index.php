<?php
/*
 * /SFY/index.php  — Root redirect
 * Redirect ke halaman utama SongForYou
 */
$queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: /SFY/Ujian-Praktek/SongForYou/index.php' . $queryString);
exit;
?>
