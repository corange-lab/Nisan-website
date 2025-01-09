<?php
ob_start(function ($buffer) {
    return str_replace([".jpg", ".png"], ".webp", $buffer);
});
?>


<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!doctype html>
<html lang="en">


<!-- header-area -->

<header>
<div class="header-top-wrap">
<div class="container">
<div class="row">
<div class="col-xl-8 col-md-7 col-sm-7">
<div class="header-top-left">
<ul>
	<li class="d-none d-xl-flex"><i class="flaticon-location"></i> <a href="https://maps.app.goo.gl/TUHbd83NAyXgmCvN9">Morden Radio, Nr. Laxmi Palace, Station
                                        Rd, Bilimora.</a></li>
                                <li class="d-none d-lg-flex"><i class="flaticon-email"></i> <a href="#mailto:hello@nisan.co.in">hello@nisan.co.in</a></li>
                                <li><i class="flaticon-clock"></i>Time : 09: AM - 09 PM</li>
</ul>
</div>
</div>

<div class="col-xl-4 col-md-5 col-sm-5">
<div class="header-top-right">
<ul>

</ul>
</div>
</div>
</div>
</div>
</div>

<div id="header-top-fixed"></div>

<div class="menu-area" id="sticky-header">
<div class="container">
<div class="row">
<div class="col-12">
<div class="mobile-nav-toggler"><i class="fas fa-bars"></i></div>

<div class="menu-wrap">
<nav class="menu-nav">
<div class="logo"><a href="index.php"><img alt="logo" src="assets/imgs/logo/logo.webp" /></a></div>

<div class="navbar-wrap main-menu d-none d-lg-flex">
<ul class="navigation">
	<li class="<?= ($currentPage == 'index.php') ? 'active' : '' ?>"><a href="/index">Home</a></li>
	<li class="<?= ($currentPage == 'about-us.php') ? 'active' : '' ?>"><a href="/about-us">About</a></li>
	<li class="<?= ($currentPage == 'services.php') ? 'active' : '' ?>"><a href="/services">Services</a></li>
	<li class="<?= ($currentPage == 'contact.php') ? 'active' : '' ?>"><a href="/contact">contacts</a></li>
</ul>
</div>

<div class="header-action d-none d-md-block">
<ul>
	<li class="header-btn"><a class="btn transparent-btn" href="/contact">Get a Quote</a></li>
</ul>
</div>
</nav>
</div>
<!-- Mobile Menu  -->
                        <div class="mobile-menu">
                            <nav class="menu-box">
                                <div class="close-btn"><i class="fal fa-times"></i></div>
                                <div class="nav-logo"><a href="/index"><img src="assets/imgs/logo/logo.webp" alt="logo"
                                            title=""></a>
                                </div>
                                <div class="menu-outer">
                                    <!--Here Menu Will Come Automatically Via Javascript / Same Menu as in Header-->
                                </div>
                                <div class="social-links">
                                    <ul class="clearfix">
                                        <li><a href="#"><span class="fab fa-facebook-f"></span></a></li>
                                        <li><a href="#"><span class="fab fa-twitter"></span></a></li>
                                        <li><a href="#"><span class="fab fa-pinterest-p"></span></a></li>
                                        <li><a href="#"><span class="fab fa-instagram"></span></a></li>
                                        <li><a href="#"><span class="fab fa-youtube"></span></a></li>
                                    </ul>
                                </div>
                            </nav>
                        </div>
                        <div class="menu-backdrop"></div>
                        <!-- End Mobile Menu --></div>
</div>
</div>
</div>
</header>
<!-- header-area-end -->