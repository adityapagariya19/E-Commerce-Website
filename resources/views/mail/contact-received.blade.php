<!DOCTYPE html><html><body style="font-family:Arial,sans-serif">
<h3>New message ({{ $contactMessage->source }})</h3><p><b>{{ $contactMessage->name }}</b> &lt;{{ $contactMessage->email }}&gt;</p><p style="white-space:pre-wrap">{{ $contactMessage->message }}</p></body></html>
