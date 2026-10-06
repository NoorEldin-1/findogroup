@if($attendanceToday)
@php $today = $attendanceToday; $status = $today['record']?->status; @endphp
<section class="card attendance-widget">
<div class="section-heading"><h2>{{ __('حضوري اليوم') }}</h2>@if($today['record'])<span><span class="point-dot point-{{ $today['data']['point'] ?? 'none' }}" aria-hidden="true"></span> @if(! empty($today['data']['point']))<b>{{ ['green' => __('خضراء'), 'black' => __('سوداء'), 'red' => __('حمراء')][$today['data']['point']] }}</b> · @endif{{ $catalog->status($status) }}</span>@endif</div>
@if($today['missing_streak'] >= $today['alert_days'])<div class="notice error" role="alert">{{ __('لديك') }} {{ $today['missing_streak'] }} {{ __('أيام عمل متتالية بدون تسجيل حضور') }}. {{ __('تواصل مع مديرك أو HR.') }}</div>@endif
@if(! $today['record'])
<form method="post" action="/admin/operations/attendance/check-in" class="inline-form">@csrf<select name="mode" aria-label="{{ __('مكان العمل') }}"><option value="office">{{ __('من المقر') }}</option><option value="remote">{{ __('من المنزل') }}</option></select><button class="button">{{ __('تسجيل الحضور') }}</button></form>
@if(! $today['workday'])<p class="muted">{{ __('اليوم ليس يوم عمل؛ الوقت يُحسب إضافيًا بلا نقطة.') }}</p>@endif
@elseif($status === 'open')
<p class="muted">{{ __('الحضور') }}: {{ substr($today['data']['check_in'], 11) }} @if(($today['data']['late_minutes'] ?? 0) > 0)· {{ __('تأخير') }} {{ $today['data']['late_minutes'] }} {{ __('دقيقة') }}@endif</p>
<form method="post" action="/admin/operations/attendance/check-out">@csrf<button class="button secondary">{{ __('تسجيل الانصراف') }}</button></form>
@else
<p class="muted">{{ __('الحضور') }}: {{ ! empty($today['data']['check_in']) ? substr($today['data']['check_in'], 11) : '—' }} · {{ __('الانصراف') }}: {{ ! empty($today['data']['check_out']) ? substr($today['data']['check_out'], 11) : '—' }}@if(($today['data']['late_minutes'] ?? 0) > 0) · {{ __('تأخير') }} {{ $today['data']['late_minutes'] }} {{ __('دقيقة') }}@endif</p>
@endif
</section>
@endif
