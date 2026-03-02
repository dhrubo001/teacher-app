<!DOCTYPE html>
<html>

<head>
    <title>Dashboard</title>
</head>

<body>

    <h1>Dashboard</h1>

    <p>Logged in as: <strong>{{ auth()->user()->phone }}</strong></p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>

</html>
