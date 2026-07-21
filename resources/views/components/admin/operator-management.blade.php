<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public $name = '';
    public $email = '';
    public $password = '';
    public $showDeleteModal = false;
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
        $this->dispatch('modal-close', name: 'create-operator-modal');
        Flux::toast('Operator berhasil ditambahkan.', variant: 'success');
    }

    public function toggleStatus($id)
    {
        $operator = User::where('admin_id', auth()->id())->find($id);
        if ($operator) {
            $operator->update(['is_active' => !$operator->is_active]);
            Flux::toast('Status operator diubah.', variant: 'success');
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
                Flux::toast('Operator berhasil dihapus.', variant: 'success');
            }
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Operator Management</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Kelola akun operator kasir untuk toko Anda.</p>
            </div>
            <flux:modal.trigger name="create-operator-modal">
                <flux:button variant="primary" icon="plus">Tambah Operator</flux:button>
            </flux:modal.trigger>
        </div>

        <flux:card>
            <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Created At</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->operators as $operator)
                        <flux:table.row>
                            <flux:table.cell>{{ $operator->name }}</flux:table.cell>
                            <flux:table.cell>{{ $operator->email }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $operator->is_active ? 'green' : 'zinc' }}">
                                    {{ $operator->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $operator->created_at->format('Y-m-d') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:button wire:click="toggleStatus({{ $operator->id }})" size="sm" variant="ghost">
                                        {{ $operator->is_active ? 'Deactivate' : 'Activate' }}
                                    </flux:button>
                                    <flux:button wire:click="confirmDelete({{ $operator->id }})" size="sm" variant="danger" icon="trash" class="px-2" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
        <div class="mt-4">
            {{ $this->operators->links() }}
        </div>
    </flux:card>
    </div>

    <flux:modal name="create-operator-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Tambah Operator</flux:heading>
                <flux:subheading>Buat akun kasir baru untuk mengelola transaksi.</flux:subheading>
            </div>

            <form wire:submit.prevent="createOperator" class="space-y-4">
                <flux:input wire:model="name" label="Nama Lengkap" placeholder="Nama Operator" required />
                <flux:input wire:model="email" type="email" label="Email" placeholder="operator@toko.com" required />
                <flux:input wire:model="password" type="password" label="Password" required />
                
                <div class="flex justify-end gap-2 pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Simpan</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Operator?</flux:heading>
                <flux:subheading>Apakah Anda yakin ingin menghapus operator ini? Tindakan ini tidak dapat dibatalkan.</flux:subheading>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="executeDelete" variant="danger">Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>