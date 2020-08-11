<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html lang="zxx">

<head>
    <!-- Basic Page Needs
    ================================================== -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Specific Meta
    ================================================== -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="description" content="">
    <meta name="keywords" content=""/>
    <meta name="author" content="">
    <!-- Titles
    ================================================== -->
    <title>SpaceFinEX</title>
    <!-- Favicons
    ================================================== -->
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="72x72" href="/Public/Home/fe/assets/images/">
    <link rel="apple-touch-icon" sizes="114x114" href="/Public/Home/fe/assets/images/">
    <!-- Custom Font
    ================================================== -->
    <link href="/Public/Home/fe/static/css/7a82141c14b3451d91a674b43285e946.css" rel="stylesheet">
    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="/Public/Home/fe/static/css/bootstrap.min.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/owl.carousel.min.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/meanmenu.min.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/simple-scrollbar.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/odometer-theme-default.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/fontawesome.all.min.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/lightcase.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/chartist.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/flaticon.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/style.css">
    <link rel="stylesheet" href="/Public/Home/fe/static/css/toastr.min.css">
    <script src="/Public/Home/fe/static/js/modernizr.min.js"></script>
    <!-- All The JS Files
    ================================================== -->
    <script src="/Public/Home/fe/static/js/jquery.js"></script>
    <script src="/Public/Home/fe/static/js/popper.min.js"></script>
    <script src="/Public/Home/fe/static/js/bootstrap.min.js"></script>
    <script src="/Public/Home/fe/static/js/plugins.js"></script>
    <script src="/Public/Home/fe/static/js/meanmenu.min.js"></script>
    <script src="/Public/Home/fe/static/js/imagesloaded.pkgd.min.js"></script>
    <script src="/Public/Home/fe/static/js/jquery.nice-select.min.js"></script>
    <script src="/Public/Home/fe/static/js/theia-sticky-sidebar.min.js"></script>
    <script src="/Public/Home/fe/static/js/resizesensor.min.js"></script>
    <script src="/Public/Home/fe/static/js/owl.carousel.min.js"></script>
    <script src="/Public/Home/fe/static/js/lightcase.js"></script>
    <script src="/Public/Home/fe/static/js/simple-scrollbar.min.js"></script>
    <script src="/Public/Home/fe/static/js/scrolla.jquery.min.js"></script>
    <script src="/Public/Home/fe/static/js/odometer.min.js"></script>
    <script src="/Public/Home/fe/static/js/isinviewport.jquery.js"></script>
    <script src="/Public/Home/fe/static/js/circle-progress.min.js"></script>
    <script src="/Public/Home/fe/static/js/chartist.min.js"></script>
    <script src="/Public/Home/fe/static/js/chartistpoint.js"></script>
    <script src="/Public/Home/fe/static/js/toastr.min.js"></script>
    <!-- main-js -->
    <script src="/Public/Home/fe/static/js/main.js"></script>
    <!-- layer.js-->
    <script src="/Public/Home/fe/static/js/layer/layer.js"></script>

</head>

<body>
<div class="preloader" style="display: none;">
    <div class="preloader-inner">
        <div class="preloader-icon">
            <span></span>
            <span></span>
        </div>
    </div>
</div>


