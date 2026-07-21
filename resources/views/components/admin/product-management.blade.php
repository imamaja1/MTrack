<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Mary\Traits\Toast;

new class extends Component
{
    use WithPagination, Toast;

    public bool $showProductModal = false;
    public bool $showDeleteModal = false;
    public $editingId = null;
    public $deletingId = null;
    
    public $sku = '';
    public $name = '';
    public $cost_price = '';
    public $selling_price = '';
    public $stock = 0;
    public $min_stock_alert = 5;

    public function getProductsProperty()
    {
        return Product::where('admin_id', auth()->id())
            ->withSum(['stockMovements as total_added' => function($q) {
                $q->where('type', 'in');
            }], 'qty')
            ->withSum('transactionItems as total_sold', 'qty')
            ->latest()
            ->paginate(10);
    }

    public function resetFields()
    {
        $this->resetValidation();
        $this->reset(['editingId', 'sku', 'name', 'cost_price', 'selling_price', 'stock', 'min_stock_alert']);
    }

    public function openCreateModal()
    {
        $this->resetFields();
        $this->showProductModal = true;
    }

    public function editProduct($id)
    {
        $this->resetFields();
        $product = Product::where('admin_id', auth()->id())->findOrFail($id);
        
        $this->editingId = $product->id;
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->cost_price = $product->cost_price;
        $this->selling_price = $product->selling_price;
        $this->stock = $product->stock;
        $this->min_stock_alert = $product->min_stock_alert;
        
        $this->showProductModal = true;
    }

    public function saveProduct()
    {
        $this->validate([
            'sku' => ['required', 'string', 'max:100', Rule::unique('products')->where('admin_id', auth()->id())->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock_alert' => ['required', 'integer', 'min:0'],
        ]);

        $data = [
            'sku' => $this->sku,
            'name' => $this->name,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'stock' => $this->stock,
            'min_stock_alert' => $this->min_stock_alert,
        ];

        if ($this->editingId) {
            Product::where('admin_id', auth()->id())->where('id', $this->editingId)->update($data);
            $this->success('Produk berhasil diperbarui.');
        } else {
            $data['admin_id'] = auth()->id();
            Product::create($data);
            $this->success('Produk berhasil ditambahkan.');
        }

        $this->resetFields();
        $this->showProductModal = false;
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteProduct()
    {
        if (!$this->deletingId) return;
        
        $product = Product::where('admin_id', auth()->id())->find($this->deletingId);
        if ($product) {
            $hasTransactions = \Illuminate\Support\Facades\DB::table('transaction_items')->where('product_id', $product->id)->exists();
            $hasMovements = \Illuminate\Support\Facades\DB::table('stock_movements')->where('product_id', $product->id)->exists();
            
            if ($hasTransactions || $hasMovements) {
                $this->error('Produk gagal dihapus karena sudah memiliki riwayat penjualan atau pergerakan stok.');
            } else {
                $product->delete();
                $this->success('Produk berhasil dihapus.');
            }
        }
        
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Product Management</h1>
                <p class="text-sm text-base-content/70">Kelola daftar produk yang tersedia di toko Anda.</p>
            </div>
            <x-button label="Tambah Produk" icon="o-plus" wire:click="openCreateModal" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th>Stok Awal</th>
                            <th>Alert Limit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->products as $product)
                            @php
                                $initial = $product->stock - ($product->total_added ?? 0) + ($product->total_sold ?? 0);
                            @endphp
                            <tr>
                                <td class="font-mono text-xs text-base-content/60">{{ $product->sku }}</td>
                                <td class="font-medium text-base-content">{{ $product->name }}</td>
                                <td>Rp {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                                <td>{{ $initial }}</td>
                                <td>{{ $product->min_stock_alert }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-button wire:click="editProduct({{ $product->id }})" icon="o-pencil-square" class="btn-ghost btn-sm text-info" />
                                        <x-button wire:click="confirmDelete({{ $product->id }})" icon="o-trash" class="btn-ghost btn-sm text-error" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-base-content/60">Belum ada produk terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($this->products->hasPages())
                <div class="p-4 border-t border-base-200">
                    {{ $this->products->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Create/Edit Product Modal -->
    <x-modal wire:model="showProductModal" title="{{ $editingId ? 'Edit Product' : 'Create Product' }}">
        <x-form wire:submit="saveProduct">
            <x-input wire:model="sku" label="SKU" placeholder="SKU-123" required />
            <x-input wire:model="name" label="Product Name" placeholder="Item Name" required />
            <div class="grid grid-cols-2 gap-4">
                <x-input wire:model="cost_price" type="number" step="0.01" label="Cost Price" required />
                <x-input wire:model="selling_price" type="number" step="0.01" label="Selling Price" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <x-input wire:model="stock" type="number" label="Stock" required />
                <x-input wire:model="min_stock_alert" type="number" label="Min Alert" required />
            </div>
            
            <x-slot:actions>
                <x-button label="Cancel" wire:click="$set('showProductModal', false)" class="btn-ghost" />
                <x-button label="Save" type="submit" class="btn-primary" spinner="saveProduct" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Produk?">
        <p class="py-2 text-base-content/70">
            Apakah Anda yakin ingin menghapus produk ini? Semua riwayat stok yang berkaitan dengan produk ini mungkin juga akan terhapus. Aksi ini tidak dapat dibatalkan.
        </p>
        
        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Ya, Hapus" wire:click="deleteProduct" class="btn-error text-white" spinner="deleteProduct" />
        </x-slot:actions>
    </x-modal>
</div>