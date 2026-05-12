<?php
// ============================================================
// SIMULASI RSA: Pengiriman Pesan Satu Arah (Alice & Bob)
// ============================================================

// --- Cari file openssl.cnf secara otomatis (fix XAMPP Windows) ---
$possiblePaths = [
    'C:/xampp/apache/conf/openssl.cnf',
    'C:/xampp/php/extras/openssl/openssl.cnf',
    'C:/laragon/bin/apache/openssl.cnf',
    '/etc/ssl/openssl.cnf',
    '/usr/local/etc/openssl/openssl.cnf',
];
$opensslConf = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) { $opensslConf = $path; break; }
}
if ($opensslConf) putenv("OPENSSL_CONF={$opensslConf}");

// ============================================================
// BAGIAN 1: Setup Alice — Generate Pasangan Kunci RSA
// ============================================================
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'config'           => $opensslConf ?? 'C:/xampp/apache/conf/openssl.cnf',
];

$keyPair = openssl_pkey_new($config);
if (!$keyPair) $errorGenerate = 'Gagal generate kunci RSA: ' . openssl_error_string();

$publicKey = $privateKey = null;
if (isset($keyPair) && $keyPair) {
    $detail    = openssl_pkey_get_details($keyPair);
    $publicKey = $detail['key'];
    openssl_pkey_export($keyPair, $privateKey, null, $config);
}

// ============================================================
// BAGIAN 2: Bob Mengirim Pesan — Enkripsi dengan Public Key Alice
// ============================================================
$plaintext = "Halo Alice, ini pesan rahasia dari Bob";
$ciphertext = $cipherB64 = null;

if ($publicKey) {
    $ok = openssl_public_encrypt($plaintext, $ciphertext, $publicKey);
    if (!$ok) $errorEncrypt = 'Gagal mengenkripsi pesan: ' . openssl_error_string();
    else       $cipherB64   = base64_encode($ciphertext);
}

// ============================================================
// BAGIAN 3: Alice Membaca Pesan — Dekripsi dengan Private Key
// ============================================================
$decrypted = null;
if ($ciphertext && $privateKey) {
    $ok2 = openssl_private_decrypt($ciphertext, $decrypted, $privateKey);
    if (!$ok2) $errorDecrypt = 'Gagal mendekripsi pesan: ' . openssl_error_string();
}

if (isset($keyPair) && $keyPair) openssl_free_key($keyPair);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Simulasi RSA Alice dan Bob</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

body{font-family:Arial,sans-serif;background:#eef2f7;color:#111;min-height:100vh}

/* ── Identity Card (desktop: fixed sidebar) ── */
.identity-card{
    position:fixed;top:20px;left:20px;width:210px;
    background:#1e3a5f;color:#fff;border-radius:10px;
    padding:16px;box-shadow:0 4px 18px rgba(0,0,0,.22);
    z-index:999;font-size:.78rem;line-height:1.5;
}
.identity-card .avatar{
    width:46px;height:46px;border-radius:50%;background:#4a90d9;
    display:flex;align-items:center;justify-content:center;
    font-size:1.2rem;font-weight:bold;margin:0 auto 10px;
    border:2px solid #a8c8f0;color:#fff;
}
.identity-card .id-name{
    font-size:.85rem;font-weight:bold;text-align:center;color:#d4e8ff;
    border-bottom:1px solid #2e5a8a;padding-bottom:8px;margin-bottom:8px;
}
.identity-card table{width:100%;border-collapse:collapse}
.identity-card table td{padding:3px 2px;vertical-align:top}
.identity-card table td:first-child{color:#90b8e0;white-space:nowrap;padding-right:6px;font-size:.71rem}
.identity-card table td:last-child{color:#e8f2ff;font-size:.74rem}
/* chips (mobile only) */
.id-chips{display:none}

/* ── Main content ── */
.main{margin-left:246px;padding:28px 28px 50px 24px}

/* ── Page Header ── */
.page-header{
    background:linear-gradient(135deg,#1e3a5f,#2e6da4);color:#fff;
    border-radius:10px;padding:24px 28px;margin-bottom:20px;
    box-shadow:0 3px 12px rgba(30,58,95,.18);
}
.page-header h1{font-size:1.45rem;margin-bottom:5px}
.page-header p{font-size:.82rem;color:#a8c8f0}

/* ── Flow Bar ── */
.flow-bar{
    display:flex;align-items:stretch;margin-bottom:20px;
    background:#fff;border:1px solid #d0dce8;border-radius:8px;
    overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.06);
}
.flow-step{
    flex:1;text-align:center;padding:11px 6px;
    font-size:.75rem;color:#555;border-right:1px solid #d0dce8;background:#f7fafd;
}
.flow-step:last-child{border-right:none}
.flow-step .si{display:block;font-size:1rem;margin-bottom:2px;color:#2e6da4;font-weight:bold}

/* ── Section Card ── */
.section{background:#fff;border:1px solid #d0dce8;border-radius:10px;margin-bottom:18px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden}
.section-header{display:flex;align-items:center;gap:10px;padding:13px 20px;border-bottom:1px solid #e4edf5;background:#f4f8fc}
.step-badge{width:26px;height:26px;border-radius:50%;background:#2e6da4;color:#fff;font-size:.8rem;font-weight:bold;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.section-header h2{font-size:.95rem;font-weight:bold;color:#1e3a5f}
.section-body{padding:16px 20px}

.lbl{font-size:.75rem;color:#666;margin-bottom:5px;font-weight:bold;text-transform:uppercase;letter-spacing:.04em}

/* Public key box — scrollable, collapsible on mobile */
.key-box{
    background:#f4f8fc;border:1px solid #d0dce8;border-left:3px solid #2e6da4;
    border-radius:4px;padding:10px 12px;
    font-family:'Courier New',monospace;font-size:.75rem;line-height:1.6;color:#1a2a3a;
    overflow-x:auto;white-space:pre-wrap;word-break:break-all;
    max-height:180px;overflow-y:auto;margin-top:5px;transition:max-height .3s ease;
}
.key-box.expanded{max-height:400px}

pre{
    background:#f4f8fc;border:1px solid #d0dce8;border-left:3px solid #2e6da4;
    border-radius:4px;padding:10px 12px;font-family:'Courier New',monospace;
    font-size:.78rem;overflow-x:auto;white-space:pre-wrap;word-break:break-all;
    line-height:1.65;margin-top:5px;color:#1a2a3a;
}

.result-box{
    background:#f0faf3;border:1px solid #a8ddb8;border-left:4px solid #2e8b57;
    border-radius:6px;padding:12px 14px;margin-top:6px;
}
.result-text{font-size:.95rem;font-weight:bold;color:#1a6e1a}
.result-check{font-size:.78rem;color:#2e8b57;margin-top:5px}

.info-tag{
    display:inline-block;background:#e8f0fb;border:1px solid #b8d0f0;
    border-radius:4px;padding:3px 8px;font-size:.72rem;color:#2e6da4;margin:8px 4px 0 0;
}

.error-box{
    background:#fff4f4;border:1px solid #e8b0b0;border-left:4px solid #cc3333;
    border-radius:6px;padding:10px 14px;color:#cc0000;font-size:.85rem;
}

.sp{margin-top:12px}

/* Toggle button – visible only on mobile */
.toggle-btn{
    display:none;background:#2e6da4;color:#fff;border:none;border-radius:5px;
    padding:6px 14px;font-size:.76rem;cursor:pointer;margin-top:8px;
}
.toggle-btn:hover{background:#1e5a90}

/* ════════════════════════════════════
   MOBILE  < 768px
════════════════════════════════════ */
@media(max-width:767px){

    body{background:#e8eef6}

    /* Identity card → compact topbar */
    .identity-card{
        position:relative;top:auto;left:auto;width:100%;border-radius:0;
        padding:10px 14px;display:flex;align-items:center;gap:12px;
        box-shadow:0 2px 8px rgba(0,0,0,.2);
    }
    .identity-card .avatar{width:38px;height:38px;font-size:.95rem;margin:0;flex-shrink:0}
    .identity-card .id-name{font-size:.82rem;text-align:left;border-bottom:none;padding-bottom:0;margin-bottom:2px}
    /* sembunyikan tabel, tampilkan chips */
    .identity-card table{display:none}
    .id-chips{display:flex;flex-wrap:wrap;gap:3px}
    .id-chip{
        background:rgba(255,255,255,.13);border-radius:3px;
        padding:2px 7px;font-size:.67rem;color:#c8dff5;white-space:nowrap;
    }

    /* Main */
    .main{margin-left:0;padding:12px 12px 40px}

    /* Header */
    .page-header{padding:14px 16px;border-radius:8px;margin-bottom:14px}
    .page-header h1{font-size:1.05rem;margin-bottom:4px}
    .page-header p{font-size:.73rem}

    /* Flow bar → vertical */
    .flow-bar{flex-direction:column;margin-bottom:14px}
    .flow-step{
        border-right:none;border-bottom:1px solid #d0dce8;
        padding:8px 14px;text-align:left;display:flex;align-items:center;gap:10px;font-size:.77rem;
    }
    .flow-step:last-child{border-bottom:none}
    .flow-step .si{display:inline;font-size:.95rem;margin-bottom:0}

    /* Sections */
    .section{border-radius:8px;margin-bottom:14px}
    .section-header{padding:10px 14px}
    .section-header h2{font-size:.86rem}
    .section-body{padding:12px 14px}

    /* Public key collapsed by default */
    .key-box{max-height:80px;font-size:.67rem}
    .key-box.expanded{max-height:280px}
    .toggle-btn{display:inline-block}

    pre{font-size:.69rem;padding:9px 10px}
    .result-text{font-size:.86rem}
    .info-tag{font-size:.67rem;padding:2px 7px}
    .lbl{font-size:.69rem}
}

/* Extra small < 400px */
@media(max-width:399px){
    .page-header h1{font-size:.92rem}
    .main{padding:10px 10px 36px}
}
</style>
</head>
<body>

<!-- ═══════════════════════════════════════
     IDENTITY CARD
     Desktop : fixed sidebar kiri
     Mobile  : horizontal topbar
═══════════════════════════════════════ -->
<div class="identity-card">
    <div class="avatar">MH</div>
    <div>
        <div class="id-name">Muhammad Hasbih Akbar</div>
        <!-- Desktop: tabel -->
        <table>
            <tr><td>NIM</td><td>231220075</td></tr>
            <tr><td>Kelas</td><td>TI 31</td></tr>
            <tr><td>Matkul</td><td>Kriptografi</td></tr>
            <tr><td>Dosen</td><td>Sucipto, M.Kom</td></tr>
        </table>
        <!-- Mobile: chips -->
        <div class="id-chips">
            <span class="id-chip">231220075</span>
            <span class="id-chip">TI 31</span>
            <span class="id-chip">Kriptografi</span>
            <span class="id-chip">Sucipto, M.Kom</span>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════ -->
<div class="main">

    <!-- Header -->
    <div class="page-header">
        <h1>&#128274; Simulasi RSA Alice dan Bob</h1>
        <p>Bob mengenkripsi dengan Public Key Alice &mdash; Alice mendekripsi dengan Private Key-nya.</p>
    </div>

    <!-- Flow Bar -->
    <div class="flow-bar">
        <div class="flow-step"><span class="si">&#128273;</span> Alice generate pasangan kunci RSA</div>
        <div class="flow-step"><span class="si">&#128228;</span> Bob enkripsi pesan dengan Public Key</div>
        <div class="flow-step"><span class="si">&#128229;</span> Alice dekripsi pesan dengan Private Key</div>
    </div>

    <?php if(isset($errorGenerate)):?>
    <div class="error-box" style="margin-bottom:16px">&#9888; <?=htmlspecialchars($errorGenerate)?></div>
    <?php endif;?>

    <!-- SECTION 1: Public Key Alice -->
    <div class="section">
        <div class="section-header">
            <div class="step-badge">1</div>
            <h2>Public Key Alice</h2>
        </div>
        <div class="section-body">
            <?php if($publicKey):?>
            <div class="lbl">Public Key (PEM) &mdash; dibagikan ke Bob</div>
            <div class="key-box" id="pubKeyBox"><?=htmlspecialchars($publicKey)?></div>
            <button class="toggle-btn" id="toggleBtn" onclick="toggleKey()">&#9660; Tampilkan Selengkapnya</button>
            <br>
            <span class="info-tag">&#128272; RSA</span>
            <span class="info-tag">2048 bit</span>
            <span class="info-tag">Format PEM</span>
            <?php else:?>
            <div class="error-box">&#9888; Public key tidak berhasil dibuat.</div>
            <?php endif;?>
        </div>
    </div>

    <!-- SECTION 2: Ciphertext dari Bob -->
    <div class="section">
        <div class="section-header">
            <div class="step-badge">2</div>
            <h2>Ciphertext dari Bob</h2>
        </div>
        <div class="section-body">
            <?php if(isset($errorEncrypt)):?>
            <div class="error-box">&#9888; <?=htmlspecialchars($errorEncrypt)?></div>
            <?php elseif($cipherB64):?>
            <div class="lbl">Pesan Asli (Plaintext)</div>
            <pre><?=htmlspecialchars($plaintext)?></pre>
            <div class="lbl sp">Ciphertext Hasil Enkripsi (Base64)</div>
            <pre><?=htmlspecialchars($cipherB64)?></pre>
            <span class="info-tag">openssl_public_encrypt()</span>
            <span class="info-tag">PKCS#1 Padding</span>
            <span class="info-tag">Base64</span>
            <?php else:?>
            <div class="error-box">&#9888; Ciphertext tidak tersedia.</div>
            <?php endif;?>
        </div>
    </div>

    <!-- SECTION 3: Hasil Dekripsi oleh Alice -->
    <div class="section">
        <div class="section-header">
            <div class="step-badge">3</div>
            <h2>Hasil Dekripsi oleh Alice</h2>
        </div>
        <div class="section-body">
            <?php if(isset($errorDecrypt)):?>
            <div class="error-box">&#9888; <?=htmlspecialchars($errorDecrypt)?></div>
            <?php elseif($decrypted!==null):?>
            <div class="lbl">Pesan setelah didekripsi dengan Private Key Alice</div>
            <div class="result-box">
                <div class="result-text">&#128232; <?=htmlspecialchars($decrypted)?></div>
                <div class="result-check">
                    <?=($decrypted===$plaintext)
                        ?'&#10004; Dekripsi berhasil &mdash; pesan cocok dengan plaintext asli.'
                        :'&#10008; Pesan tidak cocok dengan plaintext asli!'?>
                </div>
            </div>
            <span class="info-tag">openssl_private_decrypt()</span>
            <?php else:?>
            <div class="error-box">&#9888; Dekripsi tidak dapat dilakukan.</div>
            <?php endif;?>
        </div>
    </div>

</div><!-- /.main -->

<script>
function toggleKey(){
    var box=document.getElementById('pubKeyBox');
    var btn=document.getElementById('toggleBtn');
    if(box.classList.contains('expanded')){
        box.classList.remove('expanded');
        btn.innerHTML='&#9660; Tampilkan Selengkapnya';
    } else {
        box.classList.add('expanded');
        btn.innerHTML='&#9650; Sembunyikan';
    }
}
</script>
</body>
</html>