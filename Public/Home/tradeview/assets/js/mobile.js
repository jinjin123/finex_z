(function () {
    tvApp.create(function (_config, callback) {
        utils.getSymbol(_config.baseUrl + _config.api.appSymbol, callback);
    }, function (tvConfig) {

        var app = {

            httpData: {},

            widget: null,

            activeChart: null,

            getBarTimer: null,

            /**
             * 获取时间段
             *
             * @returns {*}
             */
            get interval() {
                return tvConfig.tradingView.option.interval;
            },

            /**
             * 获取商品
             *
             * @returns {*}
             */
            get symbol() {
                return tvConfig.tradingView.symbol.symbol;
            },

            /**
             * 初始化
             */
            init: function () {
                app.initWeight();
            },

            /**
             * 初始化数据
             */
            initData: function () {
                var url = tvConfig.baseUrl + tvConfig.api.appCandles;
                utils.getCandles(url, this.symbol, utils.toSecond(this.interval), function (data) {
                    app.httpData[app.ticker] = data;
                    app._socket.subscribe();
                });
            },

            /**
             * 初始化widght
             * @param callback
             */
            initWeight: function () {

                this._socket.init();

                tvConfig.tradingView.option = $.extend(this.option, tvConfig.tradingView.option, {
                    datafeed: utils.datafeeds(this._datafeeds)
                });

                this.initData();

                this.widget = new TradingView.widget(tvConfig.tradingView.option);

                //图表加载完成事件
                this.widget.onChartReady(function () {

                    var widget = this.activeChart();

                    app.activeChart = widget;

                    //追加均线
                    widget.createStudy("Moving Average", false, false, [5], null, {
                        "plot.color": "#09e"
                    });
                    widget.createStudy("Moving Average", false, false, [10], null, {
                        "plot.color": "#dad553"
                    });

                    widget.onIntervalChanged().subscribe(null, function (interval) {
                        app._socket.unSubscribe();//取消订阅
                        tvConfig.tradingView.option.interval = interval;
                        widget.setResolution(interval);
                        app.initData();
                    });

                });

            },

            /**
             * 获取ticker
             * @returns {string}
             */
            get ticker() {
                return this.symbol + "-" + this.interval;
            },

            /**
             * 获取widght配置
             * @returns {({symbol: *, interval: *, user_id: string, fullscreen: boolean, autosize: boolean, container_id: string, datafeed: null, custom_css_url: string, library_path: string, disabled_features: string[], enabled_features: string[], theme: string, timezone: string, locale: string, overrides: {"paneProperties.background": string}} & app._init.option) | ({symbol: *, interval: *, user_id: string, fullscreen: boolean, autosize: boolean, container_id: string, datafeed: null, custom_css_url: string, library_path: string, disabled_features: string[], enabled_features: string[], theme: string, timezone: string, locale: string, overrides: {"paneProperties.background": string}} & {})}
             */
            get option() {

                var theme = tvConfig.tradingView.theme != 'dark' ? 'light' : 'dark';

                var paneColor = theme == 'light' ? '#ffffff' : '#222222';
                var _theme = {};
                switch (theme) {
                    case 'dark':
                        _theme = {
                            overrides: {
                                "paneProperties.background": "#222222",
                                "paneProperties.vertGridProperties.color": paneColor,
                                "paneProperties.horzGridProperties.color": paneColor,
                                "symbolWatermarkProperties.transparency": 90,
                                "scalesProperties.textColor": "#AAA"
                            }
                        };
                        break;
                }

                return $.extend({
                    // debug: true,
                    symbol: 'BTC-USDT',
                    // interval: 5,
                    user_id: "public_user_id",
                    fullscreen: true,
                    autosize: true,
                    container_id: 'trading-view',
                    datafeed: null,
                    custom_css_url: '../../css/custom.css',
                    disabled_features: [
                        "save_chart_properties_to_local_storage",
                        "volume_force_overlay",
                        "header_saveload",
                        "header_symbol_search",
                        "header_chart_type",
                        "header_compare",
                        "header_undo_redo",
                        "timeframes_toolbar",
                        "countdown",
                        "caption_buttons_text_if_possible",
                        "use_localstorage_for_settings",
                        "compare_symbol",
                    ],
                    enabled_features: [
                        "hide_last_na_study_output",
                        "hide_left_toolbar_by_default"
                    ],
                    theme: theme,
                    timezone: 'Asia/Shanghai',
                    locale: 'zh',
                    preset: "mobile",

                    overrides: {
                        "paneProperties.background": paneColor,
                        "paneProperties.vertGridProperties.color": paneColor,
                        "paneProperties.horzGridProperties.color": paneColor,
                        "paneProperties.crossHairProperties.color": "#989898",
                        "paneProperties.crossHairProperties.width": 1,
                        "mainSeriesProperties.candleStyle.upColor": "#69c0ad", //绿涨  红跌  蜡烛图的样式
                        "mainSeriesProperties.candleStyle.downColor": "#f25c6f",
                        "mainSeriesProperties.candleStyle.borderUpColor": "#69c0ad", //蜡烛边框颜色
                        "mainSeriesProperties.candleStyle.borderDownColor": "#f25c6f",
                        "mainSeriesProperties.candleStyle.wickUpColor": "#69c0ad", //升跌时上下两条直线的颜色
                        "mainSeriesProperties.candleStyle.wickDownColor": "#f25c6f",
                        "mainSeriesProperties.haStyle.upColor": "#69c0ad",
                        "mainSeriesProperties.haStyle.downColor": "#f25c6f", //平均线HA样式
                        "mainSeriesProperties.haStyle.borderDownColor": "#f25c6f",
                        "mainSeriesProperties.haStyle.borderUpColor": "#69c0ad",
//                        "paneProperties.rightMargin": 5, //控制边距
                        "mainSeriesProperties.barStyle.upColor": "#69c0ad",
                        "mainSeriesProperties.barStyle.downColor": "#f25c6f",
                    },
                    studies_overrides: {
                        // volume styles
                        'volume.volume.color.0': '#f25c6f', //红色
                        'volume.volume.color.1': '#69c0ad', //绿色
                    },

                    customFormatters: {
                        timeFormatter: {
                            format: function (date) {
                                return moment(date).utc().format('HH:mm');
                            }
                        },
                        dateFormatter: {
                            format: function (date) {
                                return moment(date).utc().format('YYYY-MM-DD');
                            }
                        }
                    }
                }, _theme);
            },

            /**
             * socket方法
             */
            _socket: {

                self: utils.socket(tvConfig.socketUrl, {}),

                init: function () {
                    this.self.doOpen();
                    this.self.on('message', this.onMessage);
                },

                sendMessage: function (data) {
                    var _this = this;
                    if (this.self.checkOpen()) {
                        this.self.send(data)
                    } else {
                        this.self.on('open', function () {
                            _this.self.send(data)
                        })
                    }
                },

                unSubscribe: function () {
                    this.sendMessage({
                        "op": "unsubscribe",
                        "args": this.args
                    })
                },

                subscribe: function () {
                    this.sendMessage({
                        "op": "subscribe",
                        "args": this.args
                    })
                },

                // socket获取数据后追加到http数据的数组中
                onMessage: function (data) {
                    if (data.data && data.data.length > 0) {
                        var _data = data.data[data.data.length - 1];
                        var _baseData = utils.formatCandles([_data.candle], utils.toSecond(app.interval));
                        var barsData = _baseData[0];
                        if (typeof app.httpData[app.ticker] == 'object') {
                            var len = app.httpData[app.ticker].length;
                            var last = app.httpData[app.ticker][len - 1]
                            if (last.time == barsData.time) {
                                app.httpData[app.ticker][len - 1] = barsData;
                            } else {
                                app.httpData[app.ticker].push(barsData);
                            }
                        }
                        tvConfig.tradingView.option.datafeed.barsUpdater.updateData();
                    }
                },

                /**
                 * 获取socket调用参数
                 *
                 * @param interval
                 * @returns {string[]}
                 */
                get args() {
                    return ["spot/candle" + utils.toSecond(app.interval) + "s:" + app.symbol]
                }

            },

            /**
             * datafeed方法
             */
            _datafeeds: {

                /**
                 * 默认配置
                 */
                getConfig: function () {
                    return tvConfig.tradingView.config;
                },

                /**
                 * 默认商品信息
                 */
                getSymbol: function () {
                    return tvConfig.tradingView.symbol;
                },

                /**
                 * 数据块处理
                 * @param symbolInfo
                 * @param resolution
                 * @param rangeStartDate
                 * @param rangeEndDate
                 * @param onLoadedCallback
                 */
                getBars: function (symbolInfo, resolution, rangeStartDate, rangeEndDate, onLoadedCallback) {
                    var ticker = app.ticker;
                    if (app.httpData[ticker] && app.httpData[ticker].length) {
                        var newBars = [];
                        var data = app.httpData[ticker];
                        for (var i in data) {
                            if (data[i].time >= rangeStartDate * 1000 && data[i].time <= rangeEndDate * 1000) {
                                newBars.push(data[i]);
                            }
                        }
                        onLoadedCallback(newBars);
                    } else {
                        var self = this;
                        app.getBarTimer = setTimeout(function () {
                            self.getBars(symbolInfo, resolution, rangeStartDate, rangeEndDate, onLoadedCallback);
                        }, 10);
                    }
                }

            }

        };

        app.init();
    });
})();
