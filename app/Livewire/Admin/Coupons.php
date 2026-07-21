<?php

namespace App\Livewire\Admin;

use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Coupons extends Component
{
    use Toast, WithPagination;

    public string $code = '';

    public string $type = 'fixed';

    public string $value = '';

    public string $min_purchase = '0';

    public string $expires_at = '';

    public bool $isModalOpen = false;

    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:coupons,code,NULL,id,admin_id,'.Auth::id(),
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_purchase' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
        ];
    }

    public function with(): array
    {
        return [
            'coupons' => Coupon::where('admin_id', Auth::id())->latest()->paginate(10),
        ];
    }

    public function save(): void
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

    public function toggleStatus(int $id): void
    {
        $coupon = Coupon::where('admin_id', Auth::id())->findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);
        $this->success('Status kupon diubah.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete(): void
    {
        if ($this->deletingId) {
            Coupon::where('admin_id', Auth::id())->findOrFail($this->deletingId)->delete();
            $this->success('Kupon berhasil dihapus.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function openModal(): void
    {
        $this->reset(['code', 'type', 'value', 'min_purchase', 'expires_at']);
        $this->resetValidation();
        $this->isModalOpen = true;
    }

    public function render()
    {
        return view('livewire.admin.coupons');
    }
}
