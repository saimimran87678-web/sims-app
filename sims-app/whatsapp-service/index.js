const { Client, LocalAuth } = require('whatsapp-web.js');
const express = require('express');
const qrcode = require('qrcode');
const app = express();
const port = 3000;

app.use(express.json());

// Initialize WhatsApp Client
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
        headless: true
    }
});

let isReady = false;
let qrCodeData = null;
let lastError = null;

// Event Listeners
client.on('qr', (qr) => {
    console.log('QR RECEIVED', qr);
    qrcode.toDataURL(qr, (err, url) => {
        qrCodeData = url;
    });
});

client.on('ready', () => {
    console.log('Client is ready!');
    isReady = true;
    qrCodeData = null; // Clear QR once connected
    lastError = null;
});

client.on('auth_failure', (msg) => {
    console.error('AUTHENTICATION FAILURE', msg);
    lastError = 'Authentication failure: ' + msg;
    isReady = false;
});

client.on('disconnected', (reason) => {
    console.log('Client was disconnected', reason);
    isReady = false;
    lastError = 'Disconnected: ' + reason;
    client.initialize(); // Auto reconnect
});

// API Endpoints

// 1. Status Check
app.get('/status', (req, res) => {
    res.json({
        ready: isReady,
        hasQr: !!qrCodeData,
        error: lastError
    });
});

// 2. Get QR Code
app.get('/qr', (req, res) => {
    if (qrCodeData) {
        res.json({ success: true, qr: qrCodeData });
    } else if (isReady) {
        res.json({ success: true, connected: true, message: 'Already connected' });
    } else {
        res.json({ success: false, message: 'QR not generated yet. Please wait.' });
    }
});

// 3. Send Message
app.post('/send', async (req, res) => {
    if (!isReady) {
        return res.status(503).json({ success: false, error: 'Client not ready' });
    }

    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ success: false, error: 'Phone and message are required' });
    }

    try {
        const chatId = phone.includes('@c.us') ? phone : `${phone}@c.us`;
        await client.sendMessage(chatId, message);
        res.json({ success: true, message: 'Message sent' });
    } catch (error) {
        console.error('Send Error:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// 4. Send Bulk Messages
app.post('/send-bulk', async (req, res) => {
    if (!isReady) {
        return res.status(503).json({ success: false, error: 'Client not ready' });
    }

    const { messages } = req.body; // Array of {phone, message}

    if (!Array.isArray(messages)) {
        return res.status(400).json({ success: false, error: 'Messages must be an array' });
    }

    const results = [];
    let sent = 0;
    let failed = 0;

    for (const item of messages) {
        try {
            const chatId = item.phone.includes('@c.us') ? item.phone : `${item.phone}@c.us`;
            await client.sendMessage(chatId, item.message);
            results.push({ phone: item.phone, success: true });
            sent++;

            // Add a small delay to avoid spam detection
            await new Promise(resolve => setTimeout(resolve, 500));
        } catch (error) {
            results.push({ phone: item.phone, success: false, error: error.message });
            failed++;
        }
    }

    res.json({ success: true, sent, failed, results });
});

// Start Client
client.initialize();

// Start Server
app.listen(port, '0.0.0.0', () => {
    console.log(`WhatsApp Service listening at http://0.0.0.0:${port}`);
});
