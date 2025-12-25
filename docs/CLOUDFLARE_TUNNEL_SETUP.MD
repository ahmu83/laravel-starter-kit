# Cloudflare Tunnel Setup for Local Development

This document explains how to expose multiple local development sites over HTTPS using **Cloudflare Tunnel**, without ngrok, and using a custom domain.

---

## Prerequisites

- cloudflared installed
- Domain managed by Cloudflare (e.g. devraa.com)
- Local apps available via .test domains (Valet or similar)

---

## Install cloudflared

Before creating a tunnel, install the Cloudflare Tunnel CLI (cloudflared) and authenticate it with your Cloudflare account.

### macOS (Homebrew)

```bash
brew install cloudflare/cloudflare/cloudflared
```

Verify installation:

```bash
cloudflared --version
```

Authenticate with Cloudflare:

```bash
cloudflared tunnel login
```

This opens a browser window where you authorize the domain.

---

## Setup Flow Overview

1. Install cloudflared
2. Authenticate with Cloudflare
3. Create the tunnel
4. Create DNS routes
5. Run the tunnel
6. Configure config.yml

---

## Step 1: Create the tunnel

Create a single tunnel that will be reused for all local apps.

```bash
cloudflared tunnel create devraa-local
```

This command:
- Creates the tunnel in Cloudflare
- Generates a credentials JSON file
- Outputs the tunnel ID

---

## Step 2: Create DNS routes (proxied hostnames)

Each public hostname must be explicitly routed to the tunnel.

```bash
cloudflared tunnel route dns devraa-local dvc-laravel-starter-kit-test.devraa.com
cloudflared tunnel route dns devraa-local amazingfactshome-test.devraa.com
cloudflared tunnel route dns devraa-local wp-dvc-laravel-starter-kit-test.devraa.com
```

Notes:
- These commands automatically create proxied (orange-cloud) DNS records
- This step is required once per hostname
- For every new hostname added to config.yml, run the same route command

---

## Step 3: Run the tunnel

Start the tunnel:

```bash
cloudflared tunnel run devraa-local
```

To explicitly specify the config file:

```bash
cloudflared tunnel --config ~/.cloudflared/config.yml run devraa-local
```

---

## Example config.yml

Location:

```text
~/.cloudflared/config.yml
```

Contents:

```yaml
tunnel: devraa-local
credentials-file: ~/.cloudflared/<JSON_FILE>.json

ingress:
  - hostname: dvc-laravel-starter-kit-test.devraa.com
    service: https://dvc-laravel-starter-kit.test:443
    originRequest:
      httpHostHeader: dvc-laravel-starter-kit.test
      noTLSVerify: true

  - hostname: amazingfactshome-test.devraa.com
    service: https://amazingfactshome.test:443
    originRequest:
      httpHostHeader: amazingfactshome.test
      noTLSVerify: true

  # Alternative HTTP origin if HTTPS origin causes issues
  # - hostname: amazingfactshome-test.devraa.com
  #   service: http://127.0.0.1:80
  #   originRequest:
  #     httpHostHeader: amazingfactshome.test

  - service: http_status:404
```

---

## Update site url in wp-config.php

```php
$site_urls = [
  'url' => 'https://amazingfactshome.test',
  'proxy' => 'https://amazingfactshome-test.devraa.com',
];

// Detect proxy / tunnel request
$is_proxied = (
  ! empty($_SERVER['HTTP_X_FORWARDED_HOST']) &&
  ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
  $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
);

$site_url = $is_proxied ? $site_urls['proxy'] : $site_urls['url'];

define('WP_HOME', $site_url);
define('WP_SITEURL', $site_url);

// Tell WordPress the request is HTTPS behind the proxy
if ( $is_proxied ) {
  $_SERVER['HTTPS'] = 'on';
}
```

---

## Key Notes

- One tunnel can serve multiple hostnames
- Ingress rules are matched top-down
- The final http_status:404 entry is required
- HTTPS is terminated at Cloudflare
- Origin services can be HTTP or HTTPS

---

## Common Issues

### Cloudflare returns 404

- Tunnel was not restarted after editing config.yml
- Ingress rule is missing at runtime

Fix:

```bash
cloudflared tunnel --config ~/.cloudflared/config.yml run devraa-local
```

### App redirects back to .test

- The application considers the .test domain canonical
- Update application configuration (APP_URL, WordPress siteurl/home)

---

## Result

Local apps are accessible at:

- https://dvc-laravel-starter-kit-test.devraa.com
- https://amazingfactshome-test.devraa.com

With:
- Stable HTTPS URLs
- OAuth and webhook compatibility
- No ngrok limitations
