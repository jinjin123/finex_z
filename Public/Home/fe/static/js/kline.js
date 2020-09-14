let bgColor = "#1f212d";//背景
let upColor = "#F9293E";//涨颜色
let downColor = "#00aa3b";//跌颜色
let upBorderColor = "#F9293E";//涨边框颜色
let downBorderColor = "#00aa3b";//跌边框颜色
bgColor = "#000000";
upColor = "#00aa3b";//涨颜色
downColor = "#F9293E";//跌颜色
upBorderColor = "#00aa3b";//涨边框颜色
downBorderColor = "#F9293E";//跌边框颜色
// upColor = "#1652F0";//涨颜色
// downColor = "#EEEEEE";//跌颜色
// upBorderColor = '#1652F0';//涨边框颜色
// downBorderColor = '#1652F0';//跌边框颜色

// ma  颜色
let ma5Color = "#39afe6";
let ma10Color = "#da6ee8";
let ma20Color = "#ffab42";
let ma30Color = "#00940b";

/**
 * 15:20 时:分 格式时间增加num分钟
 * @param {Object} time 起始时间
 * @param {Object} num
 */
function addTimeStr(time, num) {
    let hour = time.split(':')[0];
    let mins = Number(time.split(':')[1]);
    let mins_un = parseInt((mins + num) / 60);
    let hour_un = parseInt((Number(hour) + mins_un) / 24);
    if (mins_un > 0) {
        if (hour_un > 0) {
            let tmpVal = ((Number(hour) + mins_un) % 24) + "";
            hour = tmpVal.length > 1 ? tmpVal : '0' + tmpVal;//判断是否是一位
        } else {
            let tmpVal = Number(hour) + mins_un + "";
            hour = tmpVal.length > 1 ? tmpVal : '0' + tmpVal;
        }
        let tmpMinsVal = ((mins + num) % 60) + "";
        mins = tmpMinsVal.length > 1 ? tmpMinsVal : 0 + tmpMinsVal;//分钟数为 取余60的数
    } else {
        let tmpMinsVal = mins + num + "";
        mins = tmpMinsVal.length > 1 ? tmpMinsVal : '0' + tmpMinsVal;//不大于整除60
    }
    return hour + ":" + mins;
}

//获取增加指定分钟数的 数组  如 09:30增加2分钟  结果为 ['09:31','09:32']
function getNextTime(startTime, endTIme, offset, resultArr) {
    let result = addTimeStr(startTime, offset);
    resultArr.push(result);
    if (result == endTIme) {
        return resultArr;
    } else {
        return getNextTime(result, endTIme, offset, resultArr);
    }
}


/**
 * 不同类型的股票的交易时间会不同
 * @param {Object} type   hs=沪深  us=美股  hk=港股
 */
let time_arr = function (type) {
    if (type.indexOf('us') != -1) {
        //生成美股时间段
        let timeArr = new Array();
        timeArr.push('09:30')
        return getNextTime('09:30', '16:00', 1, timeArr);
    }
    if (type.indexOf('hs') != -1) {
        //生成沪深时间段
        let timeArr = new Array();
        timeArr.push('09:30');
        timeArr.concat(getNextTime('09:30', '11:29', 1, timeArr));
        timeArr.push('13:00');
        timeArr.concat(getNextTime('13:00', '15:00', 1, timeArr));
        return timeArr;
    }
    if (type.indexOf('hk') != -1) {
        //生成港股时间段
        let timeArr = new Array();
        timeArr.push('09:30');
        timeArr.concat(getNextTime('09:30', '11:59', 1, timeArr));
        timeArr.push('13:00');
        timeArr.concat(getNextTime('13:00', '16:00', 1, timeArr));
        return timeArr;
    }

}

let get_m_data = function (m_data, type) {
    let priceArr = new Array();
    let avgPrice = new Array();
    let vol = new Array();
    let times = time_arr(type);
    $.each(m_data.data, function (i, v) {
        priceArr.push(v[1]);
        avgPrice.push(v[2]);
        vol.push(v[3]);
    })
    return {
        priceArr: priceArr,
        avgPrice: avgPrice,
        vol: vol,
        times: times
    }
}


//==========================================分时表 option

/**
 * 生成分时option
 * @param {Object} m_data 分时数据
 * @param {Object} type 股票类型  us-美股  hs-沪深  hk-港股
 */
