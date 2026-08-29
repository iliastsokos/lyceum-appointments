@php($appName = config('app.name', 'Lyceum Appointments'))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} &middot; {{ $appName }}</title>
    {{--
        Deliberately self-contained: no @vite, no Blade components. Error
        pages (especially 500) must still render a friendly message even if
        the compiled asset pipeline itself is the thing that's broken.
    --}}
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #111827;
        }
        .card {
            width: 100%;
            max-width: 26rem;
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
            padding: 2.25rem 2rem;
            text-align: center;
        }
        .code { font-size: 2.75rem; font-weight: 700; color: #d1d5db; line-height: 1; }
        h1 { margin: 0.5rem 0 0; font-size: 1.25rem; font-weight: 600; }
        p { margin: 0.75rem 0 0; font-size: 0.9375rem; color: #4b5563; line-height: 1.5; }
        a.button {
            display: inline-block;
            margin-top: 1.75rem;
            padding: 0.625rem 1.25rem;
            background: #1f2937;
            color: #ffffff;
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        a.button:hover { background: #374151; }
        a.button:focus-visible { outline: 2px solid #4f46e5; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <div class="code" aria-hidden="true">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="button" href="{{ url('/') }}">{{ __('Go to homepage') }}</a>
    </main>
</body>
</html>
