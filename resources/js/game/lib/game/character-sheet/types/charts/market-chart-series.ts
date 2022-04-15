import MarketChartData from "./market-chart-data";

export default interface MarketChartSeries {
    label: string;

    color: string;

    data: MarketChartData[];
}
