<?php
/**
 * analytics-body.php — Include this immediately after <body> on every page.
 * Contains: GTM noscript fallback.
 */
if (!defined('GTM_CONTAINER_ID')) include_once('analytics.php');
?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= GTM_CONTAINER_ID ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
