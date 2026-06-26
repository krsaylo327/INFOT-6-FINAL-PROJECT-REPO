@component('mail::message')
# Agreement Expiring Soon

The agreement "{{ $agreement->title }}" is set to expire in {{ $days }} day(s) on {{ optional($agreement->expires_at)->format('M j, Y') }}.

@component('mail::button', ['url' => url('/agreements/'.$agreement->id)])
View Agreement
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent
