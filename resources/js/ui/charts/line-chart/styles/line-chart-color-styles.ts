import LineChartColor from 'ui/charts/line-chart/enums/line-chart-color';

const LINE_CHART_COLOR_STYLES: Record<LineChartColor, string> = {
  [LineChartColor.DANUBE]: 'text-danube-600 dark:text-danube-300',
  [LineChartColor.EMERALD]: 'text-emerald-600 dark:text-emerald-400',
  [LineChartColor.ROSE]: 'text-rose-600 dark:text-rose-400',
  [LineChartColor.REGENT_ST_BLUE]:
    'text-regent-st-blue-600 dark:text-regent-st-blue-400',
  [LineChartColor.MARIGOLD]: 'text-marigold-600 dark:text-marigold-400',
};

export default LINE_CHART_COLOR_STYLES;
