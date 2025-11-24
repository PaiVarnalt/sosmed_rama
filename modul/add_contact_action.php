<?php
session_start();
include "../lib/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$contact = intval($_GET['id']);

// Tidak boleh tambah diri sendiri
if ($contact == $user_id) {
    header("Location: add_contact.php?error=self");
    exit();
}

// Cek apakah sudah jadi kontak
$c = $pdo->prepare("SELECT * FROM contacts WHERE user_id=? AND contact_id=?");
$c->execute([$user_id, $contact]);

if ($c->rowCount() > 0) {
    header("Location: add_contact.php?error=exists");
    exit();
}

// Tambahkan kontak kedua arah (bi-directional)
$insert = $pdo->prepare("INSERT INTO contacts (user_id, contact_id) VALUES (?, ?), (?, ?)");
$insert->execute([$user_id, $contact, $contact, $user_id]);

header("Location: index.php?added=success");
