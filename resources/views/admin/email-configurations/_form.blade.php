<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 max-w-3xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        @include('admin.partials.field', ['name' => 'name', 'label' => 'Configuration name', 'value' => $config->name, 'required' => true, 'placeholder' => 'e.g. Gmail, SendGrid'])
        @include('admin.partials.field', ['name' => 'host', 'label' => 'SMTP host', 'value' => $config->host, 'required' => true, 'placeholder' => 'smtp.gmail.com'])
        @include('admin.partials.field', ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'value' => $config->port, 'required' => true])
        @include('admin.partials.field', ['name' => 'encryption', 'label' => 'Encryption', 'type' => 'select', 'value' => $config->encryption, 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None']])
        @include('admin.partials.field', ['name' => 'username', 'label' => 'Username', 'value' => $config->username])
        @include('admin.partials.field', ['name' => 'password', 'label' => 'Password / API key', 'type' => 'password', 'required' => !$config->exists,
            'hint' => $config->exists ? 'Khali rakhle purono password thakbe.' : 'Encrypted hoye save hobe.'])
        @include('admin.partials.field', ['name' => 'from_address', 'label' => 'From address', 'type' => 'email', 'value' => $config->from_address, 'required' => true])
        @include('admin.partials.field', ['name' => 'from_name', 'label' => 'From name', 'value' => $config->from_name, 'required' => true])
    </div>

    <label class="flex items-center gap-2 mt-5 text-sm text-gray-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $config->is_active)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
        Set as active (onno gulo auto inactive hoye jabe)
    </label>

    <div class="flex gap-3 mt-6">
        <button class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/30">Save</button>
        <a href="{{ route('admin.email-configurations.index') }}" class="px-5 py-2.5 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
    </div>
</div>
