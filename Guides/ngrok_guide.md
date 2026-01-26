# Remote Access Guide (Fixed Domain)

## Your Permanent URL
You can now access your app from ANYWHERE in the world at:
👉 **https://isabella-cherrylike-anomalously.ngrok-free.dev**

---

## How to Start Remote Access
Whenever you restart your PC, you need to turn two things on:

### 1. Start the App (Terminal 1)
Open a terminal and run:
```bash
cd ~/SIMS/sims-app
composer dev
```

### 2. Start the Tunnel (Terminal 2)
Open a **new terminal window** and run:
```bash
ngrok http --domain=isabella-cherrylike-anomalously.ngrok-free.dev 8000
```

---

## Troubleshooting

- **"Connection Refused"**: Check if your app is running (Step 1).
- **"Session Expired"**: Just restart the `ngrok` command.
- **"Web Interface"**: You can see traffic details at http://127.0.0.1:4040

---

## Sharing with Others
- Send them the link above.
- It works on Phones, Tablets, and other PCs.
- No installation required for them!
