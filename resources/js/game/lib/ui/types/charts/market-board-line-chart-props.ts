import MarketChartSeries from "../../../game/character-sheet/types/charts/market-chart-series";

export default interface MarketBoardLineChartProps {
    data: MarketChartSeries[] | [];

    key_for_value: string;

    dark_chart: boolean;
}
