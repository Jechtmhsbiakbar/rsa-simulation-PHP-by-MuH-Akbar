<?php
/**
 * ============================================================
 * SSL Certificate Generator - Tugas Kriptografi
 * ============================================================
 * Menggunakan PHP OpenSSL Extension untuk membuat:
 * - RSA Private Key
 * - Certificate Signing Request (CSR)
 * - Self-Signed SSL Certificate (.crt)
 * ============================================================
 */

// ─── HELPER FUNCTION: Auto-detect OpenSSL Config Path ──────
/**
 * Mencari lokasi openssl.cnf dari berbagai direktori
 * Mendukung: Windows XAMPP, Linux, macOS, Laragon, Cloud Hosting
 * 
 * @return string|null Path ke openssl.cnf atau null jika tidak ditemukan
 */
function getOpenSSLConfigPath() {
    // 1. Cek environment variable (custom path dari admin)
    if (!empty(getenv('OPENSSL_CONF'))) {
        $envPath = getenv('OPENSSL_CONF');
        if (file_exists($envPath)) {
            return $envPath;
        }
    }
    
    // 2. Definisikan kemungkinan lokasi berdasarkan OS & server setup
    $possiblePaths = [
        // Windows - XAMPP
        'C:/xampp/php/extras/ssl/openssl.cnf',
        'C:/xampp/apache/conf/openssl.cnf',
        'C:/xampp/php/extras/openssl/openssl.cnf',
        
        // Windows - Laragon
        'C:/laragon/bin/apache/openssl.cnf',
        'C:/laragon/bin/php/extras/ssl/openssl.cnf',
        
        // Windows - Alternative paths
        'C:/php/extras/ssl/openssl.cnf',
        'C:/php/openssl.cnf',
        
        // Linux / macOS
        '/etc/ssl/openssl.cnf',
        '/usr/local/etc/openssl/openssl.cnf',
        '/etc/openssl/openssl.cnf',
        '/usr/lib/ssl/openssl.cnf',
        '/usr/share/ssl/openssl.cnf',
        
        // Cloud Hosting
        '/usr/local/openssl/openssl.cnf',
        '/opt/openssl/openssl.cnf',
    ];
    
    // 3. Iterasi dan cari file yang ada
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // 4. Jika tidak ditemukan, return null (OpenSSL akan pakai default)
    return null;
}

// Inisialisasi variabel output
$privateKey = '';
$certificate = '';
$error = '';
$success = false;

// Ambil OpenSSL config path
$opensslConfigPath = getOpenSSLConfigPath();

// Debug info (untuk development)
$openSSLInfo = [
    'version' => OPENSSL_VERSION_TEXT,
    'config_path' => $opensslConfigPath ? $opensslConfigPath : 'Using system default',
    'config_found' => $opensslConfigPath ? 'Yes' : 'No (fallback ke default sistem)',
];


