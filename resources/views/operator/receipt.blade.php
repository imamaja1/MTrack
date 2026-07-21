<div id="receipt-container" style="padding: 10px;">
    <div class="text-center bold" style="font-size: 16px; margin-bottom: 5px;">
        {{ $receipt['admin']['name'] ?? 'MTrack Store' }}
    </div>
    <div class="text-center" style="margin-bottom: 10px;">
        Date: {{ \Carbon\Carbon::parse($receipt['created_at'])->format('d/m/Y H:i') }}<br>
        Cashier: {{ $receipt['operator']['name'] ?? 'Unknown' }}<br>
        INV: {{ $receipt['invoice_number'] }}<br>
        Status: 
        @if($receipt['status'] === 'completed' || $receipt['status'] === 'paid')
            LUNAS
        @elseif($receipt['status'] === 'partial')
            BAYAR PARSIAL
        @else
            BELUM LUNAS
        @endif
    </div>
    
    <div class="divider"></div>
    
    <table>
        @foreach($receipt['items'] as $item)
        <tr>
            <td colspan="3" class="bold">{{ $item['product']['name'] }}</td>
        </tr>
        <tr>
            <td>{{ $item['qty'] }} x </td>
            <td>Rp{{ number_format($item['unit_selling_price'], 0, ',', '.') }}</td>
            <td class="text-right">Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>
    
    <div class="divider"></div>
    
    <table>
        @if(isset($receipt['customer_name']))
        <tr>
            <td>Pembeli</td>
            <td class="text-right">{{ $receipt['customer_name'] }}</td>
        </tr>
        @endif
        
        @if(isset($receipt['member']))
        <tr>
            <td>Member</td>
            <td class="text-right">{{ $receipt['member']['name'] }}</td>
        </tr>
        @endif
        
        <tr>
            <td>Subtotal</td>
            <td class="text-right">Rp{{ number_format(($receipt['total_amount'] ?? 0) + ($receipt['discount_amount'] ?? 0), 0, ',', '.') }}</td>
        </tr>
        
        @if(isset($receipt['discount_amount']) && $receipt['discount_amount'] > 0)
        <tr>
            <td>
                Diskon
                @if(isset($receipt['coupon']))
                    ({{ $receipt['coupon']['code'] }})
                @endif
                @if(isset($receipt['member']))
                    (Member)
                @endif
            </td>
            <td class="text-right">-Rp{{ number_format($receipt['discount_amount'], 0, ',', '.') }}</td>
        </tr>
        @endif
        
        <tr>
            <td class="bold">TOTAL TAGIHAN</td>
            <td class="text-right bold">Rp{{ number_format($receipt['total_amount'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Telah Dibayar</td>
            <td class="text-right">Rp{{ number_format($receipt['paid_amount'], 0, ',', '.') }}</td>
        </tr>
        @if($receipt['total_amount'] > $receipt['paid_amount'])
        <tr>
            <td class="bold">SISA TAGIHAN</td>
            <td class="text-right bold text-error">Rp{{ number_format(max(0, $receipt['total_amount'] - $receipt['paid_amount']), 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($receipt['paid_amount'] > 0)
        <tr>
            <td>PAYMENT METODE</td>
            <td class="text-right">{{ strtoupper($receipt['payment_method']) }}</td>
        </tr>
        @endif
    </table>
    
    <div class="divider"></div>
    <div class="text-center" style="margin-top: 10px;">
        Terima Kasih<br>
        Silahkan Datang Kembali
    </div>
</div>
