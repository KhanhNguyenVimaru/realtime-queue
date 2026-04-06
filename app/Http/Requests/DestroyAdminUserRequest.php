<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var User|null $targetUser */
            $targetUser = $this->route('user');
            $currentUser = $this->user();

            if (! $targetUser || ! $currentUser) {
                return;
            }

            if ((int) $currentUser->id === (int) $targetUser->id) {
                $validator->errors()->add('user', 'You cannot delete your own account.');
            }
        });
    }
}
