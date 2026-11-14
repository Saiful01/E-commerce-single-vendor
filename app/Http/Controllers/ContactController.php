<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        // Hidden field that people never see: only spam bots fill it. They get a "thank you" and nothing is saved.
        if ($request->filled('website')) {
            return back()->with('success', 'Thank you! We got your message.');
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'email'   => ['nullable', 'email', 'max:100'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'phone.regex'  => 'Enter a valid mobile number.',
            'message.min'  => 'Please write a little more so we can help.',
        ]);

        ContactMessage::create($data + ['user_id' => $request->user()?->id, 'ip' => $request->ip()]);

        return redirect(url()->previous() . '#contact-form')->with('success', 'Thank you! We got your message and will call or reply soon.');
    }
}
