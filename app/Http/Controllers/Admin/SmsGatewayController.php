<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsGateway;
use App\Services\MessageSender;
use Illuminate\Http\Request;
use Throwable;

class SmsGatewayController extends Controller
{
    public function index()
    {
        $gateways = SmsGateway::orderByDesc('is_active')->latest()->paginate(15);

        return view('admin.sms-gateways.index', compact('gateways'));
    }

    public function create()
    {
        return view('admin.sms-gateways.create', ['gateway' => new SmsGateway(['http_method' => 'GET'])]);
    }

    public function store(Request $request)
    {
        SmsGateway::create($this->validated($request));

        return redirect()->route('admin.sms-gateways.index')->with('success', 'SMS gateway added.');
    }

    public function edit(SmsGateway $smsGateway)
    {
        return view('admin.sms-gateways.edit', ['gateway' => $smsGateway]);
    }

    public function update(Request $request, SmsGateway $smsGateway)
    {
        $data = $this->validated($request);

        foreach (['api_key', 'api_secret'] as $field) {
            if (empty($data[$field])) {
                unset($data[$field]); // blank = purono ta thakbe
            }
        }

        $smsGateway->update($data);

        return redirect()->route('admin.sms-gateways.index')->with('success', 'SMS gateway updated.');
    }

    public function destroy(SmsGateway $smsGateway)
    {
        $smsGateway->delete();

        return redirect()->route('admin.sms-gateways.index')->with('success', 'SMS gateway deleted.');
    }

    public function activate(SmsGateway $smsGateway)
    {
        $smsGateway->update(['is_active' => true]);

        return back()->with('success', $smsGateway->name . ' is now active.');
    }

    public function test(Request $request, SmsGateway $smsGateway, MessageSender $sender)
    {
        $request->validate(['test_number' => 'required|string|max:20']);

        if (! $smsGateway->is_active) {
            return back()->with('error', 'Test korar age ei gateway ta active koro.');
        }

        try {
            $sender->sms($request->test_number, 'Test SMS from admin panel.');
        } catch (Throwable $e) {
            return back()->with('error', 'Test fail: ' . $e->getMessage());
        }

        return back()->with('success', 'Test SMS sent to ' . $request->test_number);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'api_url' => 'required|url|max:500',
            'http_method' => 'required|in:GET,POST',
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'sender_id' => 'nullable|string|max:50',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
