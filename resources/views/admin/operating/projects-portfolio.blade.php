@extends('admin.layout')
@section('title',__('لوحة المشاريع'))
@section('content')
@php $all=collect($rows); @endphp
<div class="page-heading"><div><span class="eyebrow">{{ $catalog->label(config('operating.groups.delivery.label')) }}</span><h1>{{ __('لوحة المشاريع') }}</h1><p class="muted">{{ __('الحالة والأولوية ونسبة الإنجاز والموعد لكل مشروع جارٍ.') }}</p></div><div class="row-actions"><a class="button secondary" href="/admin/operations/projects">{{ __('جدول المشروعات') }}</a>@if($access->can(auth()->user(),'projects','write'))<a class="button" href="/admin/operations/projects/create">+ {{ __('مشروع جديد') }}</a>@endif</div></div>
<section class="card"><div class="inline-stats"><span>{{ __('مشروعات') }}: <b>{{ $all->count() }}</b></span><span class="{{ $all->where('progress.overdue',true)->count() ? 'danger' : '' }}">{{ __('متأخر') }}: <b>{{ $all->where('progress.overdue',true)->count() }}</b></span><span>{{ __('التسليم خلال 14 يومًا') }}: <b>{{ $all->where('progress.due_soon',true)->count() }}</b></span></div></section>
<form class="filters"><select name="status" aria-label="{{ __('الحالة') }}"><option value="">{{ __('كل الحالات') }}</option>@foreach($catalog->entity('projects')['statuses'] as $state)@if($state!=='archived')<option value="{{ $state }}" @selected(($filters['status']??'')===$state)>{{ $catalog->status($state) }}</option>@endif @endforeach</select><select name="priority" aria-label="{{ __('الأولوية') }}"><option value="">{{ __('كل الأولويات') }}</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected(($filters['priority']??'')===$key)>{{ $catalog->label($label) }}</option>@endforeach</select><select name="type" aria-label="{{ __('نوع المشروع') }}"><option value="">{{ __('كل الأنواع') }}</option>@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(($filters['type']??'')===$key)>{{ $catalog->label($label) }}</option>@endforeach</select><button class="button secondary">{{ __('تصفية') }}</button></form>
<div class="card table-card"><table><thead><tr><th>{{ __('المشروع') }}</th><th>{{ __('العميل') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('الأولوية') }}</th><th>{{ __('مدير المشروع') }}</th><th>{{ __('الفريق') }}</th><th>{{ __('الموعد') }}</th><th>{{ __('الإنجاز') }}</th></tr></thead><tbody>
@forelse($rows as $row)<tr>
<td><a class="item-title" href="/admin/operations/record/{{ $row['id'] }}">{{ $row['title'] }}</a>@if($row['type'])<small class="path">{{ isset($types[$row['type']]) ? $catalog->label($types[$row['type']]) : $row['type'] }}</small>@endif</td>
<td>{{ $row['client'] ?? '—' }}</td>
<td><span class="badge">{{ $catalog->status($row['status']) }}</span></td>
<td>{{ $row['priority'] && isset($priorities[$row['priority']]) ? $catalog->label($priorities[$row['priority']]) : '—' }}</td>
<td>{{ $row['manager'] ?? '—' }}</td>
<td>{{ $row['members'] }}</td>
<td>{{ $row['progress']['due'] ?? '—' }}@if($row['progress']['overdue']) <b class="danger">{{ __('متأخر') }}</b>@elseif($row['progress']['due_soon']) <small>{{ __('قريب') }}</small>@endif</td>
<td><div class="progress" role="progressbar" aria-label="{{ __('نسبة الإنجاز') }}" aria-valuenow="{{ $row['progress']['percent'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $row['progress']['percent'] ?? 0 }}%"></i></div><small>{{ $row['progress']['percent']!==null ? $row['progress']['percent'].'%' : __('غير معلوم') }}</small></td>
</tr>@empty<tr><td colspan="8"><div class="empty-state">{{ __('لا شيء هنا.') }}</div></td></tr>@endforelse
</tbody></table></div>
@endsection
