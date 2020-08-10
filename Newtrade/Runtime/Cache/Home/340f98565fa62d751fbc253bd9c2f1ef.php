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
    <link
        href="/Public/Home/fe/staticcss?family=Josefin+Sans:300,400,600,700%7COpen+Sans:300,400,600,700&display=swap"
        rel="stylesheet">


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
        <div class="page-title-area bg-primary" style="background-image: url('/Public/Home/fe/staticimages/shape/shape-dot1.png')">
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
                                <h2 class="page-title">My Chains</h2>
                            </div>
                            <div class="breadcrumb-area">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?php echo U('UserCenter/index');?>">Home</a></li>
                                    <li class="breadcrumb-item active">My Chains</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-page-block ptb-120">
            <div class="container">
                <div class="row flex-wrap-reverse">
                    <div class="col-lg-9">
                        <div class="dashboard-page-main">

                            <div class="row pricing-table-list">
                                <div class="col-lg-6">
                                    <div class="pricing-box pricing-green" data-animate="hg-fadeInUp">
                                        <div class="text-center">
                                            <img src="/Public/Home/fe/static/picture/USDT.png" width="100"
                                                class="rounded-circle mb-4" />
                                        </div>
                                        <!--
                                            <div class="pricing-icon icon-default icon-green">
                                                <div class="shape-icon"></div>
                                                <div class="icon">
                                                    <span class="flaticon-profit"></span>
                                                </div>
                                            </div>
                                            -->
                                        <div class="package-price">
                                            <div class="pricing">USDT</div>
                                            <div></div>
                                            <div class="divider"></div>
                                        </div>
                                        <div class="pricing-content">
                                            <ul class="list">
                                                <li><?php echo ($currency['USDT']['usdt_num']); ?></li>
                                            </ul>
                                        </div>
                                        <div class="pricing-footer text-center">
                                            <a href="<?php echo U('Wallent/depositUstd');?>" class="btn btn-success">Deposit</a>
                                            <a href="<?php echo U('Wallent/showWithDraw',array('currencyId'=>8,'currencyName'=>'USDT'));?>" class="btn btn-danger">Withdraw</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="pricing-box pricing-green" data-animate="hg-fadeInUp">
                                        <div class="text-center">
                                            <img src="/Public/Home/fe/static/picture/btc.png" width="100"
                                                class="rounded-circle mb-4" />
                                        </div>
                                        <!--
                                            <div class="pricing-icon icon-default icon-green">
                                                <div class="shape-icon"></div>
                                                <div class="icon">
                                                    <span class="flaticon-profit"></span>
                                                </div>
                                            </div>
                                            -->
                                        <div class="package-price">
                                            <div class="pricing">BTC</div>
                                            <div></div>
                                            <div class="divider"></div>
                                        </div>
                                        <div class="pricing-content">
                                            <ul class="list">
                                                <li><?php echo ($currency['BTC']['num']); ?></li>
                                            </ul>
                                        </div>
                                        <div class="pricing-footer text-center">
                                            <a href="<?php echo U('Wallent/depositIcon',array('currencyName'=>btc));?>" class="btn btn-success">Deposit</a>
                                            <a href="<?php echo U('Wallent/showWithDraw',array('currencyId'=>1,'currencyName'=>'BTC'));?>" class="btn btn-danger">Withdraw</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="pricing-box pricing-green" data-animate="hg-fadeInUp">
                                        <div class="text-center">
                                            <img src="/Public/Home/fe/static/picture/eth.png" width="100"
                                                class="rounded-circle mb-4" />
                                        </div>
                                        <!--
                                            <div class="pricing-icon icon-default icon-green">
                                                <div class="shape-icon"></div>
                                                <div class="icon">
                                                    <span class="flaticon-profit"></span>
                                                </div>
                                            </div>
                                            -->
                                        <div class="package-price">
                                            <div class="pricing">ETH</div>
                                            <div></div>
                                            <div class="divider"></div>
                                        </div>
                                        <div class="pricing-content">
                                            <ul class="list">
                                                <li><?php echo ($currency['ETH']['num']); ?></li>
                                            </ul>
                                        </div>
                                        <div class="pricing-footer text-center">
                                            <a href="<?php echo U('Wallent/depositIcon',array('currencyName'=>eth));?>" class="btn btn-success">Deposit</a>
                                            <a href="<?php echo U('Wallent/showWithDraw',array('currencyId'=>4,'currencyName'=>'ETH'));?>" class="btn btn-danger">Withdraw</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="pricing-box pricing-green" data-animate="hg-fadeInUp">
                                        <div class="text-center">
                                            <img src="/Public/Home/fe/static/picture/bdv.png" width="100"
                                                class="rounded-circle mb-4" />
                                        </div>
                                        <!--
                                            <div class="pricing-icon icon-default icon-green">
                                                <div class="shape-icon"></div>
                                                <div class="icon">
                                                    <span class="flaticon-profit"></span>
                                                </div>
                                            </div>
                                            -->
                                        <div class="package-price">
                                            <div class="pricing">FEC</div>
                                            <div></div>
                                            <div class="divider"></div>
                                        </div>
                                        <div class="pricing-content">
                                            <ul class="list">
                                                <li><?php echo ($currency['FEC']['num']); ?></li>
                                            </ul>
                                        </div>
                                        <div class="pricing-footer text-center">
                                            <a href="javascript:void(0);" onclick="waite()" class="btn btn-success">Deposit</a>
                                            <a href="javascript:void(0);" onclick="waite()" class="btn btn-danger">Withdraw</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="dashboard-menu-area md-mrb-60">
                            <ul class="dashboard-menu">
                                <li><a href="index.html">User Panel</a></li>
                                <?php if($real == 2 ): ?><li><a href="<?php echo U('UserCenter/realName');?>">Authenticate</a></li><?php endif; ?>
                                <li><a href="<?php echo U('Wallent/iconRecord',array('type'=>1));?>">Deposit History</a></li>
                                <li><a href="<?php echo U('Wallent/iconRecord',array('type'=>2));?>">Withdraw History</a></li>
                                <li><a href="<?php echo U('Wallent/iconChangeRecord');?>">Exchange History</a></li>
                                <li><a href="<?php echo U('UserCenter/changePass');?>">Change Password</a></li>
                                <li><a href="/Login/LoginOut">Sign Out</a></li>
                            </ul>
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
<script>
    function waite() {
        layer.msg('Coming Soon!')
    }
</script>
</html>