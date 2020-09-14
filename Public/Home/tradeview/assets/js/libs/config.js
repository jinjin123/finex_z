(function () {

    var config = {

        baseUrl: '',
        api: {
            symbol: '/TradingView/getSymbol',
            candles: '/TradingView/getCandles',
            appSymbol: '/AppView/getSymbol',
            appCandles: '/AppView/getCandles',
        },

        //socket配置
        socketUrl: 'wss://okexcomreal.bafang.com:8443/ws/v3',
        // socketUrl: 'wss://real.okex.com:8443/ws/v3',

        //tradingView配置
        tradingView: {
            option: {
                library_path: './assets/charting_library/',
            },
            config: {
                supports_search: true,
                supports_group_request: false,
                supported_resolutions: ['1', '3', '5', '15', '30', '60', '120', '240', '360', '720', '1D', '1W'],
                supports_marks: true,
                supports_timescale_marks: true,
                supports_time: true
            },
            symbol: {
                exchange: "BTCS",
                symbol: 'BTC-USDT',
                description: 'BTC-USDT',
                timezone: 'Asia/Shanghai',
                minmov: 1,
                minmov2: 0,
                pointvalue: 1,
                fractional: false,
                session: '24x7',
                has_intraday: true,
                has_no_volume: false,
                pricescale: 1e4,
                ticker: 'BTC-USDT',
                supported_resolutions: ['1', '3', '5', '15', '30', '60', '120', '240', '360', '720', '1D', '1W'],
                volume_precision: 8,
            }
        }
    };

    window.tvApp = {
        create: function (getSymbol, callback) {
            getSymbol(config, function (symbol) {
                for (var i in symbol) {
                    if (typeof config.tradingView[i] == "object") {
                        var _object = $.extend(config.tradingView[i], symbol[i]);
                        delete config.tradingView[i];
                        config.tradingView[i] = _object;
                    } else {
                        config.tradingView[i] = symbol[i];
                    }
                }
                $(function () {
                    callback(config);
                });
            });
        }
    };

})();
