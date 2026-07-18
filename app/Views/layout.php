<!DOCTYPE html>
<html lang="<?= htmlspecialchars(smb_lang()) ?>" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>smbcontrol</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <!-- TailwindCSS Local -->
    <script src="/assets/js/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bg0: '#0c0f12',
                        bg1: '#141a1f',
                        fg: '#ebe6dc',
                        muted: '#8a9188',
                        acc: '#d4a35c',
                        acc2: '#3d8f7a',
                        ok: '#6fae7a',
                        warn: '#c9844a',
                        err: '#d16b5f',
                    },
                    fontFamily: {
                        brand: ['Syne', 'sans-serif'],
                        ui: ['"DM Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out forwards',
                        'grid-move': 'gridMove 20s linear infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        gridMove: {
                            '0%': { transform: 'translateY(0)' },
                            '100%': { transform: 'translateY(40px)' },
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts Local -->
    <link href="/assets/css/fonts.css" rel="stylesheet">
    
    <style>
        :root {
            --bg0: #0c0f12;
            --bg1: #141a1f;
            --fg: #ebe6dc;
            --muted: #8a9188;
            --acc: #d4a35c;
        }
        
        body { 
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg0);
            color: var(--fg);
            overflow-x: hidden;
        }

        /* Animated Grid Background */
        .grid-bg {
            position: fixed;
            top: -40px;
            left: 0;
            width: 100vw;
            height: calc(100vh + 40px);
            background-image: 
                linear-gradient(to right, rgba(138, 145, 136, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(138, 145, 136, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .grid-bg {
                animation: none;
            }
            .animate-fade-in {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }

        /* Toasts */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            padding: 12px 16px;
            background-color: var(--bg1);
            border-left: 4px solid var(--acc);
            color: var(--fg);
            font-size: 0.875rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            animation: fadeIn 0.3s ease-out forwards;
        }
        .toast.error { border-color: #d16b5f; }
        .toast.success { border-color: #6fae7a; }
        
        /* Utility */
        .font-brand { font-family: 'Syne', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Inputs & Selects overriding default styles */
        input, select, textarea {
            background-color: var(--bg0);
            border: 1px solid var(--bg1);
            color: var(--fg);
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--acc);
            box-shadow: 0 0 0 1px var(--acc);
        }

        .check-square,
        .permission-choice {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            margin: 0;
            display: inline-grid;
            place-content: center;
            border: 1px solid rgba(138, 145, 136, 0.65);
            border-radius: 2px;
            background: #0c0f12;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }
        .check-square::before,
        .permission-choice::before {
            content: "";
            width: 8px;
            height: 5px;
            border-left: 2px solid var(--acc);
            border-bottom: 2px solid var(--acc);
            transform: rotate(-45deg) scale(0);
            transform-origin: center;
            transition: transform 0.12s ease;
        }
        .check-square:checked,
        .permission-choice:checked {
            border-color: var(--acc);
            background: rgba(212, 163, 92, 0.12);
            box-shadow: 0 0 0 1px rgba(212, 163, 92, 0.2);
        }
        .check-square:checked::before,
        .permission-choice:checked::before {
            transform: rotate(-45deg) scale(1);
        }
        .check-square:focus-visible,
        .permission-choice:focus-visible {
            outline: 2px solid var(--acc);
            outline-offset: 2px;
        }
        .check-square:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg0);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--bg1);
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--muted);
        }
        
        /* Modal & Tabs */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: var(--bg1);
            border: 1px solid var(--bg0);
            border-radius: 4px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            transition: transform 0.2s;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        .modal-overlay.active .modal-content {
            transform: scale(1);
        }
        
        .tab-btn {
            padding: 10px 16px;
            border-bottom: 2px solid transparent;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .tab-btn:hover { color: var(--fg); }
        .tab-btn.active {
            color: var(--acc);
            border-bottom-color: var(--acc);
            background: rgba(255,255,255,0.02);
        }
        .tab-pane {
            display: none;
            padding: 20px;
            flex: 1;
            overflow-y: auto;
        }
        .tab-pane.active {
            display: block;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col">
    
    <div class="grid-bg"></div>
    
    <?php if(isset($_SESSION['user_id'])): ?>
    <!-- Top Navbar -->
    <header class="sticky top-0 z-40 bg-bg0/90 backdrop-blur-sm border-b border-bg1">
        <div class="container mx-auto px-6 h-14 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="/dashboard" class="text-acc font-brand font-bold text-xl tracking-wide">SMBControl</a>
                
                <nav class="hidden md:flex gap-6 text-sm font-medium">
                    <?php 
                        $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                        $links = [
                            '/samba/users' => smb_t('SMB Users', 'Usuários SMB'),
                            '/samba/shares' => smb_t('Shares', 'Compartilhamentos'),
                            '/samba/shares-config' => smb_t('Share Parameters', 'Parâmetros dos Shares'),
                            '/reports' => smb_t('Audit', 'Auditoria'),
                            '/disks' => smb_t('Storage', 'Armazenamento'),
                            '/samba/conf' => smb_t('Global Config', 'Configuração Global'),
                            '/profile' => smb_t('Profile', 'Perfil')
                        ];
                        foreach($links as $path => $label):
                            $active = ($currentUri === $path) ? 'text-acc border-b-2 border-acc' : 'text-muted hover:text-fg';
                    ?>
                        <a href="<?= $path ?>" class="h-14 flex items-center capitalize transition-colors <?= $active ?>">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="<?= htmlspecialchars(smb_lang_toggle_url()) ?>" class="h-9 px-2 flex items-center gap-2 text-sm font-medium text-muted hover:text-acc transition-colors" title="<?= htmlspecialchars(smb_t('Switch language', 'Mudar idioma')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20M12 2a15.3 15.3 0 0 0 0 20"></path>
                    </svg>
                    <span class="font-mono text-xs"><?= htmlspecialchars(smb_lang_label()) ?></span>
                </a>
                <a href="/logout" class="text-sm font-medium text-muted hover:text-err transition-colors"><?= smb_t('logout', 'sair') ?></a>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-6 py-8 animate-fade-in">
        
        <!-- Toast Notifications Placeholder -->
        <div class="toast-container" id="toast-container">
            <?php if(isset($_SESSION['message'])): ?>
                <div class="toast success">
                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <?php if(isset($_SESSION['error'])): ?>
                <div class="toast error">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- View Content -->
        <?php require $contentView; ?>
        
    </main>
    
    <script>
        // Auto-hide toasts after 5 seconds
        setTimeout(() => {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(t => {
                t.style.opacity = '0';
                t.style.transition = 'opacity 0.5s ease';
                setTimeout(() => t.remove(), 500);
            });
        }, 5000);

        // Global Modal functions
        function openModal(id) {
            const modal = document.getElementById(id);
            if(modal) modal.classList.add('active');
        }
        function closeModal(id) {
            const modal = document.getElementById(id);
            if(modal) modal.classList.remove('active');
        }
        
        // Global Tabs function
        function switchTab(modalId, tabId) {
            const modal = document.getElementById(modalId);
            if(!modal) return;
            
            // Deactivate all
            modal.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            modal.querySelectorAll('.tab-pane').forEach(p => {
                p.classList.remove('active', 'flex');
                p.classList.add('hidden');
            });
            
            // Activate selected
            const btn = modal.querySelector(`[data-tab="${tabId}"]`);
            const pane = modal.querySelector(`#${tabId}`);
            
            if(btn) btn.classList.add('active');
            if(pane) {
                pane.classList.add('active', 'flex');
                pane.classList.remove('hidden');
            }
        }
        
        // Close modal on escape key or outside click
        document.addEventListener('keydown', (e) => {
            if(e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
            }
        });
        document.addEventListener('click', (e) => {
            if(e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>
