<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SMBControl</title>
    <link rel="icon" type="image/png" href="/favicon.png">
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
                        err: '#d16b5f',
                    },
                    fontFamily: {
                        brand: ['Syne', 'sans-serif'],
                        ui: ['"DM Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'grid-move': 'gridMove 20s linear infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
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
    <link href="/assets/css/fonts.css" rel="stylesheet">
    
    <style>
        body { 
            background-color: #0c0f12;
            color: #ebe6dc;
            overflow: hidden;
        }
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
            .grid-bg { animation: none; }
            .animate-fade-in { animation: none; opacity: 1; transform: none; }
        }
        input {
            background-color: #0c0f12 !important;
        }
    </style>
</head>
<body class="font-ui h-screen flex flex-col items-center justify-center">

    <div class="grid-bg"></div>

    <div class="w-full max-w-sm animate-fade-in z-10 flex flex-col gap-8">
        
        <div class="text-center">
            <h1 class="text-4xl font-brand font-bold text-acc tracking-wider mb-2">SMBControl</h1>
            <p class="text-xs font-mono text-muted uppercase tracking-widest">System Authentication</p>
        </div>
        
        <div class="bg-bg1 border border-bg0 p-8 rounded-sm shadow-2xl">
            <?php if (!empty($error)): ?>
                <div class="bg-err/10 border-l-2 border-err text-err px-4 py-3 text-sm font-mono mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="flex flex-col gap-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-mono text-muted uppercase tracking-wider">Username</label>
                    <input type="text" name="username" required autofocus class="w-full px-4 py-3 font-mono text-sm text-fg border border-bg0 rounded-sm focus:outline-none focus:border-acc transition-colors">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-mono text-muted uppercase tracking-wider">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 font-mono text-sm text-fg border border-bg0 rounded-sm focus:outline-none focus:border-acc transition-colors">
                </div>
                
                <button type="submit" class="w-full bg-acc text-bg0 font-brand font-bold py-3 mt-2 rounded-sm hover:bg-acc/90 transition-colors uppercase tracking-widest text-sm shadow-[0_0_15px_rgba(212,163,92,0.15)]">
                    Access System
                </button>
            </form>
        </div>
        
    </div>

</body>
</html>