function initMOption(m_data, type) {
    let m_datas = get_m_data(m_data, type);
    return {
        tooltip: { //弹框指示器
            trigger: 'axis',
            axisPointer: {
                type: 'cross'
            },
            formatter: function (params, ticket, callback) {
                let i = params[0].dataIndex;

                let color;
                if (m_datas.priceArr[i] > m_data.yestclose) {
                    color = 'style="color:#ff4242"';
                } else {
                    color = 'style="color:#26bf66"';
                }

                let html = '<div class="commColor" style="width:100px;"><div>Now <span  ' + color + ' >' + m_datas.priceArr[i] + '</span></div>';
                html += '<div>Average <span  ' + color + ' >' + m_datas.avgPrice[i] + '</span></div>';
                html += '<div>Change <span  ' + color + ' >' + ratioCalculate(m_datas.priceArr[i], m_data.yestclose) + '%</span></div>';
                html += '<div>Volume <span  ' + color + ' >' + m_datas.vol[i] + '</span></div></div>'
                return html;
            }
        },
        legend: {
            //图例控件,点击图例控制哪些系列不显示
            icon: 'rect',
            type: 'scroll',
            itemWidth: 14,
            itemHeight: 2,
            left: 0,
            top: '-1%',
            textStyle: {
                fontSize: 12,
                color: '#ffffff'
            }
        },
        axisPointer: {
            show: true
        },
        color: [ma5Color, ma10Color],
        grid: [{
            id: 'gd1',
            left: '0%',
            right: '1%',
            height: '67.5%',
            //主K线的高度,
            top: '5%'
        },
            {
                id: 'gd2',
                left: '0%',
                right: '1%',
                height: '67.5%',
                //主K线的高度,
                top: '5%'
            }, {
                id: 'gd3',
                left: '0%',
                right: '1%',
                top: '75%',
                height: '19%'
                //交易量图的高度
            }
        ],
        xAxis: [
            //==== x轴
            {
                //主图
                gridIndex: 0,
                data: m_datas.times,
                axisLabel: {
                    //label文字设置
                    show: false
                },
                splitLine: {
                    show: false,
                }
            },
            {
                show: false,
                gridIndex: 1,
                data: m_datas.times,
                axisLabel: {
                    //label文字设置
                    show: false
                },
                splitLine: {
                    show: false,
                }
            },
            {
                //交易量图
                splitNumber: 2,
                type: 'category',
                gridIndex: 2,
                data: m_datas.times,
                axisLabel: {
                    //label文字设置
                    color: '#9b9da9',
                    fontSize: 10
                },
            }
        ],
        yAxis: [
            //y轴
            {
                gridIndex: 0,
                scale: true,
                splitNumber: 3,
                axisLabel: {
                    //label文字设置
                    inside: true,
                    //label文字朝内对齐
                    fontWeight: 'bold',
                    color: function (val) {
                        if (val == m_data.yestclose) {
                            return '#aaa'
                        }
                        return val > m_data.yestclose ? upColor : downColor;
                    }
                },
                z: 4,
                splitLine: {
                    //分割线设置
                    show: false,
                    lineStyle: {
                        color: '#181a23'
                    }
                },
            },
            {
                scale: true,
                gridIndex: 1,
                splitNumber: 3,
                position: 'right',
                z: 4,
                axisLabel: {
                    //label文字设置
                    color: function (val) {
                        if (val == m_data.yestclose) {
                            return '#aaa'
                        }
                        return val > m_data.yestclose ? upColor : downColor;
                    },
                    inside: true,
                    //label文字朝内对齐
                    fontWeight: 'bold',
                    formatter: function (val) {
                        let resul = ratioCalculate(val, m_data.yestclose);
                        return Number(resul).toFixed(8) + ' %'
                    }
                },
                splitLine: {
                    //分割线设置
                    show: false,
                    lineStyle: {
                        color: '#181a23'
                    }
                },
                axisPointer: {
                    show: true,
                    label: {
                        formatter: function (params) {
                            //计算右边Y轴对应的当前价的涨幅比例
                            return ratioCalculate(params.value, m_data.yestclose) + '%';
                        }
                    }
                }
            }
            , {
                //交易图
                gridIndex: 2, z: 4,
                splitNumber: 3,
                axisLine: {
                    onZero: false
                },
                axisTick: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                axisLabel: {
                    //label文字设置
                    color: '#c7c7c7',
                    inside: true,
                    //label文字朝内对齐
                    fontSize: 8
                },
            }
        ],
        dataZoom: [],
        //animation:false,
        // 禁止动画效果
        backgroundColor: bgColor,
        blendMode: 'source-over',
        series: [{
            name: 'now price',
            type: 'line',
            data: m_datas.priceArr,
            smooth: true,
            symbol: "circle",
            //中时有小圆点
            lineStyle: {
                normal: {
                    opacity: 0.8,
                    color: '#39afe6',
                    width: 1
                }
            },
            areaStyle: {
                normal: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                        offset: 0,
                        color: 'rgba(0, 136, 212, 0.7)'
                    }, {
                        offset: 0.8,
                        color: 'rgba(0, 136, 212, 0.02)'
                    }], false),
                    shadowColor: 'rgba(0, 0, 0, 0.1)',
                    shadowBlur: 10
                }
            }
        },
            {
                name: 'average price',
                type: 'line',
                data: m_datas.avgPrice,
                smooth: true,
                symbol: "circle",
                lineStyle: {
                    //标线的样式
                    normal: {
                        opacity: 0.8,
                        color: '#da6ee8',
                        width: 1
                    }
                }
            }, {
                type: 'line',
                data: m_datas.priceArr,
                smooth: true,
                symbol: "none",
                gridIndex: 1,
                xAxisIndex: 1,
                yAxisIndex: 1,
                lineStyle: {
                    //标线的样式
                    normal: {
                        width: 0
                    }
                }
            },
            {
                name: 'Volumn',
                type: 'bar',
                gridIndex: 2,
                xAxisIndex: 2,
                yAxisIndex: 2,
                data: m_datas.vol,
                barWidth: '60%',
                itemStyle: {
                    normal: {
                        color: function (params) {
                            let colorList;
                            if (m_datas.priceArr[params.dataIndex] > m_datas.priceArr[params.dataIndex - 1]) {
                                colorList = upColor;
                            } else {
                                colorList = downColor;
                            }
                            return colorList;
                        },
                    }
                }
            }
        ]
    };
}


