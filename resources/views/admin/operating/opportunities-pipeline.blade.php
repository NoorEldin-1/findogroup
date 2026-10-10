@extends('admin.layout')
@section('title',__('خط البيع'))
@section('content')
<div class="page-heading"><div><span class="eyebrow">{{ __('المبيعات') }}</span><h1>{{ __('خط البيع') }}</h1><p class="muted">{{ __('الفرص المفتوحة حسب المرحلة، مع القيمة المتوقعة والمتابعة القادمة.') }}</p></div><div class="row-actions"><a class="button secondary" href="/admin/operations/opportunities">{{ __('جدول الفرص') }}</a>@if($access->can(auth()->user(),'opportunities','write'))<a class="button" href="/admin/operations/opportunities/create">+ {{ __('فرصة جديدة') }}</a>@endif</div></div>
<section class="card"><div class="inline-stats">
<span>{{ __('فرص مفتوحة') }}: <b>{{ $summary['open'] }}</b></span>
<span>{{ __('الإيراد المتوقع المرجّح') }}: @forelse($summary['weighted'] as $currency=>$amount)<b dir="ltr">{{ number_format($amount,2) }} {{ $currency }}</b> @empty — @endforelse</span>
<span>{{ __('فوز هذا الشهر') }}: <b>{{ $summary['won_month'] }}</b> @foreach($summary['won_month_value'] as $currency=>$amount)<small dir="ltr">({{ number_format($amount,2) }} {{ $currency }})</small> @endforeach</span>
<span>{{ __('خسارة هذا الشهر') }}: <b>{{ $summary['lost_month'] }}</b></span>
<span>{{ __('نسبة الفوز') }}: <b>{{ $summary['win_rate']!==null ? $summary['win_rate'].'%' : '—' }}</b></span>
</div></section>
<form class="filters">@if($assignees->isNotEmpty())<select name="assignee" aria-label="{{ __('المندوب') }}"><option value="">{{ __('كل المندوبين') }}</option>@foreach($assignees as $id=>$name)<option value="{{ $id }}" @selected((string)($filters['assignee']??'')===(string)$id)>{{ $name }}</option>@endforeach</select>@endif<select name="source" aria-label="{{ __('مصدر الفرصة') }}"><option value="">{{ __('كل المصادر') }}</option>@foreach($sources as $key=>$label)<option value="{{ $key }}" @selected(($filters['source']??'')===$key)>{{ $catalog->label($label) }}</option>@endforeach</select><button class="button secondary">{{ __('تصفية') }}</button></form>
<div class="workflow-board">@foreach($columns as $stage=>$column)<section><h2>{{ $catalog->status($stage) }} <small class="muted">{{ $column['count'] }}</small></h2>@foreach($column['value'] as $currency=>$amount)<p class="muted" dir="ltr">{{ number_format($amount,2) }} {{ $currency }}</p>@endforeach
@forelse($column['cards'] as $card)<a class="card operation-row pipeline-card" href="/admin/operations/record/{{ $card['id'] }}"><b>{{ $card['title'] }}</b><small>{{ $card['company'] ?? '—' }} · {{ $card['assignee'] ?? __('غير مسند') }}</small>@if($card['value'])<small dir="ltr">{{ number_format($card['value'],2) }} {{ $card['currency'] }} · {{ $card['probability'] ?? 0 }}%</small>@endif @if($card['follow_up'])<small class="{{ $card['overdue']?'danger':'' }}">{{ __('المتابعة') }}: {{ $card['follow_up'] }}@if($card['overdue']) · {{ __('متأخرة') }}@endif</small>@endif</a>@empty<p class="muted">{{ __('لا شيء هنا.') }}</p>@endforelse
</section>@endforeach</div>
<p class="help">{{ __('لنقل فرصة بين المراحل افتح السجل واستخدم الانتقال مع السبب. الخادم يتحقق من البوابات.') }}</p>
@endsection
