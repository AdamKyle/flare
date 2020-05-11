/*
Template Name: Monster Admin
Author: Themedesigner
Email: niravjoshi87@gmail.com
File: js
*/
$(function () {
    "use strict";
    // ============================================================== 
    // Total revenue chart
    // ============================================================== 
    new Chartist.Line('.total-revenue4', {
        labels: ['0', '4', '8', '12', '16', '20', '24', '30']
        , series: [
        [0, 2, 3.5, 0, 13, 1, 4, 1]
        , [0, 4, 0, 4, 0, 4, 0, 4]
      ]
        
    }, 
                     
      {
        high: 15
        , low: 0
        , showArea: true
        , fullWidth: true
        , plugins: [
        Chartist.plugins.tooltip()
      ], // As this is axis specific we need to tell Chartist to use whole numbers only on the concerned axis
        axisY: {
            onlyInteger: true
            , offset: 20
            , labelInterpolationFnc: function (value) {
                return (value / 1) + 'k';
            }
        }
        
    });
    
    // ============================================================== 
    // sales difference
    // ==============================================================
    new Chartist.Pie('.ct-chart', {
        series: [35, 15, 10]
    }, {
        donut: true
        , donutWidth: 20
        , startAngle: 0
        , showLabel: false
    });
});
// ============================================================== 
// doughnut chart option
// ============================================================== 
var doughnutChart = echarts.init(document.getElementById('sales-donute'));
// specify chart configuration item and data
option = {
    tooltip: {
        trigger: 'item'
        , formatter: "{a} <br/>{b} : {c} ({d}%)"
    }
    , legend: {
        show: false
        , data: ['Item A', 'Item B', 'Item C', 'Item D']
    }
    , toolbox: {
        show: false
        , feature: {
            dataView: {
                show: false
                , readOnly: false
            }
            , magicType: {
                show: false
                , type: ['pie', 'funnel']
                , option: {
                    funnel: {
                        x: '25%'
                        , width: '50%'
                        , funnelAlign: 'center'
                        , max: 1548
                    }
                }
            }
            , restore: {
                show: true
            }
            , saveAsImage: {
                show: true
            }
        }
    }
    , color: ["#edf1f5", "#009efb", "#55ce63", "#745af2"]
    , calculable: false
    , series: [
        {
            name: 'Source'
            , type: 'pie'
            , radius: ['80%', '90%']
            , itemStyle: {
                normal: {
                    label: {
                        show: false
                    }
                    , labelLine: {
                        show: false
                    }
                }
                , emphasis: {
                    label: {
                        show: false
                        , position: 'center'
                        , textStyle: {
                            fontSize: '30'
                            , fontWeight: 'bold'
                        }
                    }
                }
            }
            , data: [
                {
                    value: 835
                    , name: 'Item A'
                    }
                    , {
                    value: 310
                    , name: 'Item B'
                    }
                    , {
                    value: 134
                    , name: 'Item C'
                    }
                    , {
                    value: 235
                    , name: 'Item D'
                    }

            ]
        }
    ]
};
// use configuration item and data specified to show chart
doughnutChart.setOption(option, true), $(function () {
    function resize() {
        setTimeout(function () {
            doughnutChart.resize()
        }, 100)
    }
    $(window).on("resize", resize), $(".sidebartoggler").on("click", resize)
});

// ============================================================== 
// Gauge chart option
// ============================================================== 
var gaugeChart = echarts.init(document.getElementById('gauge-chart'));
option = {
    tooltip: {
        formatter: "{a} <br/>{b} : {c}%"
    }
    , toolbox: {
        show: false
        , feature: {
            mark: {
                show: true
            }
            , restore: {
                show: true
            }
            , saveAsImage: {
                show: true
            }
        }
    }
    , series: [
        {
            name: ''
            , type: 'gauge'
            , splitNumber: 0, // 分割段数，默认为5
            axisLine: { // 坐标轴线
                lineStyle: { // 属性lineStyle控制线条样式
                    color: [[0.2, '#029ff6'], [0.8, '#1badcb'], [1, '#42c386']]
                    , width: 20
                }
            }
            , axisTick: { // 坐标轴小标记
                splitNumber: 0, // 每份split细分多少段
                length: 12, // 属性length控制线长
                lineStyle: { // 属性lineStyle控制线条样式
                    color: 'auto'
                }
            }
            , axisLabel: { // 坐标轴文本标签，详见axis.axisLabel
                textStyle: { // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                    color: 'auto'
                }
            }
            , splitLine: { // 分隔线
                show: false, // 默认显示，属性show控制显示与否
                length: 50, // 属性length控制线长
                lineStyle: { // 属性lineStyle（详见lineStyle）控制线条样式
                    color: 'auto'
                }
            }
            , pointer: {
                width: 5
                , color: '#54667a'
            }
            , title: {
                show: false
                , offsetCenter: [0, '-40%'], // x, y，单位px
                textStyle: { // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                    fontWeight: 'bolder'
                }
            }
            , detail: {
                textStyle: { // 其余属性默认使用全局文本样式，详见TEXTSTYLE
                    color: 'auto'
                    , fontSize: '14'
                    , fontWeight: 'bolder'
                }
            }
            , data: [{
                value: 50
                , name: ''
            }]
        }
    ]
};
    timeTicket = setInterval(function () {
        option.series[0].data[0].value = (Math.random() * 100).toFixed(2) - 0;
        gaugeChart.setOption(option, true);
    }, 2000)
    // use configuration item and data specified to show chart
    gaugeChart.setOption(option, true), $(function () {
    function resize() {
        setTimeout(function () {
            gaugeChart.resize()
        }, 100)
    }
    $(window).on("resize", resize), $(".sidebartoggler").on("click", resize)
});