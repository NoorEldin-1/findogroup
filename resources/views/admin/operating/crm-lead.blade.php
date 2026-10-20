@extends('admin.layout')
@section('title',$lead->title)
@section('content')
@php
$fieldMap=collect($fields)->keyBy('key');
$show=function(string $key) use ($fieldMap,$data,$catalog,$titles){$value=$data[$key]??null;if($value===null||$value===''){return null;}$field=$fieldMap[$key]??null;if(!$field){return $value;}if(($field['type']??'')==='select'){return isset($field['options'][$value])?$catalog->label($field['options'][$value]):$value;}if(($field['type']??'')==='relation'){return ['client_id'=>$titles['client'],'campaign_id'=>$titles['campaign'],'assignee_id'=>$titles['assignee']][$key]??('#'.$value);}return is_string($value)?str_replace('T',' ',$value):$value;};
$funnel=\App\Services\OperatingCrm::FUNNEL; $current=array_search($lead->status,$funnel,true);
@endphp
<div class="page-heading"><div><span class="eyebrow">{{ __('ملف العميل المحتمل') }} · #{{ $lead->id }}</span><h1>{{ $lead->title }}</h1><p class="muted">{{ $data['lead_company'] ?? $titles['client'] ?? '' }} @if(!empty($data['lead_phone']))· <a dir="ltr" href="tel:{{ preg_replace('/[^\d+]/','',$data['lead_phone']) }}">{{ $data['lead_phone'] }}</a> · <a href="https://wa.me/{{ preg_replace('/\D/','',$data['lead_phone']) }}" target="_blank" rel="noopener">WhatsApp ↗</a>@endif</p></div>
<div class="row-actions"><a class="button secondary" href="/admin/operations/crm/leads">{{ __('العملاء المحتملون') }}</a>@if($canWrite)<a class="button secondary" href="/admin/operations/record/{{ $lead->id }}">{{ __('تعديل البيانات') }}</a>@endif</div></div>

<ol class="stepper" aria-label="{{ __('مراحل العميل') }}">@foreach($funnel as $index=>$stage)<li class="{{ $current!==false && $index<$current ? 'done' : ($stage===$lead->status ? 'current' : '') }}">{{ $catalog->status($stage) }}</li>@endforeach @if($current===false)<li class="current">{{ $catalog->status($lead->status) }}</li>@endif</ol>

<div class="kpi-grid">
<div class="kpi {{ $sla==='breached'?'warn':'' }}"><span class="kpi-icon">⏱</span><span>SLA</span><strong>@if($sla)<span class="sla-badge {{ $sla }}">{{ __(\App\Services\OperatingCrm::SLA_LABELS[$sla]) }}</span>@else — @endif</strong><small>@if($responseHours!==null){{ __('زمن الاستجابة') }}: {{ $responseHours }} {{ __('ساعة') }}@elseif(!empty($data['sla_due_at'])){{ __('المهلة حتى') }} {{ str_replace('T',' ',$data['sla_due_at']) }}@endif</small></div>
<div class="kpi"><span class="kpi-icon">☎</span><span>{{ __('آخر تواصل') }}</span><strong>{{ $stats['last_contact'] ?? '—' }}</strong></div>
<div class="kpi"><span class="kpi-icon">➜</span><span>{{ __('الإجراء التالي') }}</span><strong style="font-size:1rem">{{ $stats['next'] ?? '—' }}</strong></div>
<div class="kpi"><span class="kpi-icon">⧗</span><span>{{ __('أيام في المرحلة') }}</span><strong>{{ $stats['days_in_stage'] }}</strong></div>
<div class="kpi"><span class="kpi-icon">$</span><span>{{ __('القيمة المتوقعة') }}</span><strong dir="ltr">{{ isset($data['expected_value']) && $data['expected_value']!=='' ? number_format((float)$data['expected_value'],2).' '.(($data['currency'] ?? '') ?: 'USD') : '—' }}</strong><small>{{ isset($data['probability']) ? $data['probability'].'%' : '' }}</small></div>
</div>

@if($slaOpen && $isManager)<form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/sla-dismiss" class="notice error inline-form">@csrf<span>{{ __('هذا العميل تجاوز مهلة التواصل.') }}</span><input name="reason" required placeholder="{{ __('سبب إخفاء التنبيه') }}"><button class="button small secondary">{{ __('إخفاء التنبيه') }}</button></form>@elseif($slaOpen)<div class="notice error">{{ __('هذا العميل تجاوز مهلة التواصل. سجّل أول تواصل الآن.') }}</div>@endif

