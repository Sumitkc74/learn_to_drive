@extends('admin.layout.master')

@section('title', 'Profile Settings')

@section('page-style')
    <style>
        .ltd-profile-summary {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            background: linear-gradient(135deg, var(--charcoal-deep), var(--charcoal));
            color: #fff;
            border-radius: 16px;
        }

        .ltd-profile-avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            border: 3px solid var(--yellow);
            background: var(--surface);
            color: var(--charcoal-deep);
            display: grid;
            place-items: center;
            flex-shrink: 0;
            overflow: hidden;
            font-size: 1.75rem;
            font-weight: 800;
        }

        .ltd-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ltd-profile-summary h2 {
            margin: 0 0 0.25rem;
            font-size: 1.35rem;
            font-weight: 800;
        }

        .ltd-profile-summary p {
            margin: 0;
            color: rgba(255, 255, 255, 0.7);
        }

        .ltd-profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.65rem;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            background: rgba(255, 222, 23, 0.16);
            color: var(--yellow);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .ltd-settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .ltd-setting-card {
            padding: 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .ltd-setting-card--wide {
            grid-column: 1 / -1;
        }

        .ltd-setting-card__header {
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .ltd-setting-card__icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 10px;
            background: var(--yellow);
            color: var(--charcoal-deep);
        }

        .ltd-setting-card h3 {
            margin: 0 0 0.15rem;
            font-size: 1rem;
            font-weight: 800;
            color: var(--text);
        }

        .ltd-setting-card p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.82rem;
        }

        .ltd-setting-card__action {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        @media (max-width: 760px) {
            .ltd-settings-grid {
                grid-template-columns: 1fr;
            }

            .ltd-setting-card--wide {
                grid-column: auto;
            }

            .ltd-profile-summary {
                align-items: flex-start;
            }
        }
    </style>
@endsection

@section('content')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Profile Settings</h1>
        </div>
    </div>

    <section class="ltd-profile-summary" aria-label="Admin profile overview">
        <div class="ltd-profile-avatar">
            @if($user->getFirstMediaUrl())
                <img src="{{ $user->getFirstMediaUrl() }}" alt="{{ $user->name }} profile photo">
            @else
                {{ strtoupper(substr($user->name, 0, 1)) }}
            @endif
        </div>
        <div>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }} &middot; {{ $user->phoneNumber }}</p>
            <span class="ltd-profile-badge">
                <i class="fas fa-user-shield" aria-hidden="true"></i>
                {{ $user->role }}
            </span>
            <span class="ltd-profile-badge">
                <i class="fas {{ $user->email_verified_at ? 'fa-check-circle' : 'fa-exclamation-circle' }}" aria-hidden="true"></i>
                {{ $user->email_verified_at ? 'Email verified' : 'Email not verified' }}
            </span>
        </div>
    </section>

    <div class="ltd-settings-grid">
        <section class="ltd-setting-card">
            <div class="ltd-setting-card__header">
                <span class="ltd-setting-card__icon"><i class="fas fa-camera"></i></span>
                <div>
                    <h3>Profile Photo</h3>
                    <p>JPEG, PNG or WebP, up to 2 MB.</p>
                </div>
            </div>
            <form action="{{ route('profile.image.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <input type="file" name="profileImage" accept=".jpg,.jpeg,.png,.webp"
                    class="form-control @error('profileImage') is-invalid @enderror" required>
                @error('profileImage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="ltd-setting-card__action">
                    <button type="submit" class="btn btn-primary">Update Photo</button>
                </div>
            </form>
        </section>

        <section class="ltd-setting-card">
            <div class="ltd-setting-card__header">
                <span class="ltd-setting-card__icon"><i class="fas fa-user"></i></span>
                <div>
                    <h3>Full Name</h3>
                    <p>Change the name displayed in the admin panel.</p>
                </div>
            </div>
            <form action="{{ route('profile.name.update') }}" method="POST">
                @csrf
                @method('PATCH')
                <label for="name" class="form-label">Full Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}"
                    class="form-control @error('name') is-invalid @enderror" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="ltd-setting-card__action">
                    <button type="submit" class="btn btn-primary">Update Name</button>
                </div>
            </form>
        </section>

        <section class="ltd-setting-card">
            <div class="ltd-setting-card__header">
                <span class="ltd-setting-card__icon"><i class="fas fa-envelope"></i></span>
                <div>
                    <h3>Email Address</h3>
                    <p>Changing the email marks the new address as unverified.</p>
                </div>
            </div>
            <form action="{{ route('profile.email.update') }}" method="POST">
                @csrf
                @method('PATCH')
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                    class="form-control @error('email') is-invalid @enderror" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="ltd-setting-card__action">
                    <button type="submit" class="btn btn-primary">Update Email</button>
                </div>
            </form>
        </section>

        <section class="ltd-setting-card">
            <div class="ltd-setting-card__header">
                <span class="ltd-setting-card__icon"><i class="fas fa-phone"></i></span>
                <div>
                    <h3>Phone Number</h3>
                    <p>Use a 10-digit phone number.</p>
                </div>
            </div>
            <form action="{{ route('profile.phone.update') }}" method="POST">
                @csrf
                @method('PATCH')
                <label for="phoneNumber" class="form-label">Phone Number</label>
                <input id="phoneNumber" type="tel" name="phoneNumber" value="{{ old('phoneNumber', $user->phoneNumber) }}"
                    inputmode="numeric" maxlength="10"
                    class="form-control @error('phoneNumber') is-invalid @enderror" required>
                @error('phoneNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="ltd-setting-card__action">
                    <button type="submit" class="btn btn-primary">Update Phone</button>
                </div>
            </form>
        </section>

        <section class="ltd-setting-card ltd-setting-card--wide">
            <div class="ltd-setting-card__header">
                <span class="ltd-setting-card__icon"><i class="fas fa-lock"></i></span>
                <div>
                    <h3>Password</h3>
                    <p>Confirm your current password before setting a new one.</p>
                </div>
            </div>
            <form action="{{ route('profile.password.update') }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input id="current_password" type="password" name="current_password"
                            class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input id="password" type="password" name="password"
                            class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                            class="form-control" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="ltd-setting-card__action">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </section>
    </div>
@endsection
