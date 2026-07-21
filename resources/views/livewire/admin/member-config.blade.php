<?php

use Livewire\Component;
use App\Models\MemberBenefit;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public $showModal = false;
    public $showDeleteModal = false;
    public $editingId = null;
    public $deletingId = null;
    
    public $min_purchase = 0;
    public $discount_nominal = 0;
    public $cashback_nominal = 0;
    public $is_active = true;

    protected $rules = [
        'min_purchase' => 'required|numeric|min:0',
        'discount_nominal' => 'required|numeric|min:0',
        'cashback_nominal' => 'required|numeric|min:0',
    ];

    public function openModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['min_purchase', 'discount_nominal', 'cashback_nominal', 'is_active']);
        
        if ($id) {
            $benefit = MemberBenefit::where('admin_id', Auth::id())->findOrFail($id);
            $this->editingId = $benefit->id;
            $this->min_purchase = $benefit->min_purchase;
            $this->discount_nominal = $benefit->discount_nominal;
            $this->cashback_nominal = $benefit->cashback_nominal;
            $this->is_active = $benefit->is_active;
        } else {
            $this->editingId = null;
        }
        
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $benefit = MemberBenefit::where('admin_id', Auth::id())->findOrFail($this->editingId);
            $benefit->update([
                'min_purchase' => $this->min_purchase,
                'discount_nominal' => $this->discount_nominal,
                'cashback_nominal' => $this->cashback_nominal,
                'is_active' => $this->is_active,
            ]);
            $this->success('Aturan berhasil diperbarui.');
        } else {
            MemberBenefit::create([
                'admin_id' => Auth::id(),
                'min_purchase' => $this->min_purchase,
                'discount_nominal' => $this->discount_nominal,
                'cashback_nominal' => $this->cashback_nominal,
                'is_active' => $this->is_active,
            ]);
            $this->success('Aturan berhasil ditambahkan.');
        }

        $this->showModal = false;
    }

    public function toggleStatus($id)
    {
        $benefit = MemberBenefit::where('admin_id', Auth::id())->findOrFail($id);
        $benefit->update(['is_active' => !$benefit->is_active]);
        $this->success('Status aturan diubah.');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if ($this->deletingId) {
            $benefit = MemberBenefit::where('admin_id', Auth::id())->findOrFail($this->deletingId);
            $benefit->delete();
            $this->success('Aturan berhasil dihapus.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function render()
    {
        $benefits = MemberBenefit::where('admin_id', Auth::id())
            ->orderBy('min_purchase', 'asc')
            ->paginate(10);
            
        return view('livewire.admin.member-config', [
            'benefits' => $benefits
        ]);
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Config Member</h1>
                <p class="text-sm text-base-content/70">Kelola tingkat (tier) keuntungan member berdasarkan minimal belanja.</p>
            </div>
            <x-button wire:click="openModal" label="Tambah Aturan" icon="o-plus" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Min. Belanja</th>
                            <th>Potongan Harga</th>
                            <th>Cashback Poin</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($benefits as $benefit)
                        <tr>
                            <td class="font-bold text-base-content">
                                Rp {{ number_format($benefit->min_purchase, 0, ',', '.') }}
                            </td>
                            <td class="text-success font-medium">
                                Rp {{ number_format($benefit->discount_nominal, 0, ',', '.') }}
                            </td>
                            <td class="text-info font-medium">
                                +{{ number_format($benefit->cashback_nominal, 0, ',', '.') }}
                            </td>
                            <td>
                                @if($benefit->is_active)
                                    <x-badge value="Aktif" class="badge-success badge-sm" />
                                @else
                                    <x-badge value="Nonaktif" class="badge-neutral badge-sm" />
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-button wire:click="toggleStatus({{ $benefit->id }})" label="{{ $benefit->is_active ? 'Matikan' : 'Nyalakan' }}" class="btn-sm {{ $benefit->is_active ? 'btn-error btn-outline' : 'btn-primary' }}" />
                                    <x-button wire:click="openModal({{ $benefit->id }})" icon="o-pencil-square" class="btn-ghost btn-sm" />
                                    <x-button wire:click="confirmDelete({{ $benefit->id }})" icon="o-trash" class="btn-error btn-sm text-white" />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-base-content/60">Belum ada aturan keuntungan member.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($benefits->hasPages())
                <div class="p-4 border-t border-base-200">
                    {{ $benefits->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Add/Edit Rule Modal -->
    <x-modal wire:model="showModal" title="{{ $editingId ? 'Edit Aturan' : 'Tambah Aturan' }}">
        <p class="text-sm text-base-content/70 mb-4">Tentukan keuntungan yang didapat member saat mencapai minimal belanja tertentu.</p>
        <x-form wire:submit="save">
            <x-input wire:model="min_purchase" type="number" min="0" label="Minimal Belanja (Rp)" placeholder="0" />
            <x-input wire:model="discount_nominal" type="number" min="0" label="Potongan Harga (Rp)" placeholder="0" />
            <x-input wire:model="cashback_nominal" type="number" min="0" label="Cashback Poin" placeholder="0" />
            
            <x-checkbox wire:model="is_active" label="Aktifkan aturan ini sekarang" />

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showModal', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Aturan?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus aturan ini? Tindakan ini tidak dapat dibatalkan.</p>

        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Hapus" wire:click="executeDelete" class="btn-error text-white" spinner="executeDelete" />
        </x-slot:actions>
    </x-modal>
</div>
