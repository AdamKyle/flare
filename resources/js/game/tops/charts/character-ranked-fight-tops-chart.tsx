import React from "react";
import {AxisOptions, Chart} from "react-charts";
import ResizableBox from "../../components/ui/resizable-box";

type Rank = {
    rank: number,
    character_name: string,
}

type Series = {
    label: string,
    data: Rank[]
}

const primaryAxis: AxisOptions<any> = ({
    getValue: datum => datum.character_name,
});

const secondaryAxes: AxisOptions<any>[] = [
    {
        getValue: datum => datum.rank,
        elementType: 'line',
    },
];

export default class CharacterRankedFightTopsChart extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    createDataSet(data: number[]|[], labels: string[]|[]): {rank: number, character_name: string}[] {
        const chartData: {rank: number, character_name: string}[] = [];

        data.forEach((data: number, index: number) => {
            chartData.push({
                rank: data,
                character_name: labels[index],
            })
        });

        return chartData;
    }

    render() {

        const dataForChart: Series[] = [{
            label: 'Ranked Fights',
            data: this.createDataSet(this.props.data, this.props.labels),
        }];

        return (
            <ResizableBox height={350}>
                <Chart options={{data: dataForChart, primaryAxis: primaryAxis, secondaryAxes: secondaryAxes}} />
            </ResizableBox>
        )
    }
}
