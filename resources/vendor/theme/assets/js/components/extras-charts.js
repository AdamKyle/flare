if (typeof Chart !== "undefined") {
  // Colors
  let colors = {};
  colors.primary = "20, 83, 136";

  // Tooltips Options
  const tooltipOptions = {
    backgroundColor: "#ffffff",
    borderColor: "#dddddd",
    borderWidth: 0.5,
    bodyColor: "#555555",
    bodySpacing: 8,
    cornerRadius: 4,
    padding: 16,
    titleColor: "rgba(" + colors.primary + ")",
  };

  // CHARTS
  let ctx = "";

  // DASHBOARD
  // Visitors chart
  ctx = document.getElementById("visitorsChart");
  if (ctx) {
    ctx = ctx.getContext("2d");

    let gradientBackground = ctx.createLinearGradient(0, 0, 0, 450);
    gradientBackground.addColorStop(0, "rgba(" + colors.primary + ", .5)");
    gradientBackground.addColorStop(0.75, "rgba(" + colors.primary + ", 0)");

    new Chart(ctx, {
      type: "lineWithShadow",
      data: {
        labels: [
          "Jan",
          "Feb",
          "Mar",
          "Apr",
          "May",
          "Jun",
          "Jul",
          "Aug",
          "Sep",
          "Oct",
          "Nov",
          "Dec",
        ],
        datasets: [
          {
            data: [6.25, 7.5, 10, 7.5, 10, 12.5, 10, 12.5, 10, 12.5, 15, 16.25],
            backgroundColor: "rgba(" + colors.primary + ", .1)",
            // backgroundColor: gradientBackground,
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            fill: true,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverBorderWidth: 2,
            pointHoverRadius: 6,
            tension: 0.5,
          },
        ],
      },
      options: {
        plugins: {
          legend: {
            display: false,
          },
          tooltip: tooltipOptions,
        },
        scales: {
          y: {
            grid: {
              display: true,
              drawBorder: false,
            },
            min: 0,
            max: 20,
            ticks: {
              stepSize: 5,
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Categories chart
  ctx = document.getElementById("categoriesChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "polarAreaWithShadow",
      data: {
        labels: ["Potatoes", "Tomatoes", "Onions"],
        datasets: [
          {
            data: [25, 10, 15],
            backgroundColor: [
              "rgba(" + colors.primary + ", .1)",
              "rgba(" + colors.primary + ", .5)",
              "rgba(" + colors.primary + ", .25)",
            ],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
        scales: {
          r: {
            ticks: {
              display: false,
            },
          },
        },
        layout: {
          padding: 5,
        },
      },
    });
  }

  // CHARTS
  // Area chart
  ctx = document.getElementById("areaChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "line",
      data: {
        labels: ["January", "February", "March", "April", "May", "June"],
        datasets: [
          {
            data: [5, 10, 15, 10, 15, 10],
            backgroundColor: "rgba(" + colors.primary + ", .1)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            fill: true,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverBorderWidth: 2,
            pointHoverRadius: 6,
            tension: 0.5,
          },
        ],
      },
      options: {
        plugins: {
          legend: {
            display: false,
          },
          tooltip: tooltipOptions,
        },
        scales: {
          y: {
            grid: {
              display: true,
              drawBorder: false,
            },
            min: 0,
            max: 20,
            ticks: {
              stepSize: 5,
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Area chart with shadow
  ctx = document.getElementById("areaChartWithShadow");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithShadow",
      data: {
        labels: ["January", "February", "March", "April", "May", "June"],
        datasets: [
          {
            data: [5, 10, 15, 10, 15, 10],
            backgroundColor: "rgba(" + colors.primary + ", .1)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            fill: true,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverBorderWidth: 2,
            pointHoverRadius: 6,
            tension: 0.5,
          },
        ],
      },
      options: {
        plugins: {
          legend: {
            display: false,
          },
          tooltip: tooltipOptions,
        },
        scales: {
          y: {
            grid: {
              display: true,
              drawBorder: false,
            },
            min: 0,
            max: 20,
            ticks: {
              stepSize: 5,
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Bar chart
  ctx = document.getElementById("barChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["January", "February", "March", "April", "May", "June"],
        datasets: [
          {
            label: "Potatoes",
            data: [5, 10, 15, 10, 15, 10],
            backgroundColor: "rgba(" + colors.primary + ", .1)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
          {
            label: "Tomatoes",
            data: [7.5, 10, 17.5, 15, 12.5, 5],
            backgroundColor: "rgba(" + colors.primary + ", .5)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
        scales: {
          y: {
            grid: {
              display: true,
              drawBorder: false,
            },
            min: 0,
            max: 20,
            ticks: {
              stepSize: 5,
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Bar chart with shadow
  ctx = document.getElementById("barChartWithShadow");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "barWithShadow",
      data: {
        labels: ["January", "February", "March", "April", "May", "June"],
        datasets: [
          {
            label: "Potatoes",
            data: [5, 10, 15, 10, 15, 10],
            backgroundColor: "rgba(" + colors.primary + ", .1)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
          {
            label: "Tomatoes",
            data: [7.5, 10, 17.5, 15, 12.5, 5],
            backgroundColor: "rgba(" + colors.primary + ", .5)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
        scales: {
          y: {
            grid: {
              display: true,
              drawBorder: false,
            },
            min: 0,
            max: 20,
            ticks: {
              stepSize: 5,
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Line chart
  ctx = document.getElementById("lineChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "line",
      data: {
        labels: ["January", "February", "March", "April", "May", "June"],
        datasets: [
          {
            data: [5, 10, 15, 10, 15, 10],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 8,
            pointHoverBorderWidth: 2,
            tension: 0.5,
          },
        ],
      },
      options: {
        plugins: {
          legend: {
            display: false,
          },
          tooltip: tooltipOptions,
        },
        scales: {
          y: {
            grid: {
              display: true,
              drawBorder: false,
            },
            min: 0,
            max: 20,
            ticks: {
              stepSize: 5,
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Line chart with shadow
  ctx = document.getElementById("lineChartWithShadow");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithShadow",
      data: {
        labels: ["January", "February", "March", "April", "May", "June"],
        datasets: [
          {
            data: [5, 10, 15, 10, 15, 10],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 8,
            pointHoverBorderWidth: 2,
            tension: 0.5,
          },
        ],
      },
      options: {
        plugins: {
          legend: {
            display: false,
          },
          tooltip: tooltipOptions,
        },
        scales: {
          y: {
            grid: {
              display: true,
              drawBorder: false,
            },
            min: 0,
            max: 20,
            ticks: {
              stepSize: 5,
            },
          },
          x: {
            grid: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Pie chart
  ctx = document.getElementById("pieChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "pie",
      data: {
        labels: ["Potatoes", "Tomatoes", "Onions"],
        datasets: [
          {
            data: [25, 10, 15],
            backgroundColor: [
              "rgba(" + colors.primary + ", .1)",
              "rgba(" + colors.primary + ", .5)",
              "rgba(" + colors.primary + ", .25)",
            ],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
      },
    });
  }

  // Pie chart with shadow
  ctx = document.getElementById("pieChartWithShadow");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "pieWithShadow",
      data: {
        labels: ["Potatoes", "Tomatoes", "Onions"],
        datasets: [
          {
            data: [25, 10, 15],
            backgroundColor: [
              "rgba(" + colors.primary + ", .1)",
              "rgba(" + colors.primary + ", .5)",
              "rgba(" + colors.primary + ", .25)",
            ],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
      },
    });
  }

  // Doughnut chart
  ctx = document.getElementById("doughnutChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Potatoes", "Tomatoes", "Onions"],
        datasets: [
          {
            data: [25, 10, 15],
            backgroundColor: [
              "rgba(" + colors.primary + ", .1)",
              "rgba(" + colors.primary + ", .5)",
              "rgba(" + colors.primary + ", .25)",
            ],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        cutout: "75%",
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
      },
    });
  }

  // Doughnut chart with shadow
  ctx = document.getElementById("doughnutChartWithShadow");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "doughnutWithShadow",
      data: {
        labels: ["Potatoes", "Tomatoes", "Onions"],
        datasets: [
          {
            data: [25, 10, 15],
            backgroundColor: [
              "rgba(" + colors.primary + ", .1)",
              "rgba(" + colors.primary + ", .5)",
              "rgba(" + colors.primary + ", .25)",
            ],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        cutout: "75%",
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
      },
    });
  }

  // Radar chart
  ctx = document.getElementById("radarChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "radar",
      data: {
        labels: ["Drinks", "Snacks", "Lunch", "Dinner"],
        datasets: [
          {
            label: "Potatoes",
            data: [25, 25, 25, 25],
            backgroundColor: "rgba(" + colors.primary + ", .1)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            fill: true,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverBorderWidth: 2,
            pointHoverRadius: 6,
          },
          {
            label: "Tomatoes",
            data: [15, 15, 0, 15],
            backgroundColor: "rgba(" + colors.primary + ", .25",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            fill: true,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverBorderWidth: 2,
            pointHoverRadius: 6,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
        scales: {
          r: {
            max: 30,
            ticks: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Radar chart with shadow
  ctx = document.getElementById("radarChartWithShadow");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "radarWithShadow",
      data: {
        labels: ["Drinks", "Snacks", "Lunch", "Dinner"],
        datasets: [
          {
            label: "Potatoes",
            data: [25, 25, 25, 25],
            backgroundColor: "rgba(" + colors.primary + ", .1)",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            fill: true,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverBorderWidth: 2,
            pointHoverRadius: 6,
          },
          {
            label: "Tomatoes",
            data: [15, 15, 0, 15],
            backgroundColor: "rgba(" + colors.primary + ", .25",
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            fill: true,
            pointBackgroundColor: "#ffffff",
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverBorderWidth: 2,
            pointHoverRadius: 6,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
        scales: {
          r: {
            max: 30,
            ticks: {
              display: false,
            },
          },
        },
      },
    });
  }

  // Polar chart
  ctx = document.getElementById("polarChart");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "polarArea",
      data: {
        labels: ["Potatoes", "Tomatoes", "Onions"],
        datasets: [
          {
            data: [25, 10, 15],
            backgroundColor: [
              "rgba(" + colors.primary + ", .1)",
              "rgba(" + colors.primary + ", .5)",
              "rgba(" + colors.primary + ", .25)",
            ],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
        scales: {
          r: {
            ticks: {
              display: false,
            },
          },
        },
        layout: {
          padding: 5,
        },
      },
    });
  }

  // Polar chart with shadow
  ctx = document.getElementById("polarChartWithShadow");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "polarAreaWithShadow",
      data: {
        labels: ["Potatoes", "Tomatoes", "Onions"],
        datasets: [
          {
            data: [25, 10, 15],
            backgroundColor: [
              "rgba(" + colors.primary + ", .1)",
              "rgba(" + colors.primary + ", .5)",
              "rgba(" + colors.primary + ", .25)",
            ],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
          },
        ],
      },
      options: {
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              usePointStyle: true,
              padding: 20,
            },
          },
          tooltip: tooltipOptions,
        },
        scales: {
          r: {
            ticks: {
              display: false,
            },
          },
        },
        layout: {
          padding: 5,
        },
      },
    });
  }

  // Line with annotation plugin
  const lineWithAnnotationPlugin = {
    afterInit: (chart) => {
      const info = chart.canvas.parentNode;

      const value = chart.data.datasets[0].data[0];
      const heading = chart.data.datasets[0].label;
      const label = chart.data.labels[0];

      info.querySelector(".chart-heading").innerHTML = heading;
      info.querySelector(".chart-value").innerHTML = "$" + value;
      info.querySelector(".chart-label").innerHTML = label;
    },
  };

  // Line with annotation options
  const lineWithAnnotationOptions = {
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        enabled: false,
        intersect: false,
        external: (ctx) => {
          const info = ctx.chart.canvas.parentNode;

          const value = ctx.tooltip.dataPoints[0].formattedValue;
          const heading = ctx.tooltip.dataPoints[0].dataset.label;
          const label = ctx.tooltip.dataPoints[0].label;

          info.querySelector(".chart-heading").innerHTML = heading;
          info.querySelector(".chart-value").innerHTML = "$" + value;
          info.querySelector(".chart-label").innerHTML = label;
        },
      },
    },
    scales: {
      y: {
        display: false,
      },

      x: {
        display: false,
      },
    },
    layout: {
      padding: {
        left: 5,
        right: 5,
        top: 10,
        bottom: 10,
      },
    },
  };

  // Line with annotation chart 1
  ctx = document.getElementById("lineWithAnnotationChart1");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotation",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Total Orders",
            data: [1250, 1300, 1550, 900, 1800, 1100, 1600],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }

  // Line with annotation chart 2
  ctx = document.getElementById("lineWithAnnotationChart2");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotation",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Active Orders",
            data: [100, 125, 75, 125, 100, 75, 75],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }

  // Line with annotation chart 3
  ctx = document.getElementById("lineWithAnnotationChart3");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotation",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Pending Orders",
            data: [300, 300, 600, 700, 600, 300, 300],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }

  // Line with annotation chart 4
  ctx = document.getElementById("lineWithAnnotationChart4");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotation",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Shipped Orders",
            data: [200, 400, 200, 500, 100, 100, 400],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }

  // Line with annotation and shadow chart 1
  ctx = document.getElementById("lineWithAnnotationAndShadowChart1");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotationAndShadow",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Total Orders",
            data: [1250, 1300, 1550, 900, 1800, 1100, 1600],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }

  // Line with annotation and shadow chart 2
  ctx = document.getElementById("lineWithAnnotationAndShadowChart2");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotationAndShadow",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Active Orders",
            data: [100, 125, 75, 125, 100, 75, 75],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }

  // Line with annotation and shadow chart 3
  ctx = document.getElementById("lineWithAnnotationAndShadowChart3");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotationAndShadow",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Pending Orders",
            data: [300, 300, 600, 700, 600, 300, 300],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }

  // Line with annotation and shadow chart 4
  ctx = document.getElementById("lineWithAnnotationAndShadowChart4");
  if (ctx) {
    ctx.getContext("2d");
    new Chart(ctx, {
      type: "lineWithAnnotationAndShadow",
      plugins: [lineWithAnnotationPlugin],
      data: {
        labels: [
          "Monday",
          "Tuesday",
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
          "Sunday",
        ],
        datasets: [
          {
            label: "Shipped Orders",
            data: [200, 400, 200, 500, 100, 100, 400],
            borderColor: "rgba(" + colors.primary + ")",
            borderWidth: 2,
            pointBorderColor: "rgba(" + colors.primary + ")",
            pointBorderWidth: 4,
            pointRadius: 2,
            pointHoverBackgroundColor: "rgba(" + colors.primary + ")",
            pointHoverBorderColor: "#ffffff",
            pointHoverRadius: 2,
            tension: 0.5,
          },
        ],
      },
      options: lineWithAnnotationOptions,
    });
  }
}
