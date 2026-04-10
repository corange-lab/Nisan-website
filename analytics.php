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

<!-- GA4 direct tag (fallback if GTM not yet configured) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= GA4_MEASUREMENT_ID ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= GA4_MEASUREMENT_ID ?>', {
    'anonymize_ip': false,
    'allow_google_signals': true,
    'allow_ad_personalization_signals': true
  });
</script>
