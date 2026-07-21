<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new class extends Component
{
    use WithPagination;

    public $paymentAmount = 0;
    public $paymentMethod = 'cash';
    public $selectedTransactionId = null;
    public $selectedTransaction = null;
    public $receiptData = null;
    public $transactionToDelete = null;

    #[Livewire\Attributes\Url]
    public $pay = null;

    public function mount()
    {
        if ($this->pay) {
            $this->openPaymentModal($this->pay);
            $this->pay = null;
        }
    }

    public function getTransactionsProperty()
    {
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        
        return Transaction::where('admin_id', $adminId)
            ->with(['operator', 'member', 'coupon'])
            ->latest()
            ->paginate(10);
    }

    public function openPaymentModal($id)
    {
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        $transaction = Transaction::where('admin_id', $adminId)
            ->with(['items.product', 'member', 'coupon'])
            ->find($id);
        
        if ($transaction) {
            $this->selectedTransactionId = $id;
            $this->selectedTransaction = $transaction->toArray();
            $this->paymentAmount = max(0, $transaction->total_amount - $transaction->paid_amount);
            $this->dispatch('modal-show', name: 'payment-modal');
        }
    }

    public function processPayment()
    {
        $this->validate([
            'paymentAmount' => ['required', 'numeric', 'min:0'],
            'paymentMethod' => ['required', 'string'],
        ]);

        if (!$this->selectedTransactionId) return;

        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        $transaction = Transaction::where('admin_id', $adminId)->find($this->selectedTransactionId);
        
        if ($transaction) {
            $transaction->paid_amount += $this->paymentAmount;
            $transaction->payment_method = $this->paymentMethod;
            
            if ($transaction->paid_amount >= $transaction->total_amount && $transaction->total_amount > 0) {
                $transaction->status = 'completed';
            } else if ($transaction->paid_amount > 0) {
                $transaction->status = 'partial';
            } else {
                $transaction->status = 'pending';
            }
            
            $transaction->save();
            Flux::toast('Pembayaran berhasil dicatat.', variant: 'success');
        }

        $this->dispatch('modal-close', name: 'payment-modal');
        $this->selectedTransactionId = null;
        $this->selectedTransaction = null;
        $this->paymentAmount = 0;
    }

    public function printReceipt($id)
    {
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        $transaction = Transaction::where('admin_id', $adminId)
            ->with(['items.product', 'operator', 'admin', 'member', 'coupon'])
            ->find($id);
            
        if ($transaction) {
            $this->receiptData = $transaction->toArray();
            $this->dispatch('print-receipt');
        }
    }

    public function addMenu($id)
    {
        return redirect()->route('pos', ['transaction_id' => $id]);
    }

    public function confirmDeleteTransaction($id)
    {
        $this->transactionToDelete = $id;
        $this->dispatch('modal-show', name: 'delete-transaction-modal');
    }

    public function deleteTransaction()
    {
        if (!$this->transactionToDelete) return;

        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        $transaction = Transaction::where('admin_id', $adminId)->with('items')->find($this->transactionToDelete);

        if ($transaction && $transaction->paid_amount == 0) {
            \Illuminate\Support\Facades\DB::transaction(function() use ($transaction) {
                // Revert stock
                foreach ($transaction->items as $item) {
                    $product = \App\Models\Product::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->increment('stock', $item->qty);
                    }
                }
                
                // Delete items & transaction
                $transaction->items()->delete();
                $transaction->delete();
            });
            
            Flux::toast('Tagihan berhasil dihapus.', variant: 'success');
        }

        $this->dispatch('modal-close', name: 'delete-transaction-modal');
        $this->transactionToDelete = null;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Riwayat Penjualan</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Kelola riwayat transaksi penjualan, tambah pesanan ke tagihan terbuka, dan lunasi pembayaran.</p>
            </div>
            <flux:button href="{{ route('pos') }}" wire:navigate variant="primary" icon="plus">Transaksi Baru</flux:button>
        </div>

        <flux:card class="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                        <tr>
                            <th class="px-6 py-3">Tanggal / Waktu</th>
                            <th class="px-6 py-3">Invoice</th>
                            <th class="px-6 py-3">Member / Kupon</th>
                            <th class="px-6 py-3">Total Tagihan</th>
                            <th class="px-6 py-3">Sudah Dibayar</th>
                            <th class="px-6 py-3">Sisa Tagihan</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->transactions as $trx)
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <td class="px-6 py-4 text-xs">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-6 py-4 font-mono text-xs font-bold">{{ $trx->invoice_number }}</td>
                                <td class="px-6 py-4 text-xs">
                                    @if($trx->customer_name)
                                        <div class="font-semibold text-zinc-800 dark:text-zinc-200"><flux:icon.user class="w-3 h-3 inline mr-1" />{{ $trx->customer_name }}</div>
                                    @endif
                                    
                                    @if($trx->member)
                                        <div class="font-semibold text-blue-600 dark:text-blue-400"><flux:icon.star class="w-3 h-3 inline mr-1" />{{ $trx->member->name }}</div>
                                    @elseif(!$trx->customer_name)
                                        <div class="text-zinc-400">-</div>
                                    @endif
                                    
                                    @if($trx->coupon)
                                        <div class="text-green-600 dark:text-green-400 mt-1"><flux:icon.ticket class="w-3 h-3 inline mr-1" />{{ $trx->coupon->code }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-green-600">Rp {{ number_format($trx->paid_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-red-600 font-bold">Rp {{ number_format($trx->remaining_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4">
                                    @if($trx->status === 'completed')
                                        <flux:badge color="green">Lunas</flux:badge>
                                    @else
                                        <flux:badge color="orange">Belum Lunas</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @if($trx->status !== 'completed')
                                            <flux:button wire:click="openPaymentModal({{ $trx->id }})" size="sm" variant="subtle" icon="banknotes" class="!text-green-600 hover:!bg-green-100" title="Bayar / Pelunasan" />
                                            <flux:button wire:click="addMenu({{ $trx->id }})" size="sm" variant="subtle" icon="shopping-bag" class="!text-blue-600 hover:!bg-blue-100" title="Tambah Menu" />
                                            @if($trx->paid_amount == 0)
                                                <flux:button wire:click="confirmDeleteTransaction({{ $trx->id }})" size="sm" variant="subtle" icon="trash" class="!text-red-600 hover:!bg-red-100" title="Hapus Tagihan" />
                                            @endif
                                        @endif
                                        @if($trx->paid_amount > 0)
                                            <flux:button wire:click="printReceipt({{ $trx->id }})" size="sm" variant="subtle" icon="printer" class="!text-zinc-600 hover:!bg-zinc-100 dark:hover:!bg-zinc-800" title="Print Struk" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-zinc-500">
                                    Belum ada transaksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->transactions->links() }}
            </div>
        </flux:card>
    </div>

    <!-- Payment Modal -->
    <flux:modal name="payment-modal" class="md:w-[500px]">
        <form wire:submit.prevent="processPayment" class="flex flex-col gap-4">
            <flux:heading class="text-lg">Pelunasan Tagihan</flux:heading>
            
            @if($selectedTransaction)
                <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 max-h-48 overflow-y-auto mb-2">
                    <div class="text-xs font-bold text-zinc-500 mb-2 uppercase tracking-wider">Item Pesanan</div>
                    <ul class="space-y-2">
                        @foreach($selectedTransaction['items'] as $item)
                            <li class="flex justify-between items-start text-sm">
                                <div>
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $item['product']['name'] ?? 'Item' }}</span>
                                    <div class="text-xs text-zinc-500">{{ $item['qty'] }} x Rp {{ number_format($item['unit_selling_price'], 0, ',', '.') }}</div>
                                </div>
                                <div class="font-semibold text-zinc-700 dark:text-zinc-300">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="flex flex-col gap-1 text-sm border-b border-zinc-200 dark:border-zinc-700 pb-3 mb-2">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Subtotal</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format(($selectedTransaction['total_amount'] ?? 0) + ($selectedTransaction['discount_amount'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    
                    @if(isset($selectedTransaction['discount_amount']) && $selectedTransaction['discount_amount'] > 0)
                        <div class="flex justify-between text-green-600 dark:text-green-400">
                            <span>
                                Diskon 
                                @if(isset($selectedTransaction['coupon']))
                                    (Kupon: {{ $selectedTransaction['coupon']['code'] }})
                                @endif
                                @if(isset($selectedTransaction['member']))
                                    (Member: {{ $selectedTransaction['member']['name'] }})
                                @endif
                            </span>
                            <span class="font-semibold">- Rp {{ number_format($selectedTransaction['discount_amount'], 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between mt-1 pt-1 border-t border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500">Total Akhir Tagihan</span>
                        <span class="font-bold text-zinc-900 dark:text-white">Rp {{ number_format($selectedTransaction['total_amount'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Sudah Dibayar</span>
                        <span class="font-semibold text-green-600">Rp {{ number_format($selectedTransaction['paid_amount'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold mt-1">
                        <span class="text-zinc-800 dark:text-zinc-200">Sisa Tagihan</span>
                        <span class="text-red-600">Rp {{ number_format(max(0, $selectedTransaction['total_amount'] - $selectedTransaction['paid_amount']), 0, ',', '.') }}</span>
                    </div>
                </div>
            @endif
            
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                Masukkan jumlah nominal yang dibayarkan oleh pelanggan.
            </div>
            
            <flux:select wire:model="paymentMethod" label="Metode Pembayaran" class="mb-4">
                <option value="cash">Uang Tunai (Cash)</option>
                <option value="qris">QRIS</option>
                <option value="transfer">Transfer Bank</option>
            </flux:select>
            
            <flux:input wire:model="paymentAmount" type="number" step="0.01" label="Jumlah Dibayar (Rp)" required />
            
            <div class="flex justify-end gap-2 mt-4">
                <flux:modal.close>
                    <flux:button variant="outline" wire:click="$set('selectedTransaction', null)">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Proses Pembayaran</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-transaction-modal" class="md:w-96">
        <div class="flex flex-col gap-4">
            <div>
                <flux:heading size="lg">Hapus Tagihan?</flux:heading>
                <flux:subheading>
                    Tagihan ini akan dihapus permanen. Stok barang yang sudah dipesan dalam tagihan ini akan dikembalikan ke inventaris secara otomatis.
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" wire:click="$set('transactionToDelete', null)">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteTransaction">Ya, Hapus Tagihan</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Hidden Print Area -->
    @if($receiptData)
        <div id="print-area" class="hidden">
            @include('operator.receipt', ['receipt' => $receiptData])
        </div>
    @endif
</div>

@script
<script>
    $wire.on('print-receipt', () => {
        setTimeout(() => {
            const printArea = document.getElementById('print-area');
            if (printArea) {
                const printContent = printArea.innerHTML;
                const originalContent = document.body.innerHTML;
                document.body.innerHTML = printContent;
                window.print();
                document.body.innerHTML = originalContent;
                window.location.reload(); 
            }
        }, 300);
    });
</script>
@endscript
