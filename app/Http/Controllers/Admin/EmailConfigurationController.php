<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailConfiguration;
use App\Services\MessageSender;
use Illuminate\Http\Request;
use Throwable;

class EmailConfigurationController extends Controller
{
    public function index()
    {
        $configs = EmailConfiguration::orderByDesc('is_active')->latest()->paginate(15);

        return view('admin.email-configurations.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.email-configurations.create', [
            'config' => new EmailConfiguration(['port' => 587, 'encryption' => 'tls']),
        ]);
    }

    public function store(Request $request)
    {
        EmailConfiguration::create($this->validated($request, true));

        return redirect()->route('admin.email-configurations.index')->with('success', 'Email configuration saved.');
    }

    public function edit(EmailConfiguration $emailConfiguration)
    {
        return view('admin.email-configurations.edit', ['config' => $emailConfiguration]);
    }

    public function update(Request $request, EmailConfiguration $emailConfiguration)
    {
        $data = $this->validated($request, false);

        // Password blank dile purono password thakbe
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $emailConfiguration->update($data);

        return redirect()->route('admin.email-configurations.index')->with('success', 'Email configuration updated.');
    }

    public function destroy(EmailConfiguration $emailConfiguration)
    {
        $emailConfiguration->delete();

        return redirect()->route('admin.email-configurations.index')->with('success', 'Email configuration deleted.');
    }

    public function activate(EmailConfiguration $emailConfiguration)
    {
        $emailConfiguration->update(['is_active' => true]);

        return back()->with('success', $emailConfiguration->name . ' is now active.');
    }

    public function test(Request $request, EmailConfiguration $emailConfiguration, MessageSender $sender)
    {
        $request->validate(['test_email' => 'required|email']);

        if (! $emailConfiguration->is_active) {
            return back()->with('error', 'Test korar age ei configuration ta active koro.');
        }

        try {
            $sender->email($request->test_email, 'SMTP test email', 'Congratulations! Tomar SMTP configuration thikmoto kaj korche.');
        } catch (Throwable $e) {
            return back()->with('error', 'Test fail: ' . $e->getMessage());
        }

        return back()->with('success', 'Test email sent to ' . $request->test_email);
    }

    private function validated(Request $request, bool $passwordRequired): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => ($passwordRequired ? 'required' : 'nullable') . '|string|max:255',
            'encryption' => 'required|in:tls,ssl,none',
            'from_address' => 'required|email',
            'from_name' => 'required|string|max:255',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
