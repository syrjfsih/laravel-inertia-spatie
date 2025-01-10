<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RoleController extends Controller implements HasMiddleware // Implement Middleware Spatie
{
    public static function middleware()
    {
        return [
            new Middleware('permission:roles index', only: ['index']),
            new Middleware('permission:roles create', only: ['create', 'store']),
            new Middleware('permission:roles edit', only: ['edit', 'update']),
            new Middleware('permission:roles delete', only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get roles
        $roles = Role::select('id', 'name')
            ->with('permissions:id,name')
            ->when($request->search,fn($search) => $search->where('name', 'like', '%'.$request->search.'%'))
            ->latest()
            ->paginate(6);

        // render view
        return inertia('Roles/Index', ['roles' => $roles,'filters' => $request->only(['search'])]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // get permissions
        // $permissions = Permission::all();
        $data = Permission::orderBy('name')->pluck('name', 'id');
        $collection = collect($data);
        $permissions = $collection->groupBy(function ($item, $key) {
            // Memecah string menjadi array kata-kata
            $words = explode(' ', $item);

            // Mengambil kata pertama
            return $words[0];
        });
        // return $permissions;
        // render view
        return inertia('Roles/Create', ['permissions' => $permissions]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         // validate request
         $request->validate([
            'name' => 'required|min:3|max:255|unique:roles',
            'selectedPermissions' => 'required|array|min:1',
        ]);

        // create new role data
        $role = Role::create(['name' => $request->name]);

        // give permissions to role
        $role->givePermissionTo($request->selectedPermissions);

        // render view
        return to_route('roles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        // get permissions
        $data = Permission::orderBy('name')->pluck('name', 'id');
        $collection = collect($data);
        $permissions = $collection->groupBy(function ($item, $key) {
            // Memecah string menjadi array kata-kata
            $words = explode(' ', $item);

            // Mengambil kata pertama
            return $words[0];
        });

        // load permissions
        $role->load('permissions');

        // render view
        return inertia('Roles/Edit', ['role' => $role, 'permissions' => $permissions]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        // validate request
        $request->validate([
            'name' => 'required|min:3|max:255|unique:roles,name,'.$role->id,
            'selectedPermissions' => 'required|array|min:1',
        ]);

        // update role data
        $role->update(['name' => $request->name]);

        // give permissions to role
        $role->syncPermissions($request->selectedPermissions);

        // render view
        return to_route('roles.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // delete role data
        $role->delete();

        // render view
        return back();
    }
}