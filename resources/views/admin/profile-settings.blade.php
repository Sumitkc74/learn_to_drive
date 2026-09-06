@extends('admin.layout.master')

@section('title', 'Profile Settings')

@section('page-style')
    <style>
        .ltd-profile-card {
            max-width: 760px;
            margin: 0 auto;
            overflow: hidden;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(16, 24, 40, 0.08);
        }

        .ltd-profile-card__cover {
            height: 110px;
            background: linear-gradient(135deg, var(--charcoal-deep), var(--charcoal));
        }

        .ltd-profile-card__body {
            padding: 0 2rem 2rem;
        }

        .ltd-avatar-form {
            width: 112px;
            margin: -56px auto 1rem;
        }

        .ltd-avatar-button {
            position: relative;
            width: 112px;
            height: 112px;
            display: grid;
            place-items: center;
            overflow: hidden;
            padding: 0;
            border: 4px solid var(--surface);
            border-radius: 50%;
            background: var(--yellow);
            color: var(--charcoal-deep);
            font-size: 2rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
        }

        .ltd-avatar-button img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ltd-avatar-button__edit {
            position: absolute;
            right: 3px;
            bottom: 3px;
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border: 2px solid var(--surface);
            border-radius: 50%;
            background: var(--charcoal-deep);
            color: var(--yellow);
            font-size: 0.78rem;
        }

        .ltd-avatar-help {
            margin: 0.5rem 0 0;
            color: var(--text-muted);
            font-size: 0.74rem;
            text-align: center;
        }

        .ltd-profile-card__heading {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .ltd-profile-card__heading h2 {
            margin: 0 0 0.25rem;
            color: var(--text);
            font-size: 1.45rem;
            font-weight: 800;
        }

        .ltd-role-badge,
        .ltd-verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.32rem 0.65rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
        }

        .ltd-role-badge {
            background: rgba(255, 222, 23, 0.2);
            color: var(--yellow-dark);
        }

        .ltd-verification-badge.is-verified {
            background: rgba(40, 167, 69, 0.12);
            color: #218838;
        }

        .ltd-verification-badge.is-unverified {
            background: rgba(220, 53, 69, 0.1);
            color: #c82333;
        }

        .ltd-verify-action {
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--yellow-dark);
            font-size: 0.76rem;
            font-weight: 800;
            text-decoration: underline;
            cursor: pointer;
        }

        .ltd-profile-list {
            border-top: 1px solid var(--border);
        }

        .ltd-profile-item {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.8rem;
            min-height: 78px;
            border-bottom: 1px solid var(--border);
        }

        .ltd-profile-item__icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--bg);
            color: var(--yellow-dark);
        }

        .ltd-profile-item__label {
            display: block;
            margin-bottom: 0.12rem;
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .ltd-profile-item__value {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.45rem;
            color: var(--text);
            font-weight: 700;
            word-break: break-word;
        }

        .ltd-profile-edit {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            padding: 0;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: var(--surface);
            color: var(--text-muted);
        }

        .ltd-profile-edit:hover {
            border-color: var(--yellow);
            background: var(--yellow);
            color: var(--charcoal-deep);
        }

        .ltd-password-action {
            padding-top: 1.25rem;
            text-align: center;
        }

        @media (max-width: 576px) {
            .ltd-profile-card__body {
                padding-right: 1.15rem;
                padding-left: 1.15rem;
            }

            .ltd-profile-item {
                grid-template-columns: 36px minmax(0, 1fr) 34px;
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

    <section class="ltd-profile-card" aria-labelledby="profile-card-title">
        <div class="ltd-profile-card__cover"></div>
        <div class="ltd-profile-card__body">
            <form id="avatarForm" class="ltd-avatar-form" action="{{ route('profile.image.update') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <button id="avatarButton" class="ltd-avatar-button" type="button" aria-label="Change profile photo">
                    @if($user->getFirstMediaUrl())
                        <img src="{{ $user->getFirstMediaUrl() }}" alt="{{ $user->name }} profile photo">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                    <span class="ltd-avatar-button__edit"><i class="fas fa-camera" aria-hidden="true"></i></span>
                </button>
                <input id="profileImage" class="d-none" type="file" name="profileImage"
                    accept=".jpg,.jpeg,.png,.webp">
                <p class="ltd-avatar-help">Select the avatar to change your photo</p>
                @error('profileImage')<p class="text-danger small text-center mb-0">{{ $message }}</p>@enderror
            </form>

            <div class="ltd-profile-card__heading">
                <h2 id="profile-card-title">{{ $user->name }}</h2>
                <span class="ltd-role-badge">
                    <i class="fas fa-user-shield" aria-hidden="true"></i> {{ $user->role }}
                </span>
            </div>

            <div class="ltd-profile-list">
                <div class="ltd-profile-item">
                    <span class="ltd-profile-item__icon"><i class="fas fa-user"></i></span>
                    <div>
                        <span class="ltd-profile-item__label">Full Name</span>
                        <span class="ltd-profile-item__value">{{ $user->name }}</span>
                    </div>
                    <button class="ltd-profile-edit" type="button" data-toggle="modal" data-target="#nameModal"
                        aria-label="Edit full name" title="Edit full name">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>

                <div class="ltd-profile-item">
                    <span class="ltd-profile-item__icon"><i class="fas fa-envelope"></i></span>
                    <div>
                        <span class="ltd-profile-item__label">Email Address</span>
                        <span class="ltd-profile-item__value">
                            {{ $user->email }}
                            <span class="ltd-verification-badge {{ $user->email_verified_at ? 'is-verified' : 'is-unverified' }}">
                                <i class="fas {{ $user->email_verified_at ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                                {{ $user->email_verified_at ? 'Verified' : 'Not verified' }}
                            </span>
                            @unless($user->email_verified_at)
                                <form action="{{ route('verification.send') }}" method="POST">
                                    @csrf
                                    <button class="ltd-verify-action" type="submit">Verify now</button>
                                </form>
                            @endunless
                        </span>
                    </div>
                    <button class="ltd-profile-edit" type="button" data-toggle="modal" data-target="#emailModal"
                        aria-label="Edit email address" title="Edit email address">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>

                <div class="ltd-profile-item">
                    <span class="ltd-profile-item__icon"><i class="fas fa-phone"></i></span>
                    <div>
                        <span class="ltd-profile-item__label">Phone Number</span>
                        <span class="ltd-profile-item__value">
                            {{ $user->phoneNumber }}
                            <span class="ltd-verification-badge {{ $user->phone_verified_at ? 'is-verified' : 'is-unverified' }}">
                                <i class="fas {{ $user->phone_verified_at ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                                {{ $user->phone_verified_at ? 'Verified' : 'Not verified' }}
                            </span>
                            @unless($user->phone_verified_at)
                                <button class="ltd-verify-action" type="button" data-toggle="modal"
                                    data-target="#phoneVerificationModal">Verify now</button>
                            @endunless
                        </span>
                    </div>
                    <button class="ltd-profile-edit" type="button" data-toggle="modal" data-target="#phoneModal"
                        aria-label="Edit phone number" title="Edit phone number">
                        <i class="fas fa-pen"></i>
                    </button>
                </div>
            </div>

            <div class="ltd-password-action">
                <button class="btn btn-primary" type="button" data-toggle="modal" data-target="#passwordModal">
                    <i class="fas fa-lock mr-2"></i>Change Password
                </button>
            </div>
        </div>
    </section>

    @include('admin.profile-modals.name')
    @include('admin.profile-modals.email')
    @include('admin.profile-modals.phone')
    @include('admin.profile-modals.password')
    @include('admin.profile-modals.phone-verification')
@endsection

@section('page-script')
    <script>
        document.getElementById('avatarButton')?.addEventListener('click', function () {
            document.getElementById('profileImage')?.click();
        });

        document.getElementById('profileImage')?.addEventListener('change', function () {
            if (this.files.length > 0) {
                document.getElementById('avatarForm')?.submit();
            }
        });

        @if($errors->has('name'))
            $('#nameModal').modal('show');
        @elseif($errors->has('email'))
            $('#emailModal').modal('show');
        @elseif($errors->has('phoneNumber'))
            $('#phoneModal').modal('show');
        @elseif($errors->has('current_password') || $errors->has('password'))
            $('#passwordModal').modal('show');
        @endif

        @if(session('open_phone_verification') || $errors->has('code'))
            $('#phoneVerificationModal').modal('show');
        @endif
    </script>
@endsection
