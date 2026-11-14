<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageTemplateController extends Controller
{
    public function index(Request $request)
    {
        $channel = $request->query('channel');

        $templates = MessageTemplate::when(in_array($channel, ['email', 'sms']), fn ($q) => $q->where('channel', $channel))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.message-templates.index', compact('templates', 'channel'));
    }

    public function create()
    {
        return view('admin.message-templates.create', ['template' => new MessageTemplate(['channel' => 'email', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        MessageTemplate::create($this->validated($request));

        return redirect()->route('admin.message-templates.index')->with('success', 'Template created.');
    }

    public function edit(MessageTemplate $messageTemplate)
    {
        return view('admin.message-templates.edit', ['template' => $messageTemplate]);
    }

    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $messageTemplate->update($this->validated($request, $messageTemplate));

        return redirect()->route('admin.message-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(MessageTemplate $messageTemplate)
    {
        $messageTemplate->delete();

        return redirect()->route('admin.message-templates.index')->with('success', 'Template deleted.');
    }

    private function validated(Request $request, ?MessageTemplate $template = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('name'))]);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:message_templates,slug,' . ($template?->id ?? 'NULL'),
            'channel' => 'required|in:email,sms',
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        if ($data['channel'] === 'sms') {
            $data['subject'] = null;
        }

        return $data;
    }
}