<div class="site-content">

    <header class="site-header header-style-one">
    <div class="site-navigation style-one">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="navbar navbar-expand-lg navigation-area">
                        <div class="site-branding">
                            <a class="site-logo" href="/index">
                                <img src="/Public/Home/fe/static/picture/logo.png" alt="Site Logo"/>
                            </a>
                        </div>
                        <div class="mainmenu-area">
                            <nav class="menu">
                                <ul id="nav">
                                    <li><a href="/index">Home</a></li>
                                    <li><a href="/index#exchange">Exchange</a></li>
                                    <li><a href="/index/about">About</a></li>
                                    <li><a href="/index/terms">Terms</a></li>
                                    <li><a href="/index/news">News</a></li>
				    <li><a href="https://www.uxmex.com">Contract</a></li>
                                    <li class="dropdown-trigger">
                                        <a href="/UserCenter/index">User</a>
                                        <ul class="dropdown-content">
                                            <?php if(!$user): ?><li><a href="/login/showLogin">Sign In</a></li>
                                                <li><a href="/register/index">Sign Up</a></li>
                                                <?php else: ?>
                                                <li><a href="/UserCenter/index">User Panel</a></li>
                                                <li><a href="/UserCenter/myChain">My Chain</a></li>
                                                <li><a href="javascript:void(0);" onclick="logout()">Sign Out</a></li><?php endif; ?>
                                        </ul>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-menu-area">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="mobile-menu">
                        <a class="mobile-logo" href="./index.html">
                            <img src="/Public/Home/fe/static/picture/logo2.png" alt="logo">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function logout() {
        layer.closeAll();
        layer.load(2, {
            shade: [0.3, '#666']
        });
        $.ajax({
            url: '/Login/LoginOut',
            type: 'POST',
            success: function (resp) {
                layer.closeAll();
                if (resp.status) {
                    layer.msg('sign out success', {
                        shade: [0.3, '#666']
                    });
                    location.href = '/';
                    return;
                }
                layer.msg('sign out fail');
            },
            error: function (error) {
                layer.msg('sign out fail');
            }
        });
    }
