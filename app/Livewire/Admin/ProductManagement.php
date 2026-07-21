<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManagement extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $sku = '';

    public string $name = '';

    public string $cost_price = '';

    public string $selling_price = '';

    public int $stock = 0;

    public int $min_stock_alert = 5;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        return Product::where('admin_id', Auth::id())
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('sku', 'like', '%'.$this->search.'%'))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'sku', 'name', 'cost_price', 'selling_price', 'stock', 'min_stock_alert']);
        $this->min_stock_alert = 5;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $product = Product::where('admin_id', Auth::id())->findOrFail($id);
        $this->editingId = $id;
        $this->sku = $product->sku;
        $this->name = $product->name;
        $this->cost_price = (string) $product->cost_price;
        $this->selling_price = (string) $product->selling_price;
        $this->stock = $product->stock;
        $this->min_stock_alert = $product->min_stock_alert;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'sku' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock_alert' => 'required|integer|min:0',
        ]);

        $data = [
            'admin_id' => Auth::id(),
            'sku' => $this->sku,
            'name' => $this->name,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'stock' => $this->stock,
            'min_stock_alert' => $this->min_stock_alert,
        ];

        if ($this->editingId) {
            Product::where('admin_id', Auth::id())->findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Produk berhasil diperbarui.');
        } else {
            Product::create($data);
            $this->dispatch('toast', type: 'success', message: 'Produk berhasil ditambahkan.');
        }

        $this->showModal = false;
        unset($this->products);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Product::where('admin_id', Auth::id())->findOrFail($this->deletingId)->delete();
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->dispatch('toast', type: 'success', message: 'Produk berhasil dihapus.');
        unset($this->products);
    }

    public function render()
    {
        return view('livewire.admin.product-management');
    }
}
