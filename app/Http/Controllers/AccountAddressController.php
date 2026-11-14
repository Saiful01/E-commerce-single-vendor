<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountAddressController extends Controller
{
    public function index(Request $request)
    {
        return view('account.addresses', [
            'addresses' => $request->user()->addresses()->orderBy('address_type')->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $address = $request->user()->addresses()->create($this->validated($request));
        $this->settleDefault($request, $address);

        return redirect()->route('account.addresses')->with('success', 'Address added.');
    }

    public function update(Request $request, int $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->update($this->validated($request));
        $this->settleDefault($request, $address);

        return redirect()->route('account.addresses')->with('success', 'Address updated.');
    }

    public function makeDefault(Request $request, int $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->update(['is_default' => true]);
        $this->settleDefault($request, $address);

        return back()->with('success', 'Default address changed.');
    }

    public function destroy(Request $request, int $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        // Keep one default per type
        $next = $request->user()->addresses()->where('address_type', $address->address_type)->first();
        if ($address->is_default && $next) {
            $next->update(['is_default' => true]);
        }

        return back()->with('success', 'Address removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'address_type' => ['required', Rule::in(array_keys(UserAddress::TYPES))],
            'full_name'    => ['required', 'string', 'max:100'],
            'phone'        => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'city'         => ['required', 'string', 'max:100'],
            'area'         => ['required', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:500'],
            'is_default'   => ['nullable', 'boolean'],
        ]);

        $data['is_default'] = (bool) ($data['is_default'] ?? false);

        return $data;
    }

    /** One default per type; the first address of a type becomes the default. */
    private function settleDefault(Request $request, UserAddress $address): void
    {
        $same = $request->user()->addresses()->where('address_type', $address->address_type);

        if (! (clone $same)->where('is_default', true)->exists()) {
            $address->update(['is_default' => true]);
        }

        if ($address->fresh()->is_default) {
            (clone $same)->where('id', '!=', $address->id)->update(['is_default' => false]);
        }
    }
}
