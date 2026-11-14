@php
    $map = [
        'pending'   => 'bg-amber-100 text-amber-700',
        'approved'  => 'bg-green-100 text-green-700',
        'rejected'  => 'bg-red-100 text-red-700',
        'draft'     => 'bg-gray-100 text-gray-700',
        'sent'      => 'bg-green-100 text-green-700',
        'failed'    => 'bg-red-100 text-red-700',
        'active'    => 'bg-green-100 text-green-700',
        'inactive'  => 'bg-gray-100 text-gray-600',
        'email'     => 'bg-indigo-100 text-indigo-700',
        'sms'       => 'bg-sky-100 text-sky-700',
        'recovered' => 'bg-green-100 text-green-700',
        'open'      => 'bg-amber-100 text-amber-700',
    ];
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $map[$value] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($value) }}</span>
