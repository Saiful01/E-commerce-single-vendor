<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 max-w-3xl" x-data="{ channel: '{{ old('channel', $template->channel) }}' }">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        @include('admin.partials.field', ['name' => 'name', 'label' => 'Template name', 'value' => $template->name, 'required' => true])
        @include('admin.partials.field', ['name' => 'slug', 'label' => 'Slug', 'value' => $template->slug, 'hint' => 'Khali rakhle name theke auto toiri hobe. e.g. abandoned-cart-email, order-confirmation'])

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Channel <span class="text-red-500">*</span></label>
            <select name="channel" x-model="channel" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="email">Email</option>
                <option value="sms">SMS</option>
            </select>
            @error('channel')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div x-show="channel === 'email'" x-cloak>
            @include('admin.partials.field', ['name' => 'subject', 'label' => 'Email subject', 'value' => $template->subject])
        </div>
    </div>

    <div class="mt-5">
        @include('admin.partials.field', ['name' => 'body', 'label' => 'Message body', 'type' => 'textarea', 'rows' => 8, 'value' => $template->body, 'required' => true,
            'hint' => 'Placeholders: {customer_name} {cart_total} {items_count} {cart_url} {site_name} {order_number}'])
    </div>

    <label class="flex items-center gap-2 mt-5 text-sm text-gray-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        Active
    </label>

    <div class="flex gap-3 mt-6">
        <button class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/30">Save</button>
        <a href="{{ route('admin.message-templates.index') }}" class="px-5 py-2.5 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
    </div>
</div>
