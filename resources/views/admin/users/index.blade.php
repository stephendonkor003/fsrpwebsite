@extends('admin.layouts.app')

@section('title', 'Users & access')

@section('content')
    <div class="users-workspace">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Account administration</p>
                <h1>Users &amp; access</h1>
                <p>Create accounts, manage administrator access, and control who can sign in.</p>
            </div>
            <a class="button button--primary button--large" href="{{ route('admin.users.create') }}">
                @include('admin.partials.icon', ['name' => 'plus'])
                <span>Create new user</span>
            </a>
        </div>

        <section class="registration-metric-grid" aria-label="User account summary">
            @foreach(['total' => 'Total users', 'active' => 'Active accounts', 'disabled' => 'Disabled accounts', 'administrators' => 'Administrators'] as $key => $label)
                <article class="registration-metric-card">
                    <span class="registration-metric-icon">@include('admin.partials.icon', ['name' => $key === 'disabled' ? 'lock' : 'users'])</span>
                    <div><p>{{ $label }}</p><strong>{{ number_format($summary[$key]) }}</strong></div>
                </article>
            @endforeach
        </section>

        <section class="panel registration-filter-panel" aria-labelledby="user-filters-heading">
            <div class="panel-heading">
                <div><h2 id="user-filters-heading">Find a user</h2><p>Search by name or email, then narrow the results by access.</p></div>
                <a class="text-link" href="{{ route('admin.users.index') }}">Clear filters</a>
            </div>
            @if($errors->any())
                <div class="form-error-summary registration-filter-errors" role="alert" tabindex="-1" data-error-summary>
                    <div><strong>Please review this request.</strong><p>{{ $errors->first() }}</p></div>
                </div>
            @endif
            <form class="users-filter-form" method="GET" action="{{ route('admin.users.index') }}" role="search">
                <div class="filter-field">
                    <label for="user-search">Name or email</label>
                    <input id="user-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search user accounts" maxlength="150">
                </div>
                <div class="filter-field">
                    <label for="user-status">Account status</label>
                    <select id="user-status" name="status">
                        <option value="">All statuses</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="disabled" @selected($filters['status'] === 'disabled')>Disabled</option>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="user-access">Access level</label>
                    <select id="user-access" name="access">
                        <option value="">All access levels</option>
                        <option value="admin" @selected($filters['access'] === 'admin')>Administrator</option>
                        <option value="standard" @selected($filters['access'] === 'standard')>Standard account</option>
                    </select>
                </div>
                <button class="button button--primary" type="submit">Search users</button>
            </form>
        </section>

        <section class="panel content-list-panel" aria-labelledby="user-list-heading">
            <div class="panel-heading">
                <div><h2 id="user-list-heading">User accounts</h2><p>{{ number_format($users->total()) }} {{ Illuminate\Support\Str::plural('account', $users->total()) }} match your filters.</p></div>
                <span class="badge badge--neutral">Administrator controls</span>
            </div>
            @if($users->isEmpty())
                <div class="empty-state">
                    <span class="empty-state-icon">@include('admin.partials.icon', ['name' => 'users'])</span>
                    <h3>No users found</h3>
                    <p>Try another name or email, clear the filters, or create a new account.</p>
                    <a class="button button--primary" href="{{ route('admin.users.create') }}">Create new user</a>
                </div>
            @else
                <div class="table-scroll">
                    <table class="data-table users-table">
                        <thead><tr><th scope="col">User</th><th scope="col">Access</th><th scope="col">Login status</th><th scope="col">Last sign in</th><th scope="col">Actions</th></tr></thead>
                        <tbody>
                            @foreach($users as $account)
                                <tr>
                                    <td data-label="User">
                                        <div class="table-primary-cell">
                                            <span class="user-avatar" aria-hidden="true">{{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($account->name, 0, 1)) }}</span>
                                            <div>
                                                <a class="table-title-link" href="{{ route('admin.users.edit', $account) }}">{{ $account->name }}</a>
                                                <small>{{ $account->email }}</small>
                                                @if($account->is(auth()->user()))<small>Your account</small>@endif
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Access"><span class="badge badge--neutral">{{ $account->is_admin ? 'Administrator' : 'Standard account' }}</span></td>
                                    <td data-label="Login status">
                                        <span class="badge {{ $account->is_active ? 'badge--published' : 'badge--danger' }}"><span class="badge-dot" aria-hidden="true"></span>{{ $account->is_active ? 'Active' : 'Disabled' }}</span>
                                        @if($account->is_active && ! $account->is_admin)<small class="users-status-note">No administration access</small>@endif
                                    </td>
                                    <td data-label="Last sign in">
                                        @if($account->last_login_at)<time datetime="{{ $account->last_login_at->toAtomString() }}">{{ $account->last_login_at->format('d M Y, H:i') }}</time>@else Never signed in @endif
                                    </td>
                                    <td data-label="Actions">
                                        <div class="users-row-actions">
                                            <a class="button button--secondary button--small" href="{{ route('admin.users.edit', $account) }}" aria-label="Edit {{ $account->name }}">Edit user</a>
                                            @unless($account->is(auth()->user()))
                                                <form method="POST" action="{{ route('admin.users.status', $account) }}" data-confirm-action="{{ $account->is_active ? 'Disable login for '.$account->name.'? Their existing sessions will end immediately.' : 'Enable login for '.$account->name.'?' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="{{ $account->is_active ? '0' : '1' }}">
                                                    <button class="button button--small {{ $account->is_active ? 'button--danger' : 'button--primary' }}" type="submit">{{ $account->is_active ? 'Disable login' : 'Enable login' }}</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())<div class="pagination-wrap">{{ $users->onEachSide(1)->links() }}</div>@endif
            @endif
        </section>
        <p class="users-access-note">Disabling an account ends its current sessions and blocks future sign ins. Your own administrator access is protected.</p>
    </div>
@endsection
