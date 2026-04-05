/**
 * ADCOIN Ads - Frontend JavaScript
 * Handles Phantom wallet connection and UI interactions
 */

// Global state
let walletAddress = null;
let isEligible = false;
let hasSlot = false;
let slotData = null;
let slots = [];

// API Base URL
const API_BASE = '';

// Check for injected Phantom wallet
const isPhantomInstalled = () => {
    return window.solana && window.solana.isPhantom;
};

// Connect to Phantom wallet
async function connectWallet() {
    if (!isPhantomInstalled()) {
        showToast('Please install Phantom Wallet!', 'error');
        window.open('https://phantom.app/', '_blank');
        return;
    }

    try {
        const response = await window.solana.connect();
        walletAddress = response.publicKey.toString();
        
        // Send to backend
        await verifyWallet(walletAddress);
    } catch (err) {
        console.error('Connection error:', err);
        showToast('Failed to connect wallet', 'error');
    }
}

// Disconnect wallet
async function disconnectWallet() {
    if (window.solana) {
        await window.solana.disconnect();
    }
    walletAddress = null;
    isEligible = false;
    hasSlot = false;
    slotData = null;
    updateUI();
}

// Verify wallet with backend
async function verifyWallet(address) {
    try {
        const response = await fetch(API_BASE + '/api/connect_wallet.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ wallet_address: address })
        });
        
        const data = await response.json();
        
        if (data.success) {
            isEligible = data.eligible;
            hasSlot = data.has_slot;
            slotData = data.slot;
            
            if (isEligible) {
                showToast('Wallet connected!', 'success');
            } else {
                showToast('Not eligible - need more ADCOIN tokens', 'error');
            }
            
            // Listen for account changes
            window.solana.on('accountChanged', handleAccountChange);
        } else {
            showToast(data.message || 'Verification failed', 'error');
        }
        
        updateUI();
    } catch (err) {
        console.error('Verification error:', err);
        showToast('Failed to verify wallet', 'error');
    }
}

// Handle account changes (wallet switch)
function handleAccountChange(publicKey) {
    if (publicKey) {
        walletAddress = publicKey.toString();
        verifyWallet(walletAddress);
    } else {
        disconnectWallet();
    }
}

// Update UI based on connection state
function updateUI() {
    const connectBtn = document.getElementById('connectBtn');
    const walletPanel = document.getElementById('walletPanel');
    const statusBanner = document.getElementById('statusBanner');
    const manageBtn = document.getElementById('manageBtn');
    
    if (walletAddress) {
        connectBtn.classList.add('hidden');
        walletPanel.classList.remove('hidden');
        statusBanner.classList.remove('hidden');
        
        // Show truncated address
        document.getElementById('walletAddress').textContent = 
            walletAddress.slice(0, 6) + '...' + walletAddress.slice(-4);
        
        // Show eligibility status
        const badge = document.getElementById('eligibilityBadge');
        const text = document.getElementById('eligibilityText');
        
        if (isEligible) {
            badge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-degen-green/20 text-degen-green border border-degen-green/30';
            badge.textContent = 'ELIGIBLE';
            text.textContent = 'You can create an ad!';
            
            if (hasSlot) {
                document.getElementById('slotInfo').classList.remove('hidden');
                document.getElementById('userSlotId').textContent = '#' + slotData.id;
            } else {
                document.getElementById('slotInfo').classList.add('hidden');
            }
            
            manageBtn.classList.remove('hidden');
            manageBtn.textContent = hasSlot ? 'Edit Ad' : 'Create Ad';
        } else {
            badge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-500/20 text-red-400 border border-red-500/30';
            badge.textContent = 'NOT ELIGIBLE';
            text.textContent = 'Hold at least 10,000,000 ADCOIN to advertise';
            document.getElementById('slotInfo').classList.add('hidden');
            manageBtn.classList.add('hidden');
        }
    } else {
        connectBtn.classList.remove('hidden');
        walletPanel.classList.add('hidden');
        statusBanner.classList.add('hidden');
    }
}

// Load all slots
async function loadSlots() {
    try {
        const response = await fetch(API_BASE + '/api/get_slots.php');
        const data = await response.json();
        
        if (data.success) {
            slots = data.slots;
            renderGrid();
        }
    } catch (err) {
        console.error('Failed to load slots:', err);
    }
}

