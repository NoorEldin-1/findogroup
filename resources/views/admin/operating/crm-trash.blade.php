@extends('admin.layout')
@section('title',__('سلة المحذوفات'))
@section('content')
<div class="page-heading"><div>@include('admin.page-icon',['name'=>'box'])<span class="eyebrow">CRM</span><h1>{{ __('سلة المحذوفات') }}</h1><p class="muted">{{ __('السجلات المؤرشفة محفوظة هنا ويمكن استعادتها إلى حالتها السابقة. لا يوجد حذف نهائي في النظام.') }}</p></div></div>
@include('admin.operating.crm-nav')
<nav class="crm-tabs"><a href="/admin/operations/crm/trash" class="{{ $type?'':'active' }}">{{ __('الكل') }} <b>{{ $counts->sum() }}</b></a>@foreach($types as $key)<a href="?type={{ $key }}" class="{{ $type===$key?'active':'' }}">{{ $catalog->label($catalog->entity($key)['label']) }} <b>{{ $counts[$key] ?? 0 }}</b></a>@endforeach</nav>
<section class="card table-card crm-table-wrap"><table><thead><tr><th>{{ __('النوع') }}</th><th>{{ __('الاسم') }}</th><th>{{ __('حُذف بواسطة') }}</th><th>{{ __('تاريخ الحذف') }}</th><th>{{ __('السبب') }}</th><th>{{ __('الإجراءات') }}</th></tr></thead><tbody>
@forelse($page as $record)@php $by=$archivedBy[$record->id] ?? null; $from=$catalog->data($record)['archived_from'] ?? null; @endphp<tr><td>{{ $catalog->label($catalog->entity($record->type)['label']) }}</td><td><a href="/admin/operations/record/{{ $record->id }}">{{ $record->title }}</a></td><td>{{ $by?->name ?? '—' }}</td><td>{{ $by ? substr((string)$by->created_at,0,16) : substr((string)$record->updated_at,0,16) }}</td><td>{{ $by?->reason ?? '—' }}</td>
<td><form method="post" action="/admin/operations/record/{{ $record->id }}/restore" class="inline-form">@csrf<input type="hidden" name="version" value="{{ $record->version }}"><input name="reason" required placeholder="{{ __('سبب الاستعادة') }}"><button class="button small">{{ __('استعادة') }}@if($from) ← {{ $catalog->status($from) }}@endif</button></form></td></tr>
@empty<tr><td colspan="6" class="muted">{{ __('سلة المحذوفات فارغة') }}</td></tr>@endforelse</tbody></table></section>
<div class="pagination">{{ $page->links() }}</div>
@endsection
