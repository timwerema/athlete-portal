<x-mail::message>
# Someone sent you a message

**{{ $m->sender_name }}**, {{ $m->sender_role }} at {{ $m->sender_school }}, wrote to you through your shared profile link.

<x-mail::panel>
{{ $m->body }}
</x-mail::panel>

Their email: {{ $m->sender_email }}

<x-mail::button :url="$inboxUrl">
Open in your inbox
</x-mail::button>

We do not check who sends these messages. Make sure the sender is who they say they are before you share personal details. If you reply to this email, the reply goes to them from your own email address.

Recruiting and transfer contact rules are different for each division and they change. If you are unsure whether you may reply, ask your school's compliance office first.
</x-mail::message>
