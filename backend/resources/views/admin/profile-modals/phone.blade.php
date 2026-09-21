<div class="modal fade" id="phoneModal" tabindex="-1" role="dialog" aria-labelledby="phoneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="{{ route('profile.phone.update') }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="phoneModalLabel">Change Phone Number</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <label for="phoneNumber">Phone Number</label>
                    <input id="phoneNumber" type="tel" name="phoneNumber" value="{{ old('phoneNumber', $user->phoneNumber) }}"
                        inputmode="numeric" maxlength="10"
                        class="form-control @error('phoneNumber') is-invalid @enderror" required>
                    @error('phoneNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Phone</button>
                </div>
            </form>
        </div>
    </div>
</div>
