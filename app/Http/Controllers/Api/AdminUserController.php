<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyAdminUserRequest;
use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Requests\UpdateAdminUserRequest;
use App\Models\User;
use App\QueryBuilders\UserQueryBuilder;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $adminUserService) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'users' => UserQueryBuilder::buildQuery($request)->get(),
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $user = $this->adminUserService->create($request->validated());

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $this->userPayload($user->fresh()),
        ], 201);
    }

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $user = $this->adminUserService->update($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function destroy(DestroyAdminUserRequest $request, User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => optional($user->created_at)?->toIso8601String(),
            'updated_at' => optional($user->updated_at)?->toIso8601String(),
        ];
    }
}
