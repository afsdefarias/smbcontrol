<!DOCTYPE html>
<html lang="en" class="dark">
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
                            '/samba/users' => 'Users & Groups',
                            '/samba/shares' => 'Shares',
                            '/samba/shares-config' => 'Share Settings',
                            '/reports' => 'Audit Reports',
                            '/disks' => 'Storage',
                            '/samba/conf' => 'Global Settings',
                            '/profile' => 'Profile'
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
            
            <a href="/logout" class="text-sm font-medium text-muted hover:text-err transition-colors">logout</a>
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
    </script>
</body>
</html>
