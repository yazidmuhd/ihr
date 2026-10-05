<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1"
    >
    <title inertia>i-HR</title>
    @unless (app()->environment('production'))
      <meta name="robots" content="noindex, nofollow">
    @endunless

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
  </head>
  <body class="font-sans antialiased bg-slate-50">
    @if (app()->environment('staging'))
      <div role="status" style="position:sticky;top:0;z-index:9999;background:#f59e0b;color:#1c1917;font:600 13px/1.4 ui-sans-serif,system-ui,sans-serif;text-align:center;padding:6px 12px">
        Test environment — data is reset regularly and e-mails are not delivered. Please don't enter real personal information.
      </div>
    @endif
    @inertia
  </body>
</html>
