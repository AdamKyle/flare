import {AxisOptions, Chart} from "react-charts";
import React from "react";
import ResizableBox from "../resizable-box";
import MarketBoardLineChartProps from "../../../lib/ui/types/charts/market-board-line-chart-props";
import MarketChartData from "../../../lib/game/character-sheet/types/charts/market-chart-data";


export const MarketBoardLineChart = (props: MarketBoardLineChartProps) => {
    const primaryAxis = React.useMemo(
        (): AxisOptions<MarketChartData> => ({
            getValue: datum => datum.date,
        }),
        []
    )

    const secondaryAxes = React.useMemo(
        (): AxisOptions<MarketChartData>[] => [
            {
                getValue: datum => datum[props.key_for_value],
                elementType: 'line'
            },
        ],
        []
    )

    const getSeriesStyle = React.useCallback((series: any) => {
        return {
            fill: '#5597e2',
            stroke: '#5597e2',
        };
    }, []);

    const data = props.data;

    return (
        <ResizableBox height={300} width={720} style={{
            background: props.dark_chart ? "#1e293b" : "#475569",
            padding: ".5rem",
            borderRadius: "5px",
        }}>
            <Chart
                options={{
                    data,
                    primaryAxis,
                    secondaryAxes,
                    dark: true,
                    getSeriesStyle: getSeriesStyle
                }}
            />
        </ResizableBox>
    )
}
