<form method="POST" action="{{ route('collaborate.teams.store') }}" class="clb-form">
    @csrf
    <label>Team name
        <input type="text" name="name" value="{{ old('name') }}" maxlength="100" required>
        @error('name')<span class="clb-error">{{ $message }}</span>@enderror
    </label>
    <label>Description
        <textarea name="description" rows="3" maxlength="1000">{{ old('description') }}</textarea>
        @error('description')<span class="clb-error">{{ $message }}</span>@enderror
    </label>
    <label>Visibility
        <select name="visibility">
            <option value="private" @selected(old('visibility', 'private') === 'private')>Private – only the owner sees the invite code</option>
            <option value="public" @selected(old('visibility') === 'public')>Public – every member can share the invite code</option>
        </select>
        @error('visibility')<span class="clb-error">{{ $message }}</span>@enderror
    </label>
    <div class="clb-form__actions">
        <button class="clb-btn clb-btn--dark">Create team</button>
    </div>
</form>
