@extends('layouts.account')
@section('page_title', 'Addresses')

@php $input = 'w-full rounded-xl border border-black/15 bg-white px-3.5 py-2.5 text-sm focus:border-[var(--primary)] focus:outline-none'; @endphp

@section('account')
<div x-data="addressBook(@js([
        'storeUrl' => route('account.addresses.store'),
        'updateBase' => url('/account/addresses'),
        'open' => $errors->any(),
        'old' => old() ? [
            'id' => old('_edit_id'), 'address_type' => old('address_type', 'shipping'), 'full_name' => old('full_name'), 'phone' => old('phone'),
            'city' => old('city'), 'area' => old('area'), 'address_line' => old('address_line'), 'is_default' => (bool) old('is_default'),
        ] : null,
    ]))" class="space-y-5">

    <div class="flex items-center justify-between rounded-2xl bg-white p-5 md:p-6">
        <h1 class="font-display text-2xl font-extrabold">Addresses</h1>
        <button type="button" @click="create()" class="btn btn-primary !py-2.5 text-sm"><i class="bi bi-plus-lg"></i> Add address</button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse($addresses as $a)
            <article class="flex flex-col rounded-2xl bg-white p-5 text-sm">
                <div class="mb-2 flex items-center gap-2">
                    <span class="rounded-md bg-neutral-100 px-2 py-0.5 text-xs font-medium">{{ \App\Models\UserAddress::TYPES[$a->address_type] ?? $a->address_type }}</span>
                    @if($a->is_default)<span class="soft rounded-md px-2 py-0.5 text-xs font-semibold text-brand">Default</span>@endif
                </div>
                <p class="font-semibold">{{ $a->full_name }}</p>
                <p class="text-neutral-600">{{ $a->phone }}</p>
                <p class="mt-1 text-neutral-600">{{ $a->full_address }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-4 border-t pt-3 font-semibold">
                    <button type="button" class="text-brand" @click='edit(@js(["id" => $a->id, "address_type" => $a->address_type, "full_name" => $a->full_name, "phone" => $a->phone, "city" => $a->city, "area" => $a->area, "address_line" => $a->address_line, "is_default" => (bool) $a->is_default]))'>Edit</button>
                    @unless($a->is_default)
                        <form method="POST" action="{{ route('account.addresses.default', $a->id) }}">@csrf @method('PATCH')<button class="text-neutral-600 hover:text-brand">Make default</button></form>
                    @endunless
                    <form method="POST" action="{{ route('account.addresses.destroy', $a->id) }}" class="ml-auto" onsubmit="return confirm('Remove this address?')">@csrf @method('DELETE')<button class="text-red-600">Remove</button></form>
                </div>
            </article>
        @empty
            <div class="rounded-2xl bg-white px-6 py-14 text-center text-neutral-500 md:col-span-2"><i class="bi bi-geo-alt text-5xl text-neutral-300"></i><p class="mt-3">No saved addresses yet.</p></div>
        @endforelse
    </div>

    {{-- Add / edit modal --}}
    <div x-show="open" x-cloak @keydown.escape.window="open = false" class="fixed inset-0 z-50 flex items-end justify-center md:items-center md:p-6" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <form method="POST" :action="form.id ? cfg.updateBase + '/' + form.id : cfg.storeUrl"
              class="relative max-h-[92vh] w-full space-y-4 overflow-y-auto rounded-t-2xl bg-white p-5 md:max-w-lg md:rounded-2xl md:p-6" x-show="open" x-transition>
            @csrf
            <template x-if="form.id"><span><input type="hidden" name="_method" value="PUT"><input type="hidden" name="_edit_id" :value="form.id"></span></template>
            <div class="flex items-center justify-between"><h2 class="font-display text-xl font-bold" x-text="form.id ? 'Edit address' : 'Add address'"></h2>
                <button type="button" @click="open = false" class="grid h-9 w-9 place-items-center" aria-label="Close"><i class="bi bi-x-lg"></i></button></div>

            @if($errors->any())<ul class="rounded-lg bg-red-50 p-3 text-sm text-red-700">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-sm font-medium">Type
                    <select name="address_type" x-model="form.address_type" class="{{ $input }} mt-1">
                        @foreach(\App\Models\UserAddress::TYPES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                    </select></label>
                <label class="block text-sm font-medium">Full name<input name="full_name" x-model="form.full_name" required class="{{ $input }} mt-1"></label>
                <label class="block text-sm font-medium">Mobile<input name="phone" x-model="form.phone" required inputmode="tel" class="{{ $input }} mt-1"></label>
                <label class="block text-sm font-medium">City<input name="city" x-model="form.city" required class="{{ $input }} mt-1"></label>
                <label class="block text-sm font-medium sm:col-span-2">Area / Thana<input name="area" x-model="form.area" required class="{{ $input }} mt-1"></label>
                <label class="block text-sm font-medium sm:col-span-2">Full address<textarea name="address_line" x-model="form.address_line" required rows="2" class="{{ $input }} mt-1"></textarea></label>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" x-model="form.is_default" class="h-4 w-4 rounded accent-[var(--primary)]"> Use as default</label>
            <button class="btn btn-primary w-full">Save address</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function addressBook(cfg) {
        const blank = { id: null, address_type: 'shipping', full_name: '{{ addslashes(auth()->user()->name) }}', phone: '{{ addslashes((string) auth()->user()->phone) }}', city: '', area: '', address_line: '', is_default: false };
        return {
            cfg, open: cfg.open, form: cfg.old ? { ...blank, ...cfg.old } : { ...blank },
            create() { this.form = { ...blank }; this.open = true },
            edit(a) { this.form = { ...a }; this.open = true },
        }
    }
</script>
@endpush
