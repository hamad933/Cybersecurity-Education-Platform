<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title inertia>{{ config('app.name') }}</title>
    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    <x-inertia::head />
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <x-inertia::app />
</body>
</html>
