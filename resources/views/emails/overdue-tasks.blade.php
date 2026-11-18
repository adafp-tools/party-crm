<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Overdue tasks digest') }}</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; color: #1a1a1a; max-width: 600px; margin: 0 auto; padding: 24px;">
    <h2 style="color: #1a472a;">{{ __('Overdue Tasks') }}</h2>
    <p>{{ __('Hello :name,', ['name' => $recipient->name]) }}</p>
    <p>{{ __('The following tasks assigned to you are overdue:') }}</p>

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ __('Task') }}</th>
                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ __('Unit') }}</th>
                <th style="padding: 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ __('Due') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tasks as $task)
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ $task->title }}</td>
                <td style="padding: 8px; border-bottom: 1px solid #f3f4f6;">{{ $task->orgUnit?->name }}</td>
                <td style="padding: 8px; border-bottom: 1px solid #f3f4f6; color: #dc2626;">
                    {{ $task->due_at?->format('d M Y') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size: 0.85em; color: #6b7280;">
        {{ __('You are receiving this because you have overdue tasks. To stop receiving these digests, ask your administrator.') }}
    </p>
</body>
</html>
