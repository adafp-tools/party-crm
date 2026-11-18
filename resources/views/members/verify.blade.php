<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('Member Verification') }}</title>
<style>
body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f1f5f9; margin: 0; }
.card { background: #fff; border-radius: 12px; padding: 2rem; max-width: 360px; width: 100%; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.1); }
.icon { font-size: 3rem; margin-bottom: 1rem; }
.valid { color: #16a34a; }
.invalid { color: #dc2626; }
h1 { font-size: 1.5rem; margin-bottom: .5rem; }
p { color: #6b7280; font-size: .9rem; }
.name { font-size: 1.2rem; font-weight: 600; margin-top: 1rem; color: #111827; }
</style>
</head>
<body>
<div class="card">
@if ($isValid)
    <div class="icon">✅</div>
    <h1 class="valid">{{ __('Valid Member') }}</h1>
    <p class="name">{{ $firstName }} {{ $lastInitial }}</p>
    <p>{{ __('This membership card is valid.') }}</p>
@else
    <div class="icon">❌</div>
    <h1 class="invalid">{{ __('Not Valid') }}</h1>
    <p>{{ __('This membership card is not valid or the member is not currently active.') }}</p>
@endif
</div>
</body>
</html>
