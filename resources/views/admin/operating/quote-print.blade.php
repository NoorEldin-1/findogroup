@php $locale=app()->getLocale(); $fmt=fn($n)=>number_format((float)$n,2); @endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale==='ar'?'rtl':'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>{{ $number }} — Findo Group</title>
<style>
@page{size:A4;margin:14mm}
*{box-sizing:border-box}
body{margin:0;background:#e9ecf0;color:#16202b;font:14px/1.6 "Segoe UI",Tahoma,"Noto Sans Arabic",system-ui,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.toolbar{max-width:210mm;margin:16px auto 0;padding:0 16px;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}
.toolbar a,.toolbar button{font:inherit;padding:9px 16px;border-radius:10px;border:1px solid #c5ccd5;background:#fff;color:#16202b;text-decoration:none;cursor:pointer}
.toolbar button{background:#f58a2c;border-color:#f58a2c;color:#1d0e02;font-weight:600}
.toolbar :focus-visible{outline:3px solid #2b6fd6;outline-offset:2px}
.sheet{position:relative;max-width:210mm;margin:16px auto 32px;background:#fff;padding:16mm 15mm;box-shadow:0 2px 10px rgba(16,24,40,.12);overflow:hidden}
.watermark{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;font-size:68px;font-weight:800;color:rgba(200,40,40,.09);transform:rotate(-22deg)}
header{display:flex;justify-content:space-between;align-items:flex-start;gap:24px;padding-bottom:14px;border-bottom:3px solid #213a4c;margin-bottom:18px}
header img{max-height:44px;width:auto}
header h1{margin:10px 0 0;font-size:24px;color:#213a4c}
.meta{display:grid;grid-template-columns:auto auto;gap:2px 10px;font-size:13px;text-align:start}
.meta dt{color:#5d6b7a}.meta dd{margin:0;font-weight:600}
.parties{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
.parties section{background:#f5f7f9;border-radius:10px;padding:12px 14px}
.parties h2{margin:0 0 4px;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#5d6b7a}
.parties b{font-size:15px}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{padding:8px 6px;border-bottom:1px solid #e1e5ea;text-align:start;vertical-align:top}
thead th{background:#213a4c;color:#fff;font-weight:600}
.num{text-align:end;font-variant-numeric:tabular-nums;white-space:nowrap}
.totals{width:min(320px,100%);margin-top:14px;margin-inline-start:auto}
.totals td{border:0;padding:4px 6px}
.totals .grand td{border-top:2px solid #213a4c;font-weight:700;font-size:16px;padding-top:8px}
.terms{margin-top:22px;font-size:13px}
.terms h2{margin:14px 0 4px;font-size:13px;color:#213a4c}
.terms p{margin:0;white-space:pre-line}
footer{margin-top:28px;padding-top:10px;border-top:1px solid #e1e5ea;font-size:12px;color:#5d6b7a;display:flex;justify-content:space-between;gap:12px}
@media print{body{background:#fff}.toolbar{display:none}.sheet{margin:0;box-shadow:none;padding:0;max-width:none}thead{display:table-header-group}tr{break-inside:avoid}}
@media (max-width:640px){.sheet{margin:12px;padding:18px}.parties{grid-template-columns:1fr}header{flex-direction:column}.lines{overflow-x:auto}.lines table{min-width:560px}}
</style>
</head>
<body>
<div class="toolbar"><a href="/admin/operations/record/{{ $quote->id }}">{{ __('العودة للعرض') }}</a><button type="button" onclick="window.print()">{{ __('تحميل PDF / طباعة') }}</button></div>
<main class="sheet">
@if($draft)<div class="watermark" data-draft aria-hidden="true">{{ __('مسودة — غير معتمد') }}</div>@endif
<header>
<div>@include('admin.brand-logo')<h1>{{ __('عرض سعر') }}</h1></div>
<dl class="meta"><dt>{{ __('رقم العرض') }}</dt><dd dir="ltr">{{ $number }}</dd><dt>{{ __('التاريخ') }}</dt><dd>{{ $issued }}</dd>@if($valid_until)<dt>{{ __('صالح حتى') }}</dt><dd>{{ $valid_until }}</dd>@endif<dt>{{ __('الحالة') }}</dt><dd>{{ $catalog->status($status) }}</dd></dl>
</header>
<div class="parties">
<section><h2>{{ __('مقدم إلى') }}</h2><b>{{ $client['name'] ?? '—' }}</b>@foreach(['contact','email','phone','address'] as $key)@if($client[$key])<div>{{ $client[$key] }}</div>@endif @endforeach @if($client['tax_number'])<div>{{ __('الرقم الضريبي') }}: {{ $client['tax_number'] }}</div>@endif</section>
<section><h2>{{ __('من') }}</h2><b>Findo Group</b><div dir="ltr">findogroup.com</div></section>
</div>
@if($lines)<div class="lines"><table><thead><tr><th>#</th><th>{{ __('الوصف') }}</th><th>{{ __('النوع') }}</th><th class="num">{{ __('الكمية') }}</th><th class="num">{{ __('سعر الوحدة') }}</th><th class="num">{{ __('خصم %') }}</th><th class="num">{{ __('ضريبة %') }}</th><th class="num">{{ __('الإجمالي') }}</th></tr></thead><tbody>
@foreach($lines as $i=>$line)@php $net=$line['quantity']*$line['unit_price']*(1-$line['discount']/100); @endphp<tr><td>{{ $i+1 }}</td><td>{{ $line['description'] }}</td><td>{{ $line['kind']==='product'?__('منتج'):__('خدمة') }}</td><td class="num">{{ rtrim(rtrim(number_format($line['quantity'],2,'.',''),'0'),'.') }}</td><td class="num">{{ $fmt($line['unit_price']) }}</td><td class="num">{{ (float)$line['discount'] }}</td><td class="num">{{ (float)$line['tax'] }}</td><td class="num">{{ $fmt($net*(1+$line['tax']/100)) }}</td></tr>@endforeach
</tbody></table></div>@endif
<table class="totals">
@if($totals)<tr><td>{{ __('الإجمالي قبل الخصم') }}</td><td class="num">{{ $fmt($totals['subtotal']) }}</td></tr>@if($totals['line_discount'])<tr><td>{{ __('خصم البنود') }}</td><td class="num">−{{ $fmt($totals['line_discount']) }}</td></tr>@endif<tr><td>{{ __('الضريبة') }}</td><td class="num">{{ $fmt($totals['tax']) }}</td></tr>@if($totals['discount'])<tr><td>{{ __('الخصم الإضافي') }}</td><td class="num">−{{ $fmt($totals['discount']) }}</td></tr>@endif @endif
<tr class="grand"><td>{{ __('الإجمالي النهائي') }}</td><td class="num" dir="ltr">{{ $fmt($totals['total'] ?? $price) }} {{ $currency }}</td></tr>
</table>
@if($payment_terms || $terms)<section class="terms">@if($payment_terms)<h2>{{ __('شروط الدفع') }}</h2><p>{{ $payment_terms }}</p>@endif @if($terms)<h2>{{ __('الشروط والملاحظات') }}</h2><p>{{ $terms }}</p>@endif</section>@endif
<footer><span>Findo Group · findogroup.com</span><span dir="ltr">{{ $number }}</span></footer>
</main>
</body>
</html>
