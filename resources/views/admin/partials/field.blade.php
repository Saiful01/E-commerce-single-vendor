@php
    $type = $type ?? 'text';
    $current = $type === 'password' ? '' : old($name, $value ?? '');
@endphp
<div class="{{ $class ?? '' }}">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }}@if(!empty($required)) <span class="text-red-500">*</span>@endif
    </label>

    @if($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows ?? 5 }}"
                  @if(!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
                  class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $current }}</textarea>
    @elseif($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}"
                class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach($options as $k => $v)
                <option value="{{ $k }}" @selected((string) $current === (string) $k)>{{ $v }}</option>
            @endforeach
        </select>
    @else
        <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ $current }}"
               @if(!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
               @if(!empty($min)) min="{{ $min }}" @endif
               class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    @endif

    @error($name)<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    @if(!empty($hint))<p class="text-xs text-gray-500 mt-1">{{ $hint }}</p>@endif
</div>
