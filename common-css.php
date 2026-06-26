<?php include_once('analytics.php'); ?>
	<link href="/assets/imgs/favicon.png" rel="shortcut icon" type="image/x-icon" />
	<!-- Preload Rubik font file (self-hosted, no Google Fonts request) -->
	<link rel="preload" href="/assets/fonts/rubik-latin.woff2" as="font" type="font/woff2" crossorigin>
	<!-- CSS here -->
	<!-- Critical CSS - Load immediately -->
	<link href="/assets/css/rubik-self.css" rel="stylesheet" />
	<link href="/assets/css/bootstrap.min.css" rel="stylesheet" />
	<link href="/assets/css/default.css" rel="stylesheet" />
	<link href="/assets/css/style.css" rel="stylesheet" />
	<link href="/assets/css/accessibility.css" rel="stylesheet" />

	<!-- Icons: inline SVG replacement for FontAwesome + Flaticon (8KB vs 1.4MB) -->
	<link href="/assets/css/icons.css" rel="stylesheet" />

	<!-- Non-critical CSS - Load asynchronously -->
	<link href="/assets/css/animate.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" />
	<link href="/assets/css/owl.carousel.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" />
	<link href="/assets/css/odometer.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" />
	<link href="/assets/css/swiper.min.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" />
	<link href="/assets/css/slick.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" />
	<link href="/assets/css/magnific-popup.css" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'" />

	<!-- Fallback for browsers that don't support preload -->
	<noscript>
		<link href="/assets/css/animate.min.css" rel="stylesheet" />
		<link href="/assets/css/owl.carousel.min.css" rel="stylesheet" />
		<link href="/assets/css/odometer.css" rel="stylesheet" />
		<link href="/assets/css/swiper.min.css" rel="stylesheet" />
		<link href="/assets/css/slick.css" rel="stylesheet" />
		<link href="/assets/css/magnific-popup.css" rel="stylesheet" />
	</noscript>
