<?php
/**
 * analytics.php — Include this inside <head> on every page.
 * Contains: GTM head snippet + GA4 fallback + Search Console verification.
 *
 * ACTION REQUIRED before going live:
 *   1. Create GTM container at tagmanager.google.com
 *   2. Import google-ads/task4_gtm_container.json
 *   3. Replace GTM_CONTAINER_ID below with your real container ID (e.g. GTM-AB12CD3)
 *   4. Replace GA4_MEASUREMENT_ID with your real G-XXXXXXXXXX from GA4 Data Streams
 *   5. Replace SEARCH_CONSOLE_VERIFICATION with the token from task5_search_console.py
 */

define('GTM_CONTAINER_ID',           'GTM-NBBLPR9W');      // Nisan GTM container
define('GA4_MEASUREMENT_ID',         'G-WQ8D0KX6XC');      // Nisan GA4 Measurement ID
define('GADS_CONVERSION_ID',         'AW-938737099');       // Nisan Google Ads account
define('META_PIXEL_ID',              '1323468386289503');   // Nisan Website Pixel
define('SEARCH_CONSOLE_VERIFICATION', 'cCtAMLuH8J9OK9f8-EEnUMUMXnC9HRaDC5jbcqfZ4zc'); // Search Console
?>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?= GTM_CONTAINER_ID ?>');</script>
<!-- End Google Tag Manager -->

<!-- Google Search Console Verification -->
<meta name="google-site-verification" content="<?= SEARCH_CONSOLE_VERIFICATION ?>" />

<!-- GA4 + Google Ads tags -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= GA4_MEASUREMENT_ID ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  (function () {
    var accepted = false;
    try { accepted = localStorage.getItem('nisan_cookie_consent') === 'accepted'; } catch (e) {}
    gtag('consent', 'default', {
      'analytics_storage': accepted ? 'granted' : 'denied',
      'ad_storage': accepted ? 'granted' : 'denied',
      'ad_user_data': accepted ? 'granted' : 'denied',
      'ad_personalization': accepted ? 'granted' : 'denied'
    });
  })();
  gtag('config', '<?= GA4_MEASUREMENT_ID ?>', {
    'anonymize_ip': false,
    'allow_google_signals': true,
    'allow_ad_personalization_signals': true
  });
  gtag('config', '<?= GADS_CONVERSION_ID ?>');
</script>

<!-- Consent-aware Meta Pixel + first-party campaign attribution -->
<script>
(function (w, d) {
  'use strict';

  var META_PIXEL_ID = '<?= META_PIXEL_ID ?>';
  var metaLoaded = false;

  function storageGet(key) {
    try { return w.localStorage.getItem(key); } catch (e) { return null; }
  }
  function storageSet(key, value) {
    try { w.localStorage.setItem(key, value); } catch (e) {}
  }

  var query = new URLSearchParams(w.location.search);
  var attribution = {};
  ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid'].forEach(function (key) {
    var incoming = query.get(key);
    if (incoming) storageSet('nisan_' + key, incoming.slice(0, 250));
    attribution[key] = incoming || storageGet('nisan_' + key) || '';
  });
  w.nisanGetAttribution = function () { return Object.assign({}, attribution); };

  w.nisanLoadMetaPixel = function () {
    if (metaLoaded || !META_PIXEL_ID) return;
    metaLoaded = true;
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
    (w,d,'script','https://connect.facebook.net/en_US/fbevents.js');
    w.fbq('init', META_PIXEL_ID);
    w.fbq('track', 'PageView');
    d.dispatchEvent(new CustomEvent('nisanMetaReady'));
  };

  w.nisanTrackMeta = function (eventName, params) {
    if (!metaLoaded || typeof w.fbq !== 'function') return false;
    w.fbq('track', eventName, Object.assign({}, attribution, params || {}));
    return true;
  };

  function updateGoogleConsent(value) {
    if (typeof w.gtag !== 'function') return;
    w.gtag('consent', 'update', {
      'analytics_storage': value,
      'ad_storage': value,
      'ad_user_data': value,
      'ad_personalization': value
    });
  }

  function applyChoice(choice) {
    storageSet('nisan_cookie_consent', choice);
    var banner = d.getElementById('nisan-cookie-consent');
    if (banner) banner.remove();
    if (choice === 'accepted') {
      updateGoogleConsent('granted');
      w.nisanLoadMetaPixel();
    } else {
      updateGoogleConsent('denied');
    }
  }
  w.nisanResetCookieConsent = function () {
    try { w.localStorage.removeItem('nisan_cookie_consent'); } catch (e) {}
    w.location.reload();
  };

  function showConsent() {
    if (storageGet('nisan_cookie_consent')) return;
    var banner = d.createElement('div');
    banner.id = 'nisan-cookie-consent';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-label', 'Cookie preferences');
    banner.innerHTML = '<div class="ncc-copy"><strong>Privacy choices</strong><span>We use analytics and Meta advertising cookies to measure enquiries and improve our ads. <a href="/privacy-policy.php">Learn more</a>.</span></div><div class="ncc-actions"><button type="button" data-choice="essential">Essential only</button><button type="button" class="ncc-accept" data-choice="accepted">Accept analytics</button></div>';
    banner.addEventListener('click', function (event) {
      var choice = event.target.getAttribute('data-choice');
      if (choice) applyChoice(choice);
    });
    d.body.appendChild(banner);
  }

  if (storageGet('nisan_cookie_consent') === 'accepted') w.nisanLoadMetaPixel();
  if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', showConsent);
  else showConsent();
})(window, document);
</script>
<style>
#nisan-cookie-consent{position:fixed;left:18px;right:18px;bottom:18px;z-index:100001;max-width:920px;margin:auto;padding:16px 18px;background:#07182d;color:#fff;border:1px solid rgba(255,255,255,.18);border-radius:14px;box-shadow:0 12px 36px rgba(0,0,0,.28);display:flex;align-items:center;justify-content:space-between;gap:18px;font:14px/1.45 Arial,sans-serif}.ncc-copy{display:flex;flex-direction:column;gap:3px}.ncc-copy strong{font-size:15px}.ncc-copy span{color:rgba(255,255,255,.82)}.ncc-copy a{color:#62c4ff;text-decoration:underline}.ncc-actions{display:flex;gap:9px;flex-shrink:0}.ncc-actions button{border:1px solid rgba(255,255,255,.35);border-radius:8px;padding:9px 13px;background:transparent;color:#fff;font-weight:700;cursor:pointer}.ncc-actions .ncc-accept{background:#087bea;border-color:#087bea}@media(max-width:700px){#nisan-cookie-consent{bottom:64px;flex-direction:column;align-items:stretch}.ncc-actions button{flex:1}}
</style>
