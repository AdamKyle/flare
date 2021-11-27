if (typeof Chart !== "undefined") {
  // Colors
  let colors = {};
  colors.primary = "20, 83, 136";

  // Chart defaults
  Chart.defaults.color = "#555555";
  Chart.defaults.font.family = "'Nunito Sans', sans-serif";

  // Line with shadow element
  class LineWithShadowElement extends Chart.elements.LineElement {
    draw(ctx) {
      const originalStroke = ctx.stroke;

      ctx.stroke = function () {
        ctx.save();
        ctx.shadowColor = "rgba(0, 0, 0, 0.25)";
        ctx.shadowBlur = 8;
        ctx.shadowOffsetX = 0;
        ctx.shadowOffsetY = 4;
        originalStroke.apply(this, arguments);
        ctx.restore();
      };

      Chart.elements.LineElement.prototype.draw.apply(this, arguments);
    }
  }

  LineWithShadowElement.id = "lineWithShadowElement";

  Chart.register(LineWithShadowElement);

  // Line with shadow
  class LineWithShadow extends Chart.controllers.line {}

  LineWithShadow.id = "lineWithShadow";
  LineWithShadow.defaults = {
    datasetElementType: "lineWithShadowElement",
  };

  Chart.register(LineWithShadow);

  // Bar with shadow
  class BarWithShadow extends Chart.controllers.bar {
    draw(ease) {
      const ctx = this.chart.ctx;

      Chart.controllers.bar.prototype.draw.call(this, ease);
      ctx.save();
      ctx.shadowColor = "rgba(0, 0, 0, 0.25)";
      ctx.shadowBlur = 8;
      ctx.shadowOffsetX = 0;
      ctx.shadowOffsetY = 4;
      Chart.controllers.bar.prototype.draw.apply(this, arguments);
      ctx.restore();
    }
  }

  BarWithShadow.id = "barWithShadow";

  Chart.register(BarWithShadow);

  // Pie with shadow
  class PieWithShadow extends Chart.controllers.pie {}

  PieWithShadow.id = "pieWithShadow";
  PieWithShadow.defaults = {
    datasetElementType: "lineWithShadowElement",
  };

  Chart.register(PieWithShadow);

  // Doughnut with shadow
  class DoughnutWithShadow extends Chart.controllers.doughnut {}

  DoughnutWithShadow.id = "doughnutWithShadow";
  DoughnutWithShadow.defaults = {
    datasetElementType: "lineWithShadowElement",
  };

  Chart.register(DoughnutWithShadow);

  // Radar with shadow
  class RadarWithShadow extends Chart.controllers.radar {}

  RadarWithShadow.id = "radarWithShadow";
  RadarWithShadow.defaults = {
    datasetElementType: "lineWithShadowElement",
  };

  Chart.register(RadarWithShadow);

  // PolarArea with shadow
  class PolarAreaWithShadow extends Chart.controllers.polarArea {}

  PolarAreaWithShadow.id = "polarAreaWithShadow";
  PolarAreaWithShadow.defaults = {
    datasetElementType: "lineWithShadowElement",
  };

  Chart.register(PolarAreaWithShadow);

  // Line with annotation
  class LineWithAnnotation extends Chart.controllers.line {
    draw(ease) {
      const ctx = this.chart.ctx;

      Chart.controllers.line.prototype.draw.call(this, ease);

      if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
        const activePoint = this.chart.tooltip._active[0];
        const x = activePoint.element.x;
        const topY = this.chart.scales["y"].top;
        const bottomY = this.chart.scales["y"].bottom;

        ctx.save();
        ctx.beginPath();
        ctx.moveTo(x, topY);
        ctx.lineTo(x, bottomY);
        ctx.lineWidth = 1;
        ctx.strokeStyle = "rgba(0, 0, 0, 0.1)";
        ctx.stroke();
        ctx.restore();
      }
    }
  }

  LineWithAnnotation.id = "lineWithAnnotation";

  Chart.register(LineWithAnnotation);

  // Line with annotation and shadow
  class LineWithAnnotationAndShadow extends Chart.controllers.line {
    draw(ease) {
      const ctx = this.chart.ctx;

      Chart.controllers.line.prototype.draw.call(this, ease);

      if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
        const activePoint = this.chart.tooltip._active[0];
        const x = activePoint.element.x;
        const topY = this.chart.scales["y"].top;
        const bottomY = this.chart.scales["y"].bottom;

        ctx.save();
        ctx.beginPath();
        ctx.moveTo(x, topY);
        ctx.lineTo(x, bottomY);
        ctx.lineWidth = 1;
        ctx.strokeStyle = "rgba(0, 0, 0, 0.1)";
        ctx.stroke();
        ctx.restore();
      }
    }
  }

  LineWithAnnotationAndShadow.id = "lineWithAnnotationAndShadow";
  LineWithAnnotationAndShadow.defaults = {
    datasetElementType: "lineWithShadowElement",
  };

  Chart.register(LineWithAnnotationAndShadow);
}