// ─── PROSES GENERATE SSL ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitasi & ambil input dari form
    $country = strtoupper(substr(trim($_POST['country'] ?? 'ID'), 0, 2));
    $state = trim($_POST['state'] ?? '');
    $locality = trim($_POST['locality'] ?? '');
    $organization = trim($_POST['organization'] ?? '');
    $commonName = trim($_POST['common_name'] ?? '');

    // Validasi field tidak boleh kosong
    if (
        empty($country) || empty($state) || empty($locality) ||
        empty($organization) || empty($commonName)
    ) {
        $error = 'Semua field wajib diisi sebelum generate sertifikat.';
    } else {

        // ── STEP 1: Buat RSA Private Key (2048-bit) ──────────
        $privKeyConfig = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        
        // Tambahkan config path jika ditemukan
        if ($opensslConfigPath) {
            $privKeyConfig['config'] = $opensslConfigPath;
        }
        
        $privKeyResource = openssl_pkey_new($privKeyConfig);

        if (!$privKeyResource) {
            $error = 'Gagal membuat RSA Private Key:<br>';

            while ($msg = openssl_error_string()) {
                $error .= $msg . '<br>';
            }
        } else {

            // ── STEP 2: Susun Distinguished Name (DN) ────────
            $dn = [
                'countryName' => $country,
                'stateOrProvinceName' => $state,
                'localityName' => $locality,
                'organizationName' => $organization,
                'commonName' => $commonName,
            ];

            // ── STEP 3: Buat CSR (Certificate Signing Request) ─
            $csrConfig = [
                'digest_alg' => 'sha256',
            ];
            
            // Tambahkan config path jika ditemukan
            if ($opensslConfigPath) {
                $csrConfig['config'] = $opensslConfigPath;
            }
            
            $csr = openssl_csr_new($dn, $privKeyResource, $csrConfig);

            if (!$csr) {
                $error = 'Gagal membuat CSR: ' . openssl_error_string();
            } else {

                // ── STEP 4: Sign CSR → Self-Signed Certificate ─
                // Masa berlaku: 365 hari
                $signConfig = [
                    'digest_alg' => 'sha256',
                    'x509_extensions' => 'v3_ca',
                ];
                
                // Tambahkan config path jika ditemukan
                if ($opensslConfigPath) {
                    $signConfig['config'] = $opensslConfigPath;
                }
                
                $x509 = openssl_csr_sign(
                    $csr,
                    null,
                    $privKeyResource,
                    365,
                    $signConfig,
                    0
                );

                if (!$x509) {
                    $error = 'Gagal melakukan signing sertifikat: ' . openssl_error_string();
                } else {

                    // ── STEP 5: Export ke string PEM ─────────
                    $x509Export = openssl_x509_export($x509, $certificate);

                    if (!$x509Export) {

                        $error = "Export certificate gagal:<br>";

                        while ($msg = openssl_error_string()) {
                            $error .= $msg . "<br>";
                        }
                    }

                    $pkeyExport = openssl_pkey_export(
                        $privKeyResource,
                        $privateKey,
                        null,
                        $opensslConfigPath ? ['config' => $opensslConfigPath] : []
                    );

                    if ($x509Export && $pkeyExport) {

                        $success = true;

                    } else {

                        $error = 'Gagal export SSL output:<br>';

                        while ($msg = openssl_error_string()) {
                            $error .= $msg . '<br>';
                        }
                    }

                    // Bebaskan resource
                    openssl_x509_free($x509);
                }
            }

            openssl_pkey_free($privKeyResource);
        }
    }
}

