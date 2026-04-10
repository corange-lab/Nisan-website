<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internet Speed Test – Check Your Broadband Speed | Nisan Bilimora</title>
    <meta name="description" content="Test your internet speed right now. If your results are slow, switch to Nisan — fast FTTH broadband in Bilimora with speeds up to 200 Mbps and a 1-month free trial.">
    <meta name="keywords" content="speedtest, speed test, internet speed test, broadband speed test, check internet speed, slow internet bilimora, wifi speed test, nisan speedtest, speed test bilimora">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.nisan.co.in/speedtest">

    <?php include('common-css.php'); ?>

    <script>
      // Fire dataLayer event — GTM picks this up to build the retargeting audience
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event:      'speedtest_page_visit',
        page_type:  'speed_test',
        intent:     'slow_internet_check'
      });
    </script>

    <style>
        /* ── Speed Test Widget ── */
        .speedtest-widget-wrap {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.10);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .speedtest-widget-wrap iframe {
            display: block;
            width: 100%;
            min-height: 480px;
            border: none;
        }

        /* ── Result Interpretation Cards ── */
        .speed-cards { margin-top: 60px; }
        .speed-card {
            border-radius: 12px;
            padding: 28px 22px;
            text-align: center;
            height: 100%;
        }
        .speed-card .speed-range {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 8px;
        }
        .speed-card .speed-label {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 12px;
        }
        .speed-card p { font-size: 14px; margin: 0; opacity: .85; }
        .speed-card.bad   { background:#fff5f5; color:#c0392b; border:1.5px solid #fac0c0; }
        .speed-card.ok    { background:#fffbec; color:#b7770d; border:1.5px solid #ffe08a; }
        .speed-card.good  { background:#f0fdf4; color:#1e7e34; border:1.5px solid #a8e6b4; }

        /* ── Switch CTA Banner ── */
        .switch-cta-banner {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            border-radius: 20px;
            padding: 56px 40px;
            color: #fff;
            text-align: center;
        }
        .switch-cta-banner .cta-eyebrow {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            opacity: .75;
            margin-bottom: 14px;
        }
        .switch-cta-banner h2 {
            font-size: clamp(26px, 4vw, 42px);
            font-weight: 800;
            margin-bottom: 14px;
            line-height: 1.2;
        }
        .switch-cta-banner p {
            font-size: 17px;
            opacity: .88;
            max-width: 540px;
            margin: 0 auto 32px;
        }
        .switch-cta-banner .cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .switch-cta-banner .btn-white {
            background: #fff;
            color: #0d47a1;
            font-weight: 700;
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 16px;
            transition: transform .2s, box-shadow .2s;
        }
        .switch-cta-banner .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,.25);
            color: #0d47a1;
            text-decoration: none;
        }
        .switch-cta-banner .btn-outline-white {
            background: transparent;
            color: #fff;
            font-weight: 600;
            padding: 13px 28px;
            border-radius: 50px;
            border: 2px solid rgba(255,255,255,.65);
            text-decoration: none;
            font-size: 15px;
            transition: background .2s, border-color .2s;
        }
        .switch-cta-banner .btn-outline-white:hover {
            background: rgba(255,255,255,.12);
            border-color: #fff;
            color: #fff;
            text-decoration: none;
        }

        /* ── Why Nisan strip ── */
        .why-nisan-strip {
            padding: 60px 0;
        }
        .why-item {
            text-align: center;
            padding: 0 16px;
        }
        .why-item .wi-icon {
            width: 64px; height: 64px;
            background: #e8f0fe;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 18px;
            font-size: 26px;
            color: #1976d2;
        }
        .why-item h5 { font-weight: 700; font-size: 17px; margin-bottom: 8px; }
        .why-item p  { font-size: 14px; color: #666; margin: 0; }
    </style>
</head>

<body>

<?php include('header.php'); ?>

<main>

    <!-- breadcrumb-area -->
    <section class="breadcrumb-area breadcrumb-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-md-10">
                    <div class="breadcrumb-content">
                        <h1 class="title">Internet Speed Test</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Speed Test</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- breadcrumb-area-end -->

    <!-- speed-test-area -->
    <section class="section-space-top section-meadium-bottom">
        <div class="container">

            <div class="row justify-content-center mb-45">
                <div class="col-xl-7 col-lg-9 text-center">
                    <p class="text-muted mb-8" style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;font-weight:600;">Free Tool</p>
                    <h2 class="title" style="font-size:clamp(26px,4vw,38px);font-weight:800;margin-bottom:14px;">
                        Check Your Internet Speed
                    </h2>
                    <p style="font-size:16px;color:#555;">
                        Click <strong>Go</strong> below to run a real-time speed test. It measures your current download speed, upload speed, and ping.
                    </p>
                </div>
            </div>

            <!-- Ookla Speedtest Widget -->
            <div class="speedtest-widget-wrap" style="padding:40px 32px;text-align:center;">
                <script type="text/javascript">
                  var _speedtestEmbed = {"width":"100%","height":"450"};
                </script>
                <script type="text/javascript" src="https://www.speedtest.net/speedtest-embed.js" async></script>
                <noscript>
                    <a href="https://www.speedtest.net" target="_blank" rel="noopener"
                       style="display:inline-block;background:#0d47a1;color:#fff;padding:16px 36px;border-radius:50px;font-size:17px;font-weight:700;text-decoration:none;">
                        Run Speed Test on Speedtest.net
                    </a>
                </noscript>
            </div>
            <p class="text-center mt-3" style="font-size:13px;color:#aaa;">
                Powered by Speedtest by Ookla &nbsp;·&nbsp;
                <a href="https://www.speedtest.net" target="_blank" rel="noopener" style="color:#aaa;">Run on Speedtest.net directly</a>
            </p>

            <!-- What Your Result Means -->
            <div class="speed-cards">
                <div class="row justify-content-center mb-35">
                    <div class="col-lg-6 text-center">
                        <h3 style="font-weight:700;margin-bottom:8px;">What Does Your Result Mean?</h3>
                        <p style="color:#666;font-size:15px;">Use this as a quick guide to understand if your speed is holding you back.</p>
                    </div>
                </div>
                <div class="row g-3 justify-content-center">
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="speed-card bad">
                            <div class="speed-range">0 – 10 Mbps</div>
                            <div class="speed-label">Too Slow</div>
                            <p>Buffering on YouTube, video calls drop, gaming is frustrating. Time to upgrade.</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="speed-card ok">
                            <div class="speed-range">10 – 50 Mbps</div>
                            <div class="speed-label">Decent</div>
                            <p>Handles basic browsing and HD streaming, but may struggle with multiple devices.</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="speed-card good">
                            <div class="speed-range">50+ Mbps</div>
                            <div class="speed-label">Good</div>
                            <p>Smooth 4K streaming, fast downloads, and lag-free video calls on multiple devices.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
    <!-- speed-test-area-end -->

    <!-- switch-cta-area -->
    <section class="section-meadium-bottom">
        <div class="container">
            <div class="switch-cta-banner">
                <div class="cta-eyebrow">Not Happy With Your Speed?</div>
                <h2>You Deserve Faster Internet.<br>Switch to Nisan Today.</h2>
                <p>FTTH fiber broadband in Bilimora — speeds up to 200 Mbps, free installation, and a 1-month risk-free trial. No contracts. No hidden charges.</p>
                <div class="cta-btns">
                    <a href="/contact.php" class="btn-white">Get a Free Connection</a>
                    <a href="tel:+919825152400" class="btn-outline-white"><i class="fas fa-phone-alt me-2"></i>Call Us Now</a>
                </div>
            </div>
        </div>
    </section>
    <!-- switch-cta-area-end -->

    <!-- why-nisan-area -->
    <section class="why-nisan-strip">
        <div class="container">
            <div class="row justify-content-center mb-40">
                <div class="col-lg-5 text-center">
                    <h3 style="font-weight:700;">Why Nisan Is Different</h3>
                </div>
            </div>
            <div class="row justify-content-center g-4">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="why-item">
                        <div class="wi-icon"><i class="fas fa-bolt"></i></div>
                        <h5>Up to 200 Mbps</h5>
                        <p>True FTTH fiber speeds — not shared copper line speeds that drop during peak hours.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="why-item">
                        <div class="wi-icon"><i class="fas fa-shield-alt"></i></div>
                        <h5>1-Month Free Trial</h5>
                        <p>Test us at zero risk. If you're not satisfied in 30 days, you pay nothing.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="why-item">
                        <div class="wi-icon"><i class="fas fa-headset"></i></div>
                        <h5>Local Support</h5>
                        <p>We're based in Bilimora. Real people answer your calls — not a call center far away.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="why-item">
                        <div class="wi-icon"><i class="fas fa-rupee-sign"></i></div>
                        <h5>Affordable Plans</h5>
                        <p>Transparent pricing with no hidden installation fees or surprise charges.</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-45">
                <a href="/pricing.php" class="btn" style="padding:14px 36px;font-size:16px;">View Our Plans</a>
            </div>
        </div>
    </section>
    <!-- why-nisan-area-end -->

</main>

<?php include('footer.php'); ?>

</body>
</html>
