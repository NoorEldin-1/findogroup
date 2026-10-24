@extends('admin.layout')
@section('title',__('إعدادات CRM'))
@section('content')
<div class="page-heading"><div>@include('admin.page-icon',['name'=>'settings'])<span class="eyebrow">CRM</span><h1>{{ __('إعدادات CRM') }}</h1><p class="muted">{{ __('مهلة التواصل مع العملاء الجدد، والتوزيع الدوري على فريق المبيعات. كل تغيير يُسجَّل بسببه في سجل العمليات.') }}</p></div></div>
<form method="post" action="/admin/operations/crm/settings" class="card narrow">@csrf<input type="hidden" name="version" value="{{ $version }}">
<h2>{{ __('مهلة التواصل (SLA)') }}</h2>
<label class="checkbox"><input type="checkbox" name="sla_enabled" value="1" @checked($settings['sla_enabled'])> {{ __('تفعيل SLA') }}</label>
<label>{{ __('عدد الساعات') }}<input type="number" name="sla_hours" min="1" max="720" value="{{ old('sla_hours',$settings['sla_hours']) }}" dir="ltr" required></label>
<p class="help">{{ __('يظهر تنبيه إذا لم يتم التواصل مع العميل خلال هذه المدة من إنشائه. المدة بالساعات الكاملة ولا تستثني العطلات.') }}</p>
<h2>{{ __('التوزيع الدوري') }}</h2>
<label class="checkbox"><input type="checkbox" name="round_robin_enabled" value="1" @checked($settings['round_robin_enabled'])> {{ __('توزيع العملاء الجدد غير المسندين بالتناوب') }}</label>
<fieldset class="check-grid"><legend>{{ __('الموظفون المشاركون في التوزيع') }}</legend>@forelse($employees as $id=>$name)<label class="checkbox"><input type="checkbox" name="round_robin_pool[]" value="{{ $id }}" @checked(in_array((int) $id,$settings['round_robin_pool'],true))> {{ $name }}</label>@empty<p class="muted">{{ __('لا يوجد موظفون نشطون.') }}</p>@endforelse</fieldset>
<p class="help">{{ __('يُطبَّق على العملاء الذين ينشئهم المدير بدون مسؤول وعلى الاستيراد. مندوب المبيعات الذي ينشئ عميلًا يُسند إليه تلقائيًا.') }}</p>
<label>{{ __('سبب التغيير') }}<textarea name="reason" required></textarea></label>
<button class="button">{{ __('حفظ') }}</button></form>
@endsection
