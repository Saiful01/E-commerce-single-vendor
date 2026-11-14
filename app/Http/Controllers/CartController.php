<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderException;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function index()
    {
        return view('cart.index', ['cart' => $this->cart->payload()]);
    }

    public function summary(): JsonResponse
    {
        return response()->json($this->cart->payload());
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'qty'        => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        try {
            $this->cart->add($data['product_id'], $data['variant_id'] ?? null, $data['qty'] ?? 1);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->cart->payload() + ['message' => 'Added to cart']);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string'], 'qty' => ['required', 'integer', 'min:0', 'max:999']]);

        $note = $this->cart->update($data['key'], $data['qty']);

        return response()->json($this->cart->payload() + ['message' => $note]);
    }

    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string']]);
        $this->cart->remove($data['key']);

        return response()->json($this->cart->payload());
    }
}
