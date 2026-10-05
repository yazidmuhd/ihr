<?php

$list = fn (?string $value) => array_values(array_filter(array_map('trim', explode(',', (string) $value))));

return [

    /*
    | Header that carries the visitor's real IP, set by the hosting proxy.
    | Railway: X-Real-IP (its edge overwrites any value the visitor sends).
    | Leave empty when the app is reached directly (local development).
    | Never set this unless every request passes through that proxy, or visitors could fake their IP.
    */
    'client_ip_header' => env('CLIENT_IP_HEADER'),

    /*
    | Only these IPs / ranges may use the app. Comma-separated, IPv4 or IPv6, CIDR allowed:
    |   IP_ALLOWLIST="203.0.113.10, 198.51.100.0/24, 2001:db8::/48"
    | Add the office's public IP and the VPN's egress IPs. Empty = no restriction (public).
    */
    'allowlist' => $list(env('IP_ALLOWLIST')),

    /*
    | What the allowlist protects:
    |   hr   (default) the HR area and HR accounts; applicants register and apply from anywhere
    |   all  the whole site (only if candidates are internal too)
    */
    'allowlist_scope' => env('IP_ALLOWLIST_SCOPE', 'hr'),

    // Paths that are never restricted (platform health check)
    'allowlist_exempt' => ['up'],
];
