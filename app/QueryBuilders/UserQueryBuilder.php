<?php

namespace App\QueryBuilders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserQueryBuilder
{
    public static function buildQuery(Request $request): Builder
    {
        return static::apply(
            User::query()->select(['id', 'name', 'email', 'role', 'created_at', 'updated_at']),
            $request->only(['search', 'role', 'sort_by'])
        );
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $innerQuery) use ($search): void {
                $like = '%' . $search . '%';

                $innerQuery
                    ->where('id', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        $role = $filters['role'] ?? null;
        if (! empty($role)) {
            $query->where('role', $role);
        }

        $sortBy = $filters['sort_by'] ?? 'latest';
        if ($sortBy === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
