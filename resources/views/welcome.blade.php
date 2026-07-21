<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MTrack') }} - Aplikasi Kasir UMKM</title>

    <script>
        (function () {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-base-100 text-base-content font-sans antialiased selection:bg-primary selection:text-primary-content transition-colors duration-300">

    <nav class="fixed w-full z-50 top-0 border-b border-base-300 bg-base-100/80 backdrop-blur-md transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <svg class="w-8 h-8 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span class="text-xl font-bold tracking-tight text-base-content">MTrack</span>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleTheme()" data-theme-toggle class="btn btn-ghost btn-square btn-sm" aria-label="Toggle theme">
                        <x-icon name="o-sun" data-icon="sun" class="w-5 h-5" />
                        <x-icon name="o-moon" data-icon="moon" class="w-5 h-5" style="display:none" />
                    </button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Masuk</a>
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm shadow-[0_0_15px_rgba(249,115,22,0.3)]">Coba Gratis</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-primary/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-5xl lg:text-7xl font-bold tracking-tighter text-base-content mb-6">
                Kelola Kasir & Stok<br/>Bebas Pusing, Toko Makin Untung
            </h1>
            <p class="mt-4 text-lg lg:text-xl text-base-content/60 max-w-2xl mx-auto mb-10 leading-relaxed">
                Tinggalkan cara manual! MTrack adalah aplikasi kasir pintar yang catat penjualan otomatis, pantau sisa stok, dan amankan data toko Anda dari kecurangan karyawan.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg shadow-[0_0_20px_rgba(249,115,22,0.3)]">
                        Masuk ke Dashboard
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-neutral btn-lg">
                        Coba Sekarang Gratis
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <section class="py-24 border-t border-base-300 bg-base-200 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-base-content sm:text-4xl">Semua Fitur Untuk Menumbuhkan Bisnis Anda</h2>
                <p class="mt-4 text-base-content/60 max-w-2xl mx-auto">Dirancang super praktis agar Anda bisa fokus melayani pelanggan, biarkan sistem kami yang mencatat.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="p-8 rounded-2xl bg-base-100 border border-base-300 hover:border-primary/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <x-icon name="o-lock-closed" class="w-6 h-6 text-primary" />
                    </div>
                    <h3 class="text-xl font-semibold text-base-content mb-3">Keamanan Ekstra Anti-Maling</h3>
                    <p class="text-base-content/60 leading-relaxed">Sistem pintar kami memantau gerak-gerik mencurigakan di sistem Anda. Blokir akses perangkat asing secara instan agar data omset tetap rahasia.</p>
                </div>
                <div class="p-8 rounded-2xl bg-base-100 border border-base-300 hover:border-primary/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <x-icon name="o-building-office-2" class="w-6 h-6 text-primary" />
                    </div>
                    <h3 class="text-xl font-semibold text-base-content mb-3">Cabang Toko Terpusat</h3>
                    <p class="text-base-content/60 leading-relaxed">Satu bos, banyak toko? Tidak masalah! Setiap cabang toko akan memiliki pembukuan dan stoknya sendiri yang terpisah secara rapi tanpa tertukar.</p>
                </div>
                <div class="p-8 rounded-2xl bg-base-100 border border-base-300 hover:border-primary/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <x-icon name="o-shopping-cart" class="w-6 h-6 text-primary" />
                    </div>
                    <h3 class="text-xl font-semibold text-base-content mb-3">Kasir Anti-Lelet</h3>
                    <p class="text-base-content/60 leading-relaxed">Layani antrean pelanggan tanpa hambatan. Meski dua kasir bertransaksi bersamaan, perhitungan stok dijamin 100% tepat tanpa takut sisa stok jadi minus!</p>
                </div>
                <div class="p-8 rounded-2xl bg-base-100 border border-base-300 hover:border-primary/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <x-icon name="o-printer" class="w-6 h-6 text-primary" />
                    </div>
                    <h3 class="text-xl font-semibold text-base-content mb-3">Cetak Struk Langsung</h3>
                    <p class="text-base-content/60 leading-relaxed">Tingkatkan kesan profesional toko Anda. Sistem kami langsung kompatibel dengan printer thermal kasir ukuran 58mm & 80mm di pasaran.</p>
                </div>
                <div class="p-8 rounded-2xl bg-base-100 border border-base-300 hover:border-primary/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <x-icon name="o-device-phone-mobile" class="w-6 h-6 text-primary" />
                    </div>
                    <h3 class="text-xl font-semibold text-base-content mb-3">Bisa Dikontrol Lewat HP</h3>
                    <p class="text-base-content/60 leading-relaxed">Sistem siap dihubungkan langsung dengan aplikasi mobile. Anda bisa memantau laporan untung rugi dan sisa stok sambil bersantai dari layar handphone.</p>
                </div>
                <div class="p-8 rounded-2xl bg-base-100 border border-base-300 hover:border-primary/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <x-icon name="o-bolt" class="w-6 h-6 text-primary" />
                    </div>
                    <h3 class="text-xl font-semibold text-base-content mb-3">Pantau Stok Otomatis</h3>
                    <p class="text-base-content/60 leading-relaxed">Dapatkan peringatan seketika saat ada barang yang mulai habis. Tidak ada lagi kerugian karena telat belanja barang terlaris di toko Anda!</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-12 border-t border-base-300 bg-base-100 text-center transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-base-content/50 text-sm">
                &copy; {{ date('Y') }} MTrack System. Powered by Laravel 13 & Livewire.
            </p>
        </div>
    </footer>

</body>
</html>
