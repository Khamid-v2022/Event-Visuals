<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event reminder</title>
</head>
<body>
    <p>Hi {{ $user->name }},</p>
    <p>This is your {{ $windowLabel }} reminder for <strong>{{ $event->payload['name'] ?? 'an event' }}</strong>.</p>
</body>
</html>
