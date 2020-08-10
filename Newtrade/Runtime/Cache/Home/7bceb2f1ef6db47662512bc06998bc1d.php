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
    <div class="page-title-area bg-primary"
         style="background-image: url('/Public/Home/fe/static/images/shape-dot1.png')">
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
                            <h2 class="page-title">My Account</h2>
                        </div>
                        <div class="breadcrumb-area">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                                <li class="breadcrumb-item active">Sign In</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-signup-block ptb-120">
        <div class="bg-left bg-image"
             style="background-image: url('/Public/Home/fe/static/images/signup-shape.png')"></div>
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="sing-up-mockup">
                        <img src="/Public/Home/fe/static/picture/signup-mockup.png" alt="Mockup">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="user-register-area">
                        <div class="form-content">
                            <div class="form-header">
                                <h4 class="form-subheading">Sign Up here</h4>
                                <h2 class="heading">Welcome To SpaceFinEX</h2>
                            </div>
                            <form class="default-form signup-form" method="post" action="">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="Email">Email Address</label>
                                            <input id="Email" name="email" class="form-controller" type="email"
                                                   placeholder="Your Email Address">
                                        </div>
                                    </div>
<!--                                    <div class="col-lg-6">-->
<!--                                        <div class="form-group">-->
<!--                                            <label for="InviteCode">Invitation Code-->
<!--                                            </label>-->
<!--                                            <input id="InviteCode" name="code" class="form-controller"-->
<!--                                                   type="text" value="<?php echo ($_GET['code']); ?>">-->
<!--                                        </div>-->
<!--                                    </div>-->
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="f_name">　</label>
                                            <button id="sendCode" type="button"
                                                    class="btn btn-block btn-default btn-primary"
                                                    style="padding:14px 30px 10px;">Get Email
                                                Code
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="EmailCode">Email Code</label>
                                            <input id="EmailCode" name="email_code" class="form-controller"
                                                   type="text">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="PassWord">Password</label>
                                            <input id="PassWord" name="password" class="form-controller"
                                                   type="password">
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label for="RePassWord"> Confirm Password</label>
                                            <input id="RePassWord" name="repassword" class="form-controller"
                                                   type="password">
                                        </div>
                                    </div>
                                </div>
                                <div class="login-form-remember">
                                    <label>
                                        <input name="agree" type="checkbox" checked="false">
                                        <span>
                                                I agree to the 
                                                <a href="/index/terms.html">Terms, Privacy policy and fees</a>
                                            </span>
                                    </label>
                                </div>
                                <div class="login-form-remember">
                                    <label>
                                        <input name="receive" type="checkbox" checked="false">
                                        <span>Yes, I want to receive Email</span>
                                    </label>
                                </div>
                                <div class="form-btn-group">
                                    <div class="form-login-area">
                                        <button type="submit" class="btn btn-default btn-primary">Sign Up</button>
                                    </div>
                                    <div class="login-form-register-now">
                                        You have an account.
                                        <a class="btn-register-now" href="/login/showLogin">Sign In</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        toastr.options = {
            "debug": false,
            "progressBar": true,
            "positionClass": "toast-bottom-full-width",
            "onclick": null,
            "fadeIn": 300,
            "fadeOut": 1000,
            "timeOut": 5000,
            "extendedTimeOut": 1000
        };

        $(function () {

            //发送验证码
            $('#sendCode').click(function () {
                var email = $('input[name="email"]').val()
                if (email == '') {
                    layer.msg('email is empty');
                    return;
                }
                layer.msg('Sending...', {time: 2000});
                $.ajax({
                    url: 'sendEmail.html',
                    data: {
                        email: email
                    },
                    type: 'POST',
                    dateJson: 'JSON',
                    success: function (resp) {
                        layer.msg(resp.msg);
                    }
                });
            })

            //提交
            $('button[type="submit"]').click(function () {
                var email = $('input[name="email"]').val();
                var code = $('input[name="code"]').val();
                var emailCode = $('input[name="email_code"]').val();
                var password = $('input[name="password"]').val();
                var repassword = $('input[name="repassword"]').val();
                var agree = $('input[name="agree"]').prop('checked');
                var receive = $('input[name="receive"]').prop('checked');

                if (email == '') {
                    layer.msg('email is empty');
                    return false;
                }
                if (code == '') {
                    layer.msg('invite code is empty');
                    return false;
                }
                if (emailCode == '') {
                    layer.msg('email verify code is empty');
                    return false;
                }
                if (password == '') {
                    layer.msg('password is empty');
                    return false;
                }
                if (repassword == '') {
                    layer.msg('comfirm password is empty');
                    return false;
                }
                if (password != repassword) {
                    layer.msg('password is invalid');
                    return false;
                }
                if (!agree || !receive) {
                    layer.msg('please press agree');
                    return false;
                }

                layer.closeAll();
                layer.load(2, {
                    shade: [0.3,'#666']
                });

                $.ajax({
                    url: 'subRegister',
                    data: {
                        email: email,
                        code: code,
                        email_code: emailCode,
                        password: password
                    },
                    type: 'POST',
                    dataType: 'JSON',
                    success: function (resp) {
                        layer.closeAll();
                        if (resp.code == 200) {
                            location.href = '/login/showLogin';
                        }
                        layer.msg(resp.msg);
                    }
                });

                return false;

            });
        })

        // function sendCode() {
        //     $.getJSON('', function (Json) {
        //         if (Json.code == 200) {
        //             toastr.success(Json.Msg);
        //         } else {
        //             toastr.error(Json.Msg);
        //         }
        //     })
        // }
    </script>

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