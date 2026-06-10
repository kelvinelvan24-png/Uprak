<?php
/*
 * Root redirect
 * Redirect ke halaman utama SongForYou
 */
$queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: Ujian-Praktek/SongForYou/index.html' . $queryString);
exit;
?>
