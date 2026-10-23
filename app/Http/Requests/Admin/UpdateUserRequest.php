<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Closure;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'password' => ['nullable', 'string', 'max:72', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->route('user');

                if (! $user instanceof User || ! $this->user()?->is($user)) {
                    return;
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'You cannot disable your own account.');
                }

                if (! $this->boolean('is_admin')) {
                    $validator->errors()->add('is_admin', 'You cannot remove your own administrator access.');
                }
            },
        ];
    }
}
