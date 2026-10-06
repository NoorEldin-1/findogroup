@extends('admin.layout')
@section('title',__('الإشعارات'))
@section('content')
<div class="page-heading"><div><h1>{{ __('الإشعارات') }}</h1><p class="muted">{{ __('تنبيهات التشغيل الموجهة لك.') }}</p></div><form method="post" action="/admin/operations/notifications/read">@csrf<button class="button secondary">{{ __('تعليم الكل كمقروء') }}</button></form></div>
<section class="card">@forelse($notifications as $notification)<a class="operation-row" href="{{ $notification->data['url'] ?? '#' }}"><b>{{ $catalog->label($notification->data['title']) }}@if(! $notification->read_at) •@endif</b><span>{{ $catalog->label($notification->data['body']) }} · {{ $notification->created_at->format('Y-m-d H:i') }}</span></a>@empty<p class="muted">{{ __('لا توجد إشعارات.') }}</p>@endforelse</section>
{{ $notifications->links() }}
@endsection
