# Ngrok Setup Guide - Complete Reference

## What is Ngrok?
Ngrok creates a **secure tunnel** from the internet to your local PC. It gives you a public URL (like `https://xyz.ngrok-free.dev`) that anyone can use to access your app running at `localhost:8000`.

---

## Option 1: Use Existing Domain (Current Setup)

Your current fixed domain:
```
https://isabella-cherrylike-anomalously.ngrok-free.dev
```

### To use on any PC:
```bash
# 1. Install Ngrok
curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null
echo "deb https://ngrok-agent.s3.amazonaws.com buster main" | sudo tee /etc/apt/sources.list.d/ngrok.list
sudo apt update && sudo apt install ngrok

# 2. Add YOUR auth token
ngrok config add-authtoken YOUR_AUTH_TOKEN_HERE

# 3. Start tunnel with fixed domain
ngrok http --domain=isabella-cherrylike-anomalously.ngrok-free.dev 8000
```

### Get your auth token:
1. Go to: https://dashboard.ngrok.com/get-started/your-authtoken
2. Copy the token
3. Run: `ngrok config add-authtoken <token>`

---

## Option 2: Create NEW Domain (Different Ngrok Account)

If using a different Ngrok account on another PC:

### Step 1: Create Free Ngrok Account
1. Go to: https://ngrok.com/
2. Sign up for free
3. Verify email

### Step 2: Get Your Auth Token
1. Go to: https://dashboard.ngrok.com/get-started/your-authtoken
2. Copy the token

### Step 3: Get a Free Static Domain
1. Go to: https://dashboard.ngrok.com/cloud-edge/domains
2. Click "New Domain"
3. You get ONE free domain like: `your-name-randomly.ngrok-free.dev`
4. Copy this domain name

### Step 4: Configure on Your PC
```bash
# Add auth token
ngrok config add-authtoken YOUR_NEW_TOKEN

# Start with YOUR new domain
ngrok http --domain=your-new-domain.ngrok-free.dev 8000
```

### Step 5: Update Your App's .env
```bash
# Edit .env file
nano ~/SIMS/sims-app/.env

# Change these lines:
APP_URL=https://your-new-domain.ngrok-free.dev
ASSET_URL=https://your-new-domain.ngrok-free.dev

# Clear cache
cd ~/SIMS/sims-app
php artisan config:clear
```

---

## Running Multiple PCs

### Same Domain (Recommended)
- **Only ONE PC** can use the same domain at a time
- When you want to switch:
  1. Stop Ngrok on PC 1
  2. Start Ngrok on PC 2 with same domain

### Different Domains (For simultaneous use)
- Each PC gets its own Ngrok account
- Each PC gets its own domain
- Both can run at the same time
- BUT: users need to know which URL to use

---

## Quick Commands Reference

| Task | Command |
|------|---------|
| Start tunnel (fixed domain) | `ngrok http --domain=YOUR_DOMAIN 8000` |
| Start tunnel (random URL) | `ngrok http 8000` |
| Add auth token | `ngrok config add-authtoken TOKEN` |
| View Ngrok status | `ngrok status` |
| View Ngrok config | `ngrok config check` |
| Stop Ngrok | `Ctrl + C` |

---

## Troubleshooting

### Error: "You must register for a free ngrok account"
```bash
ngrok config add-authtoken YOUR_TOKEN
```

### Error: "Domain not found" or "403"
- Make sure you're using YOUR domain from YOUR account
- Check: https://dashboard.ngrok.com/cloud-edge/domains

### Error: "Tunnel session not found"
- Your auth token might be wrong
- Re-add: `ngrok config add-authtoken YOUR_TOKEN`

### Error: "Address already in use"
- Another Ngrok is running
- Kill it: `pkill ngrok`

### Error: "8000 connection refused"
- Laravel server isn't running
- Start it: `cd ~/SIMS/sims-app && php artisan serve`

---

## Your Current Configuration

**Ngrok Domain:** `isabella-cherrylike-anomalously.ngrok-free.dev`
**Ngrok Dashboard:** https://dashboard.ngrok.com

---

## Workflow Summary

```
Terminal 1: php artisan serve      → Runs Laravel at localhost:8000
Terminal 2: ngrok http ... 8000    → Exposes localhost:8000 to internet
Terminal 3: npm start              → Runs WhatsApp service

Result: App accessible at https://your-domain.ngrok-free.dev
```
