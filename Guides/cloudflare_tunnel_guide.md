# Cloudflare Tunnel Setup Guide (Beginner)

## What is a Cloudflare Tunnel?
A tunnel connects your local PC to the internet through Cloudflare's network. Instead of a random URL that changes every restart, you get a **permanent address**.

---

## Step 1: Login to Cloudflare
Open your terminal and run:
```bash
cloudflared tunnel login
```
This opens a browser. Login with your Cloudflare account and select **any domain** (even if you don't have one yet - you can use Cloudflare's free subdomain).

After login, a certificate is saved at: `~/.cloudflared/cert.pem`

---

## Step 2: Create a Named Tunnel
```bash
cloudflared tunnel create sims-tunnel
```
This creates a tunnel with the name "sims-tunnel" and generates a credentials file.

**Note the Tunnel ID** that appears (looks like: `a1b2c3d4-5678-...`)

---

## Step 3: Create Configuration File
Create the config file:
```bash
nano ~/.cloudflared/config.yml
```

Paste this content:
```yaml
tunnel: sims-tunnel
credentials-file: /home/saim/.cloudflared/<TUNNEL-ID>.json

ingress:
  - hostname: sims.yourdomain.com
    service: http://127.0.0.1:8000
  - service: http_status:404
```

**Replace:**
- `<TUNNEL-ID>` with your actual tunnel ID
- `sims.yourdomain.com` with your domain (or we'll use a free subdomain)

Save: `Ctrl+O`, `Enter`, `Ctrl+X`

---

## Step 4: Route DNS (Connect Domain to Tunnel)
```bash
cloudflared tunnel route dns sims-tunnel sims.yourdomain.com
```
This tells Cloudflare: "Point this domain to my tunnel"

---

## Step 5: Run Your Tunnel
```bash
cloudflared tunnel run sims-tunnel
```
Your app is now accessible at your permanent URL!

---

## Using a FREE Cloudflare Domain (No Purchase Needed)

If you don't own a domain, you can get a free `*.cfargotunnel.com` subdomain:

1. Go to [Cloudflare Zero Trust Dashboard](https://one.dash.cloudflare.com/)
2. Navigate to: **Networks** → **Tunnels**
3. Click **Create a tunnel**
4. Name it: `sims-tunnel`
5. Install connector (it shows commands to run)
6. Add **Public Hostname**:
   - Subdomain: `sims` (or any name)
   - Domain: Select the free option
   - Service: `http://127.0.0.1:8000`

---

## Run Tunnel on Startup (Optional)
To make the tunnel start automatically:
```bash
sudo cloudflared service install
sudo systemctl enable cloudflared
sudo systemctl start cloudflared
```

---

## Summary of Commands

| Step | Command |
|------|---------|
| Login | `cloudflared tunnel login` |
| Create tunnel | `cloudflared tunnel create sims-tunnel` |
| List tunnels | `cloudflared tunnel list` |
| Route DNS | `cloudflared tunnel route dns sims-tunnel your.domain` |
| Run tunnel | `cloudflared tunnel run sims-tunnel` |
| Delete tunnel | `cloudflared tunnel delete sims-tunnel` |

---

## Your Next Step
Run this command in your terminal to start:
```bash
cloudflared tunnel login
```
A browser will open - login with your Cloudflare account!