/**
 * 计算价格涨跌幅百分比
 * @param {Object} price 当前价
 * @param {Object} yclose 昨收价
 */
function ratioCalculate(price, yclose) {
    return ((price - yclose) / yclose * 100).toFixed(8);
}

//数组处理
function splitData(rawData) {
    let datas = [];
    let times = [];
    let vols = [];
    for (let i = 0; i < rawData.length; i++) {
        datas.push(rawData[i]);
        if (isNaN(rawData[i]['id'])) {
            let date = new Date(parseInt(rawData[i].splice(0, 1)[0]) * 1000);
            times.push(date.toLocaleString("en-US", {timeZone: "America/New_York"}));
            // times.push(new Date(parseInt(rawData[i].splice(0, 1)[0]) * 1000).toLocaleString().replace(/:\d{1,2}$/,' '));
        } else {
            times.push(rawData[i].splice(0, 1)[0]);
        }
        vols.push(rawData[i][4]);
    }

    // console.log(datas);
    // console.log(datas);
    // console.log(datas);
    return {datas: datas, times: times, vols: vols};
}


//================================MA计算公式
function calculateMA(dayCount, data) {
    let result = [];
    for (let i = 0, len = data.times.length; i < len; i++) {
        if (i < dayCount) {
            result.push('-');
            continue;
        }
        let sum = 0;
        for (let j = 0; j < dayCount; j++) {
            sum += data.datas[i - j][1];
        }
        result.push((sum / dayCount).toFixed(8));
    }
    return result;
}


//=================================================MADC计算公式

let calcEMA, calcDIF, calcDEA, calcMACD;

/*
 * 计算EMA指数平滑移动平均线，用于MACD
 * @param {number} n 时间窗口
 * @param {array} data 输入数据
 * @param {string} field 计算字段配置
 */
calcEMA = function (n, data, field) {
    let i, l, ema, a;
    a = 2 / (n + 1);
    if (field) {
        //二维数组
        ema = [data[0][field]];
        for (i = 1, l = data.length; i < l; i++) {
            ema.push((a * data[i][field] + (1 - a) * ema[i - 1]).toFixed(8));
        }
    } else {
        //普通一维数组
        ema = [data[0]];
        for (i = 1, l = data.length; i < l; i++) {
            ema.push((a * data[i] + (1 - a) * ema[i - 1]).toFixed(8));
        }
    }
    return ema;
};

