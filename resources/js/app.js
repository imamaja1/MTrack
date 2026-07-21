document.addEventListener('DOMContentLoaded', () => {
    // Listener super-responsif: Langsung tampilkan saat ada link menu yang diklik
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[wire\\:navigate], a[wire\\:navigate\\.hover]');
        if (link && link.href && link.href !== window.location.href) {
            const overlay = document.getElementById('page-loading-overlay');
            if (overlay) overlay.style.display = 'flex';
        }
    });

    // Fallback: Gunakan event bawaan Livewire sebagai perlindungan ganda
    document.addEventListener('livewire:navigating', () => {
        const overlay = document.getElementById('page-loading-overlay');
        if (overlay) overlay.style.display = 'flex';
    });

    document.addEventListener('livewire:navigated', () => {
        const overlay = document.getElementById('page-loading-overlay');
        if (overlay) overlay.style.display = 'none';
    });
});
