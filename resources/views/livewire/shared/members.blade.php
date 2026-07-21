<?php

use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public $name = '';
    public $phone = '';
    
    public $isModalOpen = false;
    public $showDeleteModal = false;
    public $deletingId = null;

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ];
    }

    public function with()
    {
        $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();
        
        return [
            'members' => Member::where('admin_id', $adminId)->latest()->paginate(10),
        ];
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();

        Member::create([
            'admin_id' => $adminId,
            'name' => $this->name,
            'phone' => $this->phone,
        ]);

        $this->reset(['name', 'phone']);
        $this->isModalOpen = false;
        
        Flux::toast('Member berhasil ditambahkan!', variant: 'success');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if ($this->deletingId) {
            $adminId = Auth::user()->role === 'operator' ? Auth::user()->admin_id : Auth::id();
            $member = Member::where('admin_id', $adminId)->findOrFail($this->deletingId);
            $member->delete();
            Flux::toast('Member berhasil dihapus.', variant: 'success');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function openModal()
    {
        $this->reset(['name', 'phone']);
        $this->resetValidation();
        $this->isModalOpen = true;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Member Pelanggan</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Kelola daftar pelanggan setia toko Anda.</p>
            </div>
            <flux:button wire:click="openModal" variant="primary" icon="plus">Tambah Member</flux:button>
        </div>

        <flux:card class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                        <tr>
                            <th class="px-6 py-3">Nama</th>
                            <th class="px-6 py-3">No. HP</th>
                            <th class="px-6 py-3">Poin</th>
                            <th class="px-6 py-3">Bergabung Sejak</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ $member->name }}</td>
                            <td class="px-6 py-4">{{ $member->phone ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <flux:badge color="amber">{{ $member->points }}</flux:badge>
                            </td>
                            <td class="px-6 py-4">{{ $member->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                <flux:button wire:click="confirmDelete({{ $member->id }})" size="sm" variant="danger" icon="trash" class="px-2" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-zinc-500">Belum ada member.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($members->hasPages())
                <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $members->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal wire:model="isModalOpen" class="w-full max-w-md">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">Tambah Member Baru</h2>
            
            <form wire:submit="save" class="flex flex-col gap-4">
                <flux:input wire:model="name" label="Nama Pelanggan" placeholder="Masukkan nama..." required />
                <flux:input wire:model="phone" label="Nomor Handphone" placeholder="Contoh: 08123456789" />
                
                <div class="flex justify-end gap-3 mt-4">
                    <flux:button wire:click="$set('isModalOpen', false)" variant="ghost">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Member?</flux:heading>
                <flux:subheading>Apakah Anda yakin ingin menghapus member ini? Tindakan ini tidak dapat dibatalkan.</flux:subheading>
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
