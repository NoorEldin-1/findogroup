@extends('admin.layout')
@section('title',__('صندوق الوارد'))
@section('content')
<div class="page-heading"><div>@include('admin.page-icon',['name'=>'bell'])<h1>{{ __('صندوق الوارد') }}</h1><p class="muted">{{ __('تنبيهات التشغيل الموجهة لك.') }}</p></div><form method="post" action="/admin/operations/notifications/read">@csrf<button class="button secondary">{{ __('تعليم الكل كمقروء') }}</button></form></div>
<nav class="crm-tabs">@foreach(['all'=>'صندوق الوارد','unread'=>'غير مقروء','sla'=>'تنبيهات SLA','leads'=>'عملاء جدد','reminders'=>'التذكيرات'] as $key=>$label)<a href="?tab={{ $key }}" class="{{ $tab===$key?'active':'' }}">{{ __($label) }}@if($counts[$key]) <b class="count-badge">{{ $counts[$key] }}</b>@endif</a>@endforeach</nav>
<section class="card">@forelse($notifications as $notification)<a class="operation-row" href="{{ $notification->data['url'] ?? '#' }}"><b>{{ $catalog->label($notification->data['title']) }}@if(! $notification->read_at) •@endif</b><span>{{ $catalog->label($notification->data['body']) }} · {{ $notification->created_at->format('Y-m-d H:i') }}</span></a>@empty<p class="muted">{{ __('لا توجد إشعارات.') }}</p>@endforelse</section>
{{ $notifications->links() }}
@endsection
