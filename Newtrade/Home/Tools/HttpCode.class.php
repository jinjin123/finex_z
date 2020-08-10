<?php
namespace Home\Tools;
/**前台常量类-http状态码
 * @author 宋建强 2017年9月26日 14:54
 * 
 */
class HttpCode
{
	//200为成功 
    const HTTP_CODE_SUCCESS=200;
    
    //用户名错误
    const HTTP_CODE_USERNAME=201;
    //密码错误
    const HTTP_CODE_PASS=202;
    //交易密码错误
    const HTTP_CODE_TRADEPASS=203;
    //图片验证码错误
    const HTTP_CODE_IMG=204;
    //短信验证码错误
    const HTTP_CODE_SMS=205;
    
}
