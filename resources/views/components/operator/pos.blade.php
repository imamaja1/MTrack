<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\StockMovement;
use App\Models\Member;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $search = '';
    public $cart = [];
    public $original_cart = [];
    public $receiptData = null;
    public bool $showCheckoutModal = false;
    
    // Member & Coupon
    public $member_id = '';
    public $customer_name = '';
    public $coupon_code = '';
    public $applied_coupon = null;
    public $coupon_error = '';

    #[Livewire\Attributes\Url]
    public $transaction_id = null;

    public function mount()
    {
        if ($this->transaction_id) {
            $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
            $transaction = Transaction::where('admin_id', $adminId)
                ->with(['items.product', 'member', 'coupon'])
                ->find($this->transaction_id);

            if ($transaction) {
                $this->member_id = $transaction->member_id;
                $this->customer_name = $transaction->customer_name;
                if ($transaction->coupon) {
                    $this->applied_coupon = $transaction->coupon->toArray();
                }

                foreach ($transaction->items as $item) {
                    $this->cart[$item->product_id] = [
                        'id' => $item->product_id,
                        'name' => $item->product->name,
                        'price' => $item->unit_selling_price,
                        'qty' => $item->qty,
                    ];
                    $this->original_cart[$item->product_id] = [
                        'qty' => $item->qty,
                    ];
                }
            }
        }
    }

    public function getProductsProperty()
    {
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        
        return Product::where('admin_id', $adminId)
            ->where(function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->take(20)
            ->get();
    }
    
    public function getMembersProperty()
    {
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        return Member::where('admin_id', $adminId)->orderBy('name')->get();
    }

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        if (!$product || $product->stock <= 0) {
            $this->addError('cart', 'Product out of stock or not found.');
            return;
        }

        if (isset($this->cart[$productId])) {
            $maxAllowed = $product->stock + ($this->original_cart[$productId]['qty'] ?? 0);
            if ($this->cart[$productId]['qty'] < $maxAllowed) {
                $this->cart[$productId]['qty']++;
            } else {
                $this->addError('cart', 'Cannot add more. Exceeds available stock.');
            }
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->selling_price,
                'qty' => 1,
            ];
        }
        
        $this->recalculateCoupon();
    }

    public function updateQty($productId, $qty)
    {
        if ($qty <= 0) {
            unset($this->cart[$productId]);
        } else {
            $product = Product::find($productId);
            $maxAllowed = $product ? ($product->stock + ($this->original_cart[$productId]['qty'] ?? 0)) : 0;
            if ($product && $qty <= $maxAllowed) {
                $this->cart[$productId]['qty'] = $qty;
            } else {
                $this->addError('cart', 'Invalid quantity or exceeds stock.');
            }
        }
        $this->recalculateCoupon();
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
        $this->recalculateCoupon();
    }

    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
    }
    
    public function getDiscountProperty()
    {
        $discount = 0;
        $subtotal = $this->subtotal;
        
        // Member Discount
        if ($this->member_id) {
            $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
            
            $benefit = App\Models\MemberBenefit::where('admin_id', $adminId)
                ->where('is_active', true)
                ->where('min_purchase', '<=', $subtotal)
                ->orderBy('min_purchase', 'desc')
                ->first();
                
            if ($benefit) {
                $discount += $benefit->discount_nominal;
            }
        }
        
        // Coupon Discount
        if ($this->applied_coupon) {
            if ($subtotal < $this->applied_coupon['min_purchase']) {
                $this->removeCoupon();
                $this->coupon_error = 'Kupon dibatalkan karena total belanja kurang dari batas minimal.';
            } else {
                if ($this->applied_coupon['type'] === 'percentage') {
                    $discount += $subtotal * ($this->applied_coupon['value'] / 100);
                } else {
                    $discount += $this->applied_coupon['value'];
                }
            }
        }
        
        return $discount > $subtotal ? $subtotal : $discount;
    }
    
    public function getTotalProperty()
    {
        return $this->subtotal - $this->discount;
    }
    
    public function getCashbackInfoProperty()
    {
        if (!$this->member_id) return 0;
        
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        
        $benefit = App\Models\MemberBenefit::where('admin_id', $adminId)
            ->where('is_active', true)
            ->where('min_purchase', '<=', $this->subtotal)
            ->orderBy('min_purchase', 'desc')
            ->first();
            
        return $benefit ? $benefit->cashback_nominal : 0;
    }
    
    public function applyCoupon()
    {
        $this->coupon_error = '';
        if (empty($this->coupon_code)) return;
        
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        
        $coupon = Coupon::where('admin_id', $adminId)
                        ->where('code', strtoupper($this->coupon_code))
                        ->first();
                        
        if (!$coupon) {
            $this->coupon_error = 'Kupon tidak ditemukan.';
            return;
        }
        
        if (!$coupon->is_active) {
            $this->coupon_error = 'Kupon sudah tidak aktif.';
            return;
        }
        
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            $this->coupon_error = 'Kupon sudah kedaluwarsa.';
            return;
        }
        
        if ($this->subtotal < $coupon->min_purchase) {
            $this->coupon_error = 'Minimal belanja Rp ' . number_format($coupon->min_purchase, 0, ',', '.') . ' untuk menggunakan kupon ini.';
            return;
        }
        
        $this->applied_coupon = $coupon->toArray();
        $this->coupon_code = '';
    }
    
    public function removeCoupon()
    {
        $this->applied_coupon = null;
        $this->coupon_error = '';
    }
    
    public function recalculateCoupon()
    {
        // This is implicitly called after cart changes to ensure discount stays valid
        $this->discount; 
    }

    public function checkout()
    {
        if (empty($this->cart)) return;

        $transactionIdToPay = null;

        try {
            DB::transaction(function () use (&$transactionIdToPay) {
                $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
                
                $transaction = null;
                if ($this->transaction_id) {
                    $transaction = Transaction::where('admin_id', $adminId)->find($this->transaction_id);
                }

                if (!$transaction) {
                    $invoiceNumber = 'INV-' . time() . '-' . rand(1000, 9999);
                    $transaction = Transaction::create([
                        'invoice_number' => $invoiceNumber,
                        'admin_id' => $adminId,
                        'operator_id' => auth()->id(),
                        'member_id' => $this->member_id ?: null,
                        'customer_name' => $this->customer_name ?: null,
                        'coupon_id' => $this->applied_coupon ? $this->applied_coupon['id'] : null,
                        'discount_amount' => 0,
                        'total_cost' => 0,
                        'total_amount' => 0,
                        'profit' => 0,
                        'payment_method' => 'unpaid',
                        'status' => 'pending',
                        'paid_amount' => 0,
                    ]);
                } else {
                    $invoiceNumber = $transaction->invoice_number;
                    // Revert old items' stock
                    $oldItems = TransactionItem::where('transaction_id', $transaction->id)->get();
                    foreach ($oldItems as $oldItem) {
                        $p = Product::lockForUpdate()->find($oldItem->product_id);
                        if ($p) {
                            $p->increment('stock', $oldItem->qty);
                        }
                    }
                    TransactionItem::where('transaction_id', $transaction->id)->delete();
                }

                $newCost = 0;
                $newAmount = 0;

                foreach ($this->cart as $productId => $item) {
                    $product = Product::where('id', $productId)->lockForUpdate()->first();
                    
                    if ($product->stock < $item['qty']) {
                        throw new \Exception("Stok tidak mencukupi untuk produk {$product->name}. Sisa stok: {$product->stock}");
                    }

                    $stockBefore = $product->stock;
                    $product->decrement('stock', $item['qty']);
                    
                    $subtotalCost = $product->cost_price * $item['qty'];
                    $subtotalAmount = $item['price'] * $item['qty'];

                    $newCost += $subtotalCost;
                    $newAmount += $subtotalAmount;

                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'qty' => $item['qty'],
                        'unit_cost_price' => $product->cost_price,
                        'unit_selling_price' => $item['price'],
                        'subtotal' => $subtotalAmount,
                    ]);

                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'sale',
                        'qty' => $item['qty'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $product->stock,
                        'reference_number' => $invoiceNumber,
                        'notes' => 'Sale transaction',
                    ]);
                }
                
                $finalNewAmount = $newAmount - $this->discount;

                $transaction->total_cost = $newCost;
                $transaction->total_amount = $finalNewAmount;
                $transaction->profit = $finalNewAmount - $newCost;
                $transaction->discount_amount = $this->discount;
                $transaction->member_id = $this->member_id ?: null;
                $transaction->customer_name = $this->customer_name ?: null;
                $transaction->coupon_id = $this->applied_coupon ? $this->applied_coupon['id'] : null;
                
                // Recalculate status
                if ($transaction->paid_amount >= $transaction->total_amount && $transaction->total_amount > 0) {
                    $transaction->status = 'completed';
                } else if ($transaction->paid_amount > 0) {
                    $transaction->status = 'partial';
                } else {
                    $transaction->status = 'pending';
                }
                
                $transaction->save();
                
                // Add points/cashback to member if applicable
                if ($this->member_id) {
                    $member = Member::find($this->member_id);
                    if ($member) {
                        $benefit = App\Models\MemberBenefit::where('admin_id', $adminId)
                            ->where('is_active', true)
                            ->where('min_purchase', '<=', $this->subtotal)
                            ->orderBy('min_purchase', 'desc')
                            ->first();
                        
                        if (!$this->transaction_id && $benefit && $benefit->cashback_nominal > 0) {
                            $member->increment('points', $benefit->cashback_nominal);
                        }
                    }
                }
                $transactionIdToPay = $transaction->id;
            });

            $this->showCheckoutModal = false;
            $this->success('Tagihan berhasil disimpan.');
            $this->redirect(route('operator.history', ['pay' => $transactionIdToPay]), navigate: true);
        } catch (\Exception $e) {
            $this->addError('cart', $e->getMessage());
        }
    }
    public function getExistingTransactionProperty()
    {
        if (!$this->transaction_id) return null;
        $adminId = auth()->user()->role === 'admin' ? auth()->id() : auth()->user()->admin_id;
        return Transaction::where('admin_id', $adminId)->find($this->transaction_id);
    }
};
?>

