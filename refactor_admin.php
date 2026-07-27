<?php

$dir = __DIR__.'/app/Livewire/Admin/';
$files = glob($dir.'*.php');

$titles = [
    'Coupons.php' => 'Manajemen Kupon',
    'Expenses.php' => 'Pengeluaran Toko',
    'FinancialReport.php' => 'Laporan Keuangan',
    'MemberConfig.php' => 'Config Member',
    'OperatorManagement.php' => 'Operator Management',
    'ProductManagement.php' => 'Product Management',
    'Stocks.php' => 'Laporan Stok Produk',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $basename = basename($file);
    $title = $titles[$basename] ?? 'Admin Panel';

    // Add use statements if not exist
    if (strpos($content, "use Livewire\Attributes\Layout;") === false) {
        $content = preg_replace('/(use Livewire\\\\Component;)/', "$1\nuse Livewire\Attributes\Layout;\nuse Livewire\Attributes\Title;", $content);
    }

    // Add attributes if not exist
    if (strpos($content, '#[Layout') === false) {
        $attributes = "#[Layout('layouts.app')]\n#[Title('$title')]\n";
        $content = preg_replace('/(class [A-Za-z0-9_]+ extends Component)/', $attributes.'$1', $content);
    }

    file_put_contents($file, $content);
    echo "Updated: $basename\n";
}
