<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 max-w-3xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        @include('admin.partials.field', ['name' => 'name', 'label' => 'Gateway name', 'value' => $gateway->name, 'required' => true, 'placeholder' => 'e.g. BulkSMSBD, Twilio'])
        @include('admin.partials.field', ['name' => 'http_method', 'label' => 'HTTP method', 'type' => 'select', 'value' => $gateway->http_method, 'options' => ['GET' => 'GET', 'POST' => 'POST']])
        @include('admin.partials.field', ['name' => 'api_url', 'label' => 'API URL', 'type' => 'url', 'value' => $gateway->api_url, 'required' => true, 'class' => 'sm:col-span-2', 'placeholder' => 'http://bulksmsbd.net/api/smsapi'])
        @include('admin.partials.field', ['name' => 'api_key', 'label' => 'API key', 'type' => 'password', 'hint' => $gateway->exists ? 'Khali rakhle purono key thakbe.' : 'Encrypted hoye save hobe.'])
        @include('admin.partials.field', ['name' => 'api_secret', 'label' => 'API secret (optional)', 'type' => 'password'])
        @include('admin.partials.field', ['name' => 'sender_id', 'label' => 'Sender ID', 'value' => $gateway->sender_id])
    </div>

    <label class="flex items-center gap-2 mt-5 text-sm text-gray-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $gateway->is_active)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        Set as active (onno gulo auto inactive hoye jabe)
    </label>

    <div class="flex gap-3 mt-6">
        <button class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/30">Save</button>
        <a href="{{ route('admin.sms-gateways.index') }}" class="px-5 py-2.5 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
    </div>
</div>
