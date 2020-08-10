<?php
/**
 * 1.0.0版本的控制器路由類
 *
 * 劉富囯
 * 2017-11-23
 */
return array(
    //====CtoC业务模块 start ====//
    /**** 刘富国 *****/
    // c2c 交易区
    'GetC2CArea' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getArea'
    ],
    //校验用户交易区有没银行卡
    'CheckUserBindBank' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'checkUserBindBank',
    ],
   //挂单前，数据准备
    'PrepareAddMainOrder' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'prepareAddMainOrder',
    ],
    //挂单业务处理
    'SubTrade' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'subTrade',
    ],

    /**** 张锡文 *****/
    //获取我的主订单列表
    'GetUserMainOrderList' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getUserMainOrderList',
    ],
    //撤销主订单
    'RevokeBigOrder' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'revokeBigOrder',
    ],
    // 获取主订单对应的子订单
    'GetUserTradeOrderList' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getUserTradeOrderList',
    ],
    //确认打款/收款
    'ConfirmOrderAcceptOrPaid' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'confirmOrderAcceptOrPaid',
    ],
    // 用户确认未收到款项
    'UnReceiptTradeOrderPaid' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'unReceiptTradeOrderPaid',
    ],
    // 根据订单id获取订单数据（用于确认收款/确认未收到款项展示页面）
    'UserConfirmPage' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'userConfirmPage',
    ],
    // 获取历史订单
    'GetUserHistoryTradeOrderList' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getUserHistoryTradeOrderList',
    ],
    /****李江*******/
    'GetTrade' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getTrade'
    ],
    //买入或者卖出按钮接口
    'BuyingOrSellingOrder' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'buyingOrSellingOrder',
    ],

    //====end CtoC业务模块====//


    //===== 币币交易 start ====//

     //获取交易区的委托类型
    'GetUserCurrency' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getUserCurrency'
    ],
    //获取交易区的委托类型
    'GetTradeAreaForEntrustTypeList' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getTradeAreaForEntrustTypeList'
    ],
    //获取币币交易交易区
    'GetTradeAreaList' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getTradeAreaList'
    ],
    //获取币币交易订单列表
    'GetMarketList' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getMarketList'
    ],
    //获取购买数量
    'GetBuyNumByPriceAndNum' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getBuyNumByPriceAndNum'
    ],
    //撤销订单
    'RevokeOrderCurrencyTrading' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'revokeOrder'
    ],
    //买单，卖单
    'ProcessTradeInfo' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'processTradeInfo'
    ],
    //订单查询
    'GetCurrencyTradingOrderList' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getOrderList'
    ],

    //获取系统基本类别：价格类型，交易类型
    'GetCurrencyTradeConfigType' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getCurrencyTradeConfigType'
    ],
    //获取交易区类型和交易区的货币类型信息和行情
    'GetTradeArea' => [
        'Server' => 'V100\\CurrencyTrading',
        'Method' => 'getTradeArea'
    ],


    //===== end 币币交易====//

    //===== start 登录登出 刘富国====//
    //PC端二维码登录
    'QRLogin' => [
        'Server' => 'V100\\Sign',
        'Method' => 'QRLogin'
    ],

    //校验登录验证码
    'CheckLoginSmsCode' => [
        'Server' => 'V100\\Sign',
        'Method' => 'checkLoginSmsCode'
    ],

    //语言版本设置
    'SetLang' => [
        'Server' => 'V100\\Sign',
        'Method' => 'setLang'
    ],

    // 发送验证码登录
    'LoginSendCode' => [
        'Server' => 'V100\\Sign',
        'Method' => 'loginSendCode'
    ],
    // 登录
    'SignIn' => [
        'Server' => 'V100\\Sign',
        'Method' => 'signIn'
    ],
    // 退出
    'LoginOut' => [
        'Server' => 'V100\\Sign',
        'Method' => 'loginOut'
    ],
    // 忘记密码
    'ForgetPwd' => [
        'Server' => 'V100\\Sign',
        'Method' => 'forgetPwd'
    ],

    //===== end 登录登出 刘富国====//

	//个人中心 - 信箱
    'GetMessage' => [
        'Server' => 'V100\\Push',
        'Method' => 'getPushMsgList'
    ],
    //个人中心 - 信箱详情
    'GetMessageDetail' => [
        'Server' => 'V100\\Push',
        'Method' => 'getPushMsgView'
    ],
    //个人中心 - 删除信箱
    'DelPushMsg' => [
        'Server' => 'V100\\Push',
        'Method' => 'delPushMsg'
    ],
    //个人中心 - 删除全部信箱
    'DelAllPushMsg' => [
        'Server' => 'V100\\Push',
        'Method' => 'delAllPushMsg'
    ],
    // 获取财务日志
    'GetUserFinance' => [
        'Server' => 'V100\\Person',
        'Method' => 'getUserFinance'
    ],
    // 获取财务类型
    'GetUserFinanceType' => [
        'Server' => 'V100\\Person',
        'Method' => 'getUserFinanceType'
    ],
    // 获取用户银行卡
    'GetUserBank' => [
        'Server' => 'V100\\Person',
        'Method' => 'getUserBank'
    ],
    // 设为默认银行卡
    'SetDefaultCard' => [
        'Server' => 'V100\\Person',
        'Method' => 'setDefaultCard'
    ],
    // 获取国家下拉
    'GetCountryDropDown' => [
        'Server' => 'V100\\Person',
        'Method' => 'getCountryDropDown'
    ],

    //p2p 未收到款项
    'UnGetMoney' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'unGetMoney'
    ],

    // P2P获取地区列表
    'GetArea' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'getArea'
    ],

    // 根据地区获取银行相关信息
    'GetBankInfoByArea' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'getBankInfoByArea'
    ],

    //获取待买入的订单列表
    'GetPendingPurchaseOrderList' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'getPendingPurchaseOrderList'
    ],

    //获取用户绑定的银卡卡信息
    'GetUserBindBankAndCurrencyInfo' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'getUserBindBankAndCurrencyInfo'
    ],

    //线下卖单
    'Selling' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'selling'
    ],
    //卖家撤销订单
    'RevokeOrder' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'revokeOrder'
    ],
    //购买订单
    'Buying' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'buying'
    ],
    //确认订单
    'ConfirmOrderPaidOrOrderAccept' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'confirmOrderPaidOrOrderAccept'
    ],
    //查询订单
    'GetOrderList' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'getOrderList'
    ],
    //获取卖家的银行信息
    'GetSellerBankInfo' => [
        'Server' => 'V100\\OffTrading',
        'Method' => 'getSellerBankInfo'
    ],

    /*
     * 李江
     */
    //提币业务接口
    'TiBi'            => [
        'Server' => 'V100\\Wallent',
        'Method' => 'tiBi'
    ],
    //首页数据
    'Index'            => [
        'Server' => 'V100\\Test',
        'Method' => 'index',
    ],
    //各个币种余额查询接口
    'BalanceSearch'    => [
        'Server' => 'V100\\Wallent',
        'Method' => 'balanceSearch',
    ],
    //查看充币记录
    'ShowChongBi'          => [
        'Server' => 'V100\\Wallent',
        'Method' => 'showChongBi',
    ],
    //查看提币记录
    'ShowTiBi'          => [
        'Server' => 'V100\\Wallent',
        'Method' => 'showTiBi',
    ],
    //绑定地址
    'BindAddress'      => [
        'Server' => 'V100\\Wallent',
        'Method' => 'bindAddress',
    ],
    //删除地址
    'DelAddress'       => [
        'Server' => 'V100\\Wallent',
        'Method' => 'delAddress',
    ],
    //获取系统所有币种信息
    'GetAllCurrency'   => [
        'Server' => 'V100\\Wallent',
        'Method' => 'getAllCurrency',
    ],
    //发送短信验证码
    'SendPhoneCode'   => [
        'Server' => 'V100\\Wallent',
        'Method' => 'sendPhoneCode',
    ],
    //首页币价信息获取
    'GetCoinInfoList' => [
        'Server' => 'V100\\Index',
        'Method' => 'getCoinInfoList',
    ],
    //只是获取充币地址接口  没有绑定则返回提示
    'GetChongBiAddress' => [
        'Server' => 'V100\\Wallent',
        'Method' => 'getChongBiAddress',
    ],
    //绑定充币地址 没有则进行绑定
    'BindChongBiAddress'=> [
        'Server' => 'V100\\Wallent',
        'Method' => 'bindChongBiAddress',
    ],
    //更新充币地址
    'ChangeChongBiAddress' => [
        'Server' => 'V100\\Wallent',
        'Method' => 'changeChongBiAddress',
    ],
    //根据currency_id获取币种地址和相应手续费==
    'GetCurrencyAddrInfo' => [
        'Server' => 'V100\\Wallent',
        'Method' => 'getCurrencyAddrInfo',
    ],
    //购买页面 获取小订单相关信息
    'GetSubOrderInfo' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getSubOrderInfo'
    ],
    //获取未完成订单
    'GetHangIntheAirTradeOrder' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getHangIntheAirTradeOrder'
    ],
    //获取已完成订单
    'GetCompletedTradeOrder' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getCompletedTradeOrder'
    ],
    //获取已撤销订单
    'GetRevokeTradeOrder' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getRevokeTradeOrder'
    ],
    //获取用户验证信息
    'GetUserCheckInfo' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getUserCheckInfo'
    ],
    //提交充币记录
    'SubChargeCoin' => [
        'Server' => 'V100\\Wallent',
        'Method' => 'subChargeCoin',
    ],
    //app设置主订单是否显示
    'SetOrderDisplay' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'setOrderDisplay',
    ],
    'GetOrderDisplay' => [
        'Server' => 'V100\\CtoCTrading',
        'Method' => 'getOrderDisplay',
    ],
	// 检测是否通过实名认证
    'CheckIsUserReal' => [
        'Server' => 'V100\\Person',
        'Method' => 'checkIsUserReal'
    ],
    'GetAllCurrencyHasBalance' => [
        'Server' => 'V100\\Wallent',
        'Method' => 'getAllCurrencyHasBalance',
    ],

    //获取交易模块是否暂停记录
    'GetTradeMaintainInfo' => [
        'Server' => 'V100\\Index',
        'Method' => 'getTradeMaintainInfo',
    ],
     //首页显示最新消息公告
    'Notice' => [
        'Server' => 'V100\\Index',
        'Method' => 'notice',
    ],
);