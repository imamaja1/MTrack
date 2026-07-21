<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public $editingId = null;
    public $deletingId = null;
    public $selectedProductId = '';
    public $qtyToAdd = 1;
    public $notes = '';

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
        
        $this->dispatch('modal-show', name: 'restock-modal');
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
        
        $this->dispatch('modal-show', name: 'restock-modal');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->dispatch('modal-show', name: 'delete-restock-modal');
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

        $this->dispatch('modal-close', name: 'delete-restock-modal');
        Flux::toast('Riwayat penambahan stok berhasil dihapus.', variant: 'success');
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
                
                Flux::toast('Berhasil memperbarui data stok.', variant: 'success');
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
                
                Flux::toast('Berhasil menambahkan stok barang.', variant: 'success');
            }
        });

        $this->dispatch('modal-close', name: 'restock-modal');
        
        $this->editingId = null;
        $this->selectedProductId = '';
        $this->qtyToAdd = 1;
        $this->notes = '';
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Riwayat Restock Barang</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Daftar catatan penambahan stok barang oleh operator/admin.</p>
            </div>
            
            <flux:button wire:click="openRestockModal" variant="primary" icon="plus">Tambah Stok Barang</flux:button>
        </div>

        <flux:card class="p-0">

        <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                        <tr>
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Produk</th>
                            <th class="px-6 py-3">Petugas</th>
                            <th class="px-6 py-3">Jumlah Ditambah</th>
                            <th class="px-6 py-3">Keterangan</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->restocks as $log)
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="px-6 py-4 text-xs text-zinc-500">{{ $log->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-6 py-4 font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $log->product->name }} <br>
                                    <span class="text-[10px] text-zinc-400 font-mono">{{ $log->product->sku }}</span>
                                </td>
                                <td class="px-6 py-4">{{ optional($log->user)->name ?? 'Sistem' }}</td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-green-600">+{{ $log->qty }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm italic text-zinc-500">{{ $log->notes }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <flux:button wire:click="editRestock({{ $log->id }})" size="sm" variant="subtle" icon="pencil-square" class="!text-blue-600 hover:!bg-blue-100" />
                                        <flux:button wire:click="confirmDelete({{ $log->id }})" size="sm" variant="subtle" icon="trash" class="!text-red-600 hover:!bg-red-100" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-zinc-500">
                                    Belum ada riwayat penambahan barang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
            {{ $this->restocks->links() }}
        </div>
    </flux:card>
    </div>

    <!-- Restock Edit/Create Modal -->
    <flux:modal name="restock-modal" class="md:w-[500px]">
        <form wire:submit.prevent="saveStock" class="flex flex-col gap-4">
            <flux:heading class="text-lg">{{ $editingId ? 'Edit Data Penambahan' : 'Form Tambah Stok' }}</flux:heading>
            
            <flux:select wire:model="selectedProductId" label="Pilih Barang" placeholder="-- Pilih Produk --" :disabled="$editingId !== null" required>
                <option value=""></option>
                @foreach($this->products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} (Sisa: {{ $product->stock }})</option>
                @endforeach
            </flux:select>
            
            <flux:input wire:model="qtyToAdd" type="number" label="Jumlah Penambahan" min="1" required />
            
            <flux:input wire:model="notes" label="Keterangan (Opsional)" placeholder="Contoh: Barang masuk dari supplier A" />
            
            <div class="flex justify-end gap-2 mt-4">
                <flux:modal.close>
                    <flux:button variant="outline">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Stok</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-restock-modal" class="md:w-[400px]">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-3 text-red-600">
                <flux:icon.exclamation-triangle class="w-8 h-8" />
                <flux:heading class="text-lg text-red-600">Hapus Riwayat Stok?</flux:heading>
            </div>
            
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Apakah Anda yakin ingin menghapus data riwayat ini? Tindakan ini akan <b>menarik kembali stok barang</b> sejumlah yang pernah ditambahkan. Aksi ini tidak dapat dibatalkan.
            </p>
            
            <div class="flex justify-end gap-2 mt-4">
                <flux:modal.close>
                    <flux:button variant="outline">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="deleteRestock" variant="danger">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
