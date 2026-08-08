<!DOCTYPE html>
<html lang="en">
<body>
    <p>Hello {{ $creator->name }},</p>
    <p>An administrator moved “{{ $itinerary->name }}” to Trash. It is no longer visible to the public.</p>
    <p>Itinerary ID: {{ $itinerary->id }}</p>
    <p>Moved to Trash at: {{ $itinerary->deleted_at->toDayDateTimeString() }}</p>
    <p>You can restore it within 30 days. Restoration returns it to Draft and does not republish it.</p>
    <p>It is scheduled for permanent removal on {{ $purgeAt->toFormattedDateString() }}.</p>
</body>
</html>
