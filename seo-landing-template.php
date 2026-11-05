<?php
require __DIR__ . '/seo-landing-pages.php';
require __DIR__ . '/seo-landing-enhancements.php';
if (!isset($landingPageKey, $landingPages[$landingPageKey], $seoEnhancements[$landingPageKey])) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$page = $landingPages[$landingPageKey];
$seo = $seoEnhancements[$landingPageKey];
$canonical = 'https://www.nisan.co.in/' . $landingPageKey;
$whatsApp = 'https://wa.me/919825152400?text=' . rawurlencode('Hello Nisan, please check ' . $page['service'] . ' availability at my address in ' . $page['area'] . '.');
$faqItems = [
    ['question'=>'How do I check '.$seo['primary'].' availability?', 'answer'=>'Send Nisan the complete address, society or building name, nearby landmark and a Google Maps location pin. The local team checks the nearest network route and confirms whether installation is currently feasible.'],
    ['question'=>'What should I compare before selecting '.$page['service'].'?', 'answer'=>'Compare verified coverage, the complete price including GST, installation and equipment, the services or speed included, support arrangements and cancellation terms. Do not select only from the headline price or speed.'],
    ['question'=>'How long does installation take in '.$page['area'].'?', 'answer'=>'Installation timing depends on network availability, building access, equipment and the cable route. After feasibility is confirmed, Nisan provides the current scheduling estimate for that property.'],
    ['question'=>'Can I request local support after installation?', 'answer'=>'Yes. Nisan operates from Station Road in Bilimora and provides local support for covered internet and Cable TV customers. Call or WhatsApp +91 98251 52400 with the customer details and issue.'],
    ['question'=>'Does an area coverage page guarantee service at every building?', 'answer'=>'No. Coverage can vary by street, society, building, port availability and cable route. Service is confirmed only after an address-level feasibility check.']
];
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
        ['@type'=>'FAQPage','mainEntity'=>array_map(function($faq){ return ['@type'=>'Question','name'=>$faq['question'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$faq['answer']]]; }, $faqItems)]
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

    <section style="padding:0 0 55px;"><div class="container"><div style="background:#fffbeb;border-left:5px solid #f59e0b;border-radius:10px;padding:24px 28px;">
        <h2 style="font-size:1.35rem;margin-bottom:10px;">Quick answer</h2>
        <p style="margin:0;line-height:1.8;"><strong><?= htmlspecialchars(ucfirst($seo['primary'])) ?></strong> is available at selected addresses subject to a property-level network check. Send the full address and location pin before choosing a plan or paying for installation. Nisan will confirm current feasibility, explain the available options and provide the applicable price and scheduling information.</p>
    </div></div></section>

    <section style="padding:60px 0;"><div class="container"><div class="row">
        <div class="col-lg-8"><div class="section-title mb-25"><span class="sub-title">SEARCH GUIDE</span><h2 class="title"><?= htmlspecialchars($seo['sectionTitle']) ?></h2></div><p style="font-size:1.05rem;line-height:1.85;"><?= htmlspecialchars($seo['section']) ?></p><h3 style="margin-top:28px;">How to choose the appropriate option</h3><p style="line-height:1.85;"><?= htmlspecialchars($seo['decision']) ?></p></div>
        <aside class="col-lg-4"><div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:24px;"><h2 style="font-size:1.3rem;">Topics covered on this page</h2><ul style="padding-left:20px;line-height:1.9;"><li><strong><?= htmlspecialchars($seo['primary']) ?></strong></li><?php foreach ($seo['secondary'] as $keyword): ?><li><?= htmlspecialchars($keyword) ?></li><?php endforeach; ?></ul><p style="font-size:.88rem;color:#64748b;margin:12px 0 0;">These phrases describe the same or closely related search need. The content answers the query naturally rather than repeating keywords unnaturally.</p></div></aside>
    </div></div></section>

    <section style="background:#eef6ff;padding:60px 0;"><div class="container"><div class="row align-items-center">
        <div class="col-lg-7"><h2>Local considerations for <?= htmlspecialchars($page['area']) ?></h2><p style="font-size:1.05rem;line-height:1.85;"><?= htmlspecialchars($seo['local']) ?></p><p style="line-height:1.85;">A coverage check should confirm more than the locality name. The technician needs to identify the correct building, available network point, cable distance and a safe route into the property. Apartments may require society or landlord permission. Shops and offices should also identify where the router, billing computer, CCTV recorder or television equipment will be placed.</p></div>
        <div class="col-lg-5"><div style="background:#fff;border-radius:14px;padding:26px;"><h3>Send these details for a useful answer</h3><ol style="line-height:2;padding-left:20px;"><li>Full address and PIN code</li><li>Society, building, shop or house name</li><li>Floor and nearby landmark</li><li>Google Maps location pin</li><li>Home, office, shop or apartment use</li></ol></div></div>
    </div></div></section>

    <section style="background:#f8fafc;padding:60px 0;"><div class="container"><div class="row g-4">
        <div class="col-lg-4"><div style="background:#fff;padding:28px;border-radius:14px;height:100%;"><h2 style="font-size:1.45rem;">1. Share the exact address</h2><p>Send the street, society or building name and a location pin. A precise address prevents misleading area-wide promises.</p></div></div>
        <div class="col-lg-4"><div style="background:#fff;padding:28px;border-radius:14px;height:100%;"><h2 style="font-size:1.45rem;">2. Confirm network feasibility</h2><p>The local team checks the nearest network point, building access and a safe cable route before confirming service.</p></div></div>
        <div class="col-lg-4"><div style="background:#fff;padding:28px;border-radius:14px;height:100%;"><h2 style="font-size:1.45rem;">3. Select and schedule</h2><p>Review the current plan or channel options, inclusions and installation timing. Installation is booked only after the details are clear.</p></div></div>
    </div></div></section>

    <section style="padding:60px 0;"><div class="container"><div class="section-title text-center mb-40"><span class="sub-title">BEFORE YOU ORDER</span><h2 class="title">Compare the complete service, not one number</h2></div><div class="row g-4">
        <div class="col-lg-3 col-md-6"><div style="border:1px solid #e2e8f0;border-radius:12px;padding:22px;height:100%;"><h3 style="font-size:1.15rem;">Coverage</h3><p>Confirm the exact building, available network point and installation route. Nearby service does not automatically guarantee your property.</p></div></div>
        <div class="col-lg-3 col-md-6"><div style="border:1px solid #e2e8f0;border-radius:12px;padding:22px;height:100%;"><h3 style="font-size:1.15rem;">Total price</h3><p>Check GST, installation, equipment, deposit, billing duration and renewal terms instead of relying only on a monthly equivalent.</p></div></div>
        <div class="col-lg-3 col-md-6"><div style="border:1px solid #e2e8f0;border-radius:12px;padding:22px;height:100%;"><h3 style="font-size:1.15rem;">Actual requirement</h3><p>Describe the people, devices, televisions or business systems that will use the service at the same time.</p></div></div>
        <div class="col-lg-3 col-md-6"><div style="border:1px solid #e2e8f0;border-radius:12px;padding:22px;height:100%;"><h3 style="font-size:1.15rem;">Local support</h3><p>Keep the support number, account details and equipment accessible so faults can be identified and handled efficiently.</p></div></div>
    </div></div></section>

    <section style="padding:60px 0;"><div class="container"><div class="row">
        <div class="col-lg-6"><h2>What to include in your enquiry</h2><ul><li>Full Bilimora address and nearby landmark</li><li>Society, building, wing or shop name</li><li>Location pin for an accurate coverage check</li><li>Whether the connection is for home or business</li><li>Main uses and number of connected devices</li></ul></div>
        <div class="col-lg-6"><h2>Useful service information</h2><p>Nisan is based at Morden Radio, opposite Laxmi Palace on Station Road, Bilimora. The team handles local coverage checks, installation and support for internet and Cable TV customers in covered areas.</p><p><a href="<?= htmlspecialchars($page['parent']) ?>">View the main service page</a> · <a href="/coverage-areas-bilimora">Bilimora coverage areas</a> · <a href="/pricing">Internet plans</a></p></div>
    </div></div></section>

    <section style="background:#f8fafc;padding:60px 0;"><div class="container"><div class="section-title text-center mb-40"><span class="sub-title">COMMON QUESTIONS</span><h2 class="title"><?= htmlspecialchars($page['h1']) ?> FAQs</h2></div><div class="row justify-content-center"><div class="col-lg-9">
        <?php foreach ($faqItems as $index=>$faq): ?><details style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 20px;margin-bottom:12px;"><summary style="font-weight:700;cursor:pointer;"><?= htmlspecialchars($faq['question']) ?></summary><p style="margin:14px 0 0;line-height:1.75;"><?= htmlspecialchars($faq['answer']) ?></p></details><?php endforeach; ?>
    </div></div></div></section>

    <section style="background:#0f172a;color:#fff;padding:52px 0;text-align:center;"><div class="container"><h2 style="color:#fff;">Check <?= htmlspecialchars($page['service']) ?> at your address</h2><p style="color:#cbd5e1;">Send your location in <?= htmlspecialchars($page['area']) ?>. The local team will confirm current availability.</p><a class="btn" href="<?= $whatsApp ?>" target="_blank" rel="noopener" style="background:#25D366;color:#fff;">Send Location on WhatsApp</a></div></section>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
