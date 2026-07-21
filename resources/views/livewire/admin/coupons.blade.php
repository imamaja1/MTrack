<?php

use Livewire\Component;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public $code = '';
    public $type = 'fixed';
    public $value = '';
    public $min_purchase = 0;
    public $expires_at = '';
    
    public $isModalOpen = false;
    public $showDeleteModal = false;
    public $deletingId = null;
    
    public function rules()
    {
        return [
            'code' => 'required|string|max:50|unique:coupons,code,NULL,id,admin_id,' . Auth::id(),
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
        ];
    }

    public function with()
    {
        return [
            'coupons' => Coupon::where('admin_id', Auth::id())->latest()->paginate(10),
        ];
    }

    public function save()
    {
        $this->validate();

        Coupon::create([
            'admin_id' => Auth::id(),
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value' => $this->value,
            'min_purchase' => $this->min_purchase,
            'is_active' => true,
            'expires_at' => $this->expires_at ?: null,
        ]);

        $this->reset(['code', 'type', 'value', 'min_purchase', 'expires_at']);
        $this->isModalOpen = false;
        Flux::toast('Kupon berhasil ditambahkan!', variant: 'success');
    }

    public function toggleStatus($id)
    {
        $coupon = Coupon::where('admin_id', Auth::id())->findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);
        Flux::toast('Status kupon diubah.', variant: 'success');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if ($this->deletingId) {
            $coupon = Coupon::where('admin_id', Auth::id())->findOrFail($this->deletingId);
            $coupon->delete();
            Flux::toast('Kupon berhasil dihapus.', variant: 'success');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function openModal()
    {
        $this->reset(['code', 'type', 'value', 'min_purchase', 'expires_at']);
        $this->resetValidation();
        $this->isModalOpen = true;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Kupon Promo</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Kelola kupon promo untuk menarik lebih banyak pelanggan.</p>
            </div>
            <flux:button wire:click="$set('isModalOpen', true)" variant="primary" icon="plus">Tambah Kupon</flux:button>
        </div>

        <flux:card class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                        <tr>
                            <th class="px-6 py-3">Kode</th>
                            <th class="px-6 py-3">Diskon</th>
                            <th class="px-6 py-3">Min. Belanja</th>
                            <th class="px-6 py-3">Expired</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <td class="px-6 py-4 font-bold text-zinc-900 dark:text-white">{{ $coupon->code }}</td>
                            <td class="px-6 py-4">
                                @if($coupon->type === 'percentage')
                                    {{ (float) $coupon->value }}%
                                @else
                                    Rp {{ number_format($coupon->value, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4">Rp {{ number_format($coupon->min_purchase, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                {{ $coupon->expires_at ? $coupon->expires_at->format('d M Y') : 'Selamanya' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($coupon->is_active)
                                    <flux:badge color="green" size="sm">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Nonaktif</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <flux:button wire:click="toggleStatus({{ $coupon->id }})" size="sm" variant="{{ $coupon->is_active ? 'danger' : 'primary' }}">
                                        {{ $coupon->is_active ? 'Matikan' : 'Nyalakan' }}
                                    </flux:button>
                                    <flux:button wire:click="confirmDelete({{ $coupon->id }})" size="sm" variant="danger" icon="trash" class="px-2" />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-zinc-500">Belum ada kupon promo.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($coupons->hasPages())
                <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $coupons->links() }}
                </div>
            @endif
        </flux:card>
    </div>

    <flux:modal wire:model="isModalOpen" class="w-full max-w-md">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-4">Buat Kupon Baru</h2>
            
            <form wire:submit="save" class="flex flex-col gap-4">
                <flux:input wire:model="code" label="Kode Promo" placeholder="Contoh: MERDEKA50" required style="text-transform: uppercase" />
                
                <flux:select wire:model="type" label="Tipe Diskon">
                    <option value="percentage">Persentase (%)</option>
                    <option value="fixed">Potongan Tetap (Rp)</option>
                </flux:select>
                
                <flux:input type="number" wire:model="value" label="Nilai Diskon" placeholder="Misal: 10 atau 50000" required min="0" />
                
                <flux:input type="number" wire:model="min_purchase" label="Minimal Belanja (Rp)" placeholder="0 jika tanpa minimal" required min="0" />
                
                <flux:input type="date" wire:model="expires_at" label="Berlaku Sampai" />
                
                <div class="flex justify-end gap-3 mt-4">
                    <flux:button wire:click="$set('isModalOpen', false)" variant="outline">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Hapus Kupon?</flux:heading>
                <flux:subheading>Apakah Anda yakin ingin menghapus kupon ini? Tindakan ini tidak dapat dibatalkan.</flux:subheading>
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
