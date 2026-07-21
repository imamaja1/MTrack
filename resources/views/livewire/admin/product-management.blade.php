<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Product Management</h1>
                <p class="text-sm text-base-content/70">Kelola katalog produk beserta harga dan stok toko Anda.</p>
            </div>
            <x-button wire:click="openCreate" label="Tambah Produk" icon="o-plus" class="btn-primary" />
        </div>

        <div class="w-full max-w-xs">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau SKU..." icon="o-magnifying-glass" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nama Produk</th>
                            <th class="text-right">Harga Modal</th>
                            <th class="text-right">Harga Jual</th>
                            <th class="text-center">Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->products as $product)
                            <tr>
                                <td class="font-mono text-xs text-base-content/60">{{ $product->sku }}</td>
                                <td class="font-medium text-base-content">{{ $product->name }}</td>
                                <td class="text-right text-base-content/70">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                                <td class="text-right font-medium text-base-content">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <x-badge value="{{ $product->stock }}" class="{{ $product->stock <= $product->min_stock_alert ? 'badge-error' : 'badge-success' }}" />
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-button wire:click="openEdit({{ $product->id }})" icon="o-pencil-square" class="btn-ghost btn-sm" />
                                        <x-button wire:click="confirmDelete({{ $product->id }})" icon="o-trash" class="btn-error btn-sm text-white" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-base-content/60 py-8">
                                    <div class="flex flex-col items-center gap-2">
                                        <x-icon name="o-squares-2x2" class="h-10 w-10 text-base-content/30" />
                                        <p class="text-sm">Belum ada produk. Tambahkan produk pertama Anda.</p>
                                    </div>
                                </td>
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
    <x-modal wire:model="showModal" title="{{ $editingId ? 'Edit Produk' : 'Tambah Produk Baru' }}">
        <x-form wire:submit="save">
            <x-input label="SKU" wire:model="sku" placeholder="Misal: PRD-001" required />
            <x-input label="Nama Produk" wire:model="name" placeholder="Nama produk" required />
            <div class="grid grid-cols-2 gap-4">
                <x-input type="number" label="Harga Modal (Rp)" wire:model="cost_price" min="0" required />
                <x-input type="number" label="Harga Jual (Rp)" wire:model="selling_price" min="0" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <x-input type="number" label="Stok Awal" wire:model="stock" min="0" required />
                <x-input type="number" label="Stok Minimum Alert" wire:model="min_stock_alert" min="0" required />
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showModal', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Produk?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>

        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Hapus" wire:click="delete" class="btn-error text-white" spinner="delete" />
        </x-slot:actions>
    </x-modal>
</div>
