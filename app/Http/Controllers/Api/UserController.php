<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with(['company'])
            ->where('company_id', Auth::user()->company_id);

        // Filters
        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        $users = $query->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,accountant,viewer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'company_id' => Auth::user()->company_id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => $request->is_active ?? true,
            ]);

            // Assign role using Spatie
            $user->assignRole($request->role);

            DB::commit();

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'created_by' => Auth::id(),
            ]);

            return new UserResource($user->load('company'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        Gate::authorize('view', $user);

        // Check if user belongs to same company
        if ($user->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return new UserResource($user->load(['company', 'permissions']));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        // Check if user belongs to same company
        if ($user->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = validator($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'nullable|in:admin,accountant,viewer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'email', 'role', 'is_active']);
            
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            // Update role if changed
            if ($request->filled('role')) {
                $user->syncRoles([$request->role]);
            }

            DB::commit();

            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'updated_by' => Auth::id(),
            ]);

            return new UserResource($user->load('company'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        // Check if user belongs to same company
        if ($user->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Don't allow deleting yourself
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot delete your own account'], 403);
        }

        DB::beginTransaction();
        try {
            $user->delete();

            DB::commit();

            Log::info('User deleted successfully', [
                'user_id' => $user->id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json(['message' => 'User deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $validator = validator($request->all(), [
            'role' => 'required|in:admin,accountant,viewer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user->syncRoles([$request->role]);
            $user->update(['role' => $request->role]);

            DB::commit();

            Log::info('Role assigned to user', [
                'user_id' => $user->id,
                'role' => $request->role,
                'assigned_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Role assigned successfully',
                'user' => new UserResource($user)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        Gate::authorize('update', $user);

        // Check if user belongs to same company
        if ($user->company_id !== Auth::user()->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Don't allow deactivating yourself
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'You cannot deactivate your own account'], 403);
        }

        DB::beginTransaction();
        try {
            $user->update(['is_active' => !$user->is_active]);

            DB::commit();

            Log::info('User status toggled', [
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'toggled_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'User status updated successfully',
                'is_active' => $user->is_active,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current user profile
     */
    public function profile()
    {
        $user = Auth::user()->load(['company', 'permissions']);
        return new UserResource($user);
    }

    /**
     * Get user statistics
     */
    public function getStats()
    {
        Gate::authorize('viewAny', User::class);

        $companyId = Auth::user()->company_id;

        $stats = [
            'total' => User::where('company_id', $companyId)->count(),
            'active' => User::where('company_id', $companyId)
                ->where('is_active', true)->count(),
            'inactive' => User::where('company_id', $companyId)
                ->where('is_active', false)->count(),
            'admins' => User::where('company_id', $companyId)
                ->where('role', 'admin')->count(),
            'accountants' => User::where('company_id', $companyId)
                ->where('role', 'accountant')->count(),
            'viewers' => User::where('company_id', $companyId)
                ->where('role', 'viewer')->count(),
            'recent' => User::where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'email', 'role', 'created_at']),
        ];

        return response()->json($stats);
    }
}