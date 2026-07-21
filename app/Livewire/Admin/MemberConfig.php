<?php

namespace App\Livewire\Admin;

use App\Models\MemberBenefit;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class MemberConfig extends Component
{
    use Toast, WithPagination;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public int $min_purchase = 0;

    public int $discount_nominal = 0;

    public int $cashback_nominal = 0;

    public bool $is_active = true;

    protected array $rules = [
        'min_purchase' => 'required|numeric|min:0',
        'discount_nominal' => 'required|numeric|min:0',
        'cashback_nominal' => 'required|numeric|min:0',
    ];

    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset(['min_purchase', 'discount_nominal', 'cashback_nominal', 'is_active']);
        $this->is_active = true;

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

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            MemberBenefit::where('admin_id', Auth::id())->findOrFail($this->editingId)->update([
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

    public function toggleStatus(int $id): void
    {
        $benefit = MemberBenefit::where('admin_id', Auth::id())->findOrFail($id);
        $benefit->update(['is_active' => ! $benefit->is_active]);
        $this->success('Status aturan diubah.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete(): void
    {
        if ($this->deletingId) {
            MemberBenefit::where('admin_id', Auth::id())->findOrFail($this->deletingId)->delete();
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

        return view('livewire.admin.member-config', ['benefits' => $benefits]);
    }
}
