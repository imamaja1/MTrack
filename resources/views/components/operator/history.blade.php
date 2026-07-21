<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use WithPagination, Toast;

    public $paymentAmount = 0;
    public $paymentMethod = 'cash';
    public $selectedTransactionId = null;
    public $selectedTransaction = null;
    public $receiptData = null;
    public $transactionToDelete = null;

    public bool $showPaymentModal = false;
    public bool $showDeleteModal = false;

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
            $this->showPaymentModal = true;
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
            $this->success('Pembayaran berhasil dicatat.');
        }

        $this->showPaymentModal = false;
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
        $this->showDeleteModal = true;
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
            
            $this->success('Tagihan berhasil dihapus.');
        }

        $this->showDeleteModal = false;
        $this->transactionToDelete = null;
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4 mb-4 md:flex-row md:justify-between md:items-center">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Riwayat Penjualan</h1>
                <p class="text-sm text-base-content/70">Kelola riwayat transaksi penjualan, tambah pesanan ke tagihan terbuka, dan lunasi pembayaran.</p>
            </div>
            <x-button label="Transaksi Baru" link="{{ route('pos') }}" wire:navigate icon="o-plus" class="btn-primary" />
        </div>

        <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr>
                            <th>Tanggal / Waktu</th>
                            <th>Invoice</th>
                            <th>Member / Kupon</th>
                            <th>Total Tagihan</th>
                            <th>Sudah Dibayar</th>
                            <th>Sisa Tagihan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->transactions as $trx)
                            <tr>
                                <td class="text-xs">{{ $trx->created_at->format('d M Y, H:i') }}</td>
                                <td class="font-mono text-xs font-bold">{{ $trx->invoice_number }}</td>
                                <td class="text-xs">
                                    @if($trx->customer_name)
                                        <div class="font-semibold text-base-content"><x-icon name="o-user" class="w-3 h-3 inline mr-1" />{{ $trx->customer_name }}</div>
                                    @endif
                                    
                                    @if($trx->member)
                                        <div class="font-semibold text-info"><x-icon name="o-star" class="w-3 h-3 inline mr-1" />{{ $trx->member->name }}</div>
                                    @elseif(!$trx->customer_name)
                                        <div class="text-base-content/40">-</div>
                                    @endif
                                    
                                    @if($trx->coupon)
                                        <div class="text-success mt-1"><x-icon name="o-ticket" class="w-3 h-3 inline mr-1" />{{ $trx->coupon->code }}</div>
                                    @endif
                                </td>
                                <td class="font-semibold">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                <td class="text-success">Rp {{ number_format($trx->paid_amount, 0, ',', '.') }}</td>
                                <td class="text-error font-bold">Rp {{ number_format($trx->remaining_amount, 0, ',', '.') }}</td>
                                <td>
                                    @if($trx->status === 'completed')
                                        <x-badge value="Lunas" class="badge-success badge-sm" />
                                    @else
                                        <x-badge value="Belum Lunas" class="badge-warning badge-sm" />
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if($trx->status !== 'completed')
                                            <x-button wire:click="openPaymentModal({{ $trx->id }})" icon="o-banknotes" class="btn-ghost btn-sm text-success" title="Bayar / Pelunasan" />
                                            <x-button wire:click="addMenu({{ $trx->id }})" icon="o-shopping-bag" class="btn-ghost btn-sm text-info" title="Tambah Menu" />
                                            @if($trx->paid_amount == 0)
                                                <x-button wire:click="confirmDeleteTransaction({{ $trx->id }})" icon="o-trash" class="btn-ghost btn-sm text-error" title="Hapus Tagihan" />
                                            @endif
                                        @endif
                                        @if($trx->paid_amount > 0)
                                            <x-button wire:click="printReceipt({{ $trx->id }})" icon="o-printer" class="btn-ghost btn-sm text-base-content/70" title="Print Struk" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-base-content/60 py-4">
                                    Belum ada transaksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-base-200">
                {{ $this->transactions->links() }}
            </div>
        </x-card>
    </div>

    <!-- Payment Modal -->
    <x-modal wire:model="showPaymentModal" title="Pelunasan Tagihan">
        <x-form wire:submit="processPayment">
            @if($selectedTransaction)
                <div class="bg-base-200/50 p-4 rounded-xl border border-base-200 max-h-48 overflow-y-auto mb-2">
                    <div class="text-xs font-bold text-base-content/60 mb-2 uppercase tracking-wider">Item Pesanan</div>
                    <ul class="space-y-2">
                        @foreach($selectedTransaction['items'] as $item)
                            <li class="flex justify-between items-start text-sm">
                                <div>
                                    <span class="font-medium text-base-content">{{ $item['product']['name'] ?? 'Item' }}</span>
                                    <div class="text-xs text-base-content/60">{{ $item['qty'] }} x Rp {{ number_format($item['unit_selling_price'], 0, ',', '.') }}</div>
                                </div>
                                <div class="font-semibold text-base-content">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="flex flex-col gap-1 text-sm border-b border-base-200 pb-3 mb-2">
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Subtotal</span>
                        <span class="font-semibold text-base-content">Rp {{ number_format(($selectedTransaction['total_amount'] ?? 0) + ($selectedTransaction['discount_amount'] ?? 0), 0, ',', '.') }}</span>
                    </div>
                    
                    @if(isset($selectedTransaction['discount_amount']) && $selectedTransaction['discount_amount'] > 0)
                        <div class="flex justify-between text-success">
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

                    <div class="flex justify-between mt-1 pt-1 border-t border-base-200">
                        <span class="text-base-content/60">Total Akhir Tagihan</span>
                        <span class="font-bold text-base-content">Rp {{ number_format($selectedTransaction['total_amount'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60">Sudah Dibayar</span>
                        <span class="font-semibold text-success">Rp {{ number_format($selectedTransaction['paid_amount'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold mt-1">
                        <span class="text-base-content">Sisa Tagihan</span>
                        <span class="text-error">Rp {{ number_format(max(0, $selectedTransaction['total_amount'] - $selectedTransaction['paid_amount']), 0, ',', '.') }}</span>
                    </div>
                </div>
            @endif
            
            <p class="text-sm text-base-content/70">
                Masukkan jumlah nominal yang dibayarkan oleh pelanggan.
            </p>
            
            <x-select
                wire:model="paymentMethod"
                label="Metode Pembayaran"
                :options="[
                    ['id' => 'cash', 'name' => 'Uang Tunai (Cash)'],
                    ['id' => 'qris', 'name' => 'QRIS'],
                    ['id' => 'transfer', 'name' => 'Transfer Bank']
                ]"
            />
            
            <x-input wire:model="paymentAmount" type="number" step="0.01" label="Jumlah Dibayar (Rp)" required />
            
            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showPaymentModal', false)" class="btn-ghost" />
                <x-button label="Proses Pembayaran" type="submit" class="btn-primary" spinner="processPayment" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Delete Confirmation Modal -->
    <x-modal wire:model="showDeleteModal" title="Hapus Tagihan?">
        <p class="py-2 text-base-content/70">
            Tagihan ini akan dihapus permanen. Stok barang yang sudah dipesan dalam tagihan ini akan dikembalikan ke inventaris secara otomatis.
        </p>
        <x-slot:actions>
            <x-button label="Batal" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
            <x-button label="Ya, Hapus Tagihan" wire:click="deleteTransaction" class="btn-error text-white" spinner="deleteTransaction" />
        </x-slot:actions>
    </x-modal>

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
