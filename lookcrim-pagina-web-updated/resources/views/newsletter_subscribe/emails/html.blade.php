<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Confirmação de Registo</title>
</head>
<body>
    <p>Obrigado por subscrever a newsletter. Para confirmar o seu registo clique no link abaixo:</p>
    <p>
        <a href="{{ url('/newsletter/confirm/'.$subscriber->remember_token) }}">Confirmar subscrição</a>
    </p>
</body>
</html>