<div class="crm-grid"><div>
@if($access->can(auth()->user(),'activities','write'))<section class="card"><h2>{{ __('تسجيل نشاط') }}</h2><form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/activity" class="field-grid">@csrf
<label>{{ __('نوع النشاط') }}<select name="kind" required>@foreach($kinds as $key=>$label)<option value="{{ $key }}" @selected($key==='call')>{{ $label }}</option>@endforeach</select></label>
<label>{{ __('نتيجة التواصل') }}<select name="result"><option value="">—</option>@foreach($results as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
<label class="long">{{ __('ماذا حدث؟') }}<textarea name="outcome" required maxlength="5000"></textarea></label>
<label>{{ __('الخطوة التالية') }}<input name="next_step" maxlength="500"></label>
<label>{{ __('موعد المتابعة القادمة') }}<input type="datetime-local" name="follow_up_at"></label>
<button class="button">{{ __('حفظ النشاط') }}</button></form></section>@endif

<section class="card"><h2>{{ __('الخط الزمني') }}</h2>@if($timeline)<ol class="timeline">@foreach($timeline as $item)<li><b>{{ $item['kind'] }}@if($item['title']) · {{ $item['title'] }}@endif</b>@if($item['body'])<p>{{ $item['body'] }}</p>@endif<small>{{ $item['at'] }}@if($item['by']) · {{ $item['by'] }}@endif</small></li>@endforeach</ol>@else<p class="muted">{{ __('لا يوجد نشاط مسجل بعد. ابدأ بتسجيل أول تواصل.') }}</p>@endif</section>

<section class="card"><h2>{{ __('ملاحظة داخلية') }}</h2><form method="post" action="/admin/operations/record/{{ $lead->id }}/comments">@csrf<textarea name="body" required maxlength="10000" placeholder="{{ __('اكتب ملاحظة سريعة...') }}"></textarea><button class="button secondary">{{ __('إضافة ملاحظة') }}</button></form></section>
</div><div>
@if($canWrite)<section class="card"><h2>{{ __('نقل المرحلة') }}</h2><form method="post" action="/admin/operations/record/{{ $lead->id }}/transition">@csrf<input type="hidden" name="version" value="{{ $lead->version }}"><label>{{ __('المرحلة الجديدة') }}<select name="target" required>@foreach($nextStages as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label><label>{{ __('السبب') }}<textarea name="reason" required maxlength="2000"></textarea></label><button class="button">{{ __('تأكيد') }}</button></form><p class="help">{{ __('الخسارة والتأجيل يحتاجان سبب الخسارة في بيانات العميل، والتأهيل يحتاج الاحتياج والحل، والعرض والفوز يحتاجان تحويله لعميل.') }}</p>
@if(empty($data['client_id']) && $access->can(auth()->user(),'clients','write'))<form method="post" action="/admin/operations/opportunities/{{ $lead->id }}/convert-client" class="inline-form">@csrf<input name="reason" required placeholder="{{ __('سبب التحويل') }}"><button class="button secondary">{{ __('تحويل إلى عميل') }}</button></form>@endif</section>@endif

<section class="card"><h2>{{ __('بيانات العميل') }}</h2><dl class="crm-facts">@foreach(['lead_contact','lead_email','country','business_profile','lead_quality','fit_status','lead_source','lead_channel','campaign_id','ad_creative','service_introduced','lead_date','client_id','need','solution','deal_type','expected_close','notes','media_buyer_notes'] as $key)@php $value=$show($key); @endphp @if($value!==null)<dt>{{ $catalog->label($fieldMap[$key]['label'] ?? ['ar'=>$key,'en'=>$key]) }}</dt><dd>{{ $value }}</dd>@endif @endforeach</dl></section>

<section class="card"><h2>{{ __('الملكية والتعيين') }}</h2><p>{{ __('المسؤول') }}: <b>{{ $titles['assignee'] ?? __('غير مسند') }}</b></p>@if($isManager && $employees->isNotEmpty())<form method="post" action="/admin/operations/crm/leads/{{ $lead->id }}/handover">@csrf<label>{{ __('تسليم إلى') }}<select name="assignee_id" required>@foreach($employees as $id=>$name)<option value="{{ $id }}" @selected((int)($data['assignee_id']??0)===(int)$id)>{{ $name }}</option>@endforeach</select></label><label>{{ __('ملاحظات للزميل') }}<textarea name="note" required maxlength="2000"></textarea></label><button class="button secondary">{{ __('تسليم') }}</button></form>@endif</section>

<section class="card"><h2>{{ __('المهام والمتابعات القادمة') }}</h2>@forelse($upcoming as $activity)@php $activityData=$catalog->data($activity); @endphp<a class="operation-row" href="/admin/operations/record/{{ $activity->id }}"><b>{{ $activity->title }}</b><span class="{{ ($activityData['due_at']??'9999')<now()->format('Y-m-d\TH:i')?'danger':'' }}">{{ str_replace('T',' ',$activityData['due_at'] ?? '') }}</span></a>@empty<p class="muted">{{ __('لا توجد متابعات قادمة.') }}</p>@endforelse</section>

<section class="card"><h2>{{ __('رحلة المراحل') }}</h2><ol class="timeline">@foreach($journey as $step)<li><b>{{ $step['label'] }}</b><small>{{ $step['at'] }}@if($step['by']) · {{ $step['by'] }}@endif</small></li>@endforeach</ol></section>

@if($quotes->isNotEmpty())<section class="card"><h2>{{ __('العروض') }}</h2>@foreach($quotes as $quote)<a class="operation-row" href="/admin/operations/record/{{ $quote->id }}"><b>{{ $quote->title }}</b><span>{{ $catalog->status($quote->status) }}</span></a>@endforeach</section>@endif

<section class="card"><h2>{{ __('المرفقات') }}</h2>@forelse($attachments as $file)<a class="operation-row" href="/admin/operations/attachments/{{ $file->id }}">{{ $file->name }}</a>@empty<p class="muted">{{ __('لا توجد مرفقات.') }}</p>@endforelse @if($canWrite)<form method="post" action="/admin/operations/record/{{ $lead->id }}/attachments" enctype="multipart/form-data" class="upload-form">@csrf<input type="file" name="file" required><button class="button small secondary">{{ __('رفع') }}</button></form>@endif</section>
</div></div>
@endsection
