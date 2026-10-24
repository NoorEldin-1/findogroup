@extends('admin.layout')
@section('title',__('لوحة الحضور اليومية'))
@section('content')
<div class="page-heading"><div>@include('admin.page-icon',['name'=>'clock'])<span class="eyebrow">{{ $date->toDateString() }}</span><h1>{{ __('لوحة الحضور اليومية') }}</h1><p class="muted">{{ $workday ? __('النقاط: خضراء في الموعد، سوداء متأخر، حمراء بلا تسجيل.') : __('هذا اليوم ليس يوم عمل.') }}</p></div><form method="get" class="inline-form"><input type="date" name="date" value="{{ $date->toDateString() }}" aria-label="{{ __('يوم العمل') }}"><button class="button secondary">{{ __('عرض') }}</button><a class="button secondary" href="/admin/operations/attendance/monthly?month={{ $date->format('Y-m') }}">{{ __('التقرير الشهري') }} ↗</a></form></div>
<div class="stats-grid"><div class="stat"><span>{{ __('حاضرون') }}</span><strong>{{ $counts['present'] }}</strong></div><div class="stat"><span>{{ __('متأخرون') }}</span><strong>{{ $counts['late'] }}</strong></div><div class="stat"><span>{{ __('غياب / عدم تسجيل') }}</span><strong>{{ $counts['absent'] }}</strong></div><div class="stat"><span>{{ __('لم يسجّلوا بعد') }}</span><strong>{{ $counts['pending'] }}</strong></div><div class="stat"><span>{{ __('غياب بعذر') }}</span><strong>{{ $counts['excused'] }}</strong></div><div class="stat"><span>{{ __('من المنزل') }}</span><strong>{{ $counts['remote'] }}</strong></div></div>
<div class="card table-card"><table><thead><tr><th>{{ __('الموظف') }}</th><th>{{ __('النقطة') }}</th><th>{{ __('الحضور') }}</th><th>{{ __('الانصراف') }}</th><th>{{ __('التأخير (د)') }}</th><th>{{ __('ساعات العمل') }}</th><th>{{ __('مكان العمل') }}</th><th>{{ __('غياب متتالٍ') }}</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>@if($row['record'])<a href="/admin/operations/record/{{ $row['record']->id }}">{{ $row['employee']->title }}</a>@else{{ $row['employee']->title }}@endif</td>
<td><span class="point-dot point-{{ $row['data']['point'] ?? 'none' }}" aria-hidden="true"></span> @if(! empty($row['data']['point']))<b>{{ ['green' => __('خضراء'), 'black' => __('سوداء'), 'red' => __('حمراء')][$row['data']['point']] }}</b> · @endif{{ $row['record'] ? $catalog->status($row['record']->status) : __('لم يسجّل بعد') }}</td>
<td>{{ ! empty($row['data']['check_in']) ? substr($row['data']['check_in'], 11) : '—' }}</td>
<td>{{ ! empty($row['data']['check_out']) ? substr($row['data']['check_out'], 11) : '—' }}</td>
<td>{{ $row['data']['late_minutes'] ?? 0 }}</td>
<td>{{ round(($row['data']['worked_minutes'] ?? 0) / 60, 2) }}</td>
<td>{{ empty($row['data']['check_in']) ? '—' : (($row['data']['mode'] ?? null) === 'remote' ? __('من المنزل') : __('من المقر')) }}</td>
<td>@if($row['streak'] >= $alertDays)<b class="count-badge">{{ $row['streak'] }}</b>@else{{ $row['streak'] }}@endif</td></tr>
@empty<tr><td colspan="8" class="muted">{{ __('لا يوجد موظفون نشطون مرتبطون بحسابات.') }}</td></tr>@endforelse
</tbody></table></div>
<div class="notice">{{ __('النقاط الحمراء تُسجَّل تلقائيًا في نهاية يوم العمل. التصحيح من سجل الحضور مع سبب مكتوب.') }}</div>
@endsection