/*
 * 计算DIF快线，用于MACD
 * @param {number} short 快速EMA时间窗口
 * @param {number} long 慢速EMA时间窗口
 * @param {array} data 输入数据
 * @param {string} field 计算字段配置
 */
calcDIF = function (short, long, data, field) {
    let i, l, dif, emaShort, emaLong;
    dif = [];
    emaShort = calcEMA(short, data, field);
    emaLong = calcEMA(long, data, field);
    for (i = 0, l = data.length; i < l; i++) {
        dif.push((emaShort[i] - emaLong[i]).toFixed(8));
    }
    return dif;
};

/*
 * 计算DEA慢线，用于MACD
 * @param {number} mid 对dif的时间窗口
 * @param {array} dif 输入数据
 */
calcDEA = function (mid, dif) {
    return calcEMA(mid, dif);
};

/*
 * 计算MACD
 * @param {number} short 快速EMA时间窗口
 * @param {number} long 慢速EMA时间窗口
 * @param {number} mid dea时间窗口
 * @param {array} data 输入数据
 * @param {string} field 计算字段配置
 */
calcMACD = function (short, long, mid, data, field) {
    let i, l, dif, dea, macd, result;
    result = {};
    macd = [];
    dif = calcDIF(short, long, data, field);
    dea = calcDEA(mid, dif);
    for (i = 0, l = data.length; i < l; i++) {
        macd.push(((dif[i] - dea[i]) * 2).toFixed(8));
    }
    result.dif = dif;
    result.dea = dea;
    result.macd = macd;
    return result;
};


