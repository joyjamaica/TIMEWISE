<form method="POST" action="{{ route('collaborate.teams.join.store') }}" class="clb-form">
    @csrf
    <label>Invitation code
        <input type="text" name="invite_code" value="{{ old('invite_code') }}" maxlength="12"
               placeholder="e.g. K7Q2M9XA" autocomplete="off" required>
        @error('invite_code')<span class="clb-error">{{ $message }}</span>@enderror
    </label>
    <div class="clb-form__actions">
        <button class="clb-btn clb-btn--dark">Join team</button>
    </div>
</form>
