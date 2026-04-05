<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADCOIN Ads - Crypto-Native Advertising</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        degen: {
                            orange: '#FF6B00',
                            dark: '#0a0a0f',
                            card: '#12121a',
                            border: '#1f1f2e',
                            green: '#00FF88',
                            red: '#ff4444'
                        }
                    },
                    fontFamily: {
                        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: #0a0a0f;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255, 107, 0, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 136, 0.03) 0%, transparent 50%);
        }
        .glow {
            box-shadow: 0 0 20px rgba(255, 107, 0, 0.3);
        }
        .glow-green {
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        .slot-empty {
            background: repeating-linear-gradient(
                45deg,
                #12121a,
                #12121a 10px,
                #1a1a24 10px,
                #1a1a24 20px
            );
        }
        .slot-link:hover {
            transform: scale(1.02);
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 10px rgba(255, 107, 0, 0.2); }
            50% { box-shadow: 0 0 25px rgba(255, 107, 0, 0.5); }
        }
        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="min-h-screen text-white">
    <!-- Header -->
    <header class="border-b border-degen-border bg-degen-card/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-degen-orange flex items-center justify-center text-xl font-bold">
                    🪙
                </div>
                <h1 class="text-2xl font-bold tracking-tight">
                    <span class="text-degen-orange">ADCOIN</span>
                    <span class="text-white">Ads</span>
                </h1>
            </div>
            <button id="connectBtn" onclick="connectWallet()" 
                class="bg-degen-orange hover:bg-orange-600 text-white font-semibold py-2 px-6 rounded-lg transition-all hover:glow">
                Connect Wallet
            </button>
            <div id="walletPanel" class="hidden flex items-center gap-4">
                <div class="text-right">
                    <div id="walletAddress" class="font-mono text-sm text-gray-400"></div>
                    <div id="tokenBalance" class="text-degen-green text-sm"></div>
                </div>
                <button id="manageBtn" onclick="showManageModal()" 
                    class="bg-degen-border hover:bg-degen-orange/20 border border-degen-border hover:border-degen-orange text-white py-2 px-4 rounded-lg transition-all">
                    Manage Ad
                </button>
                <button onclick="disconnectWallet()" class="text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Status Banner -->
    <div id="statusBanner" class="hidden bg-degen-card border-b border-degen-border">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div id="eligibilityBadge" class="px-3 py-1 rounded-full text-sm font-medium"></div>
                <span id="eligibilityText" class="text-gray-300"></span>
            </div>
            <div id="slotInfo" class="text-sm text-gray-400 hidden">
                Your Slot: <span id="userSlotId" class="text-degen-orange font-mono"></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Info Section -->
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold mb-2">Token-Gated Ad Platform</h2>
            <p class="text-gray-400 max-w-2xl mx-auto">
                Hold at least <span class="text-degen-orange font-bold">10,000,000 ADCOIN</span> tokens to claim your advertising slot.
                Slots are randomly shuffled on each visit.
            </p>
        </div>

        <!-- Banner Grid -->
        <div id="adGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            <!-- Slots will be rendered here -->
        </div>

        <!-- Stats -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <span id="occupiedCount">0</span> / 100 slots occupied
        </div>
    </main>

    <!-- Ad Management Modal -->
    <div id="adModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-degen-card border border-degen-border rounded-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-xl font-bold">Create Your Ad</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="adForm" onsubmit="handleAdSubmit(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Image (optional)</label>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                            class="w-full bg-degen-dark border border-degen-border rounded-lg px-4 py-2 text-white file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-degen-orange file:text-white file:cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Text (optional)</label>
                        <input type="text" name="text" maxlength="255" placeholder="Your ad text..."
                            class="w-full bg-degen-dark border border-degen-border rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-degen-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">Link URL *</label>
                        <input type="url" name="link_url" required placeholder="https://..."
                            class="w-full bg-degen-dark border border-degen-border rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-degen-orange focus:outline-none">
                    </div>
                </div>
                
                <div class="flex gap-3 mt-6">
                    <button type="submit" id="submitBtn" 
                        class="flex-1 bg-degen-orange hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-all hover:glow">
                        Create Ad
                    </button>
                    <button type="button" id="deleteBtn" onclick="deleteAd()" 
                        class="hidden bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-all">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-degen-card border border-degen-border rounded-lg px-4 py-3 hidden z-50">
        <p id="toastMessage"></p>
    </div>

    <script src="js/app.js"></script>
</body>
</html>