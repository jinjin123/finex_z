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


<style>

</style>

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
    <div class="page-title-area bg-black"
         style="background-image: url('/Public/Home/fe/static/images/shape-dot1.png');padding-top:220px;padding-bottom:50px;">
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
                            <h2 class="page-title"><?php echo ($name); ?></h2>
                        </div>
                        <div class="breadcrumb-area">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if($name == 'FEC'): ?><div class="container pd-t-50 text-white">
                <p>Financial Ecosystem Chain (FEC) is a decentralized protocol which establishes money markets with algorithmically
                set interest rates based on supply and demand, allowing users to frictionlessly exchange the time value of Ethereum assets.
                Different with current DeFi projects, Financial Ecosystem Chain is backed by billions of real economy,
                where the decentralized stable coin will be used as the only payment method in the ecosystem.
                Users can enjoy "Yield farming" on FEC DeFi platform with up to 50% APY return as your liquidity incentive,
                and governance token FEC will be equipped with an absolute deflation mechanism to facilitate the sustainable development of the token price.</p>

<!--                <p>Petro Coin is a service platform which helps to quickly create, manage and maintain enterprise-level-->
<!--                    networks and commercial blockchain applications. It has features such as low development costs, fast-->
<!--                    deployment, high performance and strong scalability, security and reliability. Thus, Petro Coin-->
<!--                    system is a one-stop solution for developers or business with blockchain capabilities.</p>-->
<!--                <p>Petro Coin system’s original main-sidechain model and core system integrate and abstract the-->
<!--                    underlying blockchain network, consensus, application development capabilities, and blockchian-->
<!--                    supporting facilities into user-acceptable programmable interfaces and operating interfaces,-->
<!--                    shielding them. The underlying technical details make application development simpler and more-->
<!--                    efficient, and allow enterprises and developers to focus more on the development of the-->
<!--                    applications.</p>-->
<!--                <p>Petro Coin (PETC) is the only circulating asset in the ecosystem, with a total circulation of 100-->
<!--                    million, reduces production once 2116800 blocks.</p>-->
                <p>Total supply: 200,000,000 FEC</p>
                <p>Total Subscription: 60,000,000 FEC</p>

                <div class="all_progress">
                    <div class="progress" style="height: 16px;">
                        <!--<div class="progress-bar" role="progressbar" aria-valuenow="80" aria-valuemin="0"
                             aria-valuemax="100" style="width: <?php echo ($issue["progress"]); ?>%;">
                            <?php echo ($issue["issue_num"]); ?>
                        </div>-->
                        <div class="progress-bar" role="progressbar" aria-valuenow="80" aria-valuemin="0"
                             aria-valuemax="100" style="width: <?php echo ($issue["progress"]); ?>%;">
                            <?php echo ($issue["issue_num"]); ?>
                        </div>
                    </div>
                </div>

                <p>Open Price: 0.2 USD</p>

                <p>Subscription Time: <span id="expire_day"></span> days</p>

                <div class="time">
                    <div class="time_item">
                        <div>
                            <p class="time_item_day t_active day ">89</p>
                            <p class="time_item_day time_text">day</p>
                        </div>
                    </div>
                    <div class="time_item">
                        <div>
                            <p class="time_item_hour t_active hour">23</p>
                            <p class="time_item_hour time_text">hour</p>
                        </div>
                    </div>
                    <div class="time_item">
                        <div>
                            <p class="time_item_branch t_active branch ">60</p>
                            <p class="time_item_branch time_text">minute</p>
                        </div>
                    </div>
                    <div class="time_item">
                        <div>
                            <p class="time_item_second t_active second">60</p>
                            <p class="time_item_second time_text">second</p>
                        </div>
                    </div>
                </div>
                <script>
                    //    setInterval(()=>{
                    //        let second = $('.second').text();
                    //        console.log(second)
                    //    },1000)
                </script>
                <style>

                    .all_progress {
                        margin: 30px auto;
                        text-align: left;
                    }

                    .all_progress .progress {
                        width: 80%;
                    }

                    .progress {
                        border: 1px solid white;
                        background: black;
                    }

                    .progress-bar {
                        background: rgb(200, 130, 81);
                    }

                    .time_item {
                        width: 145px;
                        height: 115px;
                        margin-right: 15px;
                        background: url('/Public/Home/fe/static/images/area.png') no-repeat;
                        background-size: 100% 100%;
                        text-align: center;
                        box-sizing: border-box;
                        padding: 4px;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                    }

                    .time {
                        display: flex;
                        flex-direction: row;
                        align-items: center;
                    }

                    .time_item div p {
                        line-height: 10px;
                    }

                    .t_active {
                        font-size: 40px;
                        font-weight: 600;
                        margin-bottom: 20px;
                    }

                    .time_item_day {
                        color: #7D2406;
                    }

                    .time_item_hour {
                        color: #CBA076;
                    }

                    .time_item_branch {
                        color: #396301;
                    }

                    .time_item_second {
                        color: #D4F2C0;
                    }

                    .time_text {
                        font-size: 12px;
                    }

                    @media screen and (max-width: 500px) {
                        .time {
                            display: flex;
                            flex-direction: row;
                            align-items: center;
                            flex-wrap: wrap;
                        }
                    }
                </style>

                <div class="row" style="z-index:9">
                    <div class="col-12 text-right pd-b-10">
                        <a href="/Public/Home/fe/static/file/whitebook.pdf" target="_blank" class="btn btn-default btn-primary">
                            <i class="fa fa-fw fa-book"></i> White book
                        </a>
                    </div>
                </div>
            </div><?php endif; ?>

        <div class="container pd-t-50">
            <div class="row">
                <div class="col-6 col-lg-2">Now :
                    <h4 class="price_Close text-white">
                        <?php if($chain["rate"] >= 0): ?><span style="color: #28a745;">
                        <?php else: ?>
                            <span style="color: #dc3545;"><?php endif; ?>
                        <?php echo ($chain["last"]); ?></span>
                    </h4>
                </div>
                <div class="col-6 col-lg-2">change :
                    <h4 class="price_Change">
                        <?php if($chain["rate"] >= 0): ?><span style="color: #28a745;">
                        <?php else: ?>
                            <span style="color: #dc3545;"><?php endif; ?>
                        <?php echo ($chain["rate"]); ?>%</span>
                    </h4>
                </div>
				
				
                <div class="col-6 col-lg-2">open : <h4 class="price_Open text-white"><?php echo ($chain["open_24h"]); ?></h4></div>
                <div class="col-6 col-lg-2">high : <h4 class="price_High text-white"><?php echo ($chain["high_24h"]); ?></h4></div>
                <div class="col-6 col-lg-2">low : <h4 class="price_Low text-white"><?php echo ($chain["low_24h"]); ?></h4></div>
                <div class="col-6 col-lg-2">
                    <div class="form-group faqs-form-area">
                        <select id="lineType" class="form-controller">
                            <option value="1min">1min</option>
                            <option value="5min">5min</option>
                            <option value="15min">15min</option>
                            <option value="30min">30min</option>
                            <option value="60min">1hour</option>
                            <option value="4hour">4hour</option>
                            <option value="1day">1day</option>
                            <option value="1week">1week</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12" id="chainChart" style="height:600px;"></div>
            </div>
            <div class="row">
                <div class="col-12"></div>
            </div>
        </div>
    </div>

    <?php if($name == 'FEC' && $is_expire): ?><!--<div class="all_progress">
            <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"
                     style="width: <?php echo ($issue["progress"]); ?>%;">
                    <?php echo ($issue["issue_num"]); ?>
                </div>
            </div>
            <div class="progress_text">
                <text class="time">1:00</text>
                <text>
                    第<?php echo ($issue["q_num"]); ?>轮
                </text>
            </div>
        </div>-->

        <div class="faqs-block pd-t-50 pd-b-50">
            <div class="container">
                <form method="post" action="" id="SellChainForm">
                    <div class="row">
                        <input type="hidden" name="BuyChainID" value="20">
                        <!--<div class="col-12 col-lg-3" style="display: none;">
                            <div class="form-group">
                                <label for="InviteCode">Invite Code</label>
                                <input class="form-controller" name="invite_code" id="InviteCode" value=""
                                       placeholder="please type your invite code"/>
                            </div>
                        </div>-->
                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label for="InviteCode">InviteCode</label>
                                <input type="text" class="form-controller" id="InviteCode" name="invite_code" value="" placeholder="please type your invite code">
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label for="SellChainID">Chain</label>
                                <select name="chain" id="SellChainID" class="form-controller">
                                    <option value="3" data-chain-name="USDT">USDT</option>
                                    <option value="1" data-chain-name="BTC">BTC</option>
                                    <option value="2" data-chain-name="ETH">ETH</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label for="SellChainQuantity">Quantity</label>
                                <input type="number" step="0.00000001" class="form-controller" name="number"
                                       id="SellChainQuantity" value="0.00000000" placeholder="chain quantity">
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label for="canBuyChainQuantityInput">≈FEC</label>
                                <input type="text" name="rate_price" class="form-controller canBuyChainQuantityInput"
                                       readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label> </label>
                                <button type="submit" class="btn btn-block btn-primary"><i class="fa fa-check"></i>
                                    Buy
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div><?php endif; ?>

    <?php if($name != 'FEC'): ?><div class="contact-form-block pd-b-120 pd-t-120">
            <div class="container">
                <div class="row align-content-center">
                    <div class="col-lg-4">
                        <table class="table trades">
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="col-lg-4">
                        <div class="contact-form-area">
                            <h2 class="section-heading" data-animate="hg-fadeInUp">Quick Buy</h2>
                            <form class="contact-form" data-animate="hg-fadeInUp" method="post" action=""
                                  id="BuyChainForm">
                                <input type="hidden" name="ChainID" value="18">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="name" id="usdt_currency">you have <?php echo ($usdt_currency); ?> USDT</label>
                                            <input name="buy_chain" type="number"
                                                   class="form-controller SellChainQuantity" value=""
                                                   placeholder="USDT quantity you will trade">
                                        </div>
                                    </div><!--./ col-12-->
                                    <div class="col-12">
                                        <input name="bc" type="hidden" value="">
                                        <input name="buy_chain_quantity" type="text" placeholder="≈ 0.00000000 <?php echo ($name); ?>"
                                               readonly class="BuyChainQuantity">
                                    </div><!--./ col-12 -->
                                    <div class="col-12 mrt-15">
                                        <button type="button" id="buy_button" class="btn btn-block btn-lg btn-success">
                                            Buy
                                        </button>
                                    </div><!--./ col-lg-6 -->
                                </div><!-- /.row -->
                            </form><!-- /.contact-form -->
                        </div>
                    </div><!-- /.col-lg-6 -->
                    <div class="col-lg-4">
                        <div class="contact-form-area">
                            <h2 class="section-heading" data-animate="hg-fadeInUp">Quick Sell</h2>
                            <form class="contact-form" data-animate="hg-fadeInUp" method="post" action=""
                                  id="SellChainForm">
                                <input type="hidden" name="ChainID" value="18">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="name" id="coin_currency">you have <?php echo ($coin_currency); ?>
                                                <?php echo ($name); ?></label>
                                            <input name="sell_chain" type="number"
                                                   class="form-controller SellChainQuantity" value=""
                                                   placeholder="<?php echo ($name); ?> quantity you will trade">
                                        </div>
                                    </div><!--./ col-12-->
                                    <div class="col-12">
                                        <input name="sc" type="hidden" value="">
                                        <input type="text" name="sell_chain_quantity" placeholder="≈ 0.00000000 USDT"
                                               readonly class="BuyChainQuantity">
                                    </div>
                                    <div class="col-12 mrt-15">
                                        <button type="button" id="sell_button" class="btn btn-block btn-lg btn-danger">
                                            Sell
                                        </button>
                                    </div><!--./ col-lg-6 -->
                                </div><!-- /.row -->
                            </form><!-- /.contact-form -->
                        </div><!-- /.contact-form-area -->
                    </div>
                    <!-- /.col-lg-6 -->
                </div><!-- /.row -->
            </div><!-- /.container -->
        </div><?php endif; ?>

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

