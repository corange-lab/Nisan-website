


<head>
    <!-- Google tag -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-938737099"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'AW-938737099');
        // Conversion event snippet
        gtag('event', 'conversion', { 'send_to': 'AW-938737099/oaWPCO7v14AaEMv7z78D' });
    </script>
    <title>Thank You – Nisan Cable TV & Internet</title>
    <?php include('common-css.php'); ?>
</head>


<body>

<?php include('header.php'); ?>

<!-- main-area -->
<main>

    <!-- breadcrumb-area -->
    <section class="breadcrumb-area breadcrumb-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-8">
                    <div class="breadcrumb-content">
                        <h3 class="title">Thank You!</h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/index">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Thank You</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- breadcrumb-area-end -->

    <!-- thank-you-area -->
    <section class="thank-you-area section-space">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-5 col-lg-6 col-md-8 col-sm-11">
                    <div class="error-img text-center mb-40">
                        <!--<img src="assets/imgs/images/thank-you.webp" alt="Thank You">-->
                    </div>
                    <div class="error-content text-center mb-40">
                        <h3 class="title">Thanks for Showing Interest!</h3>
                        <p>We have received your request for a new connection. Our team will contact you soon.</p>
                        <p>Redirecting to the home page in <span id="countdown">10</span> seconds...</p>
                        <a href="/index" class="btn">Go Back to Home Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- thank-you-area-end -->

   

</main>
<!-- main-area-end -->

<?php include('footer.php'); ?>

<!-- JS here -->
<script src="assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/jquery.magnific-popup.min.js"></script>
<script src="assets/js/owl.carousel.min.js"></script>
<script src="assets/js/jquery.odometer.min.js"></script>
<script src="assets/js/jquery.appear.js"></script>
<script src="assets/js/jquery.flipster.min.js"></script>
<script src="assets/js/jquery-ui.min.js"></script>
<script src="assets/js/jquery.nice-select.min.js"></script>
<script src="assets/js/jarallax.min.js"></script>
<script src="assets/js/slick.min.js"></script>
<script src="assets/js/swiper.min.js"></script>
<script src="assets/js/wow.min.js"></script>
<script src="assets/js/main.js"></script>

<!-- Countdown Timer and Redirect Script -->
<script>
    let countdownElement = document.getElementById('countdown');
    let seconds = 10;

    const countdownTimer = setInterval(function () {
        seconds--;
        countdownElement.innerText = seconds;

        if (seconds === 0) {
            clearInterval(countdownTimer);
            window.location.href = "index.php"; // Redirect to home page
        }
    }, 1000); // Update every 1 second
</script>

</body>
</html>
