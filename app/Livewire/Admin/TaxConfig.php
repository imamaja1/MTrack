<?php

namespace App\Livewire\Admin;

use App\Models\TaxConfig as TaxModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('components.layouts.app')]
#[Title('Pengaturan PPN')]
class TaxConfig extends Component
{
    use Toast, WithPagination;

    public string $name = 'PPN';

    public string $type = 'percentage';

    public string $value = '';

    public bool $is_active = true;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
        ];
    }

    public function openModal(?int $id = null): void
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

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            TaxModel::where('admin_id', Auth::id())->findOrFail($this->editingId)->update([
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

    public function toggleStatus(int $id): void
    {
        $tax = TaxModel::where('admin_id', Auth::id())->findOrFail($id);
        $tax->update(['is_active' => ! $tax->is_active]);
        $this->success('Status aturan PPN diubah.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete(): void
    {
        if ($this->deletingId) {
            TaxModel::where('admin_id', Auth::id())->findOrFail($this->deletingId)->delete();
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

        return view('livewire.admin.tax-config', ['taxes' => $taxes]);
    }
}