// Shuffle array (Fisher-Yates)
function shuffleArray(array) {
    const arr = [...array];
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

// Render the banner grid
function renderGrid() {
    const grid = document.getElementById('adGrid');
    const occupiedCount = document.getElementById('occupiedCount');
    
    // Shuffle slots for display
    const shuffled = shuffleArray(slots);
    
    // Count occupied
    const occupied = slots.filter(s => s.owner_wallet).length;
    occupiedCount.textContent = occupied;
    
    grid.innerHTML = shuffled.map(slot => {
        if (!slot.owner_wallet || !slot.link_url) {
            return `
                <div class="aspect-video rounded-lg slot-empty border border-degen-border/50 flex items-center justify-center">
                    <span class="text-gray-600 text-xs">#${slot.id}</span>
                </div>
            `;
        }
        
        const content = slot.image_url 
            ? `<img src="${slot.image_url}" alt="Ad" class="w-full h-full object-cover rounded-lg">`
            : `<div class="w-full h-full flex items-center justify-center p-2 text-center">
                <span class="text-sm text-white font-medium">${escapeHtml(slot.text || 'AD')}</span>
               </div>`;
        
        return `
            <a href="${escapeHtml(slot.link_url)}" target="_blank" rel="noopener noreferrer" 
                class="aspect-video rounded-lg overflow-hidden border border-degen-border hover:border-degen-orange transition-all slot-link relative">
                ${content}
            </a>
        `;
    }).join('');
}

// Show manage modal
function showManageModal() {
    const modal = document.getElementById('adModal');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const form = document.getElementById('adForm');
    
    if (hasSlot) {
        title.textContent = 'Edit Your Ad';
        submitBtn.textContent = 'Update Ad';
        deleteBtn.classList.remove('hidden');
        
        // Pre-fill form
        form.link_url.value = slotData.link_url || '';
        form.text.value = slotData.text || '';
    } else {
        title.textContent = 'Create Your Ad';
        submitBtn.textContent = 'Create Ad';
        deleteBtn.classList.add('hidden');
        
        // Reset form
        form.reset();
    }
    
    modal.classList.remove('hidden');
}

// Close modal
function closeModal() {
    document.getElementById('adModal').classList.add('hidden');
}

// Handle ad form submit
async function handleAdSubmit(e) {
    e.preventDefault();
    
    if (!walletAddress) {
        showToast('Please connect wallet first', 'error');
        return;
    }
    
    const form = e.target;
    const formData = new FormData();
    formData.append('wallet_address', walletAddress);
    formData.append('link_url', form.link_url.value);
    formData.append('text', form.text.value);
    
    if (form.image.files[0]) {
        formData.append('image', form.image.files[0]);
    }
    
    const endpoint = hasSlot ? '/api/update_ad.php' : '/api/create_ad.php';
    const submitBtn = document.getElementById('submitBtn');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    try {
        const response = await fetch(API_BASE + endpoint, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            hasSlot = true;
            slotData = data;
            closeModal();
            loadSlots();
            verifyWallet(walletAddress); // Refresh state
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Failed to save ad', 'error');
    }
    
    submitBtn.disabled = false;
    submitBtn.textContent = hasSlot ? 'Update Ad' : 'Create Ad';
}

// Delete ad
async function deleteAd() {
    if (!walletAddress || !hasSlot) return;
    
    if (!confirm('Are you sure you want to delete your ad? This cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(API_BASE + '/api/delete_ad.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ wallet_address: walletAddress })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Ad deleted', 'success');
            hasSlot = false;
            slotData = null;
            closeModal();
            loadSlots();
            updateUI();
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Failed to delete ad', 'error');
    }
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    toast.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg z-50 border ${
        type === 'success' ? 'bg-degen-green/20 border-degen-green text-degen-green' :
        type === 'error' ? 'bg-red-500/20 border-red-500 text-red-400' :
        'bg-degen-card border-degen-border text-white'
    }`;
    
    toast.classList.remove('hidden');
    
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadSlots();
    
    // Check if already connected
    if (window.solana && window.solana.isConnected && window.solana.publicKey) {
        walletAddress = window.solana.publicKey.toString();
        verifyWallet(walletAddress);
    }
});

// Listen for Phantom ready
window.addEventListener('load', () => {
    if (window.solana) {
        window.solana.on('connect', () => {
            if (!walletAddress && window.solana.publicKey) {
                walletAddress = window.solana.publicKey.toString();
                verifyWallet(walletAddress);
            }
        });
    }
});