<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Stocks extends Component
{
    use WithPagination;

    public string $search = '';

    public function getProductsProperty()
    {
        return Product::where('admin_id', Auth::id())
            ->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%');
            })
            ->withSum(['stockMovements as total_added' => function ($q) {
                $q->where('type', 'in');
            }], 'qty')
            ->withSum('transactionItems as total_sold', 'qty')
            ->orderBy('name')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.stocks');
    }
}
