<div class="modal fade" id="phoneVerificationModal" tabindex="-1" role="dialog"
    aria-labelledby="phoneVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="phoneVerificationModalLabel">Verify Phone Number</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Generate a six-digit development code for {{ $user->phoneNumber }}.</p>

                @if(session('local_phone_otp'))
                    <div class="alert alert-warning">
                        Local verification code:
                        <strong class="ml-1">{{ session('local_phone_otp') }}</strong>
                        <div class="small mt-1">In production, this code will be delivered by the configured SMS provider.</div>
                    </div>
                @endif

                <form action="{{ route('profile.phone.verification.send') }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-block">Generate Code</button>
                </form>

                <form action="{{ route('profile.phone.verification.verify') }}" method="POST">
                    @csrf
                    <label for="code">Verification Code</label>
                    <input id="code" type="text" name="code" inputmode="numeric" maxlength="6"
                        class="form-control text-center @error('code') is-invalid @enderror"
                        placeholder="000000" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-primary btn-block mt-3">Verify Phone</button>
                </form>
            </div>
        </div>
    </div>
</div>
