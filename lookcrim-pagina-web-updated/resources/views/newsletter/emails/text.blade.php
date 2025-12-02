{{ $newsletter->subject ?? 'Newsletter' }}

--

{{ strip_tags($newsletter->content) }}
