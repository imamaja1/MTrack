<?php

use Livewire\Component;
use App\Models\MemberBenefit;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;

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
            Flux::toast('Aturan berhasil diperbarui.', variant: 'success');
        } else {
            MemberBenefit::create([
                'admin_id' => Auth::id(),
                'min_purchase' => $this->min_purchase,
                'discount_nominal' => $this->discount_nominal,
                'cashback_nominal' => $this->cashback_nominal,
                'is_active' => $this->is_active,
            ]);
            Flux::toast('Aturan berhasil ditambahkan.', variant: 'success');
        }

        $this->showModal = false;
    }

    public function toggleStatus($id)
    {
        $benefit = MemberBenefit::where('admin_id', Auth::id())->findOrFail($id);
        $benefit->update(['is_active' => !$benefit->is_active]);
        Flux::toast('Status aturan diubah.', variant: 'success');
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
            Flux::toast('Aturan berhasil dihapus.', variant: 'success');
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
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Config Member</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Kelola tingkat (tier) keuntungan member berdasarkan minimal belanja.</p>
            </div>
            <flux:button wire:click="openModal" variant="primary" icon="plus">Tambah Aturan</flux:button>
        </div>

        <flux:card class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                        <tr>
                            <th class="px-6 py-3">Min. Belanja</th>
                            <th class="px-6 py-3">Potongan Harga</th>
                            <th class="px-6 py-3">Cashback Poin</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($benefits as $benefit)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 font-bold text-zinc-900 dark:text-white">
                                Rp {{ number_format($benefit->min_purchase, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-green-600 dark:text-green-400">
                                Rp {{ number_format($benefit->discount_nominal, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-blue-600 dark:text-blue-400">
                                +{{ number_format($benefit->cashback_nominal, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($benefit->is_active)
                                    <flux:badge color="green" size="sm">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Nonaktif</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <flux:button wire:click="toggleStatus({{ $benefit->id }})" size="sm" variant="{{ $benefit->is_active ? 'danger' : 'primary' }}">
                                        {{ $benefit->is_active ? 'Matikan' : 'Nyalakan' }}
                                    </flux:button>
                                    <flux:button wire:click="openModal({{ $benefit->id }})" size="sm" variant="outline" icon="pencil-square" class="px-2" />
                                    <flux:button wire:click="confirmDelete({{ $benefit->id }})" size="sm" variant="danger" icon="trash" class="px-2" />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-zinc-500">Belum ada aturan keuntungan member.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($benefits->hasPages())
                <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $benefits->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal wire:model="showModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Aturan' : 'Tambah Aturan' }}</flux:heading>
                <flux:subheading>Tentukan keuntungan yang didapat member saat mencapai minimal belanja tertentu.</flux:subheading>
            </div>

            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="min_purchase" type="number" min="0" label="Minimal Belanja (Rp)" placeholder="0" />
                <flux:input wire:model="discount_nominal" type="number" min="0" label="Potongan Harga (Rp)" placeholder="0" />
                <flux:input wire:model="cashback_nominal" type="number" min="0" label="Cashback Poin" placeholder="0" />
                
                <flux:checkbox wire:model="is_active" label="Aktifkan aturan ini sekarang" />

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
                <flux:heading size="lg">Hapus Aturan?</flux:heading>
                <flux:subheading>Apakah Anda yakin ingin menghapus aturan ini? Tindakan ini tidak dapat dibatalkan.</flux:subheading>
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
