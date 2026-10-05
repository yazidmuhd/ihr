<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Access restricted · i-HR</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc;
               font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; color: #0f172a; }
        main { max-width: 30rem; margin: 1rem; padding: 2rem; background: #fff; border-radius: 1rem;
               box-shadow: 0 0 0 1px #e2e8f0; }
        h1 { margin: 0 0 .5rem; font-size: 1.25rem; }
        p, li { color: #475569; line-height: 1.55; font-size: .95rem; }
        code { background: #f1f5f9; padding: .15rem .45rem; border-radius: .4rem; font-size: .95rem; color: #0f172a; }
        ul { padding-left: 1.2rem; }
    </style>
</head>
<body>
<main>
    <h1>i-HR is only available on the company network</h1>
    <p>Your connection comes from <code>{{ $ip }}</code>, which is not an approved network.</p>
    <ul>
        <li>Connect to the company VPN, then reload this page.</li>
        <li>Already on the VPN? Make sure it sends <em>all</em> traffic through the VPN
            (not split tunnelling), or ask IT to route this site through it.</li>
        <li>Still blocked? Send the address above to your HR system administrator.</li>
    </ul>
</main>
</body>
</html>
