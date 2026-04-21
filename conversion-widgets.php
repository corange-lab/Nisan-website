<!-- ═══════════════════════════════════════════════════════
     CONVERSION WIDGETS — announcement bar · social proof · google review · exit popup
     ═══════════════════════════════════════════════════════ -->

<!-- ① Announcement Bar -->
<div id="nisan-announce-bar">
    <div class="nab-inner">
        <span class="nab-badge">Limited Time</span>
        <span class="nab-text">
            <strong>100 Mbps at ₹4,999/year</strong> — Jio charges ₹10,799 for same speed. Save ₹5,800/year. Free installation!
        </span>
        <a href="/contact.php" class="nab-cta">Claim Now</a>
    </div>
    <button class="nab-close" aria-label="Close announcement">&times;</button>
</div>

<!-- ② Social Proof Toast -->
<div id="nisan-toast" aria-live="polite" aria-atomic="true">
    <div class="nst-avatar"><i class="fas fa-wifi"></i></div>
    <div class="nst-body">
        <p class="nst-name" id="nst-name">Hardik P.</p>
        <p class="nst-msg" id="nst-msg">just installed fiber broadband at Station Road</p>
        <span class="nst-time" id="nst-time">5 minutes ago</span>
    </div>
    <button class="nst-close" aria-label="Close">&times;</button>
</div>

<!-- ③ Google Review Nudge -->
<div id="nisan-review-nudge" aria-label="Rate us on Google">
    <a id="nrn-link" href="https://www.google.com/maps?cid=3262225285716485915&action=write_review" target="_blank" rel="noopener">
        <span class="nrn-stars">★★★★★</span>
        <span class="nrn-text">Rate us on Google</span>
    </a>
    <button class="nrn-close" id="nrn-close" aria-label="Close">&times;</button>
</div>

<!-- ④ Exit-Intent Popup -->
<div id="nisan-exit-overlay" role="dialog" aria-modal="true" aria-labelledby="exit-title">
    <div id="nisan-exit-popup">
        <button class="nep-close" id="nep-close" aria-label="Close">&times;</button>
        <div class="nep-icon"><i class="fas fa-bolt"></i></div>
        <h2 class="nep-title" id="exit-title">Paying ₹899/month for Jio?</h2>
        <p class="nep-sub">Nisan gives you <strong>100 Mbps for just ₹416/month</strong> in Bilimora. Same speed, half the price. Free installation.</p>
        <ul class="nep-perks">
            <li><i class="fas fa-check-circle"></i> ₹4,999/year vs Jio's ₹10,799/year</li>
            <li><i class="fas fa-check-circle"></i> Save ₹5,800 every year</li>
            <li><i class="fas fa-check-circle"></i> Free Installation + Free Router</li>
            <li><i class="fas fa-check-circle"></i> 1-Month Money-Back Guarantee</li>
        </ul>
        <div class="nep-actions">
            <a href="https://wa.me/919825152400?text=Hi%2C%20I%27m%20interested%20in%20a%20new%20internet%20connection" class="nep-btn-wa" target="_blank" rel="noopener">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
            <a href="tel:+919825152400" class="nep-btn-call">
                <i class="fas fa-phone-alt"></i> Call Now
            </a>
        </div>
        <p class="nep-small">Serving Bilimora since 1993 · 24/7 Support</p>
    </div>
</div>

<style>
/* ── ① Announcement Bar ──────────────────────────────────── */
#nisan-announce-bar {
    position: relative;
    background: linear-gradient(90deg, #0052a3 0%, #0066cc 50%, #0080ff 100%);
    color: #fff;
    text-align: center;
    padding: 10px 44px 10px 16px;
    font-size: 14px;
    line-height: 1.4;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}
