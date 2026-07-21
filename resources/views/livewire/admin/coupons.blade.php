<?php

use Livewire\Component;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component {
    use WithPagination, Toast;

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
        $this->success('Kupon berhasil ditambahkan!');
    }

    public function toggleStatus($id)
    {
        $coupon = Coupon::where('admin_id', Auth::id())->findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);
        $this->success('Status kupon diubah.');
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
            $this->success('Kupon berhasil dihapus.');
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
                <h1 class="text-2xl font-bold text-base-content">Kupon Promo</h1>
                <p class="text-sm text-base-content/70">Kelola kupon promo untuk menarik lebih banyak pelanggan.</p>
            </div>
            <x-button wire:click="openModal" label="Tambah Kupon" icon="o-plus" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Diskon</th>
                            <th>Min. Belanja</th>
                            <th>Expired</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                        <tr>
                            <td class="font-bold text-base-content">{{ $coupon->code }}</td>
                            <td>
                                @if($coupon->type === 'percentage')
                                    {{ (float) $coupon->value }}%
                                @else
                                    Rp {{ number_format($coupon->value, 0, ',', '.') }}
                                @endif
                            </td>
                            <td>Rp {{ number_format($coupon->min_purchase, 0, ',', '.') }}</td>
                            <td>
                                {{ $coupon->expires_at ? $coupon->expires_at->format('d M Y') : 'Selamanya' }}
                            </td>
                            <td>
                                @if($coupon->is_active)
                                    <x-badge value="Aktif" class="badge-success badge-sm" />
                                @else
                                    <x-badge value="Nonaktif" class="badge-neutral badge-sm" />
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-button wire:click="toggleStatus({{ $coupon->id }})" label="{{ $coupon->is_active ? 'Matikan' : 'Nyalakan' }}" class="btn-sm {{ $coupon->is_active ? 'btn-error btn-outline' : 'btn-primary' }}" />
                                    <x-button wire:click="confirmDelete({{ $coupon->id }})" icon="o-trash" class="btn-error btn-sm text-white" />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-base-content/60 py-4">Belum ada kupon promo.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($coupons->hasPages())
                <div class="p-4 border-t border-base-200">
                    {{ $coupons->links() }}
                </div>
            @endif
        </x-card>
    </div>

    <!-- Create Coupon Modal -->
    <x-modal wire:model="isModalOpen" title="Buat Kupon Baru">
        <x-form wire:submit="save">
            <x-input label="Kode Promo" wire:model="code" placeholder="Contoh: MERDEKA50" required style="text-transform: uppercase" />
            
            <x-select
                label="Tipe Diskon"
                wire:model="type"
                :options="[
                    ['id' => 'percentage', 'name' => 'Persentase (%)'],
                    ['id' => 'fixed', 'name' => 'Potongan Tetap (Rp)']
                ]"
            />
            
            <x-input type="number" label="Nilai Diskon" wire:model="value" placeholder="Misal: 10 atau 50000" required min="0" />
            
            <x-input type="number" label="Minimal Belanja (Rp)" wire:model="min_purchase" placeholder="0 jika tanpa minimal" required min="0" />
            
            <x-input type="date" label="Berlaku Sampai" wire:model="expires_at" />
            
            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('isModalOpen', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Kupon?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus kupon ini? Tindakan ini tidak dapat dibatalkan.</p>

        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Hapus" wire:click="executeDelete" class="btn-error text-white" spinner="executeDelete" />
        </x-slot:actions>
    </x-modal>
</div>
