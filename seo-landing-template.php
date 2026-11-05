<?php
require __DIR__ . '/seo-landing-pages.php';
if (!isset($landingPageKey, $landingPages[$landingPageKey])) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$page = $landingPages[$landingPageKey];
$canonical = 'https://www.nisan.co.in/' . $landingPageKey;
$whatsApp = 'https://wa.me/919825152400?text=' . rawurlencode('Hello Nisan, please check ' . $page['service'] . ' availability at my address in ' . $page['area'] . '.');
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title'], ENT_QUOTES) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page['description'], ENT_QUOTES) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="<?= $canonical ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page['title'], ENT_QUOTES) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page['description'], ENT_QUOTES) ?>">
    <meta property="og:type" content="website"><meta property="og:url" content="<?= $canonical ?>">
    <meta property="og:image" content="https://www.nisan.co.in/assets/imgs/metaog.webp">
    <script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@graph'=>[
        ['@type'=>'BreadcrumbList','itemListElement'=>[
            ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>'https://www.nisan.co.in/'],
            ['@type'=>'ListItem','position'=>2,'name'=>$page['h1'],'item'=>$canonical]
        ]],
        ['@type'=>['InternetServiceProvider','LocalBusiness'],'name'=>'Nisan Cable TV & Internet','url'=>$canonical,'telephone'=>'+919825152400','address'=>['@type'=>'PostalAddress','streetAddress'=>'Morden Radio, Opp. Laxmi Palace, Station Road','addressLocality'=>'Bilimora','addressRegion'=>'Gujarat','postalCode'=>'396321','addressCountry'=>'IN'],'areaServed'=>$page['area'],'description'=>$page['description']],
        ['@type'=>'FAQPage','mainEntity'=>[
            ['@type'=>'Question','name'=>'Is '.$page['service'].' available in '.$page['area'].'?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Availability is confirmed at the exact address because network routes can vary by street and building. Send the location pin, street or society name to Nisan for a feasibility check.']],
            ['@type'=>'Question','name'=>'How do I request a new connection?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Call or WhatsApp Nisan at +91 98251 52400 with the full address and required service. The local team will confirm coverage, explain current options and arrange installation if feasible.']],
            ['@type'=>'Question','name'=>'Does Nisan provide local support in Bilimora?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes. Nisan operates from Station Road, Bilimora and provides local installation and service support for covered customers.']]
        ]]
    ]], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script>
    <?php include __DIR__ . '/common-css.php'; ?>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<main id="main-content">
    <section class="breadcrumb-area breadcrumb-bg"><div class="container"><div class="row"><div class="col-lg-10"><div class="breadcrumb-content">
        <span class="sub-title" style="color:#fbbf24;font-weight:700;"><?= htmlspecialchars($page['eyebrow']) ?></span>
        <h1 class="title"><?= htmlspecialchars($page['h1']) ?></h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/">Home</a></li><li class="breadcrumb-item active"><?= htmlspecialchars($page['h1']) ?></li></ol></nav>
    </div></div></div></div></section>

    <section class="services-details-area section-space-top pb-60"><div class="container"><div class="row align-items-center">
        <div class="col-lg-7"><div class="section-title mb-25"><span class="sub-title">LOCAL AVAILABILITY CHECK</span><h2 class="title"><?= htmlspecialchars($page['h1']) ?> from Nisan</h2></div>
            <p style="font-size:1.08rem;line-height:1.8;"><?= htmlspecialchars($page['lead']) ?></p>
            <p style="font-size:1.05rem;line-height:1.8;"><?= htmlspecialchars($page['detail']) ?></p>
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:24px;"><a class="btn" href="<?= $whatsApp ?>" target="_blank" rel="noopener" style="background:#25D366;color:#fff;">Check Availability on WhatsApp</a><a class="btn" href="tel:+919825152400">Call +91 98251 52400</a></div>
        </div>
        <div class="col-lg-5"><div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:16px;padding:28px;"><h3>Best suited for</h3><ul style="line-height:2;padding-left:20px;margin:0;"><?php foreach ($page['bestFor'] as $item): ?><li><?= htmlspecialchars($item) ?></li><?php endforeach; ?></ul></div></div>
    </div></div></section>

    <section style="background:#f8fafc;padding:60px 0;"><div class="container"><div class="row g-4">
        <div class="col-lg-4"><div style="background:#fff;padding:28px;border-radius:14px;height:100%;"><h2 style="font-size:1.45rem;">1. Share the exact address</h2><p>Send the street, society or building name and a location pin. A precise address prevents misleading area-wide promises.</p></div></div>
        <div class="col-lg-4"><div style="background:#fff;padding:28px;border-radius:14px;height:100%;"><h2 style="font-size:1.45rem;">2. Confirm network feasibility</h2><p>The local team checks the nearest network point, building access and a safe cable route before confirming service.</p></div></div>
        <div class="col-lg-4"><div style="background:#fff;padding:28px;border-radius:14px;height:100%;"><h2 style="font-size:1.45rem;">3. Select and schedule</h2><p>Review the current plan or channel options, inclusions and installation timing. Installation is booked only after the details are clear.</p></div></div>
    </div></div></section>

    <section style="padding:60px 0;"><div class="container"><div class="row">
        <div class="col-lg-6"><h2>What to include in your enquiry</h2><ul><li>Full Bilimora address and nearby landmark</li><li>Society, building, wing or shop name</li><li>Location pin for an accurate coverage check</li><li>Whether the connection is for home or business</li><li>Main uses and number of connected devices</li></ul></div>
        <div class="col-lg-6"><h2>Useful service information</h2><p>Nisan is based at Morden Radio, opposite Laxmi Palace on Station Road, Bilimora. The team handles local coverage checks, installation and support for internet and Cable TV customers in covered areas.</p><p><a href="<?= htmlspecialchars($page['parent']) ?>">View the main service page</a> · <a href="/coverage-areas-bilimora">Bilimora coverage areas</a> · <a href="/pricing">Internet plans</a></p></div>
    </div></div></section>

    <section style="background:#0f172a;color:#fff;padding:52px 0;text-align:center;"><div class="container"><h2 style="color:#fff;">Check <?= htmlspecialchars($page['service']) ?> at your address</h2><p style="color:#cbd5e1;">Send your location in <?= htmlspecialchars($page['area']) ?>. The local team will confirm current availability.</p><a class="btn" href="<?= $whatsApp ?>" target="_blank" rel="noopener" style="background:#25D366;color:#fff;">Send Location on WhatsApp</a></div></section>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
