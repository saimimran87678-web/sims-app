const express = require('express');
const cors = require('cors');
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(cors());
app.use(express.json());

// State
let qrCodeData = null;
let isReady = false;
let lastError = null;

// Initialize WhatsApp Client with persistent session
const client = new Client({
    authStrategy: new LocalAuth({
        dataPath: './session'
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
        ],
        ignoreDefaultArgs: ['--disable-extensions']

    }
});

// Event: QR Code generated (for first-time auth)
client.on('qr', async (qr) => {
    console.log('📱 QR Code received. Scan with WhatsApp.');
    qrCodeData = await qrcode.toDataURL(qr);
    isReady = false;
});

// Event: Client is ready
client.on('ready', () => {
    console.log('✅ WhatsApp Client is ready!');
    isReady = true;
    qrCodeData = null;
});

// Event: Authenticated
client.on('authenticated', () => {
    console.log('🔐 Authenticated successfully!');
});

// Event: Auth failure
client.on('auth_failure', (msg) => {
    console.error('❌ Authentication failed:', msg);
    lastError = 'Authentication failed: ' + msg;
    isReady = false;
});

// Event: Disconnected
client.on('disconnected', (reason) => {
    console.log('🔌 Disconnected:', reason);
    isReady = false;
    // Try to reconnect
    client.initialize();
});

// API: Get status
app.get('/status', (req, res) => {
    res.json({
        ready: isReady,
        hasQr: !!qrCodeData,
        error: lastError
    });
});

// API: Get QR code
app.get('/qr', (req, res) => {
    if (isReady) {
        return res.json({
            success: true,
            message: 'Already connected',
            connected: true
        });
    }

    if (qrCodeData) {
        return res.json({
            success: true,
            qr: qrCodeData,
            connected: false
        });
    }

    res.json({
        success: false,
        message: 'QR code not yet available. Please wait...',
        connected: false
    });
});

