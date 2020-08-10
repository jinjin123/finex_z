<?php
/**
 * 积分加减比例
 * @author  宋建强   2017年11月1日 17:00
 */
namespace Common\Api;

class redisKeyNameLibrary{
	// PC端线下交易模块redis的key
	CONST OFF_LINE_DAKUANG_ORDER   = 'OFF_LINE_DAKUANG_ORDER';// 用户打款/确认收款防止重复点击下的key
    CONST OFF_LINE_ACCEPT_ORDER   = 'OFF_LINE_ACCEPT_ORDER';// 用户确认收款防止重复点击下的key
	CONST OFF_LINE_IS_REVOKE_ORDER = 'OFF_LINE_IS_REVOKE_ORDER';// 撤销订单防止重复点击下的key
	CONST OFF_LINE_SELL_ORDER      = 'OFF_LINE_SELL_ORDER';    // 线下交易买入及撤销并发的key
    CONST PC_OFF_LINE_TRADE      = 'PC_OFF_LINE_TRADE'; //pc卖出 防止用户多次提交
	// pc端币币交易模块redis的key
	CONST CURRENCY_TRADE_IS_BUY_ORDER = 'CURRENCY_TRADE_IS_BUY_ORDER';    // 币币交易买入及撤销并发的key
	// PC端线下交易币种信息获取
	CONST COIN_INFO_LIST_BY_BIF      = 'COIN_INFO_LIST_BY_BIF';
	CONST COIN_INFO_LIST_LONG_BY_BIF = 'COIN_INFO_LIST_LONG_BY_BIF';
    CONST COIN_INFO_FROM_OTHER = 'COIN_INFO_FROM_OTHER';
   //App端线下交易币种信息获取
    CONST APP_COIN_INFO_LIST_BY_BIF      = 'APP_COIN_INFO_LIST_BY_BIF';
	// PC端币币交易币种信息获取
	CONST CURTENCY_INFO_LIST_ALL           = 'CURTENCY_INFO_LIST_ALL';
    CONST CURTENCY_SEC_MARKET_INFO_LIST          = 'CURTENCY_SEC_MARKET_INFO_LIST';
	CONST CURTENCY_INFO_LIST_BY_OKEX       = 'CURTENCY_INFO_LIST_BY_OKEX';
    CONST CURTENCY_INFO_LIST_LOING_BY_OKEX_TO24 = 'CURTENCY_INFO_LIST_LOING_BY_OKEX_TO24';
	CONST CURTENCY_INFO_LIST_LOING_BY_OKEX = 'CURTENCY_INFO_LIST_LOING_BY_OKEX';
    CONST CURTENCY_ORDER_TO_ORDER_TRADE = 'CURTENCY_ORDER_TO_ORDER_TRADE';//pc挂单 防止用户多次提交
    CONST CURTENCY_REVOKE_ORDER = 'CURTENCY_REVOKE_ORDER';//pc撤单 防止用户多次提交
    CONST CURTENCY_ORDER_INFO_BY_TRADE_AREA_AND_TRADE_TYPE = 'CURTENCY_ORDER_INFO_BY_TRADE_AREA_AND_TRADE_TYPE';// 币币交易匹配订单有序集合获取
    CONST CURTENCY_ORDER_TO_ORDER_FOR_ORDER_INFO_BY_ORDER = 'CURTENCY_ORDER_TO_ORDER_FOR_ORDER_INFO_BY_ORDER';// pc挂单，订单信息
    CONST CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH = 'CURTENCY_ORDER_TO_ORDER_TRADE_INFO_HASH';//pc挂单成功 订单信息
    CONST CURTENCY_ORDER_TO_ORDER_TRADE_INFO_ORDER_ID = 'CURTENCY_ORDER_TO_ORDER_TRADE_INFO_ORDER_ID';//pc挂单成功 订单id

    // PC端C-TO-C交易模块redis的key
    CONST CC_LINE_DAKUANG_ORDER   = 'CC_LINE_DAKUANG_ORDER';// 用户打款/确认收款防止重复点击下的key
    CONST CC_LINE_ACCEPT_ORDER   = 'CC_LINE_ACCEPT_ORDER';// 用户确认收款防止重复点击下的key
    CONST CC_LINE_IS_REVOKE_ORDER = 'CC_LINE_IS_REVOKE_ORDER';// 撤销订单防止重复点击下的key 
    //PC端C2C交易生成主订单key值   
    CONST PC_C2C_GENERATE_MAIN_ORDER = 'PC_C2C_GENERATE_MAIN_ORDER';
    CONST PC_C2C_MIAN_ORDER_ID_INFO='PC_C2C_MIAN_ORDER_ID_INFO';
    CONST PC_C2C_ACCEPT_ORDER = 'PC_C2C_ACCEPT_ORDER';
    CONST PC_C2C_CONFIRM_ORDER = 'PC_C2C_CONFIRM_ORDER';

    CONST PC_C2C_TRADE_BUYANDSELL         = 'PC_C2C_TRADE_BUYANDSELL';          //pc挂单 防止用户多次提交
    CONST PC_C2C_TRADE_BUY_SELL         = 'PC_C2C_TRADE_BUY_SELL';          //pc买入卖出 防止用户多次提交
    CONST PC_C2C_TRADE_BUY_SELL_REVOKED = 'PC_C2C_TRADE_BUY_SELL_REVOKED';  //pc买入卖出 防止撤销
    CONST PC_C2C_TRADE_REVOKED_CANNOT_OPERAT = 'PC_C2C_TRADE_REVOKED_CANNOT_OPERAT';  //pc撤销  防止买卖

    CONST GETCURRENCYLISTCONGFIG = 'GETCURRENCYLISTCONGFIG';// 获取币种配置
    
   CONST PC_LOGIN_PASS_MISS_NUM = 'PC_LOGIN_PASS_MISS_NUM';  //登陆 密码错误次数
   CONST PC_LOGIN_TOKEN_MISS_NUM = 'PC_LOGIN_TOKEN_MISS_NUM';  //登陆 动态密令错误次数
    
    CONST CC_SCALPING_ORDER_LIST = 'CC_SCALPING_ORDER_LIST';  //刷单订单数据
    CONST CC_SCALPING_ORDER_INFO = 'CC_SCALPING_ORDER_INFO';  //刷单订单数据
 
    CONST PC_C2C_PUSH_MSG_TO_BUYER = 'PC_C2C_PUSH_MSG_TO_BUYER';  //c2c买卖订单 通知买家付款推送消息

    CONST PC_FEEDBACK = 'PC_FEEDBACK';  //防止问题反馈提交重复
    CONST PC_FEEDBACK_PROBLEM_LIST = 'PC_FEEDBACK_PROBLEM_LIST';  //问题反馈标题列表缓存key
    CONST PC_FEEDBACK_PROBLEM_LIST_LANGUAGE = 'PC_FEEDBACK_PROBLEM_LIST_LANGUAGE';  //问题反馈标题多语言列表缓存key

    CONST TRADEMAINTAINTYPEVALS = 'TRADEMAINTAINTYPEVALS';     //获取网站交易模式维护状态
    CONST CURRENCYMAINTAINTYPEVALS ='CURRENCYMAINTAINTYPEVALS';//网站币种维护状态
  
    //C2C交易  2019年3月25日 (建强 )
    const PC_C2C_BUY_SELL_NUM     = 'PC_C2C_BUY_SELL_NUM';      //订单累计购买总量
    const PC_C2C_MAIN_ORDER_NUM   = 'PC_C2C_MAIN_ORDER_NUM';   //订单发布总量 
    
}