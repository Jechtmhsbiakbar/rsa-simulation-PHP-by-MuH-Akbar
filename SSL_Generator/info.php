<?php
/**
 * OpenSSL Configuration Info & Debug
 * Halaman ini menampilkan informasi OpenSSL yang terdeteksi oleh sistem
 */

// Helper function untuk detect OpenSSL config path
function getOpenSSLConfigPath() {
    // 1. Cek environment variable
    if (!empty(getenv('OPENSSL_CONF'))) {
        $envPath = getenv('OPENSSL_CONF');
        if (file_exists($envPath)) {
            return $envPath;
        }
    }
    
    // 2. Definisikan lokasi yang mungkin
    $possiblePaths = [
        // Windows - XAMPP
        'C:/xampp/php/extras/ssl/openssl.cnf',
        'C:/xampp/apache/conf/openssl.cnf',
        'C:/xampp/php/extras/openssl/openssl.cnf',
        
        // Windows - Laragon
        'C:/laragon/bin/apache/openssl.cnf',
        'C:/laragon/bin/php/extras/ssl/openssl.cnf',
        
        // Windows - Alternative
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
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}

// Gather information
$configPath = getOpenSSLConfigPath();
$phpVersion = phpversion();
$opensslVersion = OPENSSL_VERSION_TEXT;
$isOpenSSLInstalled = extension_loaded('openssl');

// Get all PHP extensions related to security/crypto
$loadedExtensions = get_loaded_extensions();
$cryptoExtensions = array_filter($loadedExtensions, function($ext) {
    return in_array(strtolower($ext), ['openssl', 'mcrypt', 'sodium', 'gmp', 'bcmath']);
});

// Check system OS
$os = PHP_OS_FAMILY;
$osDetail = php_uname();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>OpenSSL Configuration Info - SSL Generator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .info-group {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .info-group-title {
            background: #f5f5f5;
            padding: 12px 16px;
            font-weight: bold;
            font-size: 15px;
            color: #333;
            border-bottom: 2px solid #667eea;
        }
        .info-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 200px;
        }
        .info-value {
            color: #333;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            text-align: right;
            flex: 1;
            margin-left: 20px;
            word-break: break-all;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .status-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #777;
            font-size: 13px;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: #764ba2;
        }
        .code-block {
            background: #f5f5f5;
            border-left: 3px solid #667eea;
            padding: 12px;
            margin-top: 10px;
            border-radius: 4px;
            font-family: 'Monaco', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 OpenSSL Configuration Info</h1>
            <p>Debug information untuk SSL Certificate Generator</p>
        </div>

        <div class="content">
            <!-- PHP & OpenSSL Info -->
            <div class="info-group">
                <div class="info-group-title">📋 PHP & OpenSSL Version</div>
                <div class="info-item">
                    <span class="info-label">PHP Version</span>
                    <span class="info-value">
                        <?= htmlspecialchars($phpVersion) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">OpenSSL Extension</span>
                    <span class="info-value">
                        <?php if ($isOpenSSLInstalled): ?>
                            <span class="badge badge-success">✓ Installed</span>
                        <?php else: ?>
                            <span class="badge badge-danger">✗ Not Found</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">OpenSSL Library</span>
                    <span class="info-value">
                        <?= htmlspecialchars($opensslVersion) ?>
                    </span>
                </div>
            </div>

            <!-- OpenSSL Configuration -->
            <div class="info-group">
                <div class="info-group-title">⚙️ OpenSSL Configuration</div>
                <div class="info-item">
                    <span class="info-label">Config Path Status</span>
                    <span class="info-value">
                        <?php if ($configPath): ?>
                            <span class="badge badge-success">✓ Found</span>
                        <?php else: ?>
                            <span class="badge badge-warning">⚠ Using Default</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Config File Location</span>
                    <span class="info-value">
                        <?php if ($configPath): ?>
                            <?= htmlspecialchars($configPath) ?>
                        <?php else: ?>
                            <em>System Default (auto-detected)</em>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($configPath): ?>
                <div class="info-item">
                    <span class="info-label">File Readable</span>
                    <span class="info-value">
                        <?php if (is_readable($configPath)): ?>
                            <span class="badge badge-success">✓ Yes</span>
                        <?php else: ?>
                            <span class="badge badge-danger">✗ No Permission</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- System Information -->
            <div class="info-group">
                <div class="info-group-title">💻 System Information</div>
                <div class="info-item">
                    <span class="info-label">Operating System</span>
                    <span class="info-value">
                        <?= htmlspecialchars($os) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">System Details</span>
                    <span class="info-value">
                        <?= htmlspecialchars($osDetail) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Temp Directory</span>
                    <span class="info-value">
                        <?= htmlspecialchars(sys_get_temp_dir()) ?>
                    </span>
                </div>
            </div>

            <!-- Crypto Extensions -->
            <div class="info-group">
                <div class="info-group-title">🔐 Cryptography Extensions</div>
                <?php if (!empty($cryptoExtensions)): ?>
                    <?php foreach ($cryptoExtensions as $ext): ?>
                    <div class="info-item">
                        <span class="info-label"><?= htmlspecialchars($ext) ?></span>
                        <span class="info-value">
                            <span class="badge badge-success">✓ Available</span>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="badge badge-danger">✗ No crypto extensions found</span>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Environment Variable -->
            <div class="info-group">
                <div class="info-group-title">🔧 Environment Variables</div>
                <div class="info-item">
                    <span class="info-label">OPENSSL_CONF</span>
                    <span class="info-value">
                        <?php 
                        $envVar = getenv('OPENSSL_CONF');
                        if ($envVar) {
                            echo htmlspecialchars($envVar);
                        } else {
                            echo '<em>(Not set)</em>';
                        }
                        ?>
                    </span>
                </div>
            </div>

            <!-- Quick Fix Guide -->
            <div class="info-group">
                <div class="info-group-title">🛠 Quick Fix Guide</div>
                <div class="info-item">
                    <span class="info-label">If Config Not Found</span>
                    <span class="info-value">
                        <div class="code-block">
// Set di awal file PHP:<br>
putenv("OPENSSL_CONF=/path/to/openssl.cnf");<br>
<br>
// atau di .htaccess (Apache):<br>
SetEnv OPENSSL_CONF /path/to/openssl.cnf
                        </div>
                    </span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <a href="index.php" class="back-link">← Kembali ke Generator</a>
            </div>
        </div>

        <div class="footer">
            <p>OpenSSL Configuration Info · SSL Certificate Generator · 2024</p>
            <p style="margin-top: 8px; font-size: 12px; color: #999;">
                Halaman ini membantu debugging konfigurasi OpenSSL di sistem Anda
            </p>
        </div>
    </div>
</body>
</html>
