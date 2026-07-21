<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class OperatorManagement extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_active = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function getOperatorsProperty()
    {
        return User::where('admin_id', Auth::id())
            ->where('role', 'operator')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%'))
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $operator = User::where('admin_id', Auth::id())->where('role', 'operator')->findOrFail($id);
        $this->editingId = $id;
        $this->name = $operator->name;
        $this->email = $operator->email;
        $this->password = '';
        $this->is_active = $operator->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email'.($this->editingId ? ','.$this->editingId : ''),
            'is_active' => 'boolean',
        ];

        if (! $this->editingId) {
            $rules['password'] = 'required|string|min:8';
        } elseif ($this->password) {
            $rules['password'] = 'nullable|string|min:8';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'role' => 'operator',
            'admin_id' => Auth::id(),
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            User::where('admin_id', Auth::id())->where('role', 'operator')->findOrFail($this->editingId)->update($data);
            $this->success('Operator berhasil diperbarui.');
        } else {
            User::create($data);
            $this->success('Operator berhasil ditambahkan.');
        }

        $this->showModal = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function executeDelete(): void
    {
        if ($this->deletingId) {
            User::where('admin_id', Auth::id())->where('role', 'operator')->findOrFail($this->deletingId)->delete();
            $this->success('Operator berhasil dihapus.');
        }
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function toggleStatus(int $id): void
    {
        $operator = User::where('admin_id', Auth::id())->where('role', 'operator')->findOrFail($id);
        $operator->update(['is_active' => ! $operator->is_active]);
        $this->success('Status operator diubah.');
    }

    public function render()
    {
        return view('livewire.admin.operator-management');
    }
}
