@if (session('status'))<div class="clb-flash" role="status">{{ session('status') }}</div>@endif
@if ($errors->any() && ! $errors->hasAny(['name','description','visibility','invite_code']))
    <div class="clb-flash clb-flash--error" role="alert">
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
