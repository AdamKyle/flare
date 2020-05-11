$(function() {
    
    /*<!-- ============================================================== -->*/
    /*<!-- Line Chart -->*/
    /*<!-- ============================================================== -->*/
    new Chart(document.getElementById("chart1"),
        {
            "type":"line",
            "data":{"labels":["January","February","March","April","May","June","July"],
            "datasets":[{
                            "label":"My First Dataset",
                            "data":[65,59,80,81,56,55,40],
                            "fill":false,
                            "borderColor":"rgb(86, 192, 216)",
                            "lineTension":0.1
                        }]
        },"options":{}});
    
    /*<!-- ============================================================== -->*/
    /*<!-- Bar Chart -->*/
    /*<!-- ============================================================== -->*/
    new Chart(document.getElementById("chart2"),
        {
            "type":"bar",
            "data":{"labels":["January","February","March","April","May","June","July"],
            "datasets":[{
                            "label":"My First Dataset",
                            "data":[65,59,80,81,56,55,40],
                            "fill":false,
                            "backgroundColor":["rgba(255, 99, 132, 0.2)","rgba(255, 159, 64, 0.2)","rgba(255, 205, 86, 0.2)","rgba(75, 192, 192, 0.2)","rgba(54, 162, 235, 0.2)","rgba(153, 102, 255, 0.2)","rgba(201, 203, 207, 0.2)"],
                            "borderColor":["rgb(239, 83, 80)","rgb(255, 159, 64)","rgb(255, 178, 43)","rgb(86, 192, 216)","rgb(57, 139, 247)","rgb(153, 102, 255)","rgb(201, 203, 207)"],
                            "borderWidth":1}
                        ]},
            "options":{
                "scales":{"yAxes":[{"ticks":{"beginAtZero":true}}]}
            }
        });
    
    /*<!-- ============================================================== -->*/
    /*<!-- Pie Chart -->*/
    /*<!-- ============================================================== -->*/
    new Chart(document.getElementById("chart3"),
        {
            "type":"pie",
            "data":{"labels":["Red","Blue","Yellow"],
            "datasets":[{
                "label":"My First Dataset",
                "data":[300,50,100],
                "backgroundColor":["rgb(239, 83, 80)","rgb(57, 139, 247)","rgb(255, 178, 43)"]}
            ]}
        });

    /*<!-- ============================================================== -->*/
    /*<!-- Doughnut Chart -->*/
    /*<!-- ============================================================== -->*/
    new Chart(document.getElementById("chart4"),
        {
            "type":"doughnut",
            "data":{"labels":["Red","Blue","Yellow"],
            "datasets":[{
                "label":"My First Dataset",
                "data":[300,50,100],
                "backgroundColor":["rgb(239, 83, 80)","rgb(57, 139, 247)","rgb(255, 178, 43)"]}
            ]}
        });

    /*<!-- ============================================================== -->*/
    /*<!-- PolarArea Chart -->*/
    /*<!-- ============================================================== -->*/
    new Chart(document.getElementById("chart5"),
        {
            "type":"polarArea",
            "data":{"labels":["Red","Green","Yellow","Grey","Blue"],
            "datasets":[{
                "label":"My First Dataset",
                "data":[11,16,7,3,14],
                "backgroundColor":["rgb(239, 83, 80)","rgb(86, 192, 216)","rgb(255, 178, 43)","rgb(201, 203, 207)","rgb(57, 139, 247)"
                ]}
            ]}
        });

    /*<!-- ============================================================== -->*/
    /*<!-- Radar Chart -->*/
    /*<!-- ============================================================== -->*/
    new Chart(document.getElementById("chart6"),
        {
            "type":"radar",
            "data":{"labels":["Eating","Drinking","Sleeping","Designing","Coding","Cycling","Running"],
            "datasets":[{
                "label":"My First Dataset",
                "data":[65,59,90,81,56,55,40],
                "fill":true,
                "backgroundColor":"rgba(255, 99, 132, 0.2)","borderColor":"rgb(239, 83, 80)","pointBackgroundColor":"rgb(239, 83, 80)","pointBorderColor":"#fff","pointHoverBackgroundColor":"#fff","pointHoverBorderColor":"rgb(239, 83, 80)"
            },{
                "label":"My Second Dataset",
                "data":[28,48,40,19,96,27,100],
                "fill":true,
                "backgroundColor":"rgba(54, 162, 235, 0.2)","borderColor":"rgb(57, 139, 247)","pointBackgroundColor":"rgb(57, 139, 247)","pointBorderColor":"#fff","pointHoverBackgroundColor":"#fff","pointHoverBorderColor":"rgb(57, 139, 247)"
            }
            ]},
            "options":{
                "elements":{
                    "line":{
                        "tension":0,
                        "borderWidth":3
                    }
                }
            }
        });
    
});