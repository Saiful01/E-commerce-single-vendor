<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /* ---------------------------------------------------------------
     |  INDEX  (tabs + search + filters + data for the details modal)
     * --------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $q      = trim((string) $request->query('q', ''));
        $type   = in_array($request->query('type'), ['admin', 'customer'], true) ? $request->query('type') : null;
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null;
        $role   = $request->query('role');

        $users = User::with(['roles', 'addresses'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->when($type, fn ($query) => $query->where('user_type', $type))
            ->when($status === 'active', fn ($query) => $query->where('status', true))
            ->when($status === 'inactive', fn ($query) => $query->where('status', false))
            ->when($role, fn ($query) => $query->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $counts = User::query()->selectRaw('user_type, count(*) as total')->groupBy('user_type')->pluck('total', 'user_type');

        $details = $users->getCollection()->map(fn (User $u) => [
            'id'             => $u->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'phone'          => $u->phone,
            'user_type'      => $u->user_type,
            'status'         => (bool) $u->status,
            'is_self'        => $u->id === $request->user()->id,
            'roles'          => $u->roles->pluck('name')->values(),
            'verified_at'    => $u->email_verified_at?->format('d M Y, h:i A'),
            'created_at'     => $u->created_at?->format('d M Y, h:i A'),
            'updated_at'     => $u->updated_at?->format('d M Y, h:i A'),
            'addresses'      => $u->addresses->sortByDesc('is_default')->map(fn (UserAddress $a) => [
                'type'         => $a->address_type,
                'is_default'   => $a->is_default,
                'full_name'    => $a->full_name,
                'phone'        => $a->phone,
                'city'         => $a->city,
                'area'         => $a->area,
                'address_line' => $a->address_line,
            ])->values(),
            'edit_url'       => route('admin.users.edit', $u),
        ])->values();

        return view('admin.users.index', [
            'users'    => $users,
            'details'  => $details,
            'counts'   => $counts,
            'total'    => $counts->sum(),
            'inactive' => User::where('status', false)->count(),
            'roles'    => Role::orderBy('name')->pluck('name'),
            'q'        => $q,
            'type'     => $type,
            'status'   => $status,
            'role'     => $role,
        ]);
    }

    /* ---------------------------------------------------------------
     |  CREATE / STORE
     * --------------------------------------------------------------*/
    public function create(): View
    {
        return view('admin.users.create', [
            'roles'     => Role::orderBy('name')->pluck('name'),
            'userRoles' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));

        DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'password'  => $data['password'],      // hashed by the model cast
                'user_type' => $data['user_type'],
                'status'    => $request->boolean('status'),
            ]);

            // Accounts created by an admin do not need the email verification step
            $user->forceFill(['email_verified_at' => now()])->save();

            $user->syncRoles($data['user_type'] === 'admin' ? ($data['roles'] ?? []) : []);
            $this->syncAddresses($user, $data['addresses'] ?? []);
        });

        return redirect()
            ->route('admin.users.index', ['type' => $data['user_type']])
            ->with('success', 'User created successfully.');
    }

    /* ---------------------------------------------------------------
     |  EDIT / UPDATE
     * --------------------------------------------------------------*/
    public function edit(User $user): View
    {
        $user->load('addresses');

        return view('admin.users.edit', [
            'user'      => $user,
            'roles'     => Role::orderBy('name')->pluck('name'),
            'userRoles' => $user->roles->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $user));

        // An admin must not lock themselves out
        if ($user->is($request->user())) {
            if (! $request->boolean('status')) {
                return back()->withInput()->withErrors(['status' => 'You cannot disable your own account.']);
            }
            if ($data['user_type'] !== 'admin') {
                return back()->withInput()->withErrors(['user_type' => 'You cannot change your own account type.']);
            }
        }

        DB::transaction(function () use ($request, $data, $user) {
            $payload = [
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'user_type' => $data['user_type'],
                'status'    => $request->boolean('status'),
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);

            $user->syncRoles($data['user_type'] === 'admin' ? ($data['roles'] ?? []) : []);
            $this->syncAddresses($user, $data['addresses'] ?? []);
        });

        return redirect()
            ->route('admin.users.index', ['type' => $data['user_type']])
            ->with('success', 'User updated successfully.');
    }

    /* ---------------------------------------------------------------
     |  DELETE / TOGGLE STATUS
     * --------------------------------------------------------------*/
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete(); // addresses are removed by the foreign key cascade

        return back()->with('success', 'User deleted successfully.');
    }

    /** PATCH /admin/users/{user}/toggle */
    public function toggle(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json(['success' => false, 'message' => 'You cannot disable your own account.'], 422);
        }

        $user->update(['status' => ! $user->status]);

        return response()->json([
            'success' => true,
            'status'  => (bool) $user->status,
            'message' => $user->status ? 'User enabled.' : 'User disabled.',
        ]);
    }

    /* ---------------------------------------------------------------
     |  HELPERS
     * --------------------------------------------------------------*/
    private function rules(Request $request, ?User $user = null): array
    {
        $phone = 'regex:/^[0-9+\-\s()]+$/';

        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone'     => ['nullable', 'string', 'max:20', $phone, Rule::unique('users', 'phone')->ignore($user?->id)],
            'user_type' => ['required', Rule::in(['admin', 'customer'])],
            'password'  => [$user ? 'nullable' : 'required', Rules\Password::defaults()],

            // Roles only matter for admin-panel users
            'roles'   => [$request->input('user_type') === 'admin' ? 'required' : 'nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],

            'addresses'                  => ['nullable', 'array', 'max:10'],
            'addresses.*.id'             => ['nullable', 'integer'],
            'addresses.*.address_type'   => ['required', Rule::in(array_keys(UserAddress::TYPES))],
            'addresses.*.full_name'      => ['required', 'string', 'max:100'],
            'addresses.*.phone'          => ['required', 'string', 'max:20', $phone],
            'addresses.*.city'           => ['required', 'string', 'max:100'],
            'addresses.*.area'           => ['required', 'string', 'max:100'],
            'addresses.*.address_line'   => ['required', 'string', 'max:1000'],
            'addresses.*.is_default'     => ['nullable', 'boolean'],
        ];
    }

    /**
     * Create / update / delete addresses so the DB matches the form, and make
     * sure each type (shipping, billing) has exactly one default.
     */
    private function syncAddresses(User $user, array $submitted): void
    {
        $rows = collect($submitted)->values();

        // One default per type: the one flagged in the form, otherwise the first of that type
        $defaultIndex = [];
        foreach (array_keys(UserAddress::TYPES) as $type) {
            $index = $rows->search(fn ($r) => $r['address_type'] === $type && ! empty($r['is_default']));
            if ($index === false) {
                $index = $rows->search(fn ($r) => $r['address_type'] === $type);
            }
            $defaultIndex[$type] = $index;
        }

        $keepIds = [];

        foreach ($rows as $i => $row) {
            $payload = [
                'address_type' => $row['address_type'],
                'full_name'    => $row['full_name'],
                'phone'        => $row['phone'],
                'city'         => $row['city'],
                'area'         => $row['area'],
                'address_line' => $row['address_line'],
                'is_default'   => $defaultIndex[$row['address_type']] === $i,
            ];

            // Only touch addresses that really belong to this user
            $address = ! empty($row['id']) ? $user->addresses()->find($row['id']) : null;

            if ($address) {
                $address->update($payload);
            } else {
                $address = $user->addresses()->create($payload);
            }

            $keepIds[] = $address->id;
        }

        $user->addresses()->whereNotIn('id', $keepIds)->delete();
    }
}
