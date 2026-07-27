<?php

use Livewire\Component;
use App\Models\TaxConfig as TaxModel;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

    public $name = 'PPN';
    public $type = 'percentage';
    public $value = '';
    public $is_active = true;
    
    public $showModal = false;
    public $showDeleteModal = false;
    public $editingId = null;
    public $deletingId = null;

    protected $rules = [
        'name' => 'required|string|max:100',
        'type' => 'required|in:percentage,fixed',
        'value' => 'required|numeric|min:0',
    ];

    public function openModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['name', 'type', 'value', 'is_active']);
        $this->name = 'PPN';
        $this->type = 'percentage';
        $this->is_active = true;

        if ($id) {
            $tax = TaxModel::where('admin_id', Auth::id())->findOrFail($id);
            $this->editingId = $tax->id;
            $this->name = $tax->name;
            $this->type = $tax->type;
            $this->value = (string) ($tax->value + 0);
            $this->is_active = $tax->is_active;
        } else {
            $this->editingId = null;
        }

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $tax = TaxModel::where('admin_id', Auth::id())->findOrFail($this->editingId);
            $tax->update([
                'name' => $this->name,
                'type' => $this->type,
                'value' => $this->value,
                'is_active' => $this->is_active,
            ]);
            $this->success('Aturan PPN berhasil diperbarui.');
        } else {
            TaxModel::create([
                'admin_id' => Auth::id(),
                'name' => $this->name,
                'type' => $this->type,
                'value' => $this->value,
                'is_active' => $this->is_active,
            ]);
            $this->success('Aturan PPN berhasil ditambahkan.');
        }

        $this->showModal = false;
    }

    public function toggleStatus($id)
    {
        $tax = TaxModel::where('admin_id', Auth::id())->findOrFail($id);
        $tax->update(['is_active' => !$tax->is_active]);
        $this->success('Status aturan PPN diubah.');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if ($this->deletingId) {
            $tax = TaxModel::where('admin_id', Auth::id())->findOrFail($this->deletingId);
            $tax->delete();
            $this->success('Aturan PPN berhasil dihapus.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function render()
    {
        $taxes = TaxModel::where('admin_id', Auth::id())
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.tax-config', [
            'taxes' => $taxes
        ]);
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Pengaturan PPN & Pajak</h1>
                <p class="text-sm text-base-content/70">Kelola aturan pajak atau PPN (persentase / nominal tetap) yang akan dikenakan pada transaksi kasir.</p>
            </div>
            <x-button wire:click="openModal" label="Tambah Aturan PPN" icon="o-plus" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Nama Pajak</th>
                            <th>Tipe Perhitungan</th>
                            <th>Nilai / Tarif</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($taxes as $tax)
                        <tr>
                            <td class="font-bold text-base-content">
                                {{ $tax->name }}
                            </td>
                            <td>
                                @if($tax->type === 'percentage')
                                    <span class="text-sm font-medium">Persentase (%)</span>
                                @else
                                    <span class="text-sm font-medium">Nominal Tetap (Rp)</span>
                                @endif
                            </td>
                            <td class="text-primary font-bold">
                                @if($tax->type === 'percentage')
                                    {{ rtrim(rtrim(number_format($tax->value, 2, ',', '.'), '0'), ',') }}%
                                @else
                                    Rp {{ number_format($tax->value, 0, ',', '.') }}
                                @endif
                            </td>
                            <td>
                                @if($tax->is_active)
                                    <x-badge value="Aktif" class="badge-success badge-sm" />
                                @else
                                    <x-badge value="Nonaktif" class="badge-neutral badge-sm" />
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-button wire:click="toggleStatus({{ $tax->id }})" label="{{ $tax->is_active ? 'Matikan' : 'Nyalakan' }}" class="btn-sm {{ $tax->is_active ? 'btn-error btn-outline' : 'btn-primary' }}" />
                                    <x-button wire:click="openModal({{ $tax->id }})" icon="o-pencil-square" class="btn-ghost btn-sm" />
                                    <x-button wire:click="confirmDelete({{ $tax->id }})" icon="o-trash" class="btn-error btn-sm text-white" />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-base-content/60">Belum ada aturan PPN/Pajak yang dikonfigurasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($taxes->hasPages())
                <div class="p-4 border-t border-base-200">
                    {{ $taxes->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Add/Edit Rule Modal -->
    <x-modal wire:model="showModal" title="{{ $editingId ? 'Edit Aturan PPN' : 'Tambah Aturan PPN' }}">
        <p class="text-sm text-base-content/70 mb-4">Atur tarif pajak atau PPN (contoh: PPN 11%, PB1 10%, atau biaya admin nominal tetap).</p>
        <x-form wire:submit="save">
            <x-input wire:model="name" type="text" label="Nama Pajak" placeholder="Contoh: PPN 11%, PB1 10%" required />
            
            <x-select wire:model.live="type" label="Tipe Perhitungan" :options="[['id' => 'percentage', 'name' => 'Persentase (%)'], ['id' => 'fixed', 'name' => 'Nominal Tetap (Rp)']]" required />
            
            <x-input wire:model="value" type="number" step="any" min="0" label="{{ $type === 'percentage' ? 'Persentase (%)' : 'Nominal (Rp)' }}" placeholder="0" required />
            
            <x-checkbox wire:model="is_active" label="Aktifkan aturan PPN ini sekarang" />

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showModal', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Aturan PPN?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus aturan PPN ini? Tindakan ini tidak dapat dibatalkan.</p>

        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Hapus" wire:click="executeDelete" class="btn-error text-white" spinner="executeDelete" />
        </x-slot:actions>
    </x-modal>
</div>
