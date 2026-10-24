@extends('admin.layout')
@section('title',$collections[$key])
@section('content')
<div class="page-heading"><div>@include('admin.page-icon',['name'=>'pages'])<span class="eyebrow">{{ __('أقسام الموقع المشتركة') }}</span><h1>{{ $collections[$key] }}</h1><p class="muted">{{ __('التعديلات تُطبّق على الصفحات التي تستخدم هذا القسم، مع حفظ نسخة سابقة.') }}</p></div><a class="button secondary" href="/" target="_blank">{{ __('عرض الموقع ↗') }}</a></div>
@if(in_array($key,['projects','services','systems']))<div class="notice">{{ __('إضافة عنصر تنشئ صفحة له. حذفه يؤرشف صفحته باستجابة 410؛ استخدم تحويلًا مناسبًا إذا كان له بديل. حافظ على المعرّف id للعناصر الحالية.') }}</div>@endif
@if($key==='testimonials')<div class="notice">{{ __('أضف الفيديو والصورة المصغرة من مكتبة الوسائط. ترتيب البطاقات هنا هو ترتيبها على الموقع. المدة بصيغة مثل PT46S.') }}</div>@endif
<form method="post" data-collection-form data-unsaved>@csrf<input type="hidden" name="version" value="{{ $version }}"><textarea name="data" hidden data-collection-json>{{ old('data',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) }}</textarea><div class="sticky-actions"><span>{{ __('إضافة، تعديل، حذف، وترتيب العناصر') }}</span><div class="row-actions"><a href="/admin/media" target="_blank">{{ __('مكتبة الوسائط ↗') }}</a><button class="button">{{ __('حفظ القسم') }}</button></div></div><div data-collection-editor data-key="{{ $key }}"></div></form>
<script id="collection-template" type="application/json">{!! json_encode($template, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
