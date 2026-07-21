<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

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
        
        $this->dispatch('modal-show', name: 'product-modal');
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
            Flux::toast('Produk berhasil diperbarui.', variant: 'success');
        } else {
            $data['admin_id'] = auth()->id();
            Product::create($data);
            Flux::toast('Produk berhasil ditambahkan.', variant: 'success');
        }

        $this->resetFields();
        $this->dispatch('modal-close', name: 'product-modal');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->dispatch('modal-show', name: 'delete-product-modal');
    }

    public function deleteProduct()
    {
        if (!$this->deletingId) return;
        
        $product = Product::where('admin_id', auth()->id())->find($this->deletingId);
        if ($product) {
            $hasTransactions = \Illuminate\Support\Facades\DB::table('transaction_items')->where('product_id', $product->id)->exists();
            $hasMovements = \Illuminate\Support\Facades\DB::table('stock_movements')->where('product_id', $product->id)->exists();
            
            if ($hasTransactions || $hasMovements) {
                Flux::toast('Produk gagal dihapus karena sudah memiliki riwayat penjualan atau pergerakan stok.', variant: 'danger');
            } else {
                $product->delete();
                Flux::toast('Produk berhasil dihapus.', variant: 'success');
            }
        }
        
        $this->dispatch('modal-close', name: 'delete-product-modal');
        $this->deletingId = null;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Product Management</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Kelola daftar produk yang tersedia di toko Anda.</p>
            </div>
            <flux:button wire:click="resetFields" x-on:click="$dispatch('modal-show', { name: 'product-modal' })" variant="primary" icon="plus">Tambah Produk</flux:button>
        </div>

        <flux:card>
            <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>SKU</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Cost Price</flux:table.column>
                    <flux:table.column>Selling Price</flux:table.column>
                    <flux:table.column>Stok Awal</flux:table.column>
                    <flux:table.column>Alert Limit</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->products as $product)
                        @php
                            $initial = $product->stock - ($product->total_added ?? 0) + ($product->total_sold ?? 0);
                        @endphp
                        <flux:table.row>
                            <flux:table.cell>{{ $product->sku }}</flux:table.cell>
                            <flux:table.cell>{{ $product->name }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($product->cost_price, 2) }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($product->selling_price, 2) }}</flux:table.cell>
                            <flux:table.cell>
                                {{ $initial }}
                            </flux:table.cell>
                            <flux:table.cell>{{ $product->min_stock_alert }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button wire:click="editProduct({{ $product->id }})" size="sm" variant="subtle" icon="pencil-square" class="!text-blue-600 hover:!bg-blue-100" />
                                    <flux:button wire:click="confirmDelete({{ $product->id }})" size="sm" variant="subtle" icon="trash" class="!text-red-600 hover:!bg-red-100" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
        <div class="mt-4">
            {{ $this->products->links() }}
        </div>
    </flux:card>
    </div>

    <flux:modal name="product-modal" class="md:w-96">
        <form wire:submit.prevent="saveProduct" class="flex flex-col gap-4">
            <flux:heading class="text-lg">{{ $editingId ? 'Edit Product' : 'Create Product' }}</flux:heading>
            
            <flux:input wire:model="sku" label="SKU" placeholder="SKU-123" required />
            <flux:input wire:model="name" label="Product Name" placeholder="Item Name" required />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="cost_price" type="number" step="0.01" label="Cost Price" required />
                <flux:input wire:model="selling_price" type="number" step="0.01" label="Selling Price" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="stock" type="number" label="Stock" required />
                <flux:input wire:model="min_stock_alert" type="number" label="Min Alert" required />
            </div>
            
            <div class="flex justify-end gap-2 mt-4">
                <flux:modal.close>
                    <flux:button variant="outline">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-product-modal" class="md:w-[400px]">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3 text-red-600">
                <flux:icon.exclamation-triangle class="w-8 h-8" />
                <flux:heading class="text-lg text-red-600">Hapus Produk?</flux:heading>
            </div>
            
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Apakah Anda yakin ingin menghapus produk ini? Semua riwayat stok yang berkaitan dengan produk ini mungkin juga akan terhapus. Aksi ini tidak dapat dibatalkan.
            </p>
            
            <div class="flex justify-end gap-2 mt-4">
                <flux:modal.close>
                    <flux:button variant="outline">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="deleteProduct" variant="danger">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>