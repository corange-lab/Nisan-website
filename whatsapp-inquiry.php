<?php
/*
 * Reusable WhatsApp Inquiry Form
 * Usage: <?php include('whatsapp-inquiry.php'); ?>  (adjust relative path from subfolders, e.g. '../whatsapp-inquiry.php')
 * Collects Name, Phone, Area and Interest, then opens WhatsApp with a pre-filled message.
 * 100% client-side — no backend required. Sends to +91 98251 52400.
 */
?>
<section class="wa-inq" aria-labelledby="wa-inq-title">
    <div class="wa-inq-card">
        <div class="wa-inq-head">
            <span class="wa-inq-icon"><i class="fab fa-whatsapp"></i></span>
            <div>
                <h3 id="wa-inq-title">Get a Quick Quote on WhatsApp</h3>
                <p>Fill this and we'll reply on WhatsApp within minutes — 8 AM to 10 PM, 365 days.</p>
            </div>
        </div>

        <form class="wa-inq-form" onsubmit="return nisanSendWhatsApp(event)">
            <div class="wa-inq-row">
                <div class="wa-inq-grp">
                    <label for="wa-name">Your Name</label>
                    <input type="text" id="wa-name" name="wa-name" placeholder="e.g. Rahul Patel" required>
                </div>
                <div class="wa-inq-grp">
                    <label for="wa-phone">Phone Number</label>
                    <input type="tel" id="wa-phone" name="wa-phone" placeholder="10-digit mobile" pattern="[0-9]{10}" maxlength="10" required>
                </div>
            </div>

            <div class="wa-inq-row">
                <div class="wa-inq-grp">
                    <label for="wa-area">Your Area / Society</label>
                    <input type="text" id="wa-area" name="wa-area" placeholder="e.g. Station Road, Bilimora">
                </div>
                <div class="wa-inq-grp">
                    <label for="wa-interest">I'm Interested In</label>
                    <select id="wa-interest" name="wa-interest">
                        <option value="New Internet Connection">New Internet Connection</option>
                        <option value="50 Mbps Plan (₹4,999/yr)">50 Mbps Plan (₹4,999/yr)</option>
                        <option value="100 Mbps Plan (₹5,499/yr)">100 Mbps Plan (₹5,499/yr)</option>
                        <option value="200 Mbps Plan (₹7,499/yr)">200 Mbps Plan (₹7,499/yr)</option>
                        <option value="Cable TV Connection">Cable TV Connection</option>
                        <option value="Cable TV + Internet Combo">Cable TV + Internet Combo</option>
                        <option value="Plan Renewal">Plan Renewal</option>
                        <option value="Business / Office Internet">Business / Office Internet</option>
                        <option value="Support / Complaint">Support / Complaint</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="wa-inq-btn">
                <i class="fab fa-whatsapp"></i> Send Inquiry on WhatsApp
            </button>
            <p class="wa-inq-note">No spam. Your details open directly in WhatsApp — you stay in control.</p>
        </form>
    </div>
</section>

<style>
.wa-inq { margin: 28px 0; }
.wa-inq-card {
    background: #ffffff;
    border: 1px solid #e3ebf3;
    border-top: 4px solid #25D366;
    border-radius: 16px;
    padding: 26px 28px;
    box-shadow: 0 8px 32px rgba(0,0,0,.07);
    max-width: 720px;
}
.wa-inq-head {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}
.wa-inq-icon {
    width: 52px; height: 52px;
    flex-shrink: 0;
    background: #25D366;
    color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px;
    box-shadow: 0 4px 14px rgba(37,211,102,.35);
}
.wa-inq-head h3 {
    font-size: 1.2rem !important;
    font-weight: 800 !important;
    color: #0C1020 !important;
    margin: 0 0 4px !important;
}
.wa-inq-head p {
    font-size: 13.5px;
    color: #5a6472;
    margin: 0;
    line-height: 1.5;
}
.wa-inq-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
.wa-inq-grp { display: flex; flex-direction: column; }
.wa-inq-grp label {
    font-size: 12.5px;
    font-weight: 700;
    color: #3d4552;
    margin-bottom: 6px;
}
.wa-inq-grp input,
.wa-inq-grp select {
    padding: 12px 14px;
    border: 1.5px solid #d8e0ea;
    border-radius: 10px;
    font-size: 14px;
    color: #1a1a2e;
    background: #f9fbfd;
    transition: border-color .18s, box-shadow .18s, background .18s;
    width: 100%;
    box-sizing: border-box;
}
.wa-inq-grp input:focus,
.wa-inq-grp select:focus {
    outline: none;
    border-color: #25D366;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(37,211,102,.12);
}
.wa-inq-btn {
    width: 100%;
    background: #25D366;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 14px 20px;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    transition: transform .15s, box-shadow .15s, background .15s;
    box-shadow: 0 4px 16px rgba(37,211,102,.32);
}
.wa-inq-btn:hover {
    background: #1eb857;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(37,211,102,.42);
}
.wa-inq-btn i { font-size: 19px; }
.wa-inq-note {
    text-align: center;
    font-size: 11.5px;
    color: #9aa4b2;
    margin: 12px 0 0;
}
@media (max-width: 575px) {
    .wa-inq-card { padding: 22px 18px; }
    .wa-inq-row { grid-template-columns: 1fr; gap: 12px; margin-bottom: 12px; }
    .wa-inq-head h3 { font-size: 1.08rem !important; }
}
</style>

<script>
function nisanSendWhatsApp(e) {
    e.preventDefault();
    var name     = (document.getElementById('wa-name').value || '').trim();
    var phone    = (document.getElementById('wa-phone').value || '').trim();
    var area     = (document.getElementById('wa-area').value || '').trim();
    var interest = document.getElementById('wa-interest').value || 'New Internet Connection';

    if (!name)  { document.getElementById('wa-name').focus();  return false; }
    if (phone.length !== 10) { document.getElementById('wa-phone').focus(); return false; }

    var lines = [
        'Hi Nisan, I would like to enquire.',
        '',
        'Name: ' + name,
        'Phone: ' + phone
    ];
    if (area) lines.push('Area: ' + area);
    lines.push('Interested in: ' + interest);
    lines.push('', 'Please call me back. Thank you!');

    var msg = encodeURIComponent(lines.join('\n'));
    var url = 'https://wa.me/919825152400?text=' + msg;

    // Fire ads/analytics conversion if available
    if (typeof gtag === 'function') {
        gtag('event', 'conversion', { 'send_to': 'AW-938737099/whatsapp_click' });
        gtag('event', 'whatsapp_inquiry', { 'event_category': 'lead', 'event_label': interest });
    }

    window.open(url, '_blank', 'noopener');
    return false;
}
</script>
