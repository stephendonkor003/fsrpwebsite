<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) ($this->user()?->is_admin && $this->user()?->is_active);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', $this->uniqueEmail()],
            'is_admin' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }

        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    protected function uniqueEmail(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $user = $this->route('user');
            $query = User::query()->whereRaw('LOWER(email) = ?', [$value]);

            if ($user instanceof User) {
                $query->whereKeyNot($user->getKey());
            }

            if ($query->exists()) {
                $fail('This email address is already assigned to another user.');
            }
        };
    }
}
