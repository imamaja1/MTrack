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
                <h1 class="text-2xl font-bold text-base-content">Laporan Stok Produk</h1>
                <p class="text-sm text-base-content/70">
                    Ringkasan kalkulasi stok produk dari stok awal, penambahan, hingga penjualan.
                </p>
            </div>
            
            <div class="w-64 max-w-full">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU..." icon="o-magnifying-glass" />
            </div>
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th class="text-center">Stok Awal</th>
                            <th class="text-center">Ditambahkan</th>
                            <th class="text-center">Terjual</th>
                            <th class="text-center">Sisa Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->products as $product)
                            @php
                                $added = $product->total_added ?? 0;
                                $sold = $product->total_sold ?? 0;
                                $initial = $product->stock - $added + $sold;
                            @endphp
                            <tr>
                                <td class="font-mono text-xs text-base-content/60">{{ $product->sku }}</td>
                                <td class="font-medium text-base-content">{{ $product->name }}</td>
                                <td class="text-center text-base-content/70">{{ number_format($initial, 0, ',', '.') }}</td>
                                <td class="text-center text-success font-medium">+{{ number_format($added, 0, ',', '.') }}</td>
                                <td class="text-center text-error font-medium">-{{ number_format($sold, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <x-badge value="{{ number_format($product->stock, 0, ',', '.') }}" class="{{ $product->stock <= $product->min_stock_alert ? 'badge-error' : 'badge-success' }}" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-base-content/60">
                                    Tidak ada data stok yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-base-200">
                {{ $this->products->links() }}
            </div>
        </x-card>
    </div>
</div>
