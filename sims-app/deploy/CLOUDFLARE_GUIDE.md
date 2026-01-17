# Cloudflare Tunnel Guide

This guide explains how to expose your local SIMS application to the internet using Cloudflare Tunnel.

## Prerequisites
1.  Linux Mint / Ubuntu.
2.  SIMS Application running locally (`php artisan serve`).

## Installation
Run the included setup script to install `cloudflared`:

```bash
chmod +x deploy/setup-cloudflare.sh
./deploy/setup-cloudflare.sh
```

## Quick Start (No Domain Required)
If you just want a quick public URL (e.g., for testing or temporary sharing):

1.  Start your Laravel app:
    ```bash
    php artisan serve
    ```
2.  In a **new terminal**, start the tunnel:
    ```bash
    cloudflared tunnel --url http://localhost:8000
    ```
3.  Cloudflare will print a URL like `https://funny-name-123.trycloudflare.com`.
    - **Copy this URL**. This is your public website link!

## Permanent Domain Setup (Recommended)
If you have a domain name (e.g., `myschool.com`) added to Cloudflare:

1.  **Login**:
    ```bash
    cloudflared tunnel login
    ```
    (This opens a browser to authorize your domain).

2.  **Create a Tunnel**:
    ```bash
    cloudflared tunnel create sims-local
    ```

3.  **Route DNS**:
    Connect your tunnel to a subdomain (e.g., `sims.myschool.com`):
    ```bash
    cloudflared tunnel route dns sims-local sims.myschool.com
    ```

4.  **Run the Tunnel**:
    ```bash
    cloudflared tunnel run --url http://localhost:8000 sims-local
    ```

## Keeping it Running
To keep the tunnel running even if you close the terminal, use `nohup` or `screen`:

```bash
nohup cloudflared tunnel --url http://localhost:8000 > tunnel.log 2>&1 &
```
