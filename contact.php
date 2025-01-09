<!DOCTYPE html>
<html lang="en">

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form fields and remove whitespace
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $name = trim($_POST["name"]);
    $phone = trim($_POST["number"]);

    // Set the recipient email address
    $recipient = "hello@nisan.co.in";

    // Set the email subject
    $email_subject = "New Connection request";

    // Build the email content
    $email_content = "Name:\n $name\n\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Contact:\n$phone\n\n";

    // Build the email headers
    $email_headers = "From: $email";

    // Send the email
    mail($recipient, $email_subject, $email_content, $email_headers);
} else {
    // Not a POST request, set a 403 (forbidden) response code
    http_response_code(403);
}
?>

<!-- Event snippet for Contact conversion page -->
<script>
  gtag('event', 'conversion', {'send_to': 'AW-938737099/oaWPCO7v14AaEMv7z78D'});
</script>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us – Nisan Cable TV & Internet in Bilimora</title>
    <meta name="description" content="Get in touch with Nisan Cable TV & Internet in Bilimora. Call, email, or visit us for inquiries about broadband and cable TV services.">
    <meta name="keywords" content="contact Nisan internet provider, customer support Bilimora, broadband help Bilimora, cable TV contact Bilimora">
    <link rel="canonical" href="https://www.nisan.co.in/">


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
                    <div class="col-lg-8 col-md-8">
                        <div class="breadcrumb-content">
                            <h1 class="title">Contact Nisan – Your Local Internet & Cable Provider</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="/index">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Contact</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- breadcrumb-area-end -->

        <!-- contact-area -->
        <section class="contact-area section-space-top section-meadium-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="contact-title">
                            <h3 class="title">Get in Touch</h3>
                        </div>
                        <form action="/thank-you" method="post" class="contact-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-grp">
                                        <input type="text" id="name" autocomplete="off" name="name" required>
                                        <label for="name">First Name*</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-grp">
                                        <input type="number" id="phone" autocomplete="off" name="number" required>
                                        <label for="phone">Phone</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-grp">
                                        <input type="email" id="email" autocomplete="off" name="email" required>
                                        <label for="email">Email*</label>
                                    </div>
                                </div>
                            
                            </div>
                        
                            <p class="contact-form-check">
                                <input type="checkbox" class="form-check-input" id="cookies-consent">
                                <label for="cookies-consent" class="form-check-label">I agree with that, my data is
                                    being saved for further contact, see our <a href="/privacy-policy">Privacy
                                        Policy</a></label>
                            </p>
                            <button type="submit" class="btn">Submit Message</button>
                        </form>
                    </div>
                    <div class="col-lg-4">
                        <div class="contact-info-wrap">
                            <h3 class="contact-info-title">Get in Touch</h3>
                            <p>We’re here to help you with any questions or support you need. Reach out to us using the details below:</p>
                            <ul class="contact-info-list">
                                <li><i class="fal fa-phone"></i> <a href="tel:+919825152400">+91 98251 52400</a></li>
                                <li><i class="fal fa-envelope"></i> <a href="hello@nisan.co.in</span></a></li>
                                <li><i class="fal fa-map-marker-alt"></i> <span>Morden Radio, <br>Opp. Laxmi Palace, <br>Station Road, <br>Bilimora-396321</span></li>
                            </ul>
                        </div>
                        <div class="contact-info-wrap">
                            <h3 class="contact-info-title">Live Chat</h3>
                            <p>Need immediate assistance?</p>
                            <div class="live-chat">
                                <div class="icon"><i class="flaticon-chat"></i></div>
                                <a href="#" class="live-chat-link">Live Chat to Executive</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- contact-area-end -->

        <!-- contact-map -->
        <div id="contact-map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d869.1810006621511!2d72.96510313290823!3d20.768304267406528!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be0ef0070f48dbd%3A0xe93f1944f822ffb2!2sNisan%20Internet!5e0!3m2!1sen!2sin!4v1736154583641!5m2!1sen!2sin" width="800" height="600" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <!-- contact-map-end -->

        


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
</body>

</html>