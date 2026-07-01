<?php
session_start();

// Saldo awal
if (!isset($_SESSION['saldo'])) {
    $_SESSION['saldo'] = 100000;
}

// Generate CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================================================
// TOGGLE PROTEKSI INTERAKTIF (via tombol di UI, disimpan session)
// ==========================================================
if (isset($_GET['toggle_protection'])) {
    $_SESSION['csrf_protection'] = ($_GET['toggle_protection'] === 'on');
    header('Location: bank.php');
    exit;
}

if (!isset($_SESSION['csrf_protection'])) {
    $_SESSION['csrf_protection'] = true; // default: proteksi AKTIF
}

define('CSRF_PROTECTION_ENABLED', $_SESSION['csrf_protection']);

// Reset demo (saldo & token direset ulang untuk uji coba berikutnya)
if (isset($_GET['reset'])) {
    $_SESSION['saldo'] = 100000;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: bank.php');
    exit;
}