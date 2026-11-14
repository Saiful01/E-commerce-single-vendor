<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountReturnController extends Controller
{
    public function __construct(private ReturnService $returns)
    {
    }

    public function create(Request $request, string $number)
    {
        $order = $request->user()->orders()->with(['items', 'status'])->where('order_number', $number)->firstOrFail();
        $check = $this->returns->eligibility($order);

        if (! $check['ok']) {
            return redirect()->route('account.orders.show', $number)->with('error', $check['reason']);
        }

        return view('account.returns.create', ['order' => $order, 'remaining' => $check['remaining']]);
    }

    public function store(Request $request, string $number)
    {
        $order = $request->user()->orders()->where('order_number', $number)->firstOrFail();

        $data = $request->validate([
            'qty'     => ['required', 'array'],
            'qty.*'   => ['nullable', 'integer', 'min:0', 'max:999'],
            'reason'  => ['required', Rule::in(array_keys(config('shop.return_reasons')))],
            'details' => ['nullable', 'string', 'max:1000'],
        ], ['reason.required' => 'Please tell us why you are returning.']);

        try {
            $this->returns->create($order, $data['qty'], $data['reason'], $data['details'] ?? null, 'customer', $request->user());
        } catch (OrderException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('account.orders.show', $number)->with('success', 'Your return request was sent. We will get back to you soon.');
    }
}