//=================================================MADC计算公式 end
function initKOption(cdata) {
    let data = splitData(cdata);
    let macd = calcMACD(12, 26, 9, data.datas, 1);
    return {
        tooltip: {
            //弹框指示器
            trigger: 'axis',
            axisPointer: {
                type: 'cross'
            }
        },
        legend: {
            //图例控件,点击图例控制哪些系列不显示
            icon: 'rect',
            type: 'scroll',
            itemWidth: 14,
            itemHeight: 2,
            left: 0,
            top: '-1%',
            animation: true,
            textStyle: {
                fontSize: 12,
                color: '#ffffff'
            },
            pageIconColor: '#ffffff'
        },
        axisPointer: {
            show: true
        },
        color: [ma5Color, ma10Color, ma20Color, ma30Color],
        grid: [{
            id: 'gd1',
            left: '0%',
            right: '1%',
            height: '60%',
            //主K线的高度,
            top: '5%'
        }, {
            left: '0%',
            right: '1%',
            top: '66.5%',
            height: '10%'
            //交易量图的高度
        }, {
            left: '0%',
            right: '1%',
            top: '80%',
            //MACD 指标
            height: '14%'
        }],
        xAxis: [
            //==== x轴
            {
                //主图
                type: 'category',
                data: data.times,
                scale: true,
                boundaryGap: false,
                axisLine: {
                    onZero: false
                },
                axisLabel: {
                    //label文字设置
                    show: false
                },
                splitLine: {
                    show: false,
                    lineStyle: {
                        color: '#3a3a3e'
                    }
                },
                splitNumber: 20,
                min: 'dataMin',
                max: 'dataMax'
            }, {
                //交易量图
                type: 'category',
                gridIndex: 1,
                data: data.times,
                axisLabel: {
                    //label文字设置
                    color: '#9b9da9',
                    fontSize: 10
                },
            }, { //幅图
                type: 'category',
                gridIndex: 2,
                data: data.times,
                axisLabel: {
                    show: false
                }
            }
        ],
        yAxis: [
            //y轴
            {
                //==主图
                scale: true,
                z: 4,
                axisLabel: {
                    //label文字设置
                    color: '#c7c7c7',
                    inside: true,
                    //label文字朝内对齐
                },
                splitLine: {
                    //分割线设置
                    show: false,
                    lineStyle: {
                        color: '#181a23'
                    }
                },
            }, {
                //交易图
                gridIndex: 1, splitNumber: 3, z: 4,
                axisLine: {
                    onZero: false
                },
                axisTick: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                axisLabel: {
                    //label文字设置
                    color: '#c7c7c7',
                    inside: true,
                    //label文字朝内对齐
                    fontSize: 8
                },
            }, {
                //幅图
                z: 4, gridIndex: 2, splitNumber: 4,
                axisLine: {
                    onZero: false
                },
                axisTick: {
                    show: false
                },
                splitLine: {
                    show: false
                },
                axisLabel: {
                    //label文字设置
                    color: '#c7c7c7',
                    inside: true,
                    //label文字朝内对齐
                    fontSize: 8
                },
            }
        ],
        dataZoom: [{
            type: 'slider',
            xAxisIndex: [0, 1, 2],
            //控件联动
            start: 0,
            end: 100,
            throttle: 10,
            top: '94%',
            height: '6%',
            borderColor: '#696969',
            textStyle: {
                color: '#dcdcdc'
            },
            handleSize: '80%',
            //滑块图标
            handleIcon: 'M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
            dataBackground: {
                lineStyle: {
                    color: '#fff'
                },
                //数据边界线样式
                areaStyle: {
                    color: '#696969'
                }
                //数据域填充样式
            }
        },
            // 		{
            // 			type: 'inside',
            // 			xAxisIndex: [0,1,2],
            // 控件联动
            // 		},
        ],
        animation: true,
        //禁止动画效果
        backgroundColor: bgColor,
        blendMode: 'source-over',
        series: [
            {
                name: 'K-Line',
                type: 'candlestick',
                data: data.datas,
                barWidth: '55%',
                large: true,
                largeThreshold: 100,
                itemStyle: {
                    normal: {
                        color: upColor,
                        color0: downColor,
                        borderColor: upBorderColor,
                        borderColor0: downBorderColor,
                        //opacity:0.8
                    }
                },

            },
            {
                name: 'MA',
                type: 'line',
                data: calculateMA(1, data),
                smooth: true,
                symbol: "none",
                //隐藏选中时有小圆点
                lineStyle: {
                    normal: {
                        opacity: 0.8,
                        color: '#39afe6',
                        width: 1
                    }
                },
            },
            {
                name: 'MA5',
                type: 'line',
                data: calculateMA(5, data),
                smooth: true,
                symbol: "none",
                //隐藏选中时有小圆点
                lineStyle: {
                    normal: {
                        opacity: 0.8,
                        color: '#39afe6',
                        width: 1
                    }
                },
            },
            {
                name: 'MA10',
                type: 'line',
                data: calculateMA(10, data),
                smooth: true,
                symbol: "none",
                lineStyle: {
                    //标线的样式
                    normal: {
                        opacity: 0.8,
                        color: '#da6ee8',
                        width: 1
                    }
                }
            },
            {
                name: 'MA20',
                type: 'line',
                data: calculateMA(20, data),
                smooth: true,
                symbol: "none",
                lineStyle: {
                    opacity: 0.8,
                    width: 1,
                    color: ma20Color
                }
            },
            {
                name: 'MA30',
                type: 'line',
                data: calculateMA(30, data),
                smooth: true,
                symbol: "none",
                lineStyle: {
                    normal: {
                        opacity: 0.8,
                        width: 1,
                        color: ma30Color
                    }
                }
            },
            {
                name: 'Volumn',
                type: 'bar',
                xAxisIndex: 1,
                yAxisIndex: 1,
                data: data.vols,
                barWidth: '60%',
                itemStyle: {
                    normal: {
                        color: function (params) {
                            let colorList;
                            if (data.datas[params.dataIndex][1] > data.datas[params.dataIndex][0]) {
                                colorList = upColor;
                            } else {
                                colorList = downColor;
                            }
                            return colorList;
                        },
                    }
                }
            },
            {
                name: 'MACD',
                type: 'bar',
                xAxisIndex: 2,
                yAxisIndex: 2,
                data: macd.macd,
                barWidth: '40%',
                itemStyle: {
                    normal: {
                        color: function (params) {
                            let colorList;
                            if (params.data >= 0) {
                                colorList = upColor;
                            } else {
                                colorList = downColor;
                            }
                            return colorList;
                        },
                    }
                }
            },
            {
                name: 'DIF',
                type: 'line',
                symbol: "none",
                xAxisIndex: 2,
                yAxisIndex: 2,
                data: macd.dif,
                lineStyle: {
                    normal: {
                        color: '#da6ee8',
                        width: 1
                    }
                }
            },
            {
                name: 'DEA',
                type: 'line',
                symbol: "none",
                xAxisIndex: 2,
                yAxisIndex: 2,
                data: macd.dea,
                lineStyle: {
                    normal: {
                        opacity: 0.8,
                        color: '#39afe6',
                        width: 1
                    }
                }
            }
        ]
    }
}