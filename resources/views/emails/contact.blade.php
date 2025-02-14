@component('mail::message')
# Neue Nachricht

Sie haben eine neue Nachricht über das Kontaktformular erhalten.

**Nachricht:**\
{{ $message }}

**Name:**\
{{ $name }}

**E-Mail:**\
{{ $email }}

Bitte antworten Sie dem Absender in einer neuen E-Mail.

@endcomponent
