<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'MTrack') }} - Aplikasi Kasir UMKM</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-[#0A0A0A] text-zinc-900 dark:text-zinc-100 font-sans antialiased selection:bg-orange-500 selection:text-white transition-colors duration-300">

    <!-- Navigation -->
    <nav class="fixed w-full z-50 top-0 border-b border-zinc-200 dark:border-white/10 bg-white/80 dark:bg-[#0A0A0A]/80 backdrop-blur-md transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <svg class="w-8 h-8 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">MTrack</span>
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white transition">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white transition">Masuk</a>
                        <a href="{{ route('register') }}" class="text-sm font-medium bg-orange-500 hover:bg-orange-400 text-white px-4 py-2 rounded-lg transition shadow-[0_0_15px_rgba(249,115,22,0.3)] hover:shadow-[0_0_25px_rgba(249,115,22,0.5)]">Coba Gratis</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Glow Effects -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-orange-500/10 dark:bg-orange-500/20 rounded-full blur-[120px] pointer-events-none transition-colors duration-300"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <h1 class="text-5xl lg:text-7xl font-bold tracking-tighter text-transparent bg-clip-text bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-500 dark:from-white dark:via-white dark:to-zinc-500 mb-6 transition-colors duration-300">
                Kelola Kasir & Stok<br/>Bebas Pusing, Toko Makin Untung
            </h1>
            
            <p class="mt-4 text-lg lg:text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto mb-10 leading-relaxed transition-colors duration-300">
                Tinggalkan cara manual! MTrack adalah aplikasi kasir pintar yang catat penjualan otomatis, pantau sisa stok, dan amankan data toko Anda dari kecurangan karyawan.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-8 py-3 rounded-lg bg-orange-500 hover:bg-orange-400 text-white font-medium transition shadow-[0_0_20px_rgba(249,115,22,0.3)] dark:shadow-[0_0_20px_rgba(249,115,22,0.4)] flex items-center justify-center gap-2">
                        Masuk ke Dashboard
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-8 py-3 rounded-lg bg-zinc-900 text-white dark:bg-white dark:hover:bg-zinc-200 dark:text-black font-medium transition flex items-center justify-center gap-2">
                        Coba Sekarang Gratis
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-24 border-t border-zinc-200 dark:border-white/5 bg-zinc-50 dark:bg-[#0F0F0F] transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-4xl transition-colors duration-300">Semua Fitur Untuk Menumbuhkan Bisnis Anda</h2>
                <p class="mt-4 text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto transition-colors duration-300">Dirancang super praktis agar Anda bisa fokus melayani pelanggan, biarkan sistem kami yang mencatat.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Feature 1 -->
                <div class="p-8 rounded-2xl bg-white dark:bg-[#141414] border border-zinc-200 dark:border-white/5 hover:border-orange-500/50 dark:hover:border-orange-500/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-orange-100 dark:bg-orange-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3 transition-colors duration-300">Keamanan Ekstra Anti-Maling</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">Sistem pintar kami memantau gerak-gerik mencurigakan di sistem Anda. Blokir akses perangkat asing secara instan agar data omset tetap rahasia.</p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 rounded-2xl bg-white dark:bg-[#141414] border border-zinc-200 dark:border-white/5 hover:border-orange-500/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3 transition-colors duration-300">Cabang Toko Terpusat</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">Satu bos, banyak toko? Tidak masalah! Setiap cabang toko akan memiliki pembukuan dan stoknya sendiri yang terpisah secara rapi tanpa tertukar.</p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 rounded-2xl bg-white dark:bg-[#141414] border border-zinc-200 dark:border-white/5 hover:border-orange-500/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3 transition-colors duration-300">Kasir Anti-Lelet</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">Layani antrean pelanggan tanpa hambatan. Meski dua kasir bertransaksi bersamaan, perhitungan stok dijamin 100% tepat tanpa takut sisa stok jadi minus!</p>
                </div>

                <!-- Feature 4 -->
                <div class="p-8 rounded-2xl bg-white dark:bg-[#141414] border border-zinc-200 dark:border-white/5 hover:border-orange-500/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3 transition-colors duration-300">Cetak Struk Langsung</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">Tingkatkan kesan profesional toko Anda. Sistem kami langsung kompatibel dengan printer thermal kasir ukuran 58mm & 80mm di pasaran.</p>
                </div>

                <!-- Feature 5 -->
                <div class="p-8 rounded-2xl bg-white dark:bg-[#141414] border border-zinc-200 dark:border-white/5 hover:border-orange-500/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3 transition-colors duration-300">Bisa Dikontrol Lewat HP</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">Sistem siap dihubungkan langsung dengan aplikasi mobile. Anda bisa memantau laporan untung rugi dan sisa stok sambil bersantai dari layar handphone.</p>
                </div>

                <!-- Feature 6 -->
                <div class="p-8 rounded-2xl bg-white dark:bg-[#141414] border border-zinc-200 dark:border-white/5 hover:border-orange-500/50 transition duration-300 group">
                    <div class="w-12 h-12 rounded-lg bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center mb-6 group-hover:scale-110 transition">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3 transition-colors duration-300">Pantau Stok Otomatis</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">Dapatkan peringatan seketika saat ada barang yang mulai habis. Tidak ada lagi kerugian karena telat belanja barang terlaris di toko Anda!</p>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-zinc-200 dark:border-white/10 bg-white dark:bg-[#0A0A0A] text-center transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-zinc-500 text-sm">
                &copy; {{ date('Y') }} MTrack System. Powered by Laravel 13 & Livewire.
            </p>
        </div>
    </footer>

</body>
</html>
