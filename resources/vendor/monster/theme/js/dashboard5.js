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
    new Chartist.Line('.total-revenue', {
        labels: ['0', '4', '8', '12', '16', '20']
        , series: [
        [4, 2, 3.5, 1.5, 4, 3]
        , [2, 4, 2, 4, 2, 4]
      ]
    }, {
        high: 5
        , low: 1
        , fullWidth: true
        , plugins: [
        Chartist.plugins.tooltip()
      ]
        , // As this is axis specific we need to tell Chartist to use whole numbers only on the concerned axis
        axisY: {
            onlyInteger: true
            , offset: 20
            , labelInterpolationFnc: function (value) {
                return (value / 1) + 'k';
            }
        }
    });
});    
    // ============================================================== 
    // doughnut chart option
    // ============================================================== 
    var doughnutChart = echarts.init(document.getElementById('sales-donute'));
    // specify chart configuration item and data
    option = {
        
         legend: {
             show: false,
             data: ['Item A', 'Item B', 'Item C', 'Item D']
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


// Income of the year chart
new Chartist.Bar('.income-year', {
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
  series: [
    [5, 4, 3, 7, 5, 10, 3],
    [3, 2, 9, 5, 4, 6, 4]
  ]
}, {
  high: 12
        , low: 1
        , fullWidth: true
        , plugins: [
        Chartist.plugins.tooltip()
      ]
        ,     
  axisX: {
    // On the x-axis start means top and end means bottom
    position: 'bottom'
  },
    
  axisY: {
    // On the y-axis start means left and end means right
    
  }
});