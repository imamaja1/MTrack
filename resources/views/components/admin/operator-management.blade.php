<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Mary\Traits\Toast;

new class extends Component
{
    use WithPagination, Toast;

    public $name = '';
    public $email = '';
    public $password = '';
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public $deletingId = null;

    public function getOperatorsProperty()
    {
        return User::where('role', 'operator')
            ->where('admin_id', auth()->id())
            ->latest()
            ->paginate(10);
    }

    public function createOperator()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role' => 'operator',
            'admin_id' => auth()->id(),
            'is_active' => true,
        ]);

        $this->reset(['name', 'email', 'password']);
        $this->showCreateModal = false;
        $this->success('Operator berhasil ditambahkan.');
    }

    public function toggleStatus($id)
    {
        $operator = User::where('admin_id', auth()->id())->find($id);
        if ($operator) {
            $operator->update(['is_active' => !$operator->is_active]);
            $this->success('Status operator diubah.');
        }
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if ($this->deletingId) {
            $operator = User::where('admin_id', auth()->id())->find($this->deletingId);
            if ($operator) {
                $operator->delete();
                $this->success('Operator berhasil dihapus.');
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
                <h1 class="text-2xl font-bold text-base-content">Operator Management</h1>
                <p class="text-sm text-base-content/70">Kelola akun operator kasir untuk toko Anda.</p>
            </div>
            <x-button label="Tambah Operator" icon="o-plus" wire:click="$set('showCreateModal', true)" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->operators as $operator)
                            <tr>
                                <td class="font-medium text-base-content">{{ $operator->name }}</td>
                                <td>{{ $operator->email }}</td>
                                <td>
                                    <x-badge value="{{ $operator->is_active ? 'Active' : 'Inactive' }}" class="{{ $operator->is_active ? 'badge-success' : 'badge-neutral' }}" />
                                </td>
                                <td>{{ $operator->created_at->format('Y-m-d') }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <x-button wire:click="toggleStatus({{ $operator->id }})" label="{{ $operator->is_active ? 'Deactivate' : 'Activate' }}" class="btn-ghost btn-sm" />
                                        <x-button wire:click="confirmDelete({{ $operator->id }})" icon="o-trash" class="btn-error btn-sm text-white" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-base-content/60">Belum ada operator terdaftar.</td>
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

    <!-- Create Operator Modal -->
    <x-modal wire:model="showCreateModal" title="Tambah Operator">
        <p class="text-sm text-base-content/70 mb-4">Buat akun kasir baru untuk mengelola transaksi.</p>
        <x-form wire:submit="createOperator">
            <x-input wire:model="name" label="Nama Lengkap" placeholder="Nama Operator" required />
            <x-input wire:model="email" type="email" label="Email" placeholder="operator@toko.com" required />
            <x-input wire:model="password" type="password" label="Password" required />
            
            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showCreateModal', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="createOperator" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Operator?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus operator ini? Tindakan ini tidak dapat dibatalkan.</p>

        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Hapus" wire:click="executeDelete" class="btn-error text-white" spinner="executeDelete" />
        </x-slot:actions>
    </x-modal>
</div>