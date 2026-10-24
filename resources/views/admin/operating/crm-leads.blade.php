@extends('admin.layout')
@section('title',__('العملاء المحتملون'))
@section('content')
@php $canWrite=$access->can(auth()->user(),'opportunities','write'); $q=request()->query(); @endphp
<div class="page-heading"><div><span class="eyebrow">CRM</span><h1>{{ __('العملاء المحتملون') }}</h1><p class="muted">{{ __('إدارة ومتابعة جميع العملاء المحتملين') }}</p></div><div class="row-actions">@include('admin.head-stat',['value'=>$page->total(),'label'=>__('إجمالي العملاء')])
<details class="head-menu"><summary class="button secondary">@include('admin.icon',['name'=>'download']){{ __('تصدير Excel') }}</summary><div class="head-menu-panel"><small class="muted">{{ __('يُطبَّق على الفلاتر الحالية، حتى 5,000 صف، ويُسجَّل في سجل العمليات.') }}</small>
<form method="post" action="/admin/operations/crm/leads/export" class="inline-form">@csrf @foreach($filters as $key=>$value)@if($value!==null && $value!=='')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach @foreach($columns as $column)<input type="hidden" name="columns[]" value="{{ $column }}">@endforeach
<label class="checkbox"><input type="checkbox" name="mask_phones" value="1" checked> {{ __('إخفاء أرقام الهاتف (آخر 4 أرقام فقط)') }}</label><input name="reason" placeholder="{{ __('سبب التصدير (اختياري)') }}" maxlength="2000"><button class="button">{{ __('تنزيل Excel') }}</button></form></div></details>
<a class="button secondary" href="/admin/operations/opportunities/pipeline">{{ __('خط البيع') }}</a>@if($crm->isManager(auth()->user()))<a class="button secondary" href="/admin/operations/crm/import">{{ __('استيراد') }}</a>@endif @if($canWrite)<a class="button" href="/admin/operations/opportunities/create">+ {{ __('عميل جديد') }}</a>@endif</div></div>
<form class="card filters filter-panel crm-filters" method="get" data-crm-columns-form data-filter-panel>@include('admin.filter-head',['subtitle'=>__('ابحث وفلتر العملاء للوصول للبيانات المطلوبة بسرعة')])
<label class="field wide"><span>{{ __('بحث') }}</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ابحث بالاسم أو الهاتف أو الشركة...') }}"></label>
<label class="field"><span>{{ __('نوع التاريخ') }}</span><select name="date_field">@foreach(['created'=>'تاريخ الإنشاء','contact'=>'أول تواصل','closed'=>'تاريخ الإغلاق'] as $key=>$label)<option value="{{ $key }}" @selected(($filters['date_field']??'created')===$key)>{{ __($label) }}</option>@endforeach</select></label>
<label class="field"><span>{{ __('من') }}</span><input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label><label class="field"><span>{{ __('إلى') }}</span><input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
@foreach(['stage'=>['stages','المرحلة'],'quality'=>['quality','جودة العميل'],'fit'=>['fit','مدى الملاءمة'],'source'=>['source','المصدر'],'channel'=>['channel','قناة الوارد'],'campaign'=>['campaigns','الحملة'],'assignee'=>['assignees','المسؤول'],'added_by'=>['users','أضيف بواسطة']] as $name=>[$list,$label])@if($options[$list])<label class="field"><span>{{ __($label) }}</span><select name="{{ $name }}"><option value="">{{ __('الكل') }}</option>@foreach($options[$list] as $value=>$text)<option value="{{ $value }}" @selected((string)($filters[$name]??'')===(string)$value)>{{ $text }}</option>@endforeach</select></label>@endif @endforeach
<label class="field"><span>{{ __('لكل صفحة') }}</span><select name="per_page">@foreach(\App\Services\OperatingLeads::PER_PAGE as $size)<option value="{{ $size }}" @selected($perPage===$size)>{{ $size }}</option>@endforeach</select></label>
<label class="checkbox"><input type="checkbox" name="sla" value="breached" @checked(($filters['sla']??null)==='breached')> {{ __('تجاوز SLA فقط') }}</label>
<div class="filter-actions"><details class="crm-columns-picker"><summary>{{ __('الأعمدة') }}</summary><div class="crm-columns">@foreach(\App\Services\OperatingLeads::COLUMNS as $key=>$label)<label class="checkbox"><input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key,$columns,true)) @disabled($key==='title')> {{ __($label) }}</label>@endforeach<input type="hidden" name="columns[]" value="title"></div></details>
<button class="button secondary">{{ __('تصفية') }}</button><a class="text-button" href="/admin/operations/crm/leads" data-crm-columns-reset>{{ __('إعادة ضبط') }}</a></div>
</form>
<section class="card table-card crm-table-wrap"><table><thead><tr>@foreach($columns as $column)<th>{{ __(\App\Services\OperatingLeads::COLUMNS[$column]) }}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($columns as $column)<td>@switch($column)
@case('title')<a class="item-title" href="/admin/operations/crm/leads/{{ $row['id'] }}">{{ $row['title'] }}</a>@break
@case('lead_phone')<span dir="ltr">{{ $row['lead_phone'] ?? '—' }}</span>@break
@case('status')<span class="badge {{ $row['status_key'] ?? '' }}">{{ $row['status'] }}</span>@break
@case('sla')@if($row['sla'])<span class="sla-badge {{ $row['sla'] }}">{{ __(\App\Services\OperatingCrm::SLA_LABELS[$row['sla']]) }}</span>@else — @endif @break
@case('expected_value')<span dir="ltr">{{ $row['expected_value'] ?? '—' }}</span>@break
@default{{ $row[$column] ?? '—' }}
@endswitch</td>@endforeach</tr>@empty<tr><td colspan="{{ count($columns) }}" class="muted">{{ __('لا توجد بيانات') }}</td></tr>@endforelse
</tbody></table></section>
<div class="pagination">{{ $page->links() }}</div>
@endsection
