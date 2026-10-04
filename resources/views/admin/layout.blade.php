@php
$catalog = $catalog ?? app(\App\Services\OperatingCatalog::class);
$access = $access ?? app(\App\Services\OperatingAccess::class);
$uiLocale = app()->getLocale();
@endphp
<!doctype html><html lang="{{ $uiLocale }}" dir="{{ $uiLocale==='ar'?'rtl':'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title',__('لوحة التحكم')) — Findo Group</title>@include('admin.brand-head')<link rel="stylesheet" href="/admin-assets/operating.css?v={{ filemtime(public_path('admin-assets/operating.css')) }}"><script>window.findoAdminTranslations=@json(app()->getLocale()==='en'?json_decode(file_get_contents(lang_path('en.json')),true):[]);</script><script defer src="/admin-assets/admin.js?v={{ filemtime(public_path('admin-assets/admin.js')) }}"></script><script defer src="/admin-assets/operating.js?v={{ filemtime(public_path('admin-assets/operating.js')) }}"></script></head><body>
<aside class="sidebar"><a class="brand" href="/admin">@include('admin.brand-logo')<span class="brand-caption">{{ __('نظام تشغيل Findo Group') }}</span></a><span class="workspace"><i></i> {{ __(app()->environment('local') ? 'النسخة المحلية' : (app()->environment('production') ? 'مساحة العمل' : 'نسخة المعاينة')) }}</span><nav>
<a href="/admin" class="{{ request()->is('admin')?'active':'' }}">@include('admin.icon',['name'=>'overview']){{ __('نظرة عامة') }}</a>
@foreach(config('operating.groups') as $groupKey=>$group)
@php $visibleTypes=array_filter($catalog->entities(),fn($entity,$key)=>$entity['group']===$groupKey && $access->can(auth()->user(),$key),ARRAY_FILTER_USE_BOTH); @endphp
@if($visibleTypes)<details {{ array_key_exists(request()->route('type')??'', $visibleTypes)?'open':'' }}><summary>{{ $catalog->label($group['label']) }}</summary>@foreach($visibleTypes as $key=>$entity)<a href="/admin/operations/{{ $key }}" class="{{ request()->is('admin/operations/'.$key)?'active':'' }}">{{ $catalog->label($entity['label']) }}</a>@endforeach</details>@endif
@endforeach
<a href="/admin/operations/reports">@include('admin.icon',['name'=>'performance']){{ __('تقارير التشغيل') }}</a>
@if(in_array(auth()->user()->role,['owner','tech_lead'],true))<a href="/admin/operations/integrations">{{ __('التكاملات والمزامنة') }}</a>@endif
@if(auth()->user()->cmsRole())<details {{ request()->is('admin/content*','admin/collections/*','admin/seo','admin/website','admin/media','admin/redirects','admin/submissions')?'open':'' }}><summary>{{ __('الموقع والمحتوى وSEO') }}</summary>
@foreach(['/admin/website'=>['overview','إدارة الموقع'],'/admin/contents?kind=post'=>['articles','المقالات'],'/admin/contents'=>['pages','الصفحات والمحتوى'],'/admin/media'=>['media','مكتبة الوسائط'],'/admin/seo'=>['seo','مركز SEO'],'/admin/redirects'=>['redirects','تحويلات الروابط'],'/admin/submissions'=>['requests','الطلبات والتعليقات']] as $url=>$nav)<a href="{{ $url }}">@include('admin.icon',['name'=>$nav[0]]){{ __($nav[1]) }}</a>@endforeach
@if(auth()->user()->cmsRole()!=='seo')<details><summary>{{ __('أقسام الموقع المشتركة') }}</summary>@foreach($collections as $key=>$label)<a href="/admin/collections/{{ $key }}">{{ __($label) }}</a>@endforeach</details>@endif
@if(auth()->user()->role==='owner')<a href="/admin/performance">{{ __('الأداء والصيانة') }}</a><a href="/admin/revisions">{{ __('النسخ وسجل التعديلات') }}</a>@endif
</details>@endif
@if(auth()->user()->role==='owner')<a href="/admin/users">@include('admin.icon',['name'=>'users']){{ __('المستخدمون والصلاحيات') }}</a><a href="/admin/operations/settings">{{ __('قواعد التشغيل والمعايرة') }}</a><a href="/admin/operations/permissions">{{ __('مصفوفة الصلاحيات') }}</a>@endif
</nav><div class="sidebar-bottom"><b>{{ auth()->user()->name }}</b><small>{{ $catalog->label(config('operating.roles.'.auth()->user()->role,auth()->user()->role)) }}</small><form method="post" action="/admin/logout">@csrf<button class="logout">{{ __('تسجيل الخروج') }} ↗</button></form></div></aside>
<div class="admin-main"><header class="topbar"><button class="mobile-menu" aria-label="{{ __('القائمة') }}">☰</button><span>Findo Group <b>/ @yield('title',__('نظرة عامة'))</b></span><div class="topbar-actions"><a class="language-switch" href="{{ request()->fullUrlWithQuery(['ui_lang'=>$uiLocale==='ar'?'en':'ar']) }}">{{ $uiLocale==='ar'?'EN':'عربي' }}</a>@include('admin.theme-toggle')<a class="button small secondary" href="/" target="_blank" rel="noopener">{{ __('عرض الموقع') }} ↗</a></div></header><main class="admin-content">
@if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
@if(session('integration_error'))<div class="notice error" role="alert">{{ session('integration_error') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@yield('content')</main><footer class="admin-footer">Findo Group · {{ __('إدارة مترابطة، قرار موثق.') }}</footer></div></body></html>
