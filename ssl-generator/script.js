/**
 * ============================================================
 * SSL Certificate Generator - JavaScript Handler
 * ============================================================
 * Menangani:
 * - Tab switching untuk Private Key dan Certificate
 * - Copy to clipboard functionality
 * - Form loading state
 * - Toast notifications
 * ============================================================
 */

// ─── TAB SWITCHING ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    
    // Ambil semua tab buttons
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    // Setup event listeners untuk setiap tab button
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Hapus active class dari semua buttons
            tabButtons.forEach(btn => {
                btn.classList.remove('tab-btn--active');
                btn.setAttribute('aria-selected', 'false');
            });
            
            // Hapus active class dan hidden dari semua panels
            tabPanels.forEach(panel => {
                panel.classList.remove('tab-panel--active');
                panel.setAttribute('hidden', '');
            });
            
            // Tambah active class ke button yang di-klik
            this.classList.add('tab-btn--active');
            this.setAttribute('aria-selected', 'true');
            
            // Tampilkan panel yang sesuai
            const targetPanel = document.getElementById('panel-' + targetTab);
            if (targetPanel) {
                targetPanel.classList.add('tab-panel--active');
                targetPanel.removeAttribute('hidden');
            }
        });
    });
});

// ─── COPY TO CLIPBOARD ──────────────────────────────────────
function copyContent(elementId, button) {
    const textarea = document.getElementById(elementId);
    
    if (!textarea) return;
    
    // Select text
    textarea.select();
    
    try {
        // Copy to clipboard
        document.execCommand('copy');
        
        // Show success feedback
        showToast('Copied to clipboard!');
        
        // Change button appearance
        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>Copied!';
        button.style.borderColor = 'rgba(34, 197, 94, 0.3)';
        button.style.color = '#22c55e';
        button.style.background = 'var(--green-dim)';
        
        // Restore button after 2 seconds
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.style.borderColor = '';
            button.style.color = '';
            button.style.background = '';
        }, 2000);
    } catch (err) {
        showToast('Failed to copy to clipboard');
    }
}

// ─── TOAST NOTIFICATION ─────────────────────────────────────
function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    
    if (toast && toastMsg) {
        toastMsg.textContent = message;
        toast.classList.add('show');
        
        // Sembunyikan toast setelah 3 detik
        setTimeout(function() {
            toast.classList.remove('show');
        }, 3000);
    }
}

// ─── FORM LOADING STATE ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const sslForm = document.getElementById('sslForm');
    const generateBtn = document.getElementById('generateBtn');
    
    if (sslForm && generateBtn) {
        sslForm.addEventListener('submit', function(e) {
            // Tambah loading class ke button
            generateBtn.classList.add('loading');
            generateBtn.disabled = true;
        });
    }
});

// ─── KEYBOARD SHORTCUTS ─────────────────────────────────────
document.addEventListener('keydown', function(event) {
    // Ctrl/Cmd + K untuk toggle ke Certificate tab
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        const crtTab = document.getElementById('tab-crt');
        if (crtTab) {
            crtTab.click();
        }
    }
    
    // Ctrl/Cmd + J untuk kembali ke Private Key tab
    if ((event.ctrlKey || event.metaKey) && event.key === 'j') {
        event.preventDefault();
        const keyTab = document.getElementById('tab-key');
        if (keyTab) {
            keyTab.click();
        }
    }
});
