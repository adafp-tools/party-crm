<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $campaign->subject ?? $campaign->title }}</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; color: #1a1a1a; max-width: 600px; margin: 0 auto; padding: 24px;">
    <p>{{ __('Hello :name,', ['name' => $recipient->first_name]) }}</p>

    <div style="margin: 16px 0; white-space: pre-line;">{{ $campaign->body }}</div>

    <p style="font-size: 0.85em; color: #6b7280; margin-top: 32px;">
        {{ __('To unsubscribe from these emails, contact your branch office.') }}
    </p>
</body>
</html>
