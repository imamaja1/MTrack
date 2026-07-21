<?php

use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public $name = '';
    public $phone = '';
    
    public bool $isModalOpen = false;
    public bool $showDeleteModal = false;
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
        
        $this->success('Member berhasil ditambahkan!');
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
            $this->success('Member berhasil dihapus.');
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
                <h1 class="text-2xl font-bold text-base-content">Member Pelanggan</h1>
                <p class="text-sm text-base-content/70">Kelola daftar pelanggan setia toko Anda.</p>
            </div>
            <x-button label="Tambah Member" wire:click="openModal" icon="o-plus" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>No. HP</th>
                            <th>Poin</th>
                            <th>Bergabung Sejak</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                        <tr>
                            <td class="font-medium text-base-content">{{ $member->name }}</td>
                            <td>{{ $member->phone ?? '-' }}</td>
                            <td>
                                <x-badge value="{{ $member->points }}" class="badge-warning badge-sm" />
                            </td>
                            <td>{{ $member->created_at->format('d M Y') }}</td>
                            <td>
                                <x-button wire:click="confirmDelete({{ $member->id }})" icon="o-trash" class="btn-error btn-sm text-white" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-base-content/60 py-4">Belum ada member.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($members->hasPages())
                <div class="p-4 border-t border-base-200">
                    {{ $members->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Add Member Modal -->
    <x-modal wire:model="isModalOpen" title="Tambah Member Baru">
        <x-form wire:submit="save">
            <x-input wire:model="name" label="Nama Pelanggan" placeholder="Masukkan nama..." required />
            <x-input wire:model="phone" label="Nomor Handphone" placeholder="Contoh: 08123456789" />
            
            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('isModalOpen', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Member?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus member ini? Tindakan ini tidak dapat dibatalkan.</p>

        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Hapus" wire:click="executeDelete" class="btn-error text-white" spinner="executeDelete" />
        </x-slot:actions>
    </x-modal>
</div>
