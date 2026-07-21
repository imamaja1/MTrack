<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use WithPagination, Toast;

    public $editingId = null;
    public $deletingId = null;
    public $selectedProductId = '';
    public $qtyToAdd = 1;
    public $notes = '';

    public bool $showRestockModal = false;
    public bool $showDeleteModal = false;

    public function getRestocksProperty()
    {
        $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();
        
        return StockMovement::with(['product', 'user'])
            ->whereHas('product', function($q) use ($adminId) {
                $q->where('admin_id', $adminId);
            })
            ->where('type', 'in')
            ->latest()
            ->paginate(15);
    }
    
    public function getProductsProperty()
    {
        $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();
        return Product::where('admin_id', $adminId)->orderBy('name')->get();
    }

    public function openRestockModal()
    {
        $this->editingId = null;
        $this->selectedProductId = '';
        $this->qtyToAdd = 1;
        $this->notes = '';
        $this->resetValidation();
        $this->showRestockModal = true;
    }

    public function editRestock($id)
    {
        $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();
        
        $movement = StockMovement::whereHas('product', function($q) use ($adminId) {
            $q->where('admin_id', $adminId);
        })->findOrFail($id);

        $this->editingId = $movement->id;
        $this->selectedProductId = $movement->product_id;
        $this->qtyToAdd = $movement->qty;
        $this->notes = $movement->notes;
        $this->resetValidation();
        $this->showRestockModal = true;
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteRestock()
    {
        if (!$this->deletingId) return;

        DB::transaction(function () {
            $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();
            
            $movement = StockMovement::whereHas('product', function($q) use ($adminId) {
                $q->where('admin_id', $adminId);
            })->findOrFail($this->deletingId);
            
            if ($movement->type !== 'in') return;
            
            $product = Product::lockForUpdate()->find($movement->product_id);
            if ($product) {
                $product->decrement('stock', $movement->qty);
            }
            
            $movement->delete();
        });

        $this->showDeleteModal = false;
        $this->success('Riwayat penambahan stok berhasil dihapus.');
        $this->deletingId = null;
    }

    public function saveStock()
    {
        $this->validate([
            'selectedProductId' => ['required', 'exists:products,id'],
            'qtyToAdd' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () {
            $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();
            $product = Product::where('admin_id', $adminId)->lockForUpdate()->findOrFail($this->selectedProductId);
            
            if ($this->editingId) {
                $movement = StockMovement::findOrFail($this->editingId);
                
                // Hitung selisih jumlah yang baru dengan yang lama
                $diff = $this->qtyToAdd - $movement->qty;
                
                // Sesuaikan stok produk
                $product->increment('stock', $diff);
                
                $movement->update([
                    'qty' => $this->qtyToAdd,
                    'stock_after' => $product->stock,
                    'notes' => $this->notes ?: 'Restock manual (diubah)',
                ]);
                
                $this->success('Berhasil memperbarui data stok.');
            } else {
                $stockBefore = $product->stock;
                $product->increment('stock', $this->qtyToAdd);
                
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'type' => 'in',
                    'qty' => $this->qtyToAdd,
                    'stock_before' => $stockBefore,
                    'stock_after' => $product->stock,
                    'notes' => $this->notes ?: 'Restock manual',
                ]);
                
                $this->success('Berhasil menambahkan stok barang.');
            }
        });

        $this->showRestockModal = false;
        
        $this->editingId = null;
        $this->selectedProductId = '';
        $this->qtyToAdd = 1;
        $this->notes = '';
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4 md:flex-row md:justify-between md:items-center">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Riwayat Restock Barang</h1>
                <p class="text-sm text-base-content/70">Daftar catatan penambahan stok barang oleh operator/admin.</p>
            </div>
            
            <x-button label="Tambah Stok Barang" wire:click="openRestockModal" icon="o-plus" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Produk</th>
                            <th>Petugas</th>
                            <th>Jumlah Ditambah</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->restocks as $log)
                            <tr>
                                <td class="text-xs text-base-content/70">{{ $log->created_at->format('d M Y, H:i') }}</td>
                                <td class="font-medium text-base-content">
                                    {{ $log->product->name }} <br>
                                    <span class="text-[10px] text-base-content/50 font-mono">{{ $log->product->sku }}</span>
                                </td>
                                <td>{{ optional($log->user)->name ?? 'Sistem' }}</td>
                                <td>
                                    <span class="font-bold text-success">+{{ $log->qty }}</span>
                                </td>
                                <td class="text-sm italic text-base-content/70">{{ $log->notes }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-button wire:click="editRestock({{ $log->id }})" icon="o-pencil-square" class="btn-ghost btn-sm text-info" />
                                        <x-button wire:click="confirmDelete({{ $log->id }})" icon="o-trash" class="btn-ghost btn-sm text-error" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-base-content/60 py-4">
                                    Belum ada riwayat penambahan barang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-base-200">
                {{ $this->restocks->links() }}
            </div>
        </x-card>
    </div>

    <!-- Restock Edit/Create Modal -->
    <x-modal wire:model="showRestockModal" :title="$editingId ? 'Edit Data Penambahan' : 'Form Tambah Stok'">
        <x-form wire:submit="saveStock">
            @php
                $productOptions = $this->products->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name . ' (Sisa: ' . $p->stock . ')'
                ]);
            @endphp

            <x-select
                wire:model="selectedProductId"
                label="Pilih Barang"
                :options="$productOptions"
                placeholder="-- Pilih Produk --"
                placeholder-value=""
                :disabled="$editingId !== null"
                required
            />
            
            <x-input wire:model="qtyToAdd" type="number" label="Jumlah Penambahan" min="1" required />
            <x-input wire:model="notes" label="Keterangan (Opsional)" placeholder="Contoh: Barang masuk dari supplier A" />
            
            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showRestockModal', false)" class="btn-ghost" />
                <x-button label="Simpan Stok" type="submit" class="btn-primary" spinner="saveStock" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Riwayat Stok?">
        <p class="py-2 text-base-content/70">
            Apakah Anda yakin ingin menghapus data riwayat ini? Tindakan ini akan <b>menarik kembali stok barang</b> sejumlah yang pernah ditambahkan. Aksi ini tidak dapat dibatalkan.
        </p>
        
        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Ya, Hapus" wire:click="deleteRestock" class="btn-error text-white" spinner="deleteRestock" />
        </x-slot:actions>
    </x-modal>
</div>