<script src="/Public/Home/fe/static/js/echarts.min.js"></script>
<script src="/Public/Home/fe/static/js/kline.js"></script>
<script>

    var rate = JSON.parse('<?php echo ($rate); ?>');
	console.log(rate)


    buy();

    function buy() {
        $('button[type="submit"]').click(function () {

            var inviteCode = $('input[name="invite_code"]').val();
            var chain = $('select[name="chain"]').children('option:checked').val();
            var number = $('input[name="number"]').val();
            var qnum = '<?php echo ($issue["q_num"]); ?>';

            if (number <= 0) {
                layer.msg('quantity is invalid');
                return false;
            }

            layer.closeAll();
            layer.load(2, {
                shade: [0.3, '#666']
            });

            $.ajax({
                url: 'buy',
                data: {
                    invite_code: inviteCode,
                    chain: chain,
                    number: number,
                    qnum: qnum
                },
                dataType: 'JSON',
                type: 'POST',
                success: function (resp) {
                    layer.closeAll();
                    layer.msg(resp.msg);
                    if (resp.status) location.reload();
                }
            });

            return false;
        });
    }

    blurBuySell();

    function blurBuySell() {
        $('input[name="buy_chain"]').keyup(function () {
            var rate = "<?php echo ($chain["last"]); ?>";
			//console.log();
            var value = $(this).val();
            var quantity = (1 / rate * value).toFixed(8);
            var text = '≈ ' + quantity + ' <?php echo ($name); ?>';
            $('input[name="bc"]').val(quantity);
            $('input[name="buy_chain_quantity"]').prop({
                'placeholder': text
            });
        });
        $('input[name="sell_chain"]').keyup(function () {
            var rate = "<?php echo ($chain["last"]); ?>";
            var value = $(this).val();
            var quantity = (rate * value).toFixed(8);
            var text = '≈ ' + quantity + ' USDT';
            $('input[name="sc"]').val(quantity);
            $('input[name="sell_chain_quantity"]').prop({
                'placeholder': text
            });
        });
    }

    //提交委托单
    submitChain();

    function submitChain() {
        var submit = function (number, type, price, callback) {
            $.ajax({
                url: '/index/trade',
                data: {
                    name: '<?php echo ($name); ?>',
                    number: number,
                    type: type,
                    price: price
                },
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function () {
                    layer.closeAll();
                    layer.load(2, {
                        shade: [0.3, '#666']
                    });
                },
                success: callback
            });
        };
        $('#buy_button').click(function () {
            var number = $('input[name="buy_chain"]').val();
            var price = $('input[name="bc"]').val();
            if (number <= 0) return layer.msg('Quantity is empty');
            submit(number, 'buy', price, function (resp) {
                layer.closeAll();
                layer.msg(resp.msg);
                if(resp.status) getUserCurrency();
            });
        });
        $('#sell_button').click(function () {
            var number = $('input[name="sell_chain"]').val();
            var price = $('input[name="sc"]').val();
            if (number <= 0) return layer.msg('Quantity is empty');
            submit(number, 'sell', price, function (resp) {
                layer.closeAll();
                layer.msg(resp.msg);
                if(resp.status) getUserCurrency();
            });
        });

    }

    function getUserCurrency() {
        var name = '<?php echo ($name); ?>';
        $.ajax({
            url: '/Index/getUserCurrency',
            data: {
                name: name
            },
            dataType: 'JSON',
            type: 'GET',
            beforeSend: function () {
                $('#usdt_currency').text('Getting Currency...');
                $('#coin_currency').text('Getting Currency...');
            },
            success: function (resp) {
                setTimeout(function () {
                    $('#usdt_currency').text('you have ' + resp[0] + ' USDT');
                    $('#coin_currency').text('you have ' + resp[1] + ' ' + name);
                }, 800);
            }
        });
    }

    //自动计算价格
    $('select[name="chain"]').change(price);
    $('input[name="number"]').keyup(price);

    function price() {
        var number = $('input[name="number"]').val();
        var chain = $('select[name="chain"]').children('option:checked').val();

        var value = '';
        switch (chain) {
            case '1':
                value = 'btc';
                break;
            case '2':
                value = 'eth';
                break;
            case '3':
                value = 'usdt';
                break;
        }

        $('input[name="rate_price"]').val((number * rate[value]).toFixed(2));
    }

    let kChart = echarts.init(document.getElementById('chainChart'));

    setTimeout(getKLine, 2000);
    // (function(){
    //     var interval = setInterval(getKLine, 2*1000);
    // })();

    change();

    function change() {
        $('#lineType').change(function () {
            getKLine();
        });
    }

    function getKLine() {
        var lineType = $('#lineType').children('option:checked').val();
        if (lineType == '' || lineType == undefined || lineType == 'undefined') {
            lineType = localStorage.lineType;
        }
        if (lineType == '' || lineType == undefined || lineType == 'undefined') {
            lineType = '1min';
        }
        localStorage.lineType = lineType;

        let granularitys = {
            '1min': '60',
            '5min': '300',
            '15min': '900',
            '30min': '1800',
            '60min': '3600',
            '4hour': '14400',
            '1day': '86400',
            '1week': '604800'
        };

        let name = getQueryString('name');

        if (!name) return;

        $.ajax({
            url: '/TradingView/getCandles',
            data: {
                symbol: name.toUpperCase() + '-USDT',
                granularity: granularitys[lineType]
            },
            dataType: 'JSON',
            type: 'GET',
            beforeSend: function () {
                layer.msg('Kline Loading...', {time: 0});
            },
            success: function (resp) {
                layer.closeAll();
                if (resp.data.length == 0) return layer.msg('No Kline Data');
                if (resp.code == 0 && resp.data.length > 0) {
                    let tempData = [];
                    $.each(resp.data, function (index, item) {
                        if (index < resp.data.length && index > resp.data.length - 70) {
                            // 原来：开收低高，现在：1开2高3低4收
                            tempData.push([
                                parseInt(item[0]),
                                parseFloat(item[1]),
                                parseFloat(item[4]),
                                parseFloat(item[3]),
                                parseFloat(item[2]),
                                parseFloat(item[5])
                            ])
                        }
                    });
                    kChart.setOption(initKOption(tempData));
                }
            }
        });
    }

    getTrade();

    function getTrade() {
        let name = getQueryString('name');
        if (!name) return;
        $.ajax({
            url: '/TradingView/getTrades',
            data: {
                symbol: name.toUpperCase()
            },
            dataType: 'JSON',
            type: 'GET',
            success: function (Json) {
                if (Json.hasOwnProperty('code')) return false;
                let tradeArr = [];
                $.each(Json, function (index, item) {
                    tradeArr.push(item);
                })
                showTrade(tradeArr, 0);
            }
        });
    }

    function showTrade(tradeData, beginTrade) {
        let step = beginTrade + 6;
        if (step >= tradeData.length) {
            getTrade();
        } else {
            let tempHtml = '';
            let tradeStyle = 'text-success';

            for (let i = beginTrade; i < step; i++) {
                if (tradeData[i].side == 'buy') {
                    tradeStyle = 'text-success';
                } else {
                    tradeStyle = 'text-danger';
                }
                tempHtml += '<tr class="' + tradeStyle + ' border-0">';
                tempHtml += '<td>';
                tempHtml += parseFloat(tradeData[i].price).toFixed(4);
                tempHtml += '</td>';
                tempHtml += '<td class="text-right">';
                tempHtml += parseFloat(tradeData[i].size).toFixed(8);
                tempHtml += '</td>';
                tempHtml += '</tr>';
            }
            $('.trades tbody').html(tempHtml);
            setTimeout(function () {
                showTrade(tradeData, step)
            }, 1000);
        }
    }

    function getQueryString(name) {
        var reg = new RegExp('(^|&)' + name + '=([^&]*)(&|$)', 'i');
        var r = window.location.search.substr(1).match(reg);
        if (r != null) {
            return unescape(r[2]);
        }
        return null;
    }

    countTime();

    function countTime() {
        //获取当前时间
        var date = new Date();
        var now = date.getTime();
        //设置截止时间
        var str = "<?php echo ($expire); ?>";
        var endDate = new Date(str);
        var end = endDate.getTime();

        //时间差
        var leftTime = end - now;
        //定义变量 d,h,m,s保存倒计时的时间
        var d, h, m, s;
        if (leftTime >= 0) {
            d = Math.floor(leftTime / 1000 / 60 / 60 / 24);
            h = Math.floor(leftTime / 1000 / 60 / 60 % 24);
            m = Math.floor(leftTime / 1000 / 60 % 60);
            s = Math.floor(leftTime / 1000 % 60);
        }
        $('.time_item_day.t_active, #expire_day').text(d);
        $('.time_item_hour.t_active').text(h);
        $('.time_item_branch.t_active').text(m);
        $('.time_item_second.t_active').text(s);
        // $('.time').text(d + " day " + h + " hour " + m + " minute " + s + " second");

        //递归每秒调用countTime方法，显示动态时间效果
        setTimeout(countTime, 1000);
    }
</script>

</html>