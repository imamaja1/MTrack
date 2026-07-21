<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

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

        Flux::toast('Pengeluaran berhasil dicatat.', variant: 'success');

        $this->reset(['expense_category_id', 'description', 'amount']);
        $this->expense_date = date('Y-m-d');
        $this->dispatch('modal-close', name: 'add-expense-modal');
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

        Flux::toast('Kategori berhasil ditambahkan.', variant: 'success');
        $this->reset('newCategoryName');
    }

    public function deleteCategory(int $id): void
    {
        ExpenseCategory::where('admin_id', Auth::id())->where('id', $id)->delete();
        Flux::toast('Kategori berhasil dihapus.', variant: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->expenseToDelete = $id;
        $this->dispatch('modal-show', name: 'delete-expense-modal');
    }

    public function deleteExpense(): void
    {
        if ($this->expenseToDelete) {
            Expense::where('admin_id', Auth::id())->where('id', $this->expenseToDelete)->delete();
            Flux::toast('Pengeluaran berhasil dihapus.', variant: 'success');
        }

        $this->expenseToDelete = null;
        $this->dispatch('modal-close', name: 'delete-expense-modal');
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4 mb-4 md:flex-row md:justify-between md:items-center">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Pengeluaran Toko</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Kelola catatan pengeluaran operasional toko Anda.</p>
            </div>
            <div class="flex gap-2">
                <flux:modal.trigger name="manage-categories-modal">
                    <flux:button variant="outline" icon="tag">Kelola Kategori</flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="add-expense-modal">
                    <flux:button variant="primary" icon="plus">Catat Pengeluaran</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <flux:card class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                        <tr>
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Judul / Kategori</th>
                            <th class="px-6 py-3">Keterangan</th>
                            <th class="px-6 py-3">Nominal</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->expenses as $expense)
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="px-6 py-4 text-xs">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                                <td class="px-6 py-4">
                                    <flux:badge color="indigo" size="sm">{{ $expense->title }}</flux:badge>
                                </td>
                                <td class="px-6 py-4 text-xs">{{ $expense->description ?: '-' }}</td>
                                <td class="px-6 py-4 font-bold text-red-600">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    <flux:button wire:click="confirmDelete({{ $expense->id }})" size="sm" variant="subtle" icon="trash" class="!text-red-600 hover:!bg-red-100" title="Hapus" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-zinc-400">
                                        <flux:icon.inbox class="h-10 w-10" />
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

            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->expenses->links() }}
            </div>
        </flux:card>
    </div>

    {{-- ── Modal: Kelola Kategori ─────────────────────────────────────── --}}
    <flux:modal name="manage-categories-modal" class="md:w-[26rem]">
        <div class="flex flex-col gap-5">
            <flux:heading size="lg">Kelola Kategori Pengeluaran</flux:heading>

            {{-- Tambah Kategori Baru --}}
            <form wire:submit.prevent="saveCategory" class="flex gap-2">
                <div class="flex-1">
                    <flux:input
                        wire:model="newCategoryName"
                        placeholder="Nama kategori baru... (cth: Listrik, Gaji)"
                        autofocus
                    />
                    @error('newCategoryName')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <flux:button type="submit" variant="primary" icon="plus" />
            </form>

            {{-- Daftar Kategori --}}
            <div class="flex flex-col gap-2 max-h-72 overflow-y-auto">
                @forelse($this->categories as $cat)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-2.5 dark:border-zinc-700">
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $cat->name }}</span>
                        <flux:button
                            wire:click="deleteCategory({{ $cat->id }})"
                            wire:confirm="Hapus kategori '{{ $cat->name }}'? Pengeluaran yang terkait tidak akan terhapus."
                            size="sm"
                            variant="subtle"
                            icon="trash"
                            class="!text-red-500 hover:!bg-red-50"
                        />
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 py-8 text-center dark:border-zinc-600">
                        <p class="text-sm text-zinc-400">Belum ada kategori. Tambahkan di atas!</p>
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- ── Modal: Catat Pengeluaran ──────────────────────────────────── --}}
    <flux:modal name="add-expense-modal" class="md:w-[26rem]">
        <form wire:submit.prevent="saveExpense" class="flex flex-col gap-4">
            <flux:heading size="lg">Catat Pengeluaran</flux:heading>

            <flux:input wire:model="expense_date" type="date" label="Tanggal Pengeluaran" required />

            @if($this->categories->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-700/40 dark:bg-amber-500/10 dark:text-amber-400">
                    Belum ada kategori. Silakan buat kategori terlebih dahulu melalui tombol <strong>Kelola Kategori</strong>.
                </div>
            @else
                <flux:select wire:model="expense_category_id" label="Judul Pengeluaran (Kategori)" required>
                    <option value="" disabled>Pilih kategori...</option>
                    @foreach($this->categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input wire:model="amount" type="number" step="0.01" label="Nominal (Rp)" placeholder="50000" required />
            <flux:textarea wire:model="description" label="Keterangan (Opsional)" placeholder="Catatan tambahan..." rows="2" />

            <div class="flex justify-end gap-2 mt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" :disabled="$this->categories->isEmpty()">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- ── Modal: Konfirmasi Hapus ───────────────────────────────────── --}}
    <flux:modal name="delete-expense-modal" class="md:w-96">
        <div class="flex flex-col gap-4">
            <div>
                <flux:heading size="lg">Hapus Catatan?</flux:heading>
                <flux:subheading>
                    Apakah Anda yakin ingin menghapus catatan pengeluaran ini? Tindakan ini tidak dapat dibatalkan.
                </flux:subheading>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="$set('expenseToDelete', null)">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteExpense">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
