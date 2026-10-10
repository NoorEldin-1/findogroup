@extends('admin.layout')
@section('title',$client->title)
@section('content')
@php
$sectionLabels=['contacts'=>__('جهات الاتصال'),'opportunities'=>__('الفرص'),'activities'=>__('المتابعات والأنشطة'),'quotes'=>__('العروض'),'contracts'=>__('العقود'),'projects'=>__('المشروعات'),'invoices'=>__('الفواتير'),'receipts'=>__('التحصيل'),'support'=>__('تذاكر الدعم')];
$clientFields=collect($catalog->entity('clients')['fields'])->keyBy('key');
$money=fn(array $amounts)=>$amounts?collect($amounts)->map(fn($amount,$currency)=>number_format($amount,2).' '.$currency)->implode(' · '):'0';
@endphp
<div class="page-heading"><div><span class="eyebrow">{{ __('ملف العميل') }}</span><h1>{{ $client->title }}</h1><p class="muted"><span class="badge">{{ $catalog->status($client->status) }}</span> {{ collect([$data['industry'] ?? null,$data['country'] ?? null])->filter()->implode(' · ') }}</p></div><div class="row-actions"><a class="button secondary" href="/admin/operations/record/{{ $client->id }}">{{ __('تعديل بيانات العميل') }}</a></div></div>
<section class="card"><dl class="payload">@foreach(['company','decision_maker','contact','phone','email','website','address','tax_number'] as $key)@if(!empty($data[$key]) && $clientFields->has($key))<dt>{{ $catalog->label($clientFields[$key]['label']) }}</dt><dd class="wrap">{{ $data[$key] }}</dd>@endif @endforeach</dl></section>
<section class="card"><div class="inline-stats"><span>{{ __('فرص مفتوحة') }}: <b>{{ $stats['open_leads'] }}</b></span><span>{{ __('قيمة الصفقات المكسوبة') }}: <b dir="ltr">{{ $money($stats['won_value']) }}</b></span><span>{{ __('مشروعات جارية') }}: <b>{{ $stats['active_projects'] }}</b></span>
@if($outstanding!==null)<span>{{ __('المتبقي على العميل') }}: <b dir="ltr" class="{{ array_sum($outstanding)>0?'danger':'' }}">{{ $money($outstanding) }}</b></span><span>{{ __('المحصّل') }}: <b dir="ltr">{{ $money($collected) }}</b></span>@endif</div></section>
<div class="operating-editor"><div>
@foreach($sectionLabels as $type=>$label)@if($sections[$type]!==null)<section class="card"><h2>{{ $label }} <small class="muted">{{ $sections[$type]->count() }}</small></h2>@forelse($sections[$type] as $row)<a class="operation-row" href="/admin/operations/record/{{ $row->id }}"><b>{{ $row->title }}</b><span>{{ $catalog->status($row->status) }}</span></a>@empty<p class="muted">{{ __('لا شيء هنا.') }}</p>@endforelse
@if(in_array($type,['contacts','activities','opportunities'],true) && $access->can(auth()->user(),$type,'write'))<a class="button secondary" href="/admin/operations/{{ $type }}/create">+ {{ __('إضافة') }}</a>@endif</section>@endif @endforeach
</div><aside><section class="card"><h2>{{ __('تاريخ العلاقة') }}</h2>@forelse($timeline as $item)<div class="revision-row"><div><b>{{ $item['title'] }}</b><small class="path">{{ $item['at'] }} · {{ $item['label'] }}</small></div></div>@empty<p class="muted">{{ __('لا شيء هنا.') }}</p>@endforelse</section></aside></div>
@endsection
