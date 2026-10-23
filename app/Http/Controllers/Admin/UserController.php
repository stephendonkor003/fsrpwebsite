<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(UserIndexRequest $request): View
    {
        $filters = array_merge([
            'search' => '',
            'status' => '',
            'access' => '',
            'per_page' => 25,
        ], $request->validated());
        $search = Str::lower(trim((string) $filters['search']));
        $query = User::query();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$search.'%']);
            });
        }

        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if ($filters['access'] !== '' && $filters['access'] !== null) {
            $query->where('is_admin', $filters['access'] === 'admin');
        }

        return view('admin.users.index', [
            'users' => $query->orderBy('name')->orderBy('id')
                ->paginate((int) $filters['per_page'])->withQueryString(),
            'filters' => $filters,
            'summary' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('is_active', true)->count(),
                'disabled' => User::query()->where('is_active', false)->count(),
                'administrators' => User::query()->where('is_admin', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => null, 'formMode' => 'create']);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create($request->safe()->only(['name', 'email', 'password', 'is_admin', 'is_active']));

        return redirect()->route('admin.users.index')->with('status', 'User account created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, 'formMode' => 'edit']);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'is_admin', 'is_active']);
        $passwordChanged = $request->filled('password');

        if ($passwordChanged) {
            $data['password'] = $request->validated('password');
        }

        $this->saveUser($user, $data, (int) $request->user()->getKey(), $passwordChanged);

        if ($passwordChanged && $request->user()->is($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Your password was updated. Sign in with your new password.');
        }

        return redirect()->route('admin.users.index')->with('status', 'User account updated.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        $this->saveUser($user, ['is_active' => $request->boolean('is_active')], (int) $request->user()->getKey());

        return redirect()->route('admin.users.index')->with(
            'status',
            $request->boolean('is_active') ? 'User sign-in enabled.' : 'User sign-in disabled and active sessions ended.',
        );
    }

    /**
     * @param  array{name?: string, email?: string, is_admin?: bool|int|string, is_active?: bool|int|string, password?: string}  $data
     */
    private function saveUser(User $user, array $data, int $administratorId, bool $passwordChanged = false): void
    {
        DB::transaction(function () use ($user, $data, $administratorId, $passwordChanged): void {
            $administrators = User::query()->where('is_admin', true)->where('is_active', true)
                ->orderBy('id')->lockForUpdate()->get();

            abort_unless($administrators->contains('id', $administratorId), 403);

            $user->refresh();
            $removesAdministrator = $user->is_admin && $user->is_active
                && (! (bool) ($data['is_admin'] ?? $user->is_admin) || ! (bool) ($data['is_active'] ?? $user->is_active));

            if ($removesAdministrator && $administrators->count() <= 1) {
                throw ValidationException::withMessages(['is_active' => 'At least one active administrator must remain.']);
            }

            $user->fill($data);
            $revokesAccess = $passwordChanged
                || ($user->isDirty('is_active') && ! $user->is_active)
                || ($user->isDirty('is_admin') && ! $user->is_admin);

            if ($revokesAccess) {
                $user->setRememberToken(Str::random(60));
                $user->session_version = (int) $user->session_version + 1;
            }

            $user->save();

            if ($revokesAccess && config('session.driver') === 'database') {
                DB::connection(config('session.connection'))
                    ->table(config('session.table', 'browser_sessions'))
                    ->where('user_id', $user->getKey())->delete();
            }
        });
    }
}
