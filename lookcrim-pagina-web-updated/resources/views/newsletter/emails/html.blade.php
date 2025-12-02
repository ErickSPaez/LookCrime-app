<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $newsletter->subject ?? 'Newsletter' }}</title>
</head>
<body>
    <div style="font-family: Arial, Helvetica, sans-serif; color: #222;">
        @if(!empty($newsletter->image))
            <img src="{{ asset($newsletter->image) }}" alt="" style="max-width:100%; height:auto;" />
        @endif

        <h1>{{ $newsletter->subject }}</h1>
        <div>
            {!! $newsletter->content !!}
        </div>

        @if(isset($user) && $user)
            <p>Olá {{ $user->email }},</p>
        @endif
    </div>
    <footer style="font-size:12px;color:#999;margin-top:20px;">
        <p>Recebeu este email porque subscreveu a newsletter.</p>
    </footer>
</body>
</html>
