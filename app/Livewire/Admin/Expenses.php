<?php

namespace App\Livewire\Admin;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class Expenses extends Component
{
    use Toast, WithPagination;

    public bool $showCategoryModal = false;

    public bool $showExpenseModal = false;

    public bool $showDeleteModal = false;

    public ?int $expense_category_id = null;

    public string $description = '';

    public string $amount = '';

    public string $expense_date = '';

    public ?int $expenseToDelete = null;

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
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        $category = ExpenseCategory::where('admin_id', Auth::id())
            ->findOrFail($this->expense_category_id);

        Expense::create([
            'admin_id' => Auth::id(),
            'expense_category_id' => $this->expense_category_id,
            'title' => $category->name,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
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

        $exists = ExpenseCategory::where('admin_id', Auth::id())
            ->whereRaw('LOWER(name) = ?', [strtolower($this->newCategoryName)])
            ->exists();

        if ($exists) {
            $this->addError('newCategoryName', 'Kategori dengan nama ini sudah ada.');

            return;
        }

        ExpenseCategory::create([
            'admin_id' => Auth::id(),
            'name' => $this->newCategoryName,
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

    public function render()
    {
        return view('livewire.admin.expenses');
    }
}
