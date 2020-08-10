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
    <!-- Custom Font
    ================================================== -->
    <link
        href="https://fonts.googleapis.com/css?family=Josefin+Sans:300,400,600,700%7COpen+Sans:300,400,600,700&display=swap"
        rel="stylesheet">
    <!-- CSS
    ================================================== -->


<body>
<div class="preloader">
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
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="page-header-content text-center">
                            <div class="page-header-caption">
                                <h2 class="page-title"><?php echo ($currencyName); ?></h2>
                            </div>
                            <div class="breadcrumb-area">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo U('UserCenter/index');?>">Home</a></li>
                                    <li class="breadcrumb-item"><a href="<?php echo U('UserCenter/myChain');?>">Chain</a></li>
                                    <li class="breadcrumb-item active">Deposit</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="testimonial-block pd-t-120 pd-b-200 bg-gradient">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="section-title text-center">
                            <div class="subtitle" data-animate="hg-fadeInUp">if you want to deposit</div>
                            <h2 class="title-main" data-animate="hg-fadeInUp"><?php echo ($currencyName); ?></h2><!-- /.title-main -->
                            <div class="title-text" data-animate="hg-fadeInUp">you must copy this wallet address and
                                send <?php echo ($currencyName); ?> to this wallet address.</div><!-- /.title-text -->
                        </div>
                        <div class="text-center">
                            <img style="" src='/Public/Uploads/<?php echo ($iconAddress); ?>.png'>
                            <p style="text-align:center;word-wrap:break-word;font-weight:bold;">
                                <?php echo ($iconAddress); ?></p>
                            <button onclick="toClipboard('<?php echo ($iconAddress); ?>')"
                                class="btn btn-lg btn-warning"><i class="fa fa-clipboard"></i> copy to
                                clipboard</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="site-footer bg-primary pd-t-120"
        style="background-image: url('/Public/Home/fe/static/images/cloud-star.png')">
    <div class="footer-cloud-bg"
         style="background-image: url('/Public/Home/fe/static/images/cloud.png')"></div>
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
                                <p>SpaceFinEX@gmail.com</p>
                            </div>
                        </div>
                    </aside>
                </div>
                <div class="col-lg-4">
                    <aside class="widget widget_links">
                        <h2 class="widget-title">Useful link</h2>
                        <div class="widget-content">
                            <ul>
                                <li><a href="/index/about.html">About</a></li>
                                <li><a href="/index/service.html">Services</a></li>

                                <li><a href="/index/privacy.html">Privacy policy</a></li>
                                <li><a href="/index/terms.html">Terms & Conditions</a></li>
                            </ul>
                        </div>
                    </aside>
                </div>
                <div class="col-lg-2">
                    <aside class="widget widget_links">
                        <h2 class="widget-title">My Account</h2>
                        <div class="widget-content">
                            <ul>
                                <li><a href="/UserCenter/index.html">User Center</a></li>
                                <li><a href="/UserCenter/myChain.html">My Chains</a></li>
                                <li><a href="/UserCenter/index.html">Setting</a></li>
                                <li><a href="/Login/LoginOut">Sign Out</a></li>
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
                        <p>Copyright © SpaceFinEX 2019-2020 . All rights reserved</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

    </div>
</body>

</html>