#nisan-announce-bar.nab-hidden { display: none; }
.nab-inner { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: center; }
.nab-badge {
    background: #ff6b00;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}
.nab-text { color: rgba(255,255,255,.95); }
.nab-cta {
    background: #fff;
    color: #0066cc;
    font-weight: 700;
    font-size: 13px;
    padding: 5px 14px;
    border-radius: 20px;
    text-decoration: none;
    white-space: nowrap;
    transition: background .2s, color .2s;
}
.nab-cta:hover { background: #ff6b00; color: #fff; }
.nab-close {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255,255,255,.7);
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
    padding: 0 4px;
    transition: color .2s;
}
.nab-close:hover { color: #fff; }

/* ── ② Social Proof Toast ────────────────────────────────── */
#nisan-toast {
    position: fixed;
    bottom: 90px;
    left: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 30px rgba(0,0,0,.15);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 36px 14px 14px;
    max-width: 300px;
    z-index: 9990;
    transform: translateX(-340px);
    transition: transform .4s cubic-bezier(.34,1.56,.64,1);
    border-left: 4px solid #0066cc;
}
#nisan-toast.nst-visible { transform: translateX(0); }
.nst-avatar {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #0066cc, #0099ff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
    font-size: 17px;
}
.nst-body { min-width: 0; }
.nst-name {
    font-weight: 700;
    font-size: 13px;
    color: #1a1a2e;
    margin: 0 0 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.nst-msg {
    font-size: 12px;
    color: #444;
    margin: 0 0 4px;
    line-height: 1.4;
}
.nst-time {
    font-size: 11px;
    color: #0066cc;
    font-weight: 600;
}
.nst-close {
    position: absolute;
    top: 8px;
    right: 8px;
    background: none;
    border: none;
    color: #bbb;
    font-size: 15px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: color .2s;
}
.nst-close:hover { color: #333; }
@media (max-width: 991px) {
    #nisan-toast { bottom: 72px; }
}

/* ── ③ Google Review Nudge ───────────────────────────────── */
#nisan-review-nudge {
    position: fixed;
    bottom: 148px;
    left: 20px;
    background: #fff;
    border-radius: 50px;
    box-shadow: 0 4px 20px rgba(0,0,0,.14);
    display: flex;
    align-items: center;
    gap: 0;
    z-index: 9989;
    transform: translateX(-260px);
    transition: transform .4s cubic-bezier(.34,1.56,.64,1);
    border-left: 4px solid #fbbc04;
    overflow: hidden;
}
#nisan-review-nudge.nrn-visible { transform: translateX(0); }
#nrn-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    text-decoration: none;
    color: #333;
}
#nrn-link:hover { background: #fffbea; }
.nrn-stars {
    color: #fbbc04;
    font-size: 13px;
    letter-spacing: 1px;
    line-height: 1;
}
.nrn-text {
    font-size: 12px;
    font-weight: 700;
    color: #333;
    white-space: nowrap;
}
.nrn-close {
    background: none;
    border: none;
    color: #bbb;
    font-size: 15px;
    padding: 10px 12px 10px 4px;
    cursor: pointer;
    line-height: 1;
    transition: color .2s;
    flex-shrink: 0;
}
.nrn-close:hover { color: #555; }
@media (max-width: 991px) {
    #nisan-review-nudge { bottom: 130px; }
}

/* ── ④ Exit-Intent Popup ────────────────────────────────── */
#nisan-exit-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(3px);
}
#nisan-exit-overlay.nep-open { display: flex; }
#nisan-exit-popup {
    background: #fff;
    border-radius: 20px;
    max-width: 440px;
    width: 100%;
    padding: 40px 36px 32px;
    text-align: center;
    position: relative;
    animation: nepIn .35s cubic-bezier(.34,1.56,.64,1);
}
@keyframes nepIn {
    from { transform: scale(.8) translateY(30px); opacity: 0; }
    to   { transform: scale(1)  translateY(0);    opacity: 1; }
}
.nep-close {
    position: absolute;
    top: 14px;
    right: 18px;
    background: #f0f0f0;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    color: #666;
    transition: background .2s, color .2s;
}
.nep-close:hover { background: #e0e0e0; color: #333; }
.nep-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #ff6b00, #ff8c00);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    font-size: 28px;
    color: #fff;
    box-shadow: 0 6px 20px rgba(255,107,0,.35);
}
.nep-title {
    font-size: 24px;
    font-weight: 800;
    color: #1a1a2e;
    margin: 0 0 10px;
}
.nep-sub {
    font-size: 15px;
    color: #555;
    margin: 0 0 20px;
    line-height: 1.5;
}
.nep-perks {
    list-style: none;
    padding: 0;
    margin: 0 0 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 12px;
    text-align: left;
}
.nep-perks li {
    font-size: 13px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 7px;
}
.nep-perks li i { color: #25D366; font-size: 15px; }
.nep-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.nep-btn-wa, .nep-btn-call {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: transform .15s, box-shadow .15s;
}
.nep-btn-wa:hover, .nep-btn-call:hover { transform: translateY(-2px); }
.nep-btn-wa {
    background: #25D366;
    color: #fff;
    box-shadow: 0 4px 14px rgba(37,211,102,.35);
}
.nep-btn-wa:hover { box-shadow: 0 6px 20px rgba(37,211,102,.5); color: #fff; }
.nep-btn-call {
    background: #0066cc;
    color: #fff;
    box-shadow: 0 4px 14px rgba(0,102,204,.3);
}
.nep-btn-call:hover { box-shadow: 0 6px 20px rgba(0,102,204,.45); color: #fff; }
.nep-small {
    font-size: 12px;
    color: #aaa;
    margin: 0;
}
@media (max-width: 480px) {
    #nisan-exit-popup { padding: 32px 20px 24px; }
    .nep-title { font-size: 20px; }
    .nep-perks { grid-template-columns: 1fr; }
    .nep-actions { flex-direction: column; align-items: center; }
    .nep-btn-wa, .nep-btn-call { width: 100%; justify-content: center; }
}
</style>

<script>
(function () {

    /* ── ① Announcement Bar ───────────────────────────── */
    var bar = document.getElementById('nisan-announce-bar');
    if (bar) {
        if (sessionStorage.getItem('nab_closed')) {
            bar.classList.add('nab-hidden');
        } else {
            bar.querySelector('.nab-close').addEventListener('click', function () {
                bar.classList.add('nab-hidden');
                sessionStorage.setItem('nab_closed', '1');
            });
        }
    }

    /* ── ② Social Proof Toast ─────────────────────────── */
    var signups = [
        { name: 'Hardik Patel',    area: 'Station Road',                  action: 'got new broadband connection',    ago: 'Today'         },
        { name: 'Meera Shah',      area: 'Ganesh Apartment',              action: 'renewed annual plan',             ago: 'Today'         },
        { name: 'Chintan Vora',    area: 'Laxmi Palace',                  action: 'got new fiber broadband',         ago: 'Today'         },
        { name: 'Heena Desai',     area: 'Prajapati Colony',              action: 'renewed Internet + TV plan',      ago: 'Yesterday'     },
        { name: 'Rajesh Trivedi',  area: 'Soniwad',                       action: 'got new broadband installed',     ago: 'Yesterday'     },
        { name: 'Kavita Modi',     area: 'Desra Road',                    action: 'renewed broadband connection',    ago: 'Yesterday'     },
        { name: 'Jignesh Bhatt',   area: 'Jamna Nagar',                   action: 'switched to Nisan fiber',         ago: '2 days ago'    },
        { name: 'Priya Rana',      area: 'Raval Street',                  action: 'got new connection installed',    ago: '2 days ago'    },
        { name: 'Suresh Kapoor',   area: 'AnandBaug Society',             action: 'renewed yearly plan',             ago: '2 days ago'    },
        { name: 'Anita Gohil',     area: 'Kumbharwad',                    action: 'got new broadband connection',    ago: '3 days ago'    },
        { name: 'Mayur Choksi',    area: 'Nr. Somnath Mandir',            action: 'renewed Internet + TV plan',      ago: '3 days ago'    },
        { name: 'Dipti Mehta',     area: 'Bhuri House, Bunder Road',      action: 'got fiber internet installed',    ago: '3 days ago'    },
        { name: 'Bhavesh Patel',   area: 'Beside Swaminarayan Temple',    action: 'renewed annual broadband plan',   ago: '4 days ago'    },
        { name: 'Sneha Thakkar',   area: 'Desra',                         action: 'got new connection',              ago: '4 days ago'    },
        { name: 'Ketan Shah',      area: 'Station Road',                  action: 'upgraded to 100 Mbps plan',       ago: '4 days ago'    },
        { name: 'Varsha Parmar',   area: 'Ganesh Apartment',              action: 'renewed broadband connection',    ago: '5 days ago'    },
        { name: 'Tushar Patel',    area: 'Laxmi Palace',                  action: 'got new fiber broadband',         ago: '5 days ago'    },
        { name: 'Nisha Valand',    area: 'Prajapati Colony',              action: 'renewed Internet + TV plan',      ago: '5 days ago'    },
        { name: 'Amrut Chauhan',   area: 'Soniwad',                       action: 'switched to Nisan fiber',         ago: '6 days ago'    },
        { name: 'Pooja Dave',      area: 'Desra Road',                    action: 'renewed yearly broadband plan',   ago: '6 days ago'    },
        { name: 'Manish Soni',     area: 'Jamna Nagar',                   action: 'got new broadband installed',     ago: '1 week ago'    },
        { name: 'Ritu Joshi',      area: 'Raval Street',                  action: 'renewed annual plan',             ago: '1 week ago'    },
        { name: 'Nilesh Desai',    area: 'AnandBaug Society',             action: 'got fiber internet installed',    ago: '1 week ago'    },
        { name: 'Komal Patel',     area: 'Kumbharwad',                    action: 'renewed broadband connection',    ago: '1 week ago'    },
        { name: 'Dhaval Shah',     area: 'Nr. Somnath Mandir',            action: 'got new connection installed',    ago: '1 week ago'    },
        { name: 'Foram Desai',     area: 'Bhuri House, Bunder Road',      action: 'renewed Internet + TV plan',      ago: '8 days ago'    },
        { name: 'Kishan Patel',    area: 'Beside Swaminarayan Temple',    action: 'upgraded to 150 Mbps plan',       ago: '8 days ago'    },
        { name: 'Alka Trivedi',    area: 'Desra',                         action: 'got new fiber broadband',         ago: '9 days ago'    },
        { name: 'Paresh Shah',     area: 'Station Road',                  action: 'renewed yearly plan',             ago: '9 days ago'    },
        { name: 'Geeta Vora',      area: 'Ganesh Apartment',              action: 'got broadband connection',        ago: '10 days ago'   },
    ];

    var toast    = document.getElementById('nisan-toast');
    var nstName  = document.getElementById('nst-name');
    var nstMsg   = document.getElementById('nst-msg');
    var nstTime  = document.getElementById('nst-time');

    // Shuffle so each visitor sees different order
    for (var i = signups.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var tmp = signups[i]; signups[i] = signups[j]; signups[j] = tmp;
    }
    var toastIdx = 0;
    var toastTimer;

    function showToast() {
        if (!toast) return;
        var s = signups[toastIdx % signups.length];
        toastIdx++;
        nstName.textContent = s.name;
        nstMsg.textContent  = s.action + ' at ' + s.area;
        nstTime.textContent = s.ago;
        toast.classList.add('nst-visible');
        toastTimer = setTimeout(hideToast, 6500);
    }

    function hideToast() {
        if (!toast) return;
        toast.classList.remove('nst-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(showToast, 42000);
    }

    if (toast) {
        toast.querySelector('.nst-close').addEventListener('click', function () {
            toast.classList.remove('nst-visible');
            clearTimeout(toastTimer);
        });
        setTimeout(showToast, 8000);
    }

    /* ── ③ Google Review Nudge ────────────────────────── */
    var nudge    = document.getElementById('nisan-review-nudge');
    var nrnClose = document.getElementById('nrn-close');

    if (nudge) {
        if (!sessionStorage.getItem('nrn_closed')) {
            // Show after 100 seconds — user has likely read enough to form an opinion
            setTimeout(function () {
                nudge.classList.add('nrn-visible');
            }, 100000);
        }
        nrnClose.addEventListener('click', function (e) {
            e.preventDefault();
            nudge.classList.remove('nrn-visible');
            sessionStorage.setItem('nrn_closed', '1');
        });
    }

    /* ── ④ Exit-Intent Popup ──────────────────────────── */
    var overlay  = document.getElementById('nisan-exit-overlay');
    var nepClose = document.getElementById('nep-close');
    var shown    = false;

    function openExitPopup() {
        if (shown || sessionStorage.getItem('nep_shown')) return;
        shown = true;
        sessionStorage.setItem('nep_shown', '1');
        overlay.classList.add('nep-open');
    }

    function closeExitPopup() {
        overlay.classList.remove('nep-open');
    }

    if (overlay) {
        document.addEventListener('mouseleave', function (e) {
            if (e.clientY < 10) openExitPopup();
        });

        var lastScrollY = window.scrollY;
        var scrollTimer;
        window.addEventListener('scroll', function () {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(function () {
                var now = window.scrollY;
                if (lastScrollY > 200 && now < lastScrollY - 80) openExitPopup();
                lastScrollY = now;
            }, 150);
        }, { passive: true });

        nepClose.addEventListener('click', closeExitPopup);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeExitPopup();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeExitPopup();
        });
    }

})();
</script>
