<?php
// ============================================================
// BUKU TAMU VERSI AMAN (SECURE) — SETELAH PATCH
// Patch: htmlspecialchars() pada setiap output
// Fitur baru: Hapus Semua Komentar
// ============================================================

$file_komentar = 'komentar.txt';

if (isset($_POST['hapus_semua'])) {
    file_put_contents($file_komentar, '');
    header('Location: buku_tamu_aman.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama'])) {
    $nama = $_POST['nama'];
    $komentar = $_POST['komentar'];
    $baris = $nama . '|' . $komentar . "\n";
    file_put_contents($file_komentar, $baris, FILE_APPEND);
    header('Location: buku_tamu_aman.php');
    exit;
}

if (file_exists($file_komentar)) {
    $daftar_komentar = file($file_komentar, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
} else {
    $daftar_komentar = [];
}

$jumlah_komentar = count($daftar_komentar);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Buku Tamu - AMAN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h1 {
            color: #27ae60;
        }

        .form-group {
            margin-bottom: 12px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 4px;
        }

        input[type=text],
        textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .btn-kirim {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-kirim:hover {
            background: #1e8449;
        }

        .btn-hapus {
            background: #7f8c8d;
            color: white;
            padding: 8px 16px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn-hapus:hover {
            background: #636e72;
        }

        .komentar-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .nama {
            font-weight: bold;
            color: #27ae60;
        }

        .label-aman {
            background: #27ae60;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .komentar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .jumlah-info {
            font-size: 14px;
            color: #555;
        }

        .kosong {
            color: #999;
            font-style: italic;
        }

        .form-hapus {
            display: inline;
        }

        .navbar {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .nav-link {
            padding: 9px 18px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid transparent;
        }

        .nav-aktif {
            background: #27ae60;
            color: white;
            border-color: #27ae60;
            cursor: default;
            pointer-events: none;
        }

        .nav-lain {
            background: white;
            color: #c0392b;
            border-color: #c0392b;
        }

        .nav-lain:hover {
            background: #c0392b;
            color: white;
        }

        .card-identitas {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            border-radius: 12px;
            padding: 16px;
            margin: 18px 0 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
        }

        .card-identitas h3 {
            margin: 0 0 10px;
            color: #065f46;
        }

        .card-grid {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 8px 14px;
        }

        .card-label {
            font-weight: bold;
            color: #064e3b;
        }

        .card-value {
            color: #111827;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="buku_tamu.php" class="nav-link nav-lain">⚠ Buku Tamu (Rentan)</a>
        <a href="buku_tamu_aman.php" class="nav-link nav-aktif">✅ Buku Tamu (Aman)</a>
    </nav>

    <h1>📖 Buku Tamu <span class="label-aman">✅ VERSI AMAN</span></h1>

    <div class="card-identitas">
        <h3>Identitas Mahasiswa</h3>
        <div class="card-grid">
            <div class="card-label">Nama Mahasiswa</div>
            <div class="card-value">Muhammad Hasbih Akbar</div>
            <div class="card-label">NIM</div>
            <div class="card-value">231220075</div>
            <div class="card-label">Kelas</div>
            <div class="card-value">TI 31</div>
            <div class="card-label">Matakuliah</div>
            <div class="card-value">Kriptografi</div>
            <div class="card-label">Dosen Pengampu</div>
            <div class="card-value">Sucipto, M.Kom</div>
        </div>
    </div>

    <form method="POST" action="buku_tamu_aman.php">
        <div class="form-group">
            <label for="nama">Nama:</label>
            <input type="text" id="nama" name="nama" placeholder="Masukkan nama Anda" required>
        </div>
        <div class="form-group">
            <label for="komentar">Komentar:</label>
            <textarea id="komentar" name="komentar" rows="3" placeholder="Tulis komentar..." required></textarea>
        </div>
        <button type="submit" class="btn-kirim">Kirim Komentar</button>
    </form>

    <hr>

    <div class="komentar-header">
        <h2 style="margin:0;">Daftar Komentar
            <?php
            echo '<span class="jumlah-info">(' . $jumlah_komentar . ' komentar)</span>';
            ?>
        </h2>

        <?php if ($jumlah_komentar > 0): ?>
            <form method="POST" action="buku_tamu_aman.php" class="form-hapus"
                onsubmit="return confirm('Hapus semua <?= $jumlah_komentar ?> komentar? Tindakan ini tidak dapat dibatalkan.');">
                <input type="hidden" name="hapus_semua" value="1">
                <button type="submit" class="btn-hapus">🗑️ Hapus Semua Komentar</button>
            </form>
        <?php endif; ?>
    </div>

    <?php
    if (empty($daftar_komentar)) {
        echo '<p class="kosong">Belum ada komentar.</p>';
    } else {
        foreach ($daftar_komentar as $baris) {
            $bagian = explode('|', $baris, 2);
            $nama = $bagian[0] ?? '';
            $komentar = $bagian[1] ?? '';

            $nama_aman = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
            $komentar_aman = htmlspecialchars($komentar, ENT_QUOTES, 'UTF-8');

            echo '<div class="komentar-box">';
            echo '<span class="nama">' . $nama_aman . '</span>: ';
            echo $komentar_aman;
            echo '</div>';
        }
    }
    ?>
</body>

</html>