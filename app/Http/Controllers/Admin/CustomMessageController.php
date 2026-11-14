<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomMessage;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\MessageSender;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomMessageController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $messages = CustomMessage::with('user')
            ->when(in_array($status, ['draft', 'sent', 'failed']), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.custom-messages.index', compact('messages', 'status'));
    }

    public function create()
    {
        return view('admin.custom-messages.create', $this->formData(new CustomMessage(['channel' => 'email'])));
    }

    public function store(Request $request, MessageSender $sender)
    {
        $message = CustomMessage::create($this->validated($request) + [
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        if ($request->boolean('send_now')) {
            return $this->dispatchMessage($message, $sender);
        }

        return redirect()->route('admin.custom-messages.index')->with('success', 'Message saved as draft.');
    }

    public function show(CustomMessage $customMessage)
    {
        $customMessage->load(['user', 'creator']);

        return view('admin.custom-messages.show', ['message' => $customMessage]);
    }

    public function edit(CustomMessage $customMessage)
    {
        if ($customMessage->status === 'sent') {
            return redirect()->route('admin.custom-messages.show', $customMessage)
                ->with('error', 'Pathano message edit kora jabe na.');
        }

        return view('admin.custom-messages.edit', $this->formData($customMessage));
    }

    public function update(Request $request, CustomMessage $customMessage, MessageSender $sender)
    {
        if ($customMessage->status === 'sent') {
            return back()->with('error', 'Pathano message edit kora jabe na.');
        }

        $customMessage->update($this->validated($request) + ['status' => 'draft', 'error' => null]);

        if ($request->boolean('send_now')) {
            return $this->dispatchMessage($customMessage, $sender);
        }

        return redirect()->route('admin.custom-messages.index')->with('success', 'Message updated.');
    }

    public function destroy(CustomMessage $customMessage)
    {
        $customMessage->delete();

        return redirect()->route('admin.custom-messages.index')->with('success', 'Message deleted.');
    }

    public function send(CustomMessage $customMessage, MessageSender $sender)
    {
        if ($customMessage->status === 'sent') {
            return back()->with('error', 'Message already sent.');
        }

        return $this->dispatchMessage($customMessage, $sender);
    }

    private function dispatchMessage(CustomMessage $message, MessageSender $sender)
    {
        try {
            $sender->send($message->channel, $message->recipient, $message->subject, $message->body);
            $message->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            $flash = ['success', 'Message sent successfully.'];
        } catch (Throwable $e) {
            $message->update(['status' => 'failed', 'error' => $e->getMessage()]);
            $flash = ['error', 'Message pathano jayni: ' . $e->getMessage()];
        }

        return redirect()->route('admin.custom-messages.show', $message)->with(...$flash);
    }

    private function formData(CustomMessage $message): array
    {
        return [
            'message' => $message,
            'customers' => User::orderBy('name')->get(['id', 'name', 'email', 'phone']),
            'templates' => MessageTemplate::where('is_active', true)->get(['id', 'name', 'channel', 'subject', 'body']),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'channel' => 'required|in:email,sms',
            'user_id' => 'nullable|exists:users,id',
            'recipient' => 'nullable|string|max:255',
            'subject' => 'nullable|required_if:channel,email|string|max:255',
            'body' => 'required|string',
        ]);

        // Recipient khali thakle customer-er email/phone nibe
        if (empty($data['recipient']) && ! empty($data['user_id'])) {
            $user = User::find($data['user_id']);
            $data['recipient'] = $data['channel'] === 'email' ? $user->email : ($user->phone ?? null);
        }

        if (empty($data['recipient'])) {
            throw ValidationException::withMessages(['recipient' => 'Recipient (email/phone) dao ba customer select koro.']);
        }

        if ($data['channel'] === 'email' && ! filter_var($data['recipient'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['recipient' => 'Valid email address dao.']);
        }

        if ($data['channel'] === 'sms') {
            $data['subject'] = null;
        }

        return $data;
    }
}
