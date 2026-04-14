@if (session('status'))
    <div role="status" class="rounded border border-green-700 bg-green-50 px-3 py-2 text-sm text-green-900">
        {{ session('status') }}
    </div>
@endif

@php
    $sessionErrors = session('errors');
    $authErrors = $sessionErrors instanceof \Illuminate\Support\ViewErrorBag
        ? $sessionErrors->getBag('default')->all()
        : $errors->all();
    $authErrors = $authErrors === [] ? (array) session('auth_error_messages', []) : $authErrors;
@endphp

@if (count($authErrors) > 0)
    <div role="alert" class="rounded border border-red-700 bg-red-50 px-3 py-2 text-sm text-red-900">
        <ul class="space-y-1">
            @foreach ($authErrors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
