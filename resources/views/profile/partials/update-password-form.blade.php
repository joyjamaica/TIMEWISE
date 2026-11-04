<section>
    <header class="profile-section-heading" id="password-settings">
        <span class="profile-section-heading__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3m-4 5v2"/></svg>
        </span>
        <span>
            <h2>{{ __('Password & security') }}</h2>
            <p>{{ __('Choose a strong password to keep your account protected.') }}</p>
        </span>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="profile-password-form">
        @csrf
        @method('put')

        <div class="profile-fields profile-fields--password">
        <div class="profile-field">
            <x-input-label for="current_password" :value="__('Current Password')" />
            <x-text-input id="current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="profile-field-error" />
        </div>

        <div class="profile-field">
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="profile-field-error" />
        </div>

        <div class="profile-field">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="profile-field-error" />
        </div>
        </div>

        <div class="profile-form-footer">
            <span class="profile-form-footer__hint">{{ __('Use at least 8 characters and avoid reusing passwords.') }}</span>
            <button type="submit" class="profile-submit">{{ __('Update password') }}</button>
        </div>
    </form>
</section>
