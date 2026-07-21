<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination;

    public $search = '';

    public function getProductsProperty()
    {
        return Product::where('admin_id', Auth::id())
            ->where(function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->withSum(['stockMovements as total_added' => function($q) {
                $q->where('type', 'in');
            }], 'qty')
            ->withSum('transactionItems as total_sold', 'qty')
            ->orderBy('name')
            ->paginate(15);
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Stok Produk</h1>
                <p class="text-zinc-500 dark:text-zinc-400">
                    Ringkasan kalkulasi stok produk dari stok awal, penambahan, hingga penjualan.
                </p>
            </div>
            
            <div class="w-64 max-w-full">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU..." icon="magnifying-glass" />
            </div>
        </div>

        <flux:card class="p-0">

        <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                        <tr>
                            <th class="px-6 py-3">SKU</th>
                            <th class="px-6 py-3">Nama Produk</th>
                            <th class="px-6 py-3 text-center">Stok Awal</th>
                            <th class="px-6 py-3 text-center">Ditambahkan</th>
                            <th class="px-6 py-3 text-center">Terjual</th>
                            <th class="px-6 py-3 text-center">Sisa Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->products as $product)
                            @php
                                $added = $product->total_added ?? 0;
                                $sold = $product->total_sold ?? 0;
                                $initial = $product->stock - $added + $sold;
                            @endphp
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="px-6 py-4 font-mono text-xs text-zinc-500">{{ $product->sku }}</td>
                                <td class="px-6 py-4 font-medium">{{ $product->name }}</td>
                                <td class="px-6 py-4 text-center text-zinc-500">{{ number_format($initial, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center text-green-600 font-medium">+{{ number_format($added, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center text-red-600 font-medium">-{{ number_format($sold, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <flux:badge color="{{ $product->stock <= $product->min_stock_alert ? 'red' : 'green' }}">
                                        {{ number_format($product->stock, 0, ',', '.') }}
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-zinc-500">
                                    Tidak ada data stok yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
        
        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
            {{ $this->products->links() }}
        </div>
    </flux:card>
    </div>
</div>
