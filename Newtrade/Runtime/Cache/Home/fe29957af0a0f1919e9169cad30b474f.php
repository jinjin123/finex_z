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
    <div class="hero-block bg-primary" style="background-image: url('/Public/Home/fe/static/images/shape-dot5.png')">
        <div class="element-effect">
            <img src="/Public/Home/fe/static/picture/line3.png" alt="Line">
            <img src="/Public/Home/fe/static/picture/line3.png" alt="Line">
            <img src="/Public/Home/fe/static/picture/line3.png" alt="Line">
            <img src="/Public/Home/fe/static/picture/line3.png" alt="Line">
            <img src="/Public/Home/fe/static/picture/line3.png" alt="Line">
        </div>
        <div class="hero-shape-top" style="background-image: url('/Public/Home/fe/static/images/hero-shape-top.png')"></div>
        <div class="hero-shape-bottom"
             style="background-image: url('/Public/Home/fe/static/images/header-shape.png')"></div>
        <div class="hero-block-inner">
            <div class="container">
                <div class="row hero-content-info-area justify-content-end">
                    <div class="col-lg-9">
                        <div class="hero-mockup-thumb-area">
                            <div class="hero-info-list text-white">
                                <div class="line-shape">
                                    <img src="/Public/Home/fe/static/picture/line-shape.png" alt="Icon">
                                </div>
                                <div class="hero-info-list-inner">
                                    <div class="hero-info">
                                        <div class="text">Get Profit</div>
                                        <div class="icon">
                                            <img src="/Public/Home/fe/static/picture/icon-4.png" alt="Icon">
                                        </div>
                                    </div>
                                    <div class="hero-info">
                                        <div class="icon">
                                            <img src="/Public/Home/fe/static/picture/icon-2.png" alt="Icon">
                                        </div>
                                        <div class="text">Trade</div>
                                    </div>
                                    <div class="hero-info">
                                        <div class="icon">
                                            <img src="/Public/Home/fe/static/picture/icon.png" alt="Icon">
                                        </div>
                                        <div class="text">Deposit</div>
                                    </div>
                                    <div class="hero-info">
                                        <div class="icon">
                                            <img src="/Public/Home/fe/static/picture/icon-3.png" alt="Icon">
                                        </div>
                                        <div class="text">Create account</div>
                                    </div>
                                </div>
                            </div>
                            <div class="hero-thumb-area">
                                <div class="hero-thumb-inc">
                                    <img src="/Public/Home/fe/static/picture/profit-inc.png" alt="Inc">
                                </div>
                                <div class="hero-thumb">
                                    <img src="/Public/Home/fe/static/picture/hero-thumb.png" alt="Thumbnail">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row hero-content-area align-content-center">
                    <div class="col-lg-6">
                        <div class="hero-content">
                            <div class="hero-subtitle">Best Exchange</div>
                            <h2 class="hero-title">We provide the best trading platform</h2><!-- /.hero-title -->
                            <div class="form-group-btn">
                                <a class="btn btn-default btn-primary" href="/register/index">Open An
                                    Account</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="deposit-withdraw-block bg-image bg-primary ptb-120"
         style="background-image: url('/Public/Home/fe/static/images/shape01.png')">
        <div class="coin-thumb" data-animate="hg-fadeInRight">
            <img src="/Public/Home/fe/static/picture/conis.png" alt="Thumbnail">
        </div>
        <div class="container" id="exchange">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="section-title text-center text-white">
                        <div class="subtitle" data-animate="hg-fadeInUp"></div>
                        <h2 class="title-main" data-animate="hg-fadeInUp">Chains</h2>
                        <div class="title-text" data-animate="hg-fadeInUp"> The price/USDT
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="deposit-withdraw-content" data-animate="hg-fadeInUp">
                        <div class="table-responsive deposit-table full">
                            <table class="table chainTable">
                                <thead></thead>
                                <tbody>

                                <tr style="cursor: pointer;" onclick="location.href='/index/chart?name=fec'">
                                    <td width="10%">Icon</td>
                                    <td width="10%">Base</td>
                                    <td width="10%">Now</td>
                                    <td width="10%">Change</td>
                                    <td width="10%">Open</td>
                                    <td width="10%">High</td>
                                    <td width="10%">Low</td>
                                    <td width="15%">Quote Volume</td>
                                    <td width="15%">Base Volume</td>
                                </tr>

                                <?php if(is_array($chain)): foreach($chain as $k=>$vo): ?><tr style="cursor: pointer;"
                                        onclick="location.href='/index/chart?name=<?php echo ($vo["instrument_id"]); ?>'">
                                        <td width="10%">
                                            <img src="/Public/Home/fe/static/images/<?php echo ($vo["instrument_id"]); ?>.png" width="38px">
                                        </td>
                                        <td width="10%"><?php echo ($vo["instrument_id"]); ?></td>
                                        <td width="10%"><?php echo ($vo["last"]); ?></td>
                                        <td width="10%">
                                            <?php if($vo["rate"] >= 0): ?><span style="color: #28a745;">
                                            <?php else: ?>
                                                <span style="color: #dc3545;"><?php endif; ?>
                                            <?php echo ($vo["rate"]); ?>%</span>
                                        </td>
                                        <td width="10%"><?php echo ($vo["open_24h"]); ?></td>
                                        <td width="10%"><?php echo ($vo["high_24h"]); ?></td>
                                        <td width="10%"><?php echo ($vo["low_24h"]); ?></td>
                                        <td width="15%"><?php echo ($vo["quote_volume_24h"]); ?></td>
                                        <td width="15%"><?php echo ($vo["base_volume_24h"]); ?></td>
                                    </tr><?php endforeach; endif; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="work-brand-block style-one pd-b-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="brand-list" data-animate="hg-fadeInLeft">
                        <a href="/index/chart?name=BTC" class="brands-link">
                            <img src="/Public/Home/fe/static/images/BTC.png" alt="BTC">
                        </a>
                        <a href="/index/chart?name=ETH" class="brands-link">
                            <img src="/Public/Home/fe/static/images/ETH.png" alt="ETH">
                        </a>
                        <a href="/index/chart?name=BCH" class="brands-link">
                            <img src="/Public/Home/fe/static/images/BCH.png" alt="BCH">
                        </a>
                        <a href="/index/chart?name=LTC" class="brands-link">
                            <img src="/Public/Home/fe/static/images/LTC.png" alt="LTC">
                        </a>
                        <a href="/index/chart?name=FEC" class="brands-link">
                            <img src="/Public/Home/fe/static/images/FEC.png" alt="BDV">
                        </a>
                        <a href="/index/chart?name=EOS" class="brands-link">
                            <img src="/Public/Home/fe/static/images/EOS.png" alt="EOS">
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section-title">
                        <div class="subtitle" data-animate="hg-fadeInUp">HOT CHAINS
                        </div>
                        <h2 class="title-main" data-animate="hg-fadeInUp">Best Choice</h2><!-- /.title-main -->
                        <div class="title-text" data-animate="hg-fadeInUp"> We recommend you to choose active and valuable coins

                        </div><!-- /.title-text -->
                    </div>
                    <div class="work-brand-content" data-animate="hg-fadeInUp">
                        <a class="btn btn-default btn-primary" href="/Login/showLogin">Please Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="/Public/Home/fe/static/js/moment.min.js"></script>
    <script src="/Public/Home/fe/static/js/numeral.min.js"></script>
    <script>
    </script>

    <div id="work-process-block" class="work-process-block ptb-120">
        <div class="element-effect">
            <img class="star" src="/Public/Home/fe/static/picture/star2.png" alt="Icon">
            <img class="line" src="/Public/Home/fe/static/picture/line2.png" alt="Icon">
            <img class="triangle" src="/Public/Home/fe/static/picture/triangle2.png" alt="Icon">
            <img class="rectangle" src="/Public/Home/fe/static/picture/rectangle.png" alt="Icon">
            <img class="circle" src="/Public/Home/fe/static/picture/circle.png" alt="Icon">
            <img class="circle2" src="/Public/Home/fe/static/picture/circle2.png" alt="Icon">
        </div>
        <div class="container ml-b-45">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="section-title text-center">
                        <h4 class="subtitle" data-animate="hg-fadeInUp">Fast & Easy Process.</h4>
                        <h2 class="title-main" data-animate="hg-fadeInUp">Simple and pleasant Trade</h2>
                        <p class="title-text" data-animate="hg-fadeInUp"></p>
                    </div>
                </div>
            </div>

            <div class="row process-list">
                <div class="bg-line" style="background-image:url('/Public/Home/fe/static/images/linearrow.png')"></div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="single-process" data-animate="hg-fadeInUp">
                        <div class="icon color-red">
                            <span class="flaticon-profile"></span>
                        </div>
                        <h2 class="process-step">Creat Account</h2>
                        <h3 class="process-no">STEP 01</h3>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="single-process" data-animate="hg-fadeInUp">
                        <div class="icon color-blue">
                            <span class="flaticon-click"></span>
                        </div>
                        <h2 class="process-step">Deposit</h2>
                        <h3 class="process-no">STEP 02</h3>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="single-process" data-animate="hg-fadeInUp">
                        <div class="icon color-green">
                            <span class="flaticon-debit-card"></span>
                        </div>
                        <h2 class="process-step">Trade</h2>
                        <h3 class="process-no">STEP 03</h3>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="single-process" data-animate="hg-fadeInUp">
                        <div class="icon color-yellow">
                            <span class="flaticon-bars"></span>
                        </div>
                        <h2 class="process-step">Get Profit</h2>
                        <h3 class="process-no">STEP 04</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="our-vision-block bg-primary pd-t-120">
        <div class="section-vertical-title-area">
            <h2 class="vertical-title"><span>our</span> vision</h2>
        </div>
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
                            </div>
                            <div class="vision-info-list">
                                <div class="card-info-one" data-animate="hg-fadeInUp">
                                    <div class="icon bg-green">
                                        <span class="flaticon-employee-1"></span>
                                    </div>
                                    <div class="info">
                                        <h3 class="heading">Customer Relationship</h3>
                                        <p>Be pleasant to trade and be the builders of the platform.
                                        </p>
                                    </div>
                                </div>
                                <div class="card-info-one" data-animate="hg-fadeInUp">
                                    <div class="icon bg-red">
                                        <span class="flaticon-bar-chart"></span>
                                    </div>
                                    <div class="info">
                                        <h3 class="heading">Our Company Growth</h3>
                                        <p>Strength and growth come only through continuous effort and struggle.
                                        </p>
                                    </div>
                                </div>
                                <div class="card-info-one" data-animate="hg-fadeInUp">
                                    <div class="icon bg-turquoise">
                                        <span class="flaticon-employee"></span>
                                    </div>
                                    <div class="info">
                                        <h3 class="heading">100M Members</h3>
                                        <p>Stand with us to fulfill the 100M-Members goal.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vision-thumb-area" data-animate="hg-fadeInLeft">
                            <img src="/Public/Home/fe/static/picture/vision-thumb.png" alt="Thumb">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="latest-news-block bg-gradient pd-t-120">
        <div class="container ml-b-30">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="section-title text-center">
                        <div class="subtitle" data-animate="hg-fadeInUp">Larest News Post</div>
                        <h2 class="title-main" data-animate="hg-fadeInUp">News</h2>
                        <div class="title-text" data-animate="hg-fadeInUp"></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php if(is_array($news)): foreach($news as $k=>$vo): ?><div class="col-lg-4 col-md-6">
                        <article class="post post-grid" data-animate="hg-fadeInUp">
                            <a href="<?php echo U('getNewsDetail',array('id'=>$vo['id']));?>">
                                <img src="<?php echo ($url); echo ($vo["face_img"]); ?>"
                                     alt=""/>
                            </a>
                            <div class="post-details">
                                <h2 class="entry-title">
                                    <a href="/Index/getNewsDetail/id/<?php echo ($vo["id"]); ?>.html"><?php echo ($vo['title']); ?></a>
                                </h2>
                                <div class="entry-content">
                                    <p><?php echo ($vo['content']); ?></p>
                                </div>
                            </div>
                            <p style="color: grey">Release Time: <?php echo (date("Y-m-d",$vo["add_time"])); ?></p>
                        </article>
                    </div><?php endforeach; endif; ?>
            </div>
        </div>
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