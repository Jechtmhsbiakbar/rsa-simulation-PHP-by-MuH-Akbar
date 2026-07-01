<?php
require_once 'config.php';

$pesan = '';
$pesanTipe = ''; // 'sukses' atau 'gagal'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (CSRF_PROTECTION_ENABLED) {
        // ================= MODE SETELAH PATCH =================
        $tokenDikirim = $_POST['csrf_token'] ?? '';

        if (empty($tokenDikirim) || !hash_equals($_SESSION['csrf_token'], $tokenDikirim)) {
            http_response_code(403);
            echo '<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>CSRF Detected</title></head>
<body style="background:#ffffff; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;">
<h1 style="font-family: Arial, sans-serif; color:#000; text-align:center; padding:0 20px;">⛔ ERROR: SERANGAN CSRF TERDETEKSI!</h1>
</body>
</html>';
            exit;
        }
    }

    // ================= PROSES TRANSFER =================
    $jumlah = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 0;

    if ($jumlah > 0 && $jumlah <= $_SESSION['saldo']) {
        $_SESSION['saldo'] -= $jumlah;
        $pesan = "Transfer sebesar Rp" . number_format($jumlah, 0, ',', '.') . " berhasil dilakukan.";
        $pesanTipe = 'sukses';
    } else {
        $pesan = "Transfer gagal. Jumlah tidak valid atau saldo tidak mencukupi.";
        $pesanTipe = 'gagal';
    }

    if (CSRF_PROTECTION_ENABLED) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Demo - CSRF</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <!-- ===== CARD MELAYANG IDENTITAS ===== -->
    <div class="identity-card">
        <div class="identity-header">📋 Identitas Mahasiswa</div>
        <table>
            <tr>
                <td>Nama</td>
                <td>:</td>
                <td>Muhammad Hasbih Akbar</td>
            </tr>
            <tr>
                <td>NIM</td>
                <td>:</td>
                <td>231220075</td>
            </tr>
            <tr>
                <td>Kelas</td>
                <td>:</td>
                <td>TI 31</td>
            </tr>
            <tr>
                <td>Mata Kuliah</td>
                <td>:</td>
                <td>Kriptografi</td>
            </tr>
            <tr>
                <td>Dosen Pengampu</td>
                <td>:</td>
                <td>Sucipto, M.kom</td>
            </tr>
        </table>
    </div>

    <div class="page-wrap">
        <div class="container">
            <h1>🏦 Bank Demo</h1>
            <p class="subtitle">Simulasi Kerentanan &amp; Proteksi CSRF Token</p>

            <!-- ===== KONTROL PROTEKSI ===== -->
            <div class="control-box">
                <div class="control-label">
                    Status Proteksi CSRF:
                    <?php if (CSRF_PROTECTION_ENABLED): ?>
                        <span class="badge badge-on">🔒 AKTIF (Patched)</span>
                    <?php else: ?>
                        <span class="badge badge-off">🔓 NONAKTIF (Vulnerable)</span>
                    <?php endif; ?>
                </div>

                <div class="switch-row">
                    <a href="?toggle_protection=off"
                        class="switch-btn <?php echo !CSRF_PROTECTION_ENABLED ? 'switch-active-off' : ''; ?>">Matikan
                        Proteksi</a>
                    <a href="?toggle_protection=on"
                        class="switch-btn <?php echo CSRF_PROTECTION_ENABLED ? 'switch-active-on' : ''; ?>">Aktifkan
                        Proteksi</a>
                </div>
                <a href="?reset=1" class="reset-link">↻ Reset Saldo &amp; Token</a>
            </div>

            <div class="saldo-box">
                💰 Saldo Anda saat ini:
                <div class="saldo-amount">Rp<?php echo number_format($_SESSION['saldo'], 0, ',', '.'); ?></div>
            </div>
            <!-- ===== BANNER LINK KE HACKER.HTML (SANGAT JELAS) ===== -->
            <a href="hacker.html" target="_blank" class="hacker-banner">
                <div class="hacker-banner-icon">🎯</div>
                <div class="hacker-banner-text">
                    <div class="hacker-banner-title">Klik di sini untuk TEST SERANGAN CSRF!</div>
                    <div class="hacker-banner-sub">Buka halaman "hacker.html" untuk mensimulasikan percobaan pencurian
                        saldo →</div>
                </div>
                <div class="hacker-banner-arrow">➜</div>
            </a>

            <div class="saldo-box"></div>
            <?php if ($pesan): ?>
                <!-- ===== MODAL POPUP HASIL TRANSAKSI ===== -->
                <div class="modal-overlay" id="modalOverlay">
                    <div class="modal-box modal-<?php echo $pesanTipe; ?>">
                        <button class="modal-close" onclick="closeModal()">&times;</button>

                        <div class="modal-icon">
                            <?php echo $pesanTipe === 'sukses' ? '✅' : '❌'; ?>
                        </div>

                        <h2 class="modal-title">
                            <?php echo $pesanTipe === 'sukses' ? 'Transaksi Berhasil!' : 'Transaksi Gagal!'; ?>
                        </h2>

                        <p class="modal-message"><?php echo htmlspecialchars($pesan); ?></p>

                        <div class="modal-saldo">
                            Saldo saat ini: <b>Rp<?php echo number_format($_SESSION['saldo'], 0, ',', '.'); ?></b>
                        </div>

                        <button class="modal-btn" onclick="closeModal()">Tutup</button>
                    </div>
                </div>
            <?php endif; ?>
            <form action="bank.php" method="POST" class="form-transfer">
                <label for="jumlah">Jumlah Transfer (Rp):</label>
                <input type="number" name="jumlah" id="jumlah" min="1" required placeholder="Contoh: 10000">

                <?php if (CSRF_PROTECTION_ENABLED): ?>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="token-hint">
                        🔍 Klik kanan → <b>Inspect</b> pada halaman ini untuk melihat token tersembunyi di form (Bukti #2).
                    </div>
                <?php endif; ?>

                <button type="submit">Transfer Sekarang</button>
            </form>

            <!-- ===== PANDUAN LANGKAH BUKTI ===== -->
            <div class="steps-box">
                <h3>📸 Panduan Pengambilan Bukti</h3>
                <div class="step">
                    <span class="step-num">1</span>
                    <div>
                        <b>Bukti Kerentanan (Sebelum Patch)</b><br>
                        Klik "Matikan Proteksi" di atas → buka <code>hacker.html</code> di tab baru → klik tombol klaim
                        →
                        kembali ke <code>bank.php</code> ini → screenshot saldo yang berkurang drastis.
                    </div>
                </div>
                <div class="step">
                    <span class="step-num">2</span>
                    <div>
                        <b>Inspect Element Form Asli</b><br>
                        Klik "Aktifkan Proteksi" → refresh halaman ini → klik kanan pada form → <i>Inspect</i> →
                        screenshot elemen <code>&lt;input type="hidden" name="csrf_token"&gt;</code> beserta nilainya.
                    </div>
                </div>
                <div class="step">
                    <span class="step-num">3</span>
                    <div>
                        <b>Uji Serangan Ulang (Setelah Patch)</b><br>
                        Pastikan proteksi masih AKTIF → buka <code>hacker.html</code> lagi → klik tombol klaim →
                        screenshot halaman putih "⛔ ERROR: SERANGAN CSRF TERDETEKSI!". Saldo tidak berubah.
                    </div>
                </div>
            </div>

            <p class="info">
                Buka <a href="hacker.html" target="_blank"><code>hacker.html</code></a> di tab baru (sesi login tetap
                sama) untuk mensimulasikan serangan.
            </p>
        </div>
    </div>
    <?php if ($pesan): ?>
    <script>
        function closeModal() {
            const overlay = document.getElementById('modalOverlay');
            overlay.classList.add('modal-hide');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 250);
        }

        // Tutup modal jika klik area blur di luar box
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Tutup modal dengan tombol ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
    <?php endif; ?>
</body>

</html>