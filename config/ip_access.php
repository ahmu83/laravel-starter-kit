<?php

return [

    /*
  |--------------------------------------------------------------------------
  | Strict IP Allowlist (Exact Matches)
  |--------------------------------------------------------------------------
  |
  | These lists are used when the IP access mode is `strict`.
  | Each entry must match the client IP exactly after normalization.
  |
  | Environment variable format (comma-separated):
  |
  |   IP_ACCESS_ALLOWED_IPV4=162.197.8.160,10.0.0.5
  |   IP_ACCESS_ALLOWED_IPV6=2001:4860:7:110e::d5
  |
  | Typical use cases:
  | - Admin-only dashboards (Pulse, Horizon, Telescope)
  | - One-off personal access
  | - Highly sensitive internal tools
  |
  */

    'allowed_ipv4' => array_filter(
        array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV4', '')))
    ),

    'allowed_ipv6' => array_filter(
        array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV6', '')))
    ),

    /*
  |--------------------------------------------------------------------------
  | Class / Network-Based IP Allowlist
  |--------------------------------------------------------------------------
  |
  | These lists are used when the IP access mode is `class`.
  |
  | Recommended format: CIDR notation
  |
  | Environment variable format (comma-separated):
  |
  |   IP_ACCESS_ALLOWED_IPV4_CLASS=162.197.8.0/24,10.0.0.0/8
  |   IP_ACCESS_ALLOWED_IPV6_CLASS=2001:4860:7:110e::/64
  |
  | How this behaves:
  | - IPv4: allows any IP within the specified subnet
  | - IPv6: allows any IP within the specified prefix
  |
  | Typical use cases:
  | - Office networks
  | - VPN ranges
  | - Cloud provider blocks
  | - Dynamic residential IPs
  |
  | Notes:
  | - CIDR is strongly recommended for clarity and correctness
  | - Single IPs (e.g. 1.2.3.4) will still work but are treated
  |   as class anchors, not exact matches
  |
  */

    'allowed_ipv4_class' => array_filter(
        array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV4_CLASS', '')))
    ),

    'allowed_ipv6_class' => array_filter(
        array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV6_CLASS', '')))
    ),

];
