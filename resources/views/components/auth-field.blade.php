{{-- <x-auth-field name="email" label="Email" type="email" icon="bi-envelope" autocomplete="username" required /> --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'icon' => null, 'hint' => null])

@php
    $isPassword = $type === 'password';
    $error = $errors->first($name);
@endphp

<div x-data="{ show: false }">
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>
    <div class="relative">
        @if($icon)<i class="bi {{ $icon }} pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400"></i>@endif

        <input id="{{ $name }}" name="{{ $name }}"
               @if($isPassword) :type="show ? 'text' : 'password'" @else type="{{ $type }}" value="{{ old($name, $value) }}" @endif
               @if($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
               {{ $attributes->merge(['class' => 'w-full rounded-xl border bg-white py-3 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 '
                    . ($icon ? 'pl-10 ' : 'pl-3.5 ') . ($isPassword ? 'pr-11 ' : 'pr-3.5 ')
                    . ($error ? 'border-red-400 focus:ring-red-200' : 'border-black/15 focus:border-[var(--primary)] focus:ring-[color-mix(in_srgb,var(--primary)_25%,transparent)]')]) }}>

        @if($isPassword)
            <button type="button" @click="show = !show" class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-neutral-500 hover:text-brand" :aria-label="show ? 'Hide password' : 'Show password'">
                <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
            </button>
        @endif
    </div>
    @if($hint && ! $error)<p class="mt-1.5 text-xs text-neutral-500">{{ $hint }}</p>@endif
    @if($error)<p id="{{ $name }}-error" class="mt-1.5 text-xs font-medium text-red-600" role="alert">{{ $error }}</p>@endif
</div>
