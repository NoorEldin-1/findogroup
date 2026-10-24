@extends('admin.layout')
@section('title',__('استيراد العملاء'))
@section('content')
<div class="page-heading"><div>@include('admin.page-icon',['name'=>'download'])<span class="eyebrow">CRM</span><h1>{{ __('استيراد العملاء') }}</h1><p class="muted">{{ __('ارفع ملف Excel أو CSV، وسنربط الأعمدة تلقائيًا ثم تعاين قبل التنفيذ.') }}</p></div></div>
@include('admin.operating.crm-nav')
<ol class="stepper"><li class="current">{{ __('رفع الملف') }}</li><li>{{ __('ربط الأعمدة') }}</li><li>{{ __('معاينة') }}</li><li class="{{ $result?'current':'' }}">{{ __('اكتمل') }}</li></ol>
@if($result)<section class="card"><h2>{{ __('اكتمل الاستيراد') }}</h2><div class="kpi-grid"><div class="kpi"><strong>{{ $result['created'] }}</strong><span>{{ __('عملاء جدد') }}</span></div><div class="kpi"><strong>{{ $result['duplicates'] }}</strong><span>{{ __('تكرارات تم تخطيها') }}</span></div><div class="kpi {{ $result['failed']?'warn':'' }}"><strong>{{ count($result['failed']) }}</strong><span>{{ __('صفوف مرفوضة') }}</span></div></div>
@if($result['failed'])<h3>{{ __('الصفوف المرفوضة') }}</h3><ul>@foreach($result['failed'] as $row)<li>{{ __('صف') }} {{ $row['row'] }}: {{ $row['error'] }}</li>@endforeach</ul>@endif
@if($result['warnings'])<details><summary>{{ __('ملاحظات على الصفوف') }} ({{ count($result['warnings']) }})</summary><ul>@foreach($result['warnings'] as $row)<li>{{ __('صف') }} {{ $row['row'] }}: {{ $row['warning'] }}</li>@endforeach</ul></details>@endif
<a class="button" href="/admin/operations/crm/leads">{{ __('عرض العملاء') }}</a></section>@endif
<form method="post" action="/admin/operations/crm/import" enctype="multipart/form-data" class="card upload-form">@csrf<label>{{ __('الملف') }} (.xlsx · .csv)<input type="file" name="file" accept=".xlsx,.csv,.txt" required></label><button class="button">{{ __('رفع ومتابعة') }}</button>
<p class="help">{{ __('الحد الأقصى 2000 صف و5MB. الأعمدة المدعومة:') }} {{ collect($fields)->map(fn($label)=>__($label))->implode('، ') }}</p></form>
@endsection