// API: Logout and Reset Session
app.post('/logout', async (req, res) => {
    try {
        console.log('🚪 Logout requested via API');
        isReady = false;
        qrCodeData = null;

        // Try to logout nicely first
        try {
            await client.logout();
        } catch (e) {
            console.log('⚠️ Logout warning (non-fatal):', e.message);
        }

        // Destroy the client instance
        try {
            await client.destroy();
        } catch (e) {
            console.log('⚠️ Destroy warning (non-fatal):', e.message);
        }

        // Delete session and cache directories to ensure full reset
        // Using rmSync with recursive: true for Node 14+
        const pathsToDelete = [
            path.join(__dirname, 'session'),
            path.join(__dirname, '.wwebjs_cache'),
            path.join(__dirname, '.wwebjs_auth')
        ];

        pathsToDelete.forEach(dir => {
            if (fs.existsSync(dir)) {
                try {
                    fs.rmSync(dir, { recursive: true, force: true });
                    console.log(`Deleted: ${dir}`);
                } catch (err) {
                    console.error(`Failed to delete ${dir}:`, err.message);
                }
            }
        });

        console.log('✅ Session cleared locally');

        // Re-initialize client to generate new QR
        console.log('🔄 Restarting client...');
        client.initialize();

        res.json({ success: true, message: 'Logged out and session cleared. Please wait for new QR code.' });

    } catch (error) {
        console.error('❌ Error during logout:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// API: Send message
app.post('/send', async (req, res) => {
    const { phone, message } = req.body;

    if (!isReady) {
        return res.status(503).json({
            success: false,
            error: 'WhatsApp is not connected. Please scan QR code first.'
        });
    }

    if (!phone || !message) {
        return res.status(400).json({
            success: false,
            error: 'Phone and message are required'
        });
    }

    try {
        // Format phone number (remove + and ensure it's in correct format)
        let formattedPhone = phone.replace(/[^0-9]/g, '');

        // Convert Pakistani local format to international
        if (formattedPhone.startsWith('0')) {
            formattedPhone = '92' + formattedPhone.substring(1);
        }

        // WhatsApp format: number@c.us
        const chatId = formattedPhone + '@c.us';

        // Check if number exists on WhatsApp
        const isRegistered = await client.isRegisteredUser(chatId);
        if (!isRegistered) {
            return res.status(400).json({
                success: false,
                error: `Number ${phone} is not registered on WhatsApp`
            });
        }

        // Send message (sendSeen: false to avoid markedUnread bug)
        await client.sendMessage(chatId, message, { sendSeen: false });

        console.log(`📤 Message sent to ${formattedPhone}`);

        res.json({
            success: true,
            message: 'Message sent successfully',
            to: formattedPhone
        });

    } catch (error) {
        console.error('❌ Error sending message:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// API: Send bulk messages
app.post('/send-bulk', async (req, res) => {
    const { messages } = req.body; // Array of { phone, message }

    if (!isReady) {
        return res.status(503).json({
            success: false,
            error: 'WhatsApp is not connected'
        });
    }

    if (!messages || !Array.isArray(messages)) {
        return res.status(400).json({
            success: false,
            error: 'Messages array is required'
        });
    }

    const results = [];

    for (const item of messages) {
        try {
            let formattedPhone = item.phone.replace(/[^0-9]/g, '');
            if (formattedPhone.startsWith('0')) {
                formattedPhone = '92' + formattedPhone.substring(1);
            }

            const chatId = formattedPhone + '@c.us';
            await client.sendMessage(chatId, item.message, { sendSeen: false });

            results.push({ phone: item.phone, success: true });

            // Small delay between messages to avoid rate limiting
            await new Promise(resolve => setTimeout(resolve, 1000));

        } catch (error) {
            results.push({ phone: item.phone, success: false, error: error.message });
        }
    }

    res.json({
        success: true,
        results,
        sent: results.filter(r => r.success).length,
        failed: results.filter(r => !r.success).length
    });
});

// Configure Multer for file uploads
const multer = require('multer');
const { MessageMedia } = require('whatsapp-web.js');

const upload = multer({ dest: 'uploads/' });

// API: Send Media (Image, DB, Audio)
// Expects: 'phone', 'caption' (optional), and file in 'file' field
app.post('/send-media', upload.single('file'), async (req, res) => {
    if (!isReady) {
        return res.status(503).json({ success: false, error: 'WhatsApp not connected' });
    }

    if (!req.file || !req.body.phone) {
        return res.status(400).json({ success: false, error: 'Phone and file are required' });
    }

    const { phone, caption, isVoice } = req.body;
    const filePath = req.file.path;

    try {
        let formattedPhone = phone.replace(/[^0-9]/g, '');
        if (formattedPhone.startsWith('0')) {
            formattedPhone = '92' + formattedPhone.substring(1);
        }
        const chatId = formattedPhone + '@c.us';

        // Check if registered
        const isRegistered = await client.isRegisteredUser(chatId);
        if (!isRegistered) {
            fs.unlinkSync(filePath); // Cleanup
            return res.status(400).json({ success: false, error: 'Number not registered' });
        }

        // Create MessageMedia instance from uploaded file
        const media = MessageMedia.fromFilePath(filePath);

        // Options for voice notes and sendSeen fix
        const options = { sendSeen: false };
        if (caption) options.caption = caption;
        if (isVoice === 'true') options.sendAudioAsVoice = true;

        await client.sendMessage(chatId, media, options);

        console.log(`📤 Media sent to ${formattedPhone}`);

        // Cleanup uploaded file
        fs.unlinkSync(filePath);

        res.json({ success: true, message: 'Media sent successfully' });

    } catch (error) {
        console.error('❌ Error sending media:', error);
        if (fs.existsSync(filePath)) fs.unlinkSync(filePath); // Cleanup on error
        res.status(500).json({ success: false, error: error.message });
    }
});

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, '0.0.0.0', () => {
    console.log(`🚀 WhatsApp Service running on port ${PORT}`);
    console.log('📡 Initializing WhatsApp client...');
    client.initialize();
});
