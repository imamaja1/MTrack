<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Operator Management</h1>
                <p class="text-sm text-base-content/70">Kelola akun operator yang bertugas menjalankan kasir.</p>
            </div>
            <x-button wire:click="openCreate" label="Tambah Operator" icon="o-plus" class="btn-primary" />
        </div>

        <div class="w-full max-w-xs">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..." icon="o-magnifying-glass" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->operators as $operator)
                            <tr>
                                <td class="font-medium text-base-content">{{ $operator->name }}</td>
                                <td class="text-base-content/70 text-sm">{{ $operator->email }}</td>
                                <td>
                                    @if($operator->is_active)
                                        <x-badge value="Aktif" class="badge-success badge-sm" />
                                    @else
                                        <x-badge value="Nonaktif" class="badge-neutral badge-sm" />
                                    @endif
                                </td>
                                <td class="text-xs text-base-content/60">{{ $operator->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-button wire:click="toggleStatus({{ $operator->id }})" label="{{ $operator->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" class="btn-sm {{ $operator->is_active ? 'btn-error btn-outline' : 'btn-primary' }}" />
                                        <x-button wire:click="openEdit({{ $operator->id }})" icon="o-pencil-square" class="btn-ghost btn-sm" />
                                        <x-button wire:click="confirmDelete({{ $operator->id }})" icon="o-trash" class="btn-error btn-sm text-white" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-base-content/60 py-8">
                                    <div class="flex flex-col items-center gap-2">
                                        <x-icon name="o-users" class="h-10 w-10 text-base-content/30" />
                                        <p class="text-sm">Belum ada operator. Tambahkan operator pertama Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($this->operators->hasPages())
                <div class="p-4 border-t border-base-200">
                    {{ $this->operators->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Create/Edit Operator Modal -->
    <x-modal wire:model="showModal" title="{{ $editingId ? 'Edit Operator' : 'Tambah Operator Baru' }}">
        <x-form wire:submit="save">
            <x-input label="Nama Lengkap" wire:model="name" placeholder="John Doe" required />
            <x-input label="Email" wire:model="email" type="email" placeholder="operator@toko.com" required />
            <x-input label="{{ $editingId ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password' }}" wire:model="password" type="password" :required="!$editingId" placeholder="Minimal 8 karakter" />
            <x-checkbox wire:model="is_active" label="Akun aktif" />

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showModal', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Operator?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus operator ini? Semua data yang terkait akan hilang.</p>

        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Hapus" wire:click="executeDelete" class="btn-error text-white" spinner="executeDelete" />
        </x-slot:actions>
    </x-modal>
</div>
