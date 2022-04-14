import {AxisOptions, Chart} from "react-charts";
import React from "react";
import ResizableBox from "../resizable-box";
import {SeriesFocusStatus, SeriesStyles} from "react-charts/types/types";
import {DateTime} from "luxon";

type DailyStars = {
    date: Date,
    stars: number,
}

type Series = {
    label: string,
    color: string,
    data: DailyStars[]
}

let dateOne: any = DateTime.now();
dateOne = dateOne.toJSDate();

let dateTwo: any = DateTime.now().plus({ days: 1 });
dateTwo = dateTwo.toJSDate();

let dateThree: any = DateTime.now().plus({ days: 5 });
dateThree = dateThree.toJSDate();

const data: Series[] = [
    {
        label: 'React Charts',
        color: 'rgb(19,133,133)',
        data: [
            {
                date: dateOne,
                stars: 234324,
            },
            {
                date: dateTwo,
                stars: 2342,
            },
            {
                date: dateThree,
                stars: 24234234,
            }
            // ...
        ]
    },
]

export const LineChart = (props: {dark_chart: boolean}) => {
    const primaryAxis = React.useMemo(
        (): AxisOptions<DailyStars> => ({
            getValue: datum => datum.date,
        }),
        []
    )

    const secondaryAxes = React.useMemo(
        (): AxisOptions<DailyStars>[] => [
            {
                getValue: datum => datum.stars,
            },
        ],
        []
    )

    const getSeriesStyle = React.useCallback((series) => {
        return {
            fill: '#5597e2',
            stroke: '#5597e2',
        };
    }, []);

    return (
        <ResizableBox height={300} width={720} style={{
            background: props.dark_chart ? "rgba(0, 27, 45, 0.9)" : "rgba(255,255,255,0.9)",
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
