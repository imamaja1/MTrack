<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use WithPagination, Toast;

    // Modal state
    public bool $showCategoryModal = false;
    public bool $showExpenseModal = false;
    public bool $showDeleteModal = false;

    // Form: tambah pengeluaran
    public ?int $expense_category_id = null;
    public string $description = '';
    public string $amount = '';
    public string $expense_date = '';
    public ?int $expenseToDelete = null;

    // Form: tambah kategori
    public string $newCategoryName = '';

    public function mount(): void
    {
        $this->expense_date = date('Y-m-d');
    }

    public function getExpensesProperty()
    {
        return Expense::with('category')
            ->where('admin_id', Auth::id())
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function getCategoriesProperty()
    {
        return ExpenseCategory::where('admin_id', Auth::id())
            ->orderBy('name')
            ->get();
    }

    public function saveExpense(): void
    {
        $this->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'description'         => 'nullable|string',
            'amount'              => 'required|numeric|min:0',
            'expense_date'        => 'required|date',
        ]);

        $category = ExpenseCategory::where('admin_id', Auth::id())
                        ->findOrFail($this->expense_category_id);

        Expense::create([
            'admin_id'            => Auth::id(),
            'expense_category_id' => $this->expense_category_id,
            'title'               => $category->name,
            'description'         => $this->description,
            'amount'              => $this->amount,
            'expense_date'        => $this->expense_date,
        ]);

        $this->success('Pengeluaran berhasil dicatat.');

        $this->reset(['expense_category_id', 'description', 'amount']);
        $this->expense_date = date('Y-m-d');
        $this->showExpenseModal = false;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'newCategoryName' => 'required|string|max:100',
        ]);

        // Cek duplikat
        $exists = ExpenseCategory::where('admin_id', Auth::id())
                    ->whereRaw('LOWER(name) = ?', [strtolower($this->newCategoryName)])
                    ->exists();

        if ($exists) {
            $this->addError('newCategoryName', 'Kategori dengan nama ini sudah ada.');
            return;
        }

        ExpenseCategory::create([
            'admin_id' => Auth::id(),
            'name'     => $this->newCategoryName,
        ]);

        $this->success('Kategori berhasil ditambahkan.');
        $this->reset('newCategoryName');
    }

    public function deleteCategory(int $id): void
    {
        ExpenseCategory::where('admin_id', Auth::id())->where('id', $id)->delete();
        $this->success('Kategori berhasil dihapus.');
    }

    public function confirmDelete(int $id): void
    {
        $this->expenseToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteExpense(): void
    {
        if ($this->expenseToDelete) {
            Expense::where('admin_id', Auth::id())->where('id', $this->expenseToDelete)->delete();
            $this->success('Pengeluaran berhasil dihapus.');
        }

        $this->expenseToDelete = null;
        $this->showDeleteModal = false;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4 mb-4 md:flex-row md:justify-between md:items-center">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Pengeluaran Toko</h1>
                <p class="text-sm text-base-content/70">Kelola catatan pengeluaran operasional toko Anda.</p>
            </div>
            <div class="flex gap-2">
                <x-button label="Kelola Kategori" icon="o-tag" wire:click="$set('showCategoryModal', true)" class="btn-outline" />
                <x-button label="Catat Pengeluaran" icon="o-plus" wire:click="$set('showExpenseModal', true)" class="btn-primary" />
            </div>
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul / Kategori</th>
                            <th>Keterangan</th>
                            <th>Nominal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->expenses as $expense)
                            <tr>
                                <td class="text-xs">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                                <td>
                                    <x-badge value="{{ $expense->title }}" class="badge-accent badge-sm" />
                                </td>
                                <td class="text-xs">{{ $expense->description ?: '-' }}</td>
                                <td class="font-bold text-error">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                <td>
                                    <x-button wire:click="confirmDelete({{ $expense->id }})" icon="o-trash" class="btn-ghost btn-sm text-error" title="Hapus" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-base-content/50">
                                        <x-icon name="o-inbox" class="h-10 w-10" />
                                        <p class="text-sm">Belum ada catatan pengeluaran.</p>
                                        @if($this->categories->isEmpty())
                                            <p class="text-xs">Mulai dengan menambahkan kategori terlebih dahulu.</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-base-200">
                {{ $this->expenses->links() }}
            </div>
        </x-card>
    </div>

    {{-- ── Modal: Kelola Kategori ─────────────────────────────────────── --}}
    <x-modal wire:model="showCategoryModal" title="Kelola Kategori Pengeluaran">
        <div class="flex flex-col gap-5">
            {{-- Tambah Kategori Baru --}}
            <x-form wire:submit="saveCategory">
                <div class="flex gap-2 items-start">
                    <div class="flex-1">
                        <x-input
                            wire:model="newCategoryName"
                            placeholder="Nama kategori baru... (cth: Listrik, Gaji)"
                        />
                    </div>
                    <x-button type="submit" icon="o-plus" class="btn-primary" spinner="saveCategory" />
                </div>
            </x-form>

            {{-- Daftar Kategori --}}
            <div class="flex flex-col gap-2 max-h-72 overflow-y-auto">
                @forelse($this->categories as $cat)
                    <div class="flex items-center justify-between rounded-lg border border-base-200 px-4 py-2.5">
                        <span class="text-sm font-medium text-base-content">{{ $cat->name }}</span>
                        <x-button
                            wire:click="deleteCategory({{ $cat->id }})"
                            wire:confirm="Hapus kategori '{{ $cat->name }}'? Pengeluaran yang terkait tidak akan terhapus."
                            icon="o-trash"
                            class="btn-ghost btn-sm text-error"
                        />
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-base-300 py-8 text-center">
                        <p class="text-sm text-base-content/50">Belum ada kategori. Tambahkan di atas!</p>
                    </div>
                @endforelse
            </div>

            <x-slot:actions>
                <x-button label="Tutup" wire:click="$set('showCategoryModal', false)" class="btn-ghost" />
            </x-slot:actions>
        </div>
    </x-modal>

    {{-- ── Modal: Catat Pengeluaran ──────────────────────────────────── --}}
    <x-modal wire:model="showExpenseModal" title="Catat Pengeluaran">
        <x-form wire:submit="saveExpense">
            <x-input wire:model="expense_date" type="date" label="Tanggal Pengeluaran" required />

            @if($this->categories->isEmpty())
                <div class="alert alert-warning rounded-lg text-sm">
                    Belum ada kategori. Silakan buat kategori terlebih dahulu melalui tombol <strong>Kelola Kategori</strong>.
                </div>
            @else
                <x-select
                    wire:model="expense_category_id"
                    label="Judul Pengeluaran (Kategori)"
                    :options="$this->categories"
                    placeholder="Pilih kategori..."
                    placeholder-value=""
                    required
                />
            @endif

            <x-input wire:model="amount" type="number" step="0.01" label="Nominal (Rp)" placeholder="50000" required />
            <x-textarea wire:model="description" label="Keterangan (Opsional)" placeholder="Catatan tambahan..." rows="2" />

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showExpenseModal', false)" class="btn-ghost" />
                <x-button label="Simpan" type="submit" class="btn-primary" spinner="saveExpense" :disabled="$this->categories->isEmpty()" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- ── Modal: Konfirmasi Hapus ───────────────────────────────────── --}}
    <x-modal wire:model="showDeleteModal" title="Hapus Catatan?">
        <p class="py-2 text-base-content/70">Apakah Anda yakin ingin menghapus catatan pengeluaran ini? Tindakan ini tidak dapat dibatalkan.</p>
        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Ya, Hapus" wire:click="deleteExpense" class="btn-error text-white" spinner="deleteExpense" />
        </x-slot:actions>
    </x-modal>
</div>
