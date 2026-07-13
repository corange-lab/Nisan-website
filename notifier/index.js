/**
 * Nisan Outage Notifier
 * ---------------------
 * Polls https://www.nisan.co.in/api/status.php every minute.
 * If network has been down for > ALERT_AFTER_MIN minutes, sends a WhatsApp
 * message to all customers in customers.json via WhatsApp Cloud API.
 * Sends a recovery message when service comes back up.
 *
 * Setup:
 *   1. Copy .env.example to .env and fill in your values
 *   2. Add customer numbers to customers.json
 *   3. npm install
 *   4. node index.js   (or use PM2: pm2 start index.js --name nisan-notifier)
 */

require('dotenv').config();
const axios = require('axios');

const STATUS_API      = 'https://www.nisan.co.in/api/status.php?action=status';
const ALERT_AFTER_MIN = parseInt(process.env.ALERT_AFTER_MIN || '10', 10);
const POLL_INTERVAL   = 60 * 1000; // check every 60 seconds

// WhatsApp Cloud API (Meta) — free tier works fine for this
const WA_TOKEN        = process.env.WA_TOKEN;        // your permanent access token
const WA_PHONE_ID     = process.env.WA_PHONE_ID;     // phone number ID from Meta dashboard
const WA_API_URL      = `https://graph.facebook.com/v19.0/${WA_PHONE_ID}/messages`;

// Customers to notify — loaded from customers.json
// Format: [{ "name": "Ramesh", "phone": "919825100000" }, ...]
// Phone must include country code, no + or spaces: 91XXXXXXXXXX
const CUSTOMERS       = require('./customers.json');

// State — tracked in memory (survives restarts as long as outage is ongoing via API)
let alertSent     = false;  // true once we've sent the outage alert
let recoverySent  = false;  // true once we've sent the recovery message

async function fetchStatus() {
  const res = await axios.get(STATUS_API, { timeout: 10000 });
  return res.data;
}

async function sendWhatsApp(phone, message) {
  if (!WA_TOKEN || !WA_PHONE_ID) {
    console.log(`[WA MOCK] To ${phone}: ${message}`);
    return;
  }
  await axios.post(WA_API_URL, {
    messaging_product: 'whatsapp',
    to: phone,
    type: 'text',
    text: { body: message }
  }, {
    headers: {
      Authorization: `Bearer ${WA_TOKEN}`,
      'Content-Type': 'application/json'
    }
  });
}

async function notifyAll(message) {
  console.log(`[NOTIFY] Sending to ${CUSTOMERS.length} customers: ${message}`);
  for (const customer of CUSTOMERS) {
    try {
      const personalised = message.replace('{name}', customer.name || 'Customer');
      await sendWhatsApp(customer.phone, personalised);
      console.log(`  ✓ Sent to ${customer.name} (${customer.phone})`);
      // Small delay between messages to avoid rate limiting
      await new Promise(r => setTimeout(r, 500));
    } catch (err) {
      console.error(`  ✗ Failed to send to ${customer.name}: ${err.message}`);
    }
  }
}

async function poll() {
  try {
    const data = await fetchStatus();
    const isDown       = data.status === 'down';
    const openIncident = data.open_incident;
    const downSec      = openIncident ? openIncident.duration_sec : 0;
    const downMin      = Math.floor(downSec / 60);

    console.log(`[${new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' })}] ` +
      `Status: ${data.status.toUpperCase()} | ` +
      (openIncident ? `Down for: ${downMin} min | ` : '') +
      `Uptime 30d: ${data.uptime_30d}%`
    );

    if (isDown && openIncident && downMin >= ALERT_AFTER_MIN && !alertSent) {
      // Network has been down long enough — send alert
      const msg =
        `Dear {name}, Nisan internet service is currently experiencing a network outage ` +
        `in Bilimora West. Our team is aware and working to restore service. ` +
        `We will update you once service is restored. ` +
        `For urgent queries call +91 98251 52400. Sorry for the inconvenience. — Nisan Cable TV & Internet`;
      await notifyAll(msg);
      alertSent    = true;
      recoverySent = false;

    } else if (!isDown && alertSent && !recoverySent) {
      // Service restored — send recovery message
      const msg =
        `Dear {name}, Good news! Nisan internet service has been restored in Bilimora West. ` +
        `Your connection should be working normally now. ` +
        `If you still face any issue, please call +91 98251 52400 (7 AM–10 PM, every day). ` +
        `Thank you for your patience. — Nisan Cable TV & Internet`;
      await notifyAll(msg);
      recoverySent = true;
      alertSent    = false; // reset for next outage

    } else if (!isDown && !alertSent) {
      // All normal — reset state for next cycle
      recoverySent = false;
    }

  } catch (err) {
    console.error(`[ERROR] Failed to fetch status: ${err.message}`);
  }
}

// Run immediately then on interval
console.log(`Nisan Outage Notifier started. Alert threshold: ${ALERT_AFTER_MIN} min. Customers: ${CUSTOMERS.length}`);
poll();
setInterval(poll, POLL_INTERVAL);
