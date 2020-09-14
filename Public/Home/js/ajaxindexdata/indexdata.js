/**
 *拼接dom元素节点 无需删除
 *"ProductView_up_3JTBt"    涨幅的class 属性
 * ProductView_down_3JTBt   跌幅的class 属性 
 */

/*<div class="ProductView_product-view_1v8XP">
<div class="ProductView_details_2mwE3">
<p class="ProductView_title_3siVi">BTC/USD &nbsp;<span class="ProductView_down_3JTBt">-2.77%</span></p>
<p class="ProductView_price_1Cyoa"><span><span class="ProductView_number_233Pz">4,081.68</span> USD</span></p>
<p class="ProductView_volume_3XcuC">Volume: &nbsp;<span><span class="ProductView_number_233Pz">9,286</span> BTC</span></p>
</div>
<div class="ProductView_chart_2adJk">
<img src="__HOME_IMG__/front/svg.png" role="presentation">
</div>
</div>*/

$(function() {
    $.ajax({
        url: '/index/getMarketData',
        dataType: 'json',
        async:false,
        type: 'post',
        success: function(response) {
            if (response.code == 200) {
                MarketData(response.data);
            }
        }
    });
});

/**
 * @param data  渲染数据 输出模板
 * 
 */
function MarketData(data) {
    var html = "";
    if (!data) {
        return;
    }
    for (var i in data) {
        // 大屏不滚动
        // if ($(window).width() > 640 && i >= 7) move();

        html += '<div class="ProductView_product-view_1v8XP">';
        html += '<div class="ProductView_details_2mwE3">';
        html += '<p class="ProductView_title_3siVi">' + data[i].currencyName + '</p>';
        if (data[i].upordown == 200) {
            //+涨幅
            html += '<p class="ProductView_price_1Cyoa text-right"><span  class=" _' + i + '  ProductView_chart_2adJk "></span><span class="ProductView_number_233Pz ProductView_up_3JTBt">$' + data[i].lastPrice + '</span></p>';
            data[i].circleColor = '#37cdaf';
        } else {
            html += '<p class="ProductView_price_1Cyoa text-right"><span  class=" _' + i + ' ProductView_chart_2adJk "></span><span class="ProductView_number_233Pz ProductView_down_3JTBt">$' + data[i].lastPrice + '</span></p>';
            data[i].circleColor = '#FF4465';
        }
        html += '<p class="ProductView_volume_3XcuC">Volume: &nbsp;<span class="ProductView_number_233Pz">' + data[i].Volume+ '</span> <span>' + data[i].currencyName + '</span></p></div>';
        html += '</div>';

    }
    var dom = html;
    $('#addTen').html(dom + dom);
    for (var k in data) {
        $("._" + k).sparkline(data[k].price, { type: "line", width: "30px", height: "20", lineColor: "#fff", fillColor: "transparent", spotColor: data[k].circleColor, minSpotColor: data[k].circleColor, maxSpotColor: data[k].circleColor, });
    }
}



function Base64() {
    // private property  
    _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

    // public method for encoding  
    this.encode = function(input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;
        input = _utf8_encode(input);
        while (i < input.length) {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);
            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;
            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }
            output = output +
                _keyStr.charAt(enc1) + _keyStr.charAt(enc2) +
                _keyStr.charAt(enc3) + _keyStr.charAt(enc4);
        }
        return output;
    };

    // public method for decoding  
    this.decode = function(input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;
        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
        while (i < input.length) {
            enc1 = _keyStr.indexOf(input.charAt(i++));
            enc2 = _keyStr.indexOf(input.charAt(i++));
            enc3 = _keyStr.indexOf(input.charAt(i++));
            enc4 = _keyStr.indexOf(input.charAt(i++));
            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;
            output = output + String.fromCharCode(chr1);
            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }
        }
        output = _utf8_decode(output);
        return output;
    };

    // private method for UTF-8 encoding  
    _utf8_encode = function(string) {
        string = string.replace(/\r\n/g, "\n");
        var utftext = "";
        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);
            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }
        return utftext;
    };

    // private method for UTF-8 decoding  
    _utf8_decode = function(utftext) {
        var string = "";
        var i = 0;
        var c = 0;
        while (i < utftext.length) {
            c = utftext.charCodeAt(i);
            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            } else if ((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i + 1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            } else {
                c2 = utftext.charCodeAt(i + 1);
                c3 = utftext.charCodeAt(i + 2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }
        }
        return string;
    };
}
