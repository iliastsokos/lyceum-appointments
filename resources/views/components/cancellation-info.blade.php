@props(['appointment'])

{{-- "Ακυρώθηκε από … · Αιτιολογία: …" under a cancelled appointment;
     renders nothing for active ones or when neither is known. --}}
@if ($appointment->status === \App\Enums\AppointmentStatus::Cancelled && ($appointment->cancelledByLabel() || $appointment->cancellation_reason))
    <div {{ $attributes->merge(['class' => 'text-xs text-gray-500']) }}>
        @if ($appointment->cancelledByLabel())
            {{ __('Ακυρώθηκε από :who', ['who' => $appointment->cancelledByLabel()]) }}
        @endif
        @if ($appointment->cancellation_reason)
            @if ($appointment->cancelledByLabel()) &middot; @endif
            {{ __('Αιτιολογία') }}: {{ $appointment->cancellation_reason }}
        @endif
    </div>
@endif