// ─── HANDLE DOWNLOAD REQUEST ─────────────────────────────────
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    $content = $_POST['dl_content'] ?? '';
    if ($type === 'key') {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="private.key"');
        echo $content;
        exit;
    } elseif ($type === 'crt') {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="certificate.crt"');
        echo $content;
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SSL Certificate Generator — CryptoSec Studio</title>

    <!-- Google Fonts: Orbitron (display) + Syne (body) + JetBrains Mono (code) -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Syne:wght@400;500;600;700;800&family=JetBrains+Mono:wght@300;400;500&display=swap"
        rel="stylesheet" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css" />
</head>

<body>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  BACKGROUND AMBIENT ORBS                                   -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="ambient-layer" aria-hidden="true">
        <div class="orb orb--cyan"></div>
        <div class="orb orb--purple"></div>
        <div class="orb orb--green"></div>
        <div class="grid-overlay"></div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  NAVBAR                                                    -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <nav class="navbar" role="navigation">
        <div class="nav-inner">
            <div class="nav-brand">
                <svg class="nav-logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 2L4 8v8c0 7.18 5.16 13.9 12 15.93C23.84 29.9 28 23.18 28 16V8L16 2z"
                        stroke="url(#navGrad)" stroke-width="1.5" fill="none" />
                    <path d="M11 16l3 3 7-7" stroke="url(#navGrad)" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <defs>
                        <linearGradient id="navGrad" x1="4" y1="2" x2="28" y2="32" gradientUnits="userSpaceOnUse">
                            <stop offset="0%" stop-color="#06b6d4" />
                            <stop offset="100%" stop-color="#8b5cf6" />
                        </linearGradient>
                    </defs>
                </svg>
                <span class="nav-brand-text">Crypto<span class="brand-accent">Sec</span></span>
            </div>

            <div class="nav-tags">
                <span class="nav-tag">PHP OpenSSL</span>
                <span class="nav-tag nav-tag--active">v1.0.0</span>
            </div>

            <div class="nav-status">
                <span class="status-dot"></span>
                <span class="status-label">MuH Akbar Mhs Teladan</span>
            </div>
        </div>
    </nav>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  HERO SECTION                                              -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <header class="hero" role="banner">
        <div class="hero-inner">
            <div class="hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="#06b6d4" stroke-width="2"
                        stroke-linejoin="round" />
                </svg>
                Cryptography Final Project — 2025
            </div>

            <h1 class="hero-title">
                <span class="title-line">SSL Certificate</span>
                <span class="title-line title-gradient">Generator</span>
            </h1>

            <p class="hero-sub">
                Implementasi kriptografi asimetris berbasis RSA 2048-bit.<br />
                Generate <em>Private Key</em>, <em>CSR</em>, dan <em>Self-Signed Certificate</em>
                secara real-time menggunakan PHP OpenSSL.
            </p>

            <div class="hero-stats">
                <div class="stat-pill">
                    <span class="stat-num">2048</span>
                    <span class="stat-lbl">bit RSA</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-pill">
                    <span class="stat-num">SHA-256</span>
                    <span class="stat-lbl">Digest</span>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-pill">
                    <span class="stat-num">365</span>
                    <span class="stat-lbl">hari valid</span>
                </div>
            </div>
        </div>

        <!-- Decorative hex grid -->
        <div class="hero-hex" aria-hidden="true"></div>
    </header>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  IDENTITY CARD MAHASISWA                                   -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section class="section-identity" aria-label="Identitas Mahasiswa">
        <div class="container">

            <div class="identity-card glass-card">
                <!-- Animated border ring -->
                <div class="card-ring" aria-hidden="true"></div>

                <!-- Left: Avatar + Badge -->
                <div class="id-avatar-col">
                    <div class="avatar-wrap">
                        <div class="avatar-ring"></div>
                        <div class="avatar-inner">
                            <!-- SVG Cyber Avatar -->
                            <svg class="avatar-svg" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="40" cy="28" r="14" stroke="url(#avGrad)" stroke-width="1.5" />
                                <path d="M14 72c0-14.36 11.64-26 26-26h0c14.36 0 26 11.64 26 26" stroke="url(#avGrad)"
                                    stroke-width="1.5" stroke-linecap="round" />
                                <circle cx="40" cy="28" r="8" fill="url(#avFill)" opacity="0.3" />
                                <path d="M34 28l4 4 8-8" stroke="#06b6d4" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <defs>
                                    <linearGradient id="avGrad" x1="14" y1="14" x2="66" y2="72"
                                        gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#06b6d4" />
                                        <stop offset="100%" stop-color="#8b5cf6" />
                                    </linearGradient>
                                    <linearGradient id="avFill" x1="32" y1="20" x2="48" y2="36"
                                        gradientUnits="userSpaceOnUse">
                                        <stop offset="0%" stop-color="#06b6d4" />
                                        <stop offset="100%" stop-color="#8b5cf6" />
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                    </div>
                    <div class="crypto-badge">CRYPTOGRAPHY PROJECT</div>
                </div>

                <!-- Center: Identity Info -->
                <div class="id-info-col">
                    <div class="id-label">MAHASISWA INFORMATIKA YANG TELADAN</div>
                    <h2 class="id-name">Muhammad Hasbih Akbar</h2>
                    <div class="id-meta-grid">
                        <div class="id-meta-item">
                            <span class="meta-key">NIM</span>
                            <span class="meta-val">231220075</span>
                        </div>
                        <div class="id-meta-item">
                            <span class="meta-key">Kelas</span>
                            <span class="meta-val">TI 31</span>
                        </div>
                        <div class="id-meta-item">
                            <span class="meta-key">Mata Kuliah</span>
                            <span class="meta-val">Kriptografi</span>
                        </div>
                        <div class="id-meta-item">
                            <span class="meta-key">Dosen</span>
                            <span class="meta-val">Sucipto, M.Kom.</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Project Title -->
                <div class="id-title-col">
                    <div class="project-label">JUDUL TUGAS</div>
                    <p class="project-title">
                        Implementasi SSL Certificate Generator Menggunakan PHP OpenSSL
                    </p>
                    <div class="tech-tags">
                        <span class="tech-tag">PHP Native</span>
                        <span class="tech-tag">OpenSSL</span>
                        <span class="tech-tag">RSA 2048</span>
                        <span class="tech-tag">X.509</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  FORM GENERATOR                                            -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section class="section-form" aria-label="Form SSL Generator">
        <div class="container">

            <div class="section-header">
                <div class="section-num">01</div>
                <div class="section-meta">
                    <h2 class="section-title">Certificate <span class="text-gradient">Configuration</span></h2>
                    <p class="section-desc">Isi informasi identitas sertifikat SSL yang akan digenerate</p>
                </div>
            </div>

            <div class="form-card glass-card">

                <?php if (!empty($error)): ?>
                    <!-- Error Alert -->
                    <div class="alert alert--error" role="alert">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2" />
                            <path d="M12 8v4M12 16h.01" stroke="#ef4444" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="#result" id="sslForm" novalidate>

                    <div class="form-grid">

                        <!-- Country -->
                        <div class="field-wrap">
                            <div class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5" />
                                    <path
                                        d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"
                                        stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </div>
                            <input type="text" id="country" name="country" class="field-input" maxlength="2"
                                value="<?= htmlspecialchars($_POST['country'] ?? 'ID') ?>" placeholder=" " required />
                            <label class="field-label" for="country">Country Code (2 huruf)</label>
                            <div class="field-hint">Contoh: ID, US, SG</div>
                        </div>

                        <!-- State -->
                        <div class="field-wrap">
                            <div class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14" stroke="currentColor" stroke-width="1.5"
                                        stroke-linejoin="round" />
                                    <path d="M9 21v-4h6v4" stroke="currentColor" stroke-width="1.5"
                                        stroke-linejoin="round" />
                                </svg>
                            </div>
                            <input type="text" id="state" name="state" class="field-input"
                                value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" placeholder=" " required />
                            <label class="field-label" for="state">State / Provinsi</label>
                            <div class="field-hint">Contoh: Kalimantan Barat</div>
                        </div>

                        <!-- Locality -->
                        <div class="field-wrap">
                            <div class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 21s-8-6.882-8-12a8 8 0 1116 0c0 5.118-8 12-8 12z" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <circle cx="12" cy="9" r="3" stroke="currentColor" stroke-width="1.5" />
                                </svg>
                            </div>
                            <input type="text" id="locality" name="locality" class="field-input"
                                value="<?= htmlspecialchars($_POST['locality'] ?? '') ?>" placeholder=" " required />
                            <label class="field-label" for="locality">Locality / Kota</label>
                            <div class="field-hint">Contoh: Pontianak</div>
                        </div>

                        <!-- Organization -->
                        <div class="field-wrap">
                            <div class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <rect x="2" y="7" width="20" height="14" rx="2" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <path d="M12 12v4M10 14h4" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" />
                                </svg>
                            </div>
                            <input type="text" id="organization" name="organization" class="field-input"
                                value="<?= htmlspecialchars($_POST['organization'] ?? '') ?>" placeholder=" "
                                required />
                            <label class="field-label" for="organization">Organization Name</label>
                            <div class="field-hint">Contoh: Universitas Tanjungpura</div>
                        </div>

                        <!-- Common Name (full width) -->
                        <div class="field-wrap field-wrap--full">
                            <div class="field-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <path d="M8 21h8M12 17v4" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" />
                                </svg>
                            </div>
                            <input type="text" id="common_name" name="common_name" class="field-input"
                                value="<?= htmlspecialchars($_POST['common_name'] ?? '') ?>" placeholder=" " required />
                            <label class="field-label" for="common_name">Common Name / Domain</label>
                            <div class="field-hint">Contoh: www.fabian-portofolio.com</div>
                        </div>

                    </div><!-- /form-grid -->

                    <!-- Submit Button -->
                    <div class="form-action">
                        <button type="submit" class="btn-generate" id="generateBtn">
                            <span class="btn-inner">
                                <svg class="btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor"
                                        stroke-width="2" stroke-linejoin="round" />
                                    <path d="M8 11.5l3 3 5-5" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span class="btn-text">Generate SSL Certificate</span>
                                <span class="btn-loader" aria-hidden="true"></span>
                            </span>
                            <span class="btn-glow" aria-hidden="true"></span>
                        </button>

                        <div class="form-info">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="#8b5cf6" stroke-width="1.5" />
                                <path d="M12 8v4M12 16h.01" stroke="#8b5cf6" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                            <span>RSA 2048-bit · SHA-256 · X.509 · Valid 365 Hari</span>
                        </div>
                    </div>

                </form>

            </div><!-- /form-card -->
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  RESULT SECTION                                            -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <?php if ($success): ?>
        <section class="section-result" id="result" aria-label="Hasil Sertifikat">
            <div class="container">

                <div class="section-header">
                    <div class="section-num success-num">02</div>
                    <div class="section-meta">
                        <h2 class="section-title">
                            <svg class="title-check" width="28" height="28" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="#22c55e" stroke-width="2" />
                                <path d="M8 12l3 3 5-5" stroke="#22c55e" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            SSL <span class="text-success">Successfully Generated</span>
                        </h2>
                        <p class="section-desc">Sertifikat berhasil dibuat · RSA 2048-bit · SHA-256 · Berlaku 365 hari</p>
                    </div>
                </div>

                <!-- Success banner -->
                <div class="success-banner">
                    <div class="success-pulse" aria-hidden="true"></div>
                    <div class="success-content">
                        <div class="success-icon-wrap">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="url(#sGrad)"
                                    stroke-width="1.5" fill="none" />
                                <path d="M8 12l3 3 5-5" stroke="#22c55e" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <defs>
                                    <linearGradient id="sGrad" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#22c55e" />
                                        <stop offset="1" stop-color="#06b6d4" />
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        <div class="success-text">
                            <strong>Certificate Generated Successfully</strong>
                            <span>Private Key + SSL Certificate siap digunakan</span>
                        </div>
                    </div>
                    <div class="success-meta">
                        <div class="smeta-item"><span class="smeta-k">Algorithm</span><span class="smeta-v">RSA
                                2048-bit</span></div>
                        <div class="smeta-item"><span class="smeta-k">Signature</span><span class="smeta-v">SHA-256</span>
                        </div>
                        <div class="smeta-item"><span class="smeta-k">Validity</span><span class="smeta-v">365 Days</span>
                        </div>
                        <div class="smeta-item"><span class="smeta-k">Format</span><span class="smeta-v">PEM / X.509</span>
                        </div>
                    </div>
                </div>

                <!-- Tab Output -->
                <div class="output-card glass-card">

                    <!-- Tab Nav -->
                    <div class="tab-nav" role="tablist">
                        <button class="tab-btn tab-btn--active" role="tab" aria-selected="true" data-tab="key" id="tab-key">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Private Key
                            <span class="tab-badge">.key</span>
                        </button>
                        <button class="tab-btn" role="tab" aria-selected="false" data-tab="crt" id="tab-crt">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2"
                                    stroke-linejoin="round" />
                            </svg>
                            Certificate
                            <span class="tab-badge tab-badge--green">.crt</span>
                        </button>
                    </div>

                    <!-- Tab: Private Key -->
                    <div class="tab-panel tab-panel--active" id="panel-key" role="tabpanel" aria-labelledby="tab-key">
                        <div class="output-toolbar">
                            <div class="output-title">
                                <span class="output-dot output-dot--cyan"></span>
                                RSA Private Key — PEM Format
                            </div>
                            <div class="output-actions">
                                <button class="action-btn" onclick="copyContent('keyOutput', this)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                                        <rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor"
                                            stroke-width="2" />
                                        <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="currentColor"
                                            stroke-width="2" />
                                    </svg>
                                    Copy
                                </button>
                                <!-- Download form -->
                                <form method="POST" action="?download=key" style="display:inline;" target="_blank">
                                    <input type="hidden" name="dl_content" value="<?= htmlspecialchars($privateKey) ?>" />
                                    <button type="submit" class="action-btn action-btn--download">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        Download .key
                                    </button>
                                </form>
                            </div>
                        </div>
                        <textarea id="keyOutput" class="output-textarea"
                            readonly><?= htmlspecialchars($privateKey) ?></textarea>
                    </div>

                    <!-- Tab: Certificate -->
                    <div class="tab-panel" id="panel-crt" role="tabpanel" aria-labelledby="tab-crt" hidden>
                        <div class="output-toolbar">
                            <div class="output-title">
                                <span class="output-dot output-dot--green"></span>
                                SSL Certificate — X.509 PEM Format
                            </div>
                            <div class="output-actions">
                                <button class="action-btn" onclick="copyContent('crtOutput', this)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                                        <rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor"
                                            stroke-width="2" />
                                        <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" stroke="currentColor"
                                            stroke-width="2" />
                                    </svg>
                                    Copy
                                </button>
                                <form method="POST" action="?download=crt" style="display:inline;" target="_blank">
                                    <input type="hidden" name="dl_content" value="<?= htmlspecialchars($certificate) ?>" />
                                    <button type="submit" class="action-btn action-btn--download">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                        Download .crt
                                    </button>
                                </form>
                            </div>
                        </div>
                        <textarea id="crtOutput" class="output-textarea"
                            readonly><?= htmlspecialchars($certificate) ?></textarea>
                    </div>

                </div><!-- /output-card -->

                <!-- Cert Info Strip -->
                <div class="cert-info-strip">
                    <div class="cert-info-item">
                        <span class="ci-label">Country</span>
                        <span class="ci-value"><?= htmlspecialchars($_POST['country'] ?? '') ?></span>
                    </div>
                    <div class="cert-info-item">
                        <span class="ci-label">State</span>
                        <span class="ci-value"><?= htmlspecialchars($_POST['state'] ?? '') ?></span>
                    </div>
                    <div class="cert-info-item">
                        <span class="ci-label">Locality</span>
                        <span class="ci-value"><?= htmlspecialchars($_POST['locality'] ?? '') ?></span>
                    </div>
                    <div class="cert-info-item">
                        <span class="ci-label">Organization</span>
                        <span class="ci-value"><?= htmlspecialchars($_POST['organization'] ?? '') ?></span>
                    </div>
                    <div class="cert-info-item">
                        <span class="ci-label">Common Name</span>
                        <span class="ci-value"><?= htmlspecialchars($_POST['common_name'] ?? '') ?></span>
                    </div>
                </div>

            </div>
        </section>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  HOW IT WORKS SECTION                                      -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <section class="section-how" aria-label="Cara Kerja">
        <div class="container">

            <div class="section-header center">
                <h2 class="section-title">How It <span class="text-gradient">Works</span></h2>
                <p class="section-desc">Alur kriptografi di balik pembuatan SSL Certificate</p>
            </div>

            <div class="steps-flow">
                <div class="step-item">
                    <div class="step-num">01</div>
                    <div class="step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="11" width="18" height="11" rx="2" stroke="url(#sg1)" stroke-width="1.5" />
                            <path d="M7 11V7a5 5 0 0110 0v4" stroke="url(#sg1)" stroke-width="1.5"
                                stroke-linecap="round" />
                            <defs>
                                <linearGradient id="sg1" x1="3" y1="3" x2="21" y2="22" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4" />
                                    <stop offset="1" stop-color="#8b5cf6" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <h3 class="step-title">RSA Key Generation</h3>
                    <p class="step-desc">Membuat pasangan kunci RSA 2048-bit menggunakan <code>openssl_pkey_new()</code>
                    </p>
                </div>

                <div class="step-arrow" aria-hidden="true">→</div>

                <div class="step-item">
                    <div class="step-num">02</div>
                    <div class="step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="url(#sg2)"
                                stroke-width="1.5" stroke-linejoin="round" />
                            <polyline points="14,2 14,8 20,8" stroke="url(#sg2)" stroke-width="1.5"
                                stroke-linejoin="round" />
                            <defs>
                                <linearGradient id="sg2" x1="4" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4" />
                                    <stop offset="1" stop-color="#8b5cf6" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <h3 class="step-title">CSR Creation</h3>
                    <p class="step-desc">Membuat Certificate Signing Request dengan DN menggunakan
                        <code>openssl_csr_new()</code>
                    </p>
                </div>

                <div class="step-arrow" aria-hidden="true">→</div>

                <div class="step-item">
                    <div class="step-num">03</div>
                    <div class="step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="url(#sg3)" stroke-width="1.5"
                                stroke-linejoin="round" />
                            <path d="M9 12l2 2 4-4" stroke="url(#sg3)" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <defs>
                                <linearGradient id="sg3" x1="4" y1="2" x2="20" y2="22" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#06b6d4" />
                                    <stop offset="1" stop-color="#8b5cf6" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <h3 class="step-title">Certificate Signing</h3>
                    <p class="step-desc">Menandatangani CSR dengan SHA-256 menggunakan <code>openssl_csr_sign()</code>
                    </p>
                </div>

                <div class="step-arrow" aria-hidden="true">→</div>

                <div class="step-item">
                    <div class="step-num">04</div>
                    <div class="step-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="url(#sg4)"
                                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <defs>
                                <linearGradient id="sg4" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#22c55e" />
                                    <stop offset="1" stop-color="#06b6d4" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <h3 class="step-title">Export PEM</h3>
                    <p class="step-desc">Mengekspor Private Key & Certificate ke format PEM menggunakan
                        <code>openssl_x509_export()</code>
                    </p>
                </div>
            </div>

        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  FOOTER                                                    -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <footer class="footer" role="contentinfo">
        <div class="footer-inner">
            <div class="footer-brand">
                <svg width="20" height="20" viewBox="0 0 32 32" fill="none">
                    <path d="M16 2L4 8v8c0 7.18 5.16 13.9 12 15.93C23.84 29.9 28 23.18 28 16V8L16 2z"
                        stroke="url(#footGrad)" stroke-width="1.5" />
                    <path d="M11 16l3 3 7-7" stroke="url(#footGrad)" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <defs>
                        <linearGradient id="footGrad" x1="4" y1="2" x2="28" y2="32" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#06b6d4" />
                            <stop offset="1" stop-color="#8b5cf6" />
                        </linearGradient>
                    </defs>
                </svg>
                <span>Crypto<span class="brand-accent">Sec</span> Studio</span>
            </div>
            <div class="footer-text">
                Tugas Kriptografi — <?= date('Y') ?> · Muhammad Hasbih Akbar · NIM 231220075
            </div>
            <div class="footer-tech">
                Built with <span class="tech-highlight">PHP OpenSSL</span> · RSA 2048 · X.509
            </div>
        </div>
    </footer>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!--  TOAST NOTIFICATION                                        -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="toast" id="toast" role="status" aria-live="polite">
        <span class="toast-icon">✓</span>
        <span class="toast-msg" id="toastMsg">Copied to clipboard!</span>
    </div>

    <!-- Custom JS -->
    <script src="script.js"></script>
</body>

</html>