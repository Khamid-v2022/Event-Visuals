<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance confirmed</title>
</head>
<body>
    <p>Hi {{ $user->name }},</p>
    <p>You are confirmed for <strong>{{ $event->payload['name'] ?? 'an event' }}</strong>.</p>
    <p>We will send reminders as the event approaches.</p>
</body>
</html>
