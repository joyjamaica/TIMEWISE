<section>
    <header class="profile-section-heading">
        <span class="profile-section-heading__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.5-3.5 2.8-5.5 7-5.5s6.5 2 7 5.5"/></svg>
        </span>
        <span>
            <h2>{{ __('Personal information') }}</h2>
            <p>{{ __("Update your account's profile photo, name, and email address.") }}</p>
        </span>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="profile-information-form" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="profile-photo-field">
            <div class="profile-photo-control">
                <div class="profile-photo-preview">
                    <img
                        id="profile-photo-preview"
                        src="{{ $user->profile_photo_path ? asset('storage/'.$user->profile_photo_path) : '' }}"
                        alt="{{ __('Profile photo preview') }}"
                        @if (! $user->profile_photo_path) hidden @endif
                    >
                    <span id="profile-photo-initials" @if ($user->profile_photo_path) hidden @endif>
                        {{ collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                    </span>
                </div>
                <div class="profile-photo-input">
                    <input
                        id="profile_photo"
                        name="profile_photo"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        aria-describedby="profile-photo-help profile-photo-error"
                    >
                    <label class="profile-photo-button" for="profile_photo">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5"/></svg>
                        {{ __('Choose a photo') }}
                    </label>
                    <p id="profile-photo-help">{{ __('JPG, PNG, or WebP · Up to 2 MB') }}</p>
                    <div id="profile-photo-error" class="profile-field-error">
                        <x-input-error :messages="$errors->get('profile_photo')" />
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-fields">
            <div class="profile-field">
                <x-input-label for="name" :value="__('Full name')" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="profile-field-error" :messages="$errors->get('name')" />
            </div>

            <div class="profile-field">
                <x-input-label for="email" :value="__('Email address')" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="profile-field-error" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="profile-verification">
                        <span>
                        {{ __('Your email address is unverified.') }}
                        </span>
                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <span>{{ __('A new verification link has been sent to your email address.') }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="profile-form-footer">
            <span class="profile-form-footer__hint">{{ __('Your details are only visible to you.') }}</span>
            <button type="submit" class="profile-submit">
                {{ __('Save changes') }}
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </button>
        </div>
    </form>

    <script>
        document.getElementById('profile_photo')?.addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (!file) return;

            const preview = document.getElementById('profile-photo-preview');
            const initials = document.getElementById('profile-photo-initials');
            if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
            preview.dataset.objectUrl = URL.createObjectURL(file);
            preview.src = preview.dataset.objectUrl;
            preview.hidden = false;
            initials.hidden = true;
        });
    </script>
</section>