</script>


    <div id="sticky-header"></div>
    <div class="page-title-area bg-primary" style="background-image: url('/Public/Home/fe/static/images/shape-dot1.png')">
        <div class="shape-group">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div><!-- /.shape-group -->
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="page-header-content text-center">
                        <div class="page-header-caption">
                            <h2 class="page-title">About Us</h2>
                        </div>
                        <!--~~./ page-header-caption ~~-->
                        <div class="breadcrumb-area">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="./index.html">Home</a></li>
                                <li class="breadcrumb-item active">About</li>
                            </ol>
                        </div>
                        <!--~~./ breadcrumb-area ~~-->
                    </div>
                    <!--~~./ page-header-content ~~-->
                </div>
            </div>
        </div>
        <!--~~./ end container ~~-->
    </div>


    <div class="about-us-block ptb-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="about-mock-up-thumb">
                        <div class="mock-up-thumb" data-animate="hg-fadeInLeft">
                            <img src="/Public/Home/fe/static/picture/about1.png" alt="Thumbnail">
                        </div><!-- /.mock-up-thumb -->
                        <div class="mock-up-thumb" data-animate="hg-fadeInLeft">
                            <img src="/Public/Home/fe/static/picture/about2.png" alt="Thumbnail">
                        </div><!-- /.mock-up-thumb -->
                    </div><!-- /.about-mock-up-thumb -->
                    <div class="experience-info-area bg-image bg-overlay-primary" data-animate="hg-fadeInUp">
                        <img src="/Public/Home/fe/static/images/experiance-bg.png" alt="">
                    </div><!-- /.experience-info-area -->
                </div><!-- /.col-lg-6 -->

                <div class="col-lg-6">
                    <div class="about-us-content md-mrt-60">
                        <div class="section-title">
                            <div class="subtitle" data-animate="hg-fadeInUp">About our exchange</div>
                            <h2 class="title-main" data-animate="hg-fadeInUp">About Us</h2><!-- /.title-main -->
                            <div class="title-text" data-animate="hg-fadeInUp">
                                We focus on cryptocurrency exchange, providing advanced financial services to global traders by using blockchain technology.
                            </div><!-- /.title-text -->
                        </div><!-- /.section-title -->
                        <div class="about-info-list">
                            <div class="single-info" data-animate="hg-fadeInUp">
                                <div class="info-header">
                                    <div class="about-icon icon-small icon-red">
                                        <div class="shape-icon"></div>
                                        <div class="icon">
                                            <span class="flaticon-scroll"></span>
                                        </div>
                                    </div><!-- /.about-icon-->
                                    <div class="info-title">
                                        <h3 class="heading">We are Secure</h3>
                                    </div><!-- /.info-icon -->
                                </div><!-- /.info-title -->
                                <div class="info">
                                    <p>We regard user’s asset security as the primary goal in life.</p>
                                </div><!-- /.info -->
                            </div><!-- /.single-info -->

                            <div class="single-info" data-animate="hg-fadeInUp">
                                <div class="info-header">
                                    <div class="about-icon icon-small icon-green">
                                        <div class="shape-icon"></div>
                                        <div class="icon">
                                            <span class="flaticon-target-1"></span>
                                        </div>
                                    </div><!-- /.about-icon-->
                                    <div class="info-title">
                                        <h3 class="heading">Our Mission</h3>
                                    </div><!-- /.info-icon -->
                                </div><!-- /.info-title -->
                                <div class="info">
                                    <p>We strive hard to never stop innovating and to improve our customer experience.
                                    </p>
                                </div><!-- /.info -->
                            </div><!-- /.single-info -->

                            <div class="single-info" data-animate="hg-fadeInUp">
                                <div class="info-header">
                                    <div class="about-icon icon-small">
                                        <div class="shape-icon"></div>
                                        <div class="icon">
                                            <span class="flaticon-telephone"></span>
                                        </div>
                                    </div><!-- /.about-icon-->
                                    <div class="info-title">
                                        <h3 class="heading">Best Support</h3>
                                    </div><!-- /.info-icon -->
                                </div><!-- /.info-title -->
                                <div class="info">
                                    <p>We provide 24/7 service and support for our users worldwide.
                                    </p>
                                </div><!-- /.info -->
                            </div><!-- /.single-info -->
                        </div>
                    </div><!-- /.about-us-content -->
                </div><!-- /.col-lg-6 -->
            </div><!-- /.row -->
        </div><!-- /.container -->
    </div>

    <div class="our-vision-block bg-primary pd-t-120">
        <div class="shape-group">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <div class="section-vertical-title-area">
            <h2 class="vertical-title"><span>our</span> vision</h2>
        </div><!-- /.section-vertical-title-area -->
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="our-vision-content-area">
                        <div class="our-vision-content bg-image"
                             style="background-image: url('/Public/Home/fe/static/images/shape-dot2.png')">
                            <div class="element-effect">
                                <img class="line" src="/Public/Home/fe/static/picture/line2.png" alt="Icon">
                                <img class="triangle" src="/Public/Home/fe/static/picture/triangle2.png" alt="Icon">
                                <img class="rectangle" src="/Public/Home/fe/static/picture/rectangle.png" alt="Icon">
                                <img class="circle" src="/Public/Home/fe/static/picture/circle.png" alt="Icon">
                            </div><!-- /.element-effect -->
                            <div class="vision-info-list">
                                <div class="card-info-one" data-animate="hg-fadeInUp">
                                    <div class="icon bg-green">
                                        <span class="flaticon-employee-1"></span>
                                    </div><!-- /.icon-->
                                    <div class="info">
                                        <h3 class="heading">Customer Relationship</h3>
                                        <p>Make every user happy to trade, and users are the builders of our
                                            platform.</p>
                                    </div><!-- /.info -->
                                </div><!-- /.card-info-one -->
                                <div class="card-info-one" data-animate="hg-fadeInUp">
                                    <div class="icon bg-red">
                                        <span class="flaticon-bar-chart"></span>
                                    </div><!-- /.icon-->
                                    <div class="info">
                                        <h3 class="heading">Our Company Growth</h3>
                                        <p>Grow a little bit every day and work hard to grow every day.</p>
                                    </div><!-- /.info -->
                                </div><!-- /.card-info-one -->
                                <div class="card-info-one" data-animate="hg-fadeInUp">
                                    <div class="icon bg-turquoise">
                                        <span class="flaticon-employee"></span>
                                    </div><!-- /.icon-->
                                    <div class="info">
                                        <h3 class="heading">100M Members</h3>
                                        <p>This is our first five-year plan and we are working on her. Please
                                            testify for us.</p>
                                    </div><!-- /.info -->
                                </div><!-- /.card-info-one -->
                            </div>
                        </div><!-- /.our-vision-content -->
                        <div class="vision-thumb-area" data-animate="hg-fadeInLeft">
                            <img src="/Public/Home/fe/static/picture/vision-thumb.png" alt="Thumb">
                        </div>
                    </div><!-- /.our-vision-content-area -->
                </div>
            </div>
        </div><!-- /.container -->
    </div>

    <div class="fanfact-block ptb-120">
        <div class="container ml-b-50">
            <div class="row fanfact-promo-numbers">
                <div class="col-lg-3 col-md-6" data-animate="hg-fadeInUp">
                    <div class="promo-number">
                        <div class="odometer-wrap">
                            <div class="odometer" data-odometer-final="58">0</div>
                            <sub>K</sub>
                        </div>
                        <h4 class="promo-title">Active Members</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-animate="hg-fadeInUp">
                    <div class="promo-number number-turquoise">
                        <div class="odometer-wrap">
                            <div class="odometer" data-odometer-final="488">0</div>
                        </div>
                        <h4 class="promo-title">Running Days</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-animate="hg-fadeInUp">
                    <div class="promo-number number-green">
                        <div class="odometer-wrap">
                            <div class="odometer" data-odometer-final="989">0</div>
                            <sub>M</sub>
                        </div>
                        <h4 class="promo-title">Total Exchange</h4>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6" data-animate="hg-fadeInUp">
                    <div class="promo-number number-red">
                        <div class="odometer-wrap">
                            <div class="odometer" data-odometer-final="162">0</div>
                            <sub>K</sub>
                        </div>
                        <h4 class="promo-title">Total Members</h4>
                    </div>
                </div>
            </div><!-- /.fanfact-promo-numbers -->
        </div><!-- /.container -->
    </div>

    <div class="investor-block pd-b-120">
        <div class="container ml-b-5">
            <div class="row">
                <div class="col-lg-6">
                    <div class="section-title">
                        <div class="subtitle" data-animate="hg-fadeInUp">Our team in here</div>
                        <h2 class="title-main" data-animate="hg-fadeInUp">Our Team</h2><!-- /.title-main -->
                        <div class="title-text" data-animate="hg-fadeInUp">
                        </div><!-- /.title-text -->
                    </div><!-- /.section-title -->
                </div>
                <div class="col-lg-6">
                    <div class="btn-links-area text-right">
                        <button class="btn-links btn-prev">
                            <span class="fa fa-angle-left"></span>
                        </button>
                        <button class="btn-links btn-next">
                            <span class="fa fa-angle-right"></span>
                        </button>
                    </div><!-- /.btn-links-area -->
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="investor-carousel owl-carousel" data-animate="hg-fadeInUp">
                        <div class="investor-item">
                            <figure class="investor-thumb">
                                <img src="/Public/Home/fe/static/picture/1.jpg" alt="Sport Thumb">
                            </figure><!-- /.investor-thumb -->
                            <div class="investor-info">
                                <h3 class="investor-name"><a>Tony Denial</a></h3>
                                <div class="designation">Foundation Chairman & CEO</div>
                            </div><!-- /.investor-info -->
                        </div><!-- /.investor-item -->
                        <div class="investor-item">
                            <figure class="investor-thumb">
                                <img src="/Public/Home/fe/static/picture/2.jpg" alt="Sport Thumb">
                            </figure><!-- /.investor-thumb -->
                            <div class="investor-info">
                                <h3 class="investor-name"><a>Dony Betty</a></h3>
                                <div class="designation">Chief Marketing Officer</div>
                            </div><!-- /.investor-info -->
                        </div><!-- /.investor-item -->
                        <div class="investor-item">
                            <figure class="investor-thumb">
                                <img src="/Public/Home/fe/static/picture/4.jpeg" alt="Sport Thumb">
                            </figure><!-- /.investor-thumb -->
                            <div class="investor-info">
                                <h3 class="investor-name">Jay Belle</h3>
                                <div class="designation">Chief Technical Officer</div>
                            </div><!-- /.investor-info -->
                        </div><!-- /.investor-item -->
                        <div class="investor-item">
                            <figure class="investor-thumb">
                                <img src="/Public/Home/fe/static/picture/3.jpg" alt="Sport Thumb">
                            </figure><!-- /.investor-thumb -->
                            <div class="investor-info">
                                <h3 class="investor-name">Polly Wastern</h3>
                                <div class="designation">Chief Financial Officer</div>
                            </div><!-- /.investor-info -->
                        </div><!-- /.investor-item -->

                    </div><!-- /.investor-carousel -->
                </div><!-- /.col-12 -->
            </div><!-- /.row -->
        </div><!-- /.container -->
    </div>

    <div class="work-brand-block pd-b-120">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <!--~~ Start Brands Carousel ~~-->
                    <div class="brands-carousel-main" data-animate="hg-fadeInUp">
                        <div class="brands-carousel owl-carousel">
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/btc.png" alt="BTC">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/eth.png" alt="ETH">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/bch.png" alt="BCH">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/ltc.png" alt="LTC">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/bdv.png" alt="BDV">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/eos.png" alt="EOS">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/bsv.png" alt="BSV">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/etc.png" alt="ETC">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/xrp.png" alt="XRP">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/xem.png" alt="XEM">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/dash.png" alt="DASH">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/xmr.png" alt="XMR">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/neo.png" alt="NEO">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/cvc.png" alt="CVC">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/zrx.png" alt="ZRX">
                            </div>
                            <div class="brands-link">
                                <img src="/Public/Home/fe/static/picture/atom.png" alt="ATOM">
                            </div>
                        </div>
                    </div>
                    <!--~./ end brands carousel ~-->
                </div>
            </div>
        </div>
    </div>
    <footer class="site-footer bg-primary pd-t-120" style="background-image: url('/Public/Home/fe/static/images/cloud-star.png')">
        <div class="footer-cloud-bg" style="background-image: url('/Public/Home/fe/static/images/cloud.png')"></div>
        <div class="footer-bottom-shape">
        </div>
        <div class="man-coin">
            <img src="/Public/Home/fe/static/picture/man.png" alt="Man Coin">
        </div>
        <div class="star-group">
            <img src="/Public/Home/fe/static/picture/star.png" alt="Star">
            <img src="/Public/Home/fe/static/picture/star.png" alt="Star">
            <img src="/Public/Home/fe/static/picture/star.png" alt="Star">
            <img src="/Public/Home/fe/static/picture/star.png" alt="Star">
            <img src="/Public/Home/fe/static/picture/star.png" alt="Star">
        </div>
        <div class="tree-group">
            <div class="tree-item-left" data-animate="hg-fadeInLeft">
                <img src="/Public/Home/fe/static/picture/tree1.png" alt="Tree">
            </div>
            <div class="tree-item-right" data-animate="hg-fadeInRight">
                <img src="/Public/Home/fe/static/picture/tree2.png" alt="Tree">
            </div>
        </div>
        <div class="footer-widget-area">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <aside class="widget widget_about">
                            <div class="widget-content">
                                <div class="about-loga">
                                    <img src="/Public/Home/fe/static/picture/footer-logo.png" alt="Logo">
                                </div>
				<p>We fully understand and respect the privacy to you, 
                                 and we will adopt corresponding appropriate safety 
                                protection measures to protect your privacy in accordance 
                                with the requirements of the applicable laws and regulations.</p>
                                <div class="call-info">
                                    <h4 class="title">Contact Us</h4>
                                    <p>onefinex@gmail.com</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                    <div class="col-lg-4">
                        <aside class="widget widget_links">
                            <h2 class="widget-title">Useful link</h2>
                            <div class="widget-content">
                                <ul>
                                    <li><a href="./about.html">About</a></li>
                                    <li><a href="./service.html">Services</a></li>

                                    <li><a href="./privacy.html">Privacy policy</a></li>
                                    <li><a href="./terms.html">Terms & Conditions</a></li>
                                </ul>
                            </div>
                        </aside>
                    </div>
                    <div class="col-lg-2">
                        <aside class="widget widget_links">
                            <h2 class="widget-title">My Account</h2>
                            <div class="widget-content">
                                <ul>
                                    <li><a href="view/User.html">User Center</a></li>
                                    <li><a href="view/User.html">My Chains</a></li>
                                    <li><a href="view/User.html">Setting</a></li>
                                    <li><a href="./userOut.html">Sign Out</a></li>
                                </ul>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom-area">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="copyright-text text-center">
                            <p>Copyright © OneFinEX 2019-2020 . All rights reserved</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>
</body>

</html>