<div class="flex flex-col h-full gap-4">
    @if($this->existingTransaction)
        <div class="bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-700 dark:text-blue-400 p-4 rounded-xl flex items-center justify-between">
            <div>
                <strong class="font-bold">Mode Buka Tagihan:</strong> Anda sedang menambahkan pesanan baru ke <strong>{{ $this->existingTransaction->invoice_number }}</strong>.
            </div>
            <x-button label="Batal" link="{{ route('operator.history') }}" wire:navigate class="btn-outline btn-sm" />
        </div>
    @endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1">
    <!-- Product List -->
    <div class="lg:col-span-2 flex flex-col">
        <x-card class="flex-1 bg-base-100 shadow-sm border border-base-200">
            <div class="mb-4">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Cari nama produk atau SKU..." icon="o-magnifying-glass" />
            </div>
            @error('cart')
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">{{ $message }}</div>
            @enderror
            
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($this->products as $product)
                    <div wire:click="addToCart({{ $product->id }})" class="relative p-4 border border-base-200 rounded-xl bg-base-100 hover:border-primary hover:shadow-lg transition-all duration-200 cursor-pointer flex flex-col justify-between min-h-[140px] group">
                        
                        <!-- Top: SKU and Stock -->
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[10px] uppercase font-bold tracking-wider text-base-content/50 group-hover:text-primary transition-colors">{{ $product->sku }}</span>
                            @if($product->stock <= $product->min_stock_alert)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-error/10 text-error">
                                    Sisa {{ $product->stock }}
                                </span>
                            @else
                                <span class="text-xs text-base-content/60">
                                    Stok: {{ $product->stock }}
                                </span>
                            @endif
                        </div>
                        
                        <!-- Middle: Product Name -->
                        <div class="font-semibold text-sm leading-snug text-base-content mb-4 line-clamp-2" title="{{ $product->name }}">
                            {{ $product->name }}
                        </div>
                        
                        <!-- Bottom: Price -->
                        <div class="mt-auto">
                            <div class="text-primary font-bold text-lg leading-none">
                                Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                            </div>
                        </div>
                        
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <!-- Cart Sidebar -->
    <div class="lg:col-span-1">
        <div class="lg:sticky lg:top-4">
            <div class="bg-base-100 shadow-sm border border-base-200 rounded-2xl p-4 h-auto lg:h-[calc(100vh-6.5rem)] lg:max-h-[calc(100vh-6.5rem)] flex flex-col">
                <div class="flex items-center justify-between mb-3 shrink-0">
                    <h3 class="text-lg font-bold text-base-content flex items-center gap-2">
                        <x-icon name="o-shopping-bag" class="w-5 h-5 text-primary" />
                        Keranjang Belanja
                    </h3>
                    <span class="badge badge-primary badge-sm font-semibold">{{ collect($cart)->sum('qty') }} item</span>
                </div>
                
                <div class="flex-1 overflow-y-auto overflow-x-hidden my-2 pr-1 border-y border-base-200 divide-y divide-base-200 min-h-[140px] max-h-[280px] lg:max-h-none">
                    @forelse($cart as $id => $item)
                        <div class="flex justify-between items-center py-3 w-full gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium leading-tight line-clamp-1 mb-1 text-base-content">{{ $item['name'] }}</div>
                                <div class="text-xs text-primary font-semibold">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <input type="number" wire:model.live.debounce.500ms="cart.{{ $id }}.qty" class="input input-bordered input-sm w-16 text-center font-bold text-base-content bg-base-100" min="1" />
                                <x-button wire:click="removeFromCart({{ $id }})" icon="o-trash" class="btn-error btn-sm btn-square text-white shrink-0" />
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-12 text-base-content/50">
                            <x-icon name="o-shopping-bag" class="w-12 h-12 mb-2 opacity-30" />
                            <div class="text-sm">Keranjang masih kosong</div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-auto shrink-0 flex flex-col gap-3 pt-2">
                    <!-- Totals -->
                    <div class="bg-base-200/50 p-3.5 rounded-xl space-y-2 border border-base-200">
                        <div class="flex justify-between items-center text-sm text-base-content/70">
                            <span>Subtotal</span>
                            <span class="font-medium text-base-content">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                        </div>
                        
                        @if($this->discount > 0)
                            <div class="flex justify-between items-center text-sm text-success font-medium">
                                <span>Diskon</span>
                                <span>- Rp {{ number_format($this->discount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        
                        <div class="border-t border-base-200 pt-2 flex justify-between items-center text-base font-bold text-base-content">
                            <span>Total Bayar</span>
                            <span class="text-primary text-xl">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                        </div>
                        
                        @if($this->cashbackInfo > 0)
                            <div class="mt-2 flex justify-between items-center text-xs text-info p-2 bg-info/10 rounded-lg border border-info/20">
                                <span class="flex items-center gap-1 font-medium"><x-icon name="o-sparkles" class="w-4 h-4" /> Cashback Poin</span>
                                <span class="font-bold">+ {{ number_format($this->cashbackInfo, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <x-button wire:click="$set('showCheckoutModal', true)" label="Simpan Tagihan (Open Bill)" class="btn-primary w-full text-base font-bold shadow-md" :disabled="empty($cart)" />
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile & Tablet Sticky Bottom Floating Checkout Bar -->
@if(!empty($cart))
    <div class="lg:hidden fixed bottom-4 left-4 right-4 z-40 bg-base-100/95 backdrop-blur-md border border-base-300 shadow-2xl rounded-2xl p-3 flex items-center justify-between">
        <div>
            <div class="text-xs text-base-content/70">{{ collect($cart)->sum('qty') }} item di keranjang</div>
            <div class="font-bold text-primary text-base">Rp {{ number_format($this->total, 0, ',', '.') }}</div>
        </div>
        <x-button wire:click="$set('showCheckoutModal', true)" label="Tagihan / Bayar" icon="o-shopping-bag" class="btn-primary btn-sm font-bold shadow-sm" />
    </div>
@endif

<x-modal wire:model="showCheckoutModal" title="Pelanggan & Promo" subtitle="Pilih member dan masukkan kupon sebelum menyimpan tagihan (opsional).">
    <!-- Member Selection -->
    <div class="space-y-4">
        <x-input wire:model="customer_name" label="Nama Pembeli / No Meja" placeholder="Contoh: Budi atau Meja 4..." />
        
        @php
            $memberOptions = array_merge(
                [['id' => '', 'name' => '-- Bukan Member --']],
                $this->members->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name . ($m->phone ? ' ('.$m->phone.')' : '')
                ])->toArray()
            );
        @endphp

        <x-select wire:model.live="member_id" label="Atau Pilih Member (Opsional)" :options="$memberOptions" />
    </div>

    <!-- Coupon Section -->
    <div class="mt-4">
        @if($applied_coupon)
            <div class="flex items-center justify-between p-3 bg-success/10 border border-success/20 rounded-lg">
                <div class="flex items-center gap-2 text-success">
                    <x-icon name="o-ticket" class="w-5 h-5" />
                    <div class="text-sm font-bold">{{ $applied_coupon['code'] }} applied!</div>
                </div>
                <x-button wire:click="removeCoupon" icon="o-x-mark" class="btn-ghost btn-sm text-success" />
            </div>
        @else
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <x-input wire:model="coupon_code" label="Kupon Diskon" placeholder="Masukkan kode promo" style="text-transform: uppercase" />
                </div>
                <x-button wire:click="applyCoupon" label="Gunakan" class="btn-outline" />
            </div>
        @endif
        
        @if($coupon_error)
            <div class="text-xs text-error mt-1">{{ $coupon_error }}</div>
        @endif
    </div>

    <!-- Totals summary for modal -->
    <div class="bg-base-200/50 p-3 rounded-lg border border-base-200 mt-4">
        <div class="flex justify-between items-center font-bold">
            <span>Total Akhir</span>
            <span class="text-orange-600 dark:text-orange-500">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
        </div>
        @if($this->cashbackInfo > 0)
            <div class="text-xs text-info mt-1 flex items-center gap-1">
                <x-icon name="o-sparkles" class="w-3 h-3" /> +{{ number_format($this->cashbackInfo, 0, ',', '.') }} Poin Cashback
            </div>
        @endif
    </div>

    <x-slot:actions>
        <x-button label="Batal" wire:click="$set('showCheckoutModal', false)" class="btn-ghost" />
        <x-button label="Konfirmasi & Simpan" wire:click="checkout" class="btn-primary" spinner="checkout" />
    </x-slot:actions>
</x-modal>
</div>