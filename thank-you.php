<?php
// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = htmlspecialchars(trim($_POST["name"] ?? ''));
    $phone   = htmlspecialchars(trim($_POST["number"] ?? ''));
    $email   = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);

    if ($name && $phone) {
        $to      = "hello@nisan.co.in";
        $subject = "New Connection Request from $name";
        $body    = "Name: $name\nPhone: $phone\nEmail: $email\n";
        $headers = "From: noreply@nisan.co.in\r\nReply-To: $email";
        mail($to, $subject, $body, $headers);
    }
}
?>
<!DOCTYPE html>
<html lang="en">


<head>
    <!-- Conversion tracking fired on thank-you page load -->
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
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
            window.location.href = "/index";
        }
    }, 1000); // Update every 1 second
</script>

</body>
</html>
