<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Roles that cannot be renamed or deleted (compared case-insensitively). */
    private const PROTECTED_ROLES = ['admin', 'super admin', 'super-admin', 'superadmin'];

    /* ---------------------------------------------------------------
     |  INDEX
     * --------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $roles = Role::with('permissions:id,name')
            ->withCount('users')
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->get();

        $roles->each(fn (Role $r) => $r->setAttribute('is_protected', $this->isProtected($r)));

        $details = $roles->map(fn (Role $r) => [
            'id'                => $r->id,
            'name'              => $r->name,
            'is_protected'      => $r->is_protected,
            'users_count'       => $r->users_count,
            'permissions'       => $r->permissions->pluck('name')->sort()->values(),
            'users'             => $r->users()->orderBy('name')->limit(8)->pluck('name')->values(),
            'created_at'        => $r->created_at?->format('d M Y'),
            'edit_url'          => route('admin.roles.edit', $r),
            'users_url'         => route('admin.users.index', ['role' => $r->name]),
        ])->values();

        return view('admin.roles.index', [
            'roles'            => $roles,
            'details'          => $details,
            'totalPermissions' => Permission::count(),
            'q'                => $q,
        ]);
    }

    /* ---------------------------------------------------------------
     |  CREATE / STORE
     * --------------------------------------------------------------*/
    public function create(): View
    {
        return view('admin.roles.create', [
            'permissions' => Permission::orderBy('name')->get(),
            'assigned'    => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));

        DB::transaction(function () use ($data) {
            $role = Role::create(['name' => trim($data['name'])]);
            $role->syncPermissions($data['permissions'] ?? []);
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    /* ---------------------------------------------------------------
     |  EDIT / UPDATE
     * --------------------------------------------------------------*/
    public function edit(Role $role): View
    {
        $role->loadCount('users');
        $role->setAttribute('is_protected', $this->isProtected($role));

        return view('admin.roles.edit', [
            'role'        => $role,
            'permissions' => Permission::orderBy('name')->get(),
            'assigned'    => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $role));

        DB::transaction(function () use ($data, $role) {
            // Protected roles keep their name so existing access checks keep working
            if (! $this->isProtected($role)) {
                $role->update(['name' => trim($data['name'])]);
            }

            $role->syncPermissions($data['permissions'] ?? []);
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    /* ---------------------------------------------------------------
     |  DELETE
     * --------------------------------------------------------------*/
    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isProtected($role)) {
            return back()->with('error', "The \"{$role->name}\" role is protected and cannot be deleted.");
        }

        $users = $role->users()->count();
        if ($users > 0) {
            return back()->with('error', "This role is assigned to {$users} " . str('user')->plural($users) . '. Reassign them first.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    /* ---------------------------------------------------------------
     |  HELPERS
     * --------------------------------------------------------------*/
    private function rules(Request $request, ?Role $role = null): array
    {
        return [
            'name'          => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    private function isProtected(Role $role): bool
    {
        return in_array(mb_strtolower($role->name), self::PROTECTED_ROLES, true);
    }
}
