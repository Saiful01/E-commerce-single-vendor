@php
    $customerMap = $customers->mapWithKeys(fn ($u) => [$u->id => ['email' => $u->email, 'phone' => $u->phone ?? '']]);
@endphp
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 max-w-3xl"
     x-data="{
        channel: '{{ old('channel', $message->channel) }}',
        customers: @js($customerMap),
        templates: @js($templates),
        userId: '{{ old('user_id', $message->user_id) }}',
        recipient: @js(old('recipient', $message->recipient)),
        subject: @js(old('subject', $message->subject)),
        body: @js(old('body', $message->body)),
        fillRecipient() {
            const c = this.customers[this.userId];
            if (c) this.recipient = this.channel === 'email' ? c.email : c.phone;
        },
        applyTemplate(e) {
            const t = this.templates.find(x => x.id == e.target.value);
            if (!t) return;
            this.channel = t.channel;
            this.subject = t.subject ?? '';
            this.body = t.body;
            this.fillRecipient();
        }
     }">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Channel <span class="text-red-500">*</span></label>
            <select name="channel" x-model="channel" @change="fillRecipient()" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="email">Email</option>
                <option value="sms">SMS</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Load from template</label>
            <select @change="applyTemplate($event)" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— none —</option>
                @foreach($templates as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ strtoupper($t->channel) }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
            <select name="user_id" x-model="userId" @change="fillRecipient()" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— manual recipient —</option>
                @foreach($customers as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recipient (email / phone)</label>
            <input type="text" name="recipient" x-model="recipient" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('recipient')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-5" x-show="channel === 'email'" x-cloak>
        <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
        <input type="text" name="subject" x-model="subject" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('subject')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="mt-5">
        <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-red-500">*</span></label>
        <textarea name="body" rows="7" x-model="body" class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
        <p class="text-xs text-gray-400 mt-1" x-show="channel === 'sms'" x-cloak><span x-text="body.length"></span> characters</p>
        @error('body')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-wrap gap-3 mt-6">
        <button name="send_now" value="1" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/30">Send now</button>
        <button class="px-5 py-2.5 bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl">Save as draft</button>
        <a href="{{ route('admin.custom-messages.index') }}" class="px-5 py-2.5 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
    </div>
</div>
