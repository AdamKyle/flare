import React, {Fragment} from "react";
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

    buildLink(title: string, location: string) {
        return (
            <a href={"/information/" + location} target="_blank">
                {title} <i
                className="fas fa-external-link-alt"></i>
            </a>
        )
    }

    render() {

        const dataForChart: Series[] = [{
            label: 'Ranked Fights',
            data: this.createDataSet(this.props.data, this.props.labels),
        }];

        if (this.props.data.length > 0) {
            return (
                <ResizableBox height={350}>
                    <Chart options={{data: dataForChart, primaryAxis: primaryAxis, secondaryAxes: secondaryAxes}} />
                </ResizableBox>
            );
        }

        return (
            <Fragment>
                <p className='my-4'>
                    No one has completed any ranks yet (no chart to load). Completing a rank involves killing the the last critter in a ranks monster list.
                     While not required, there are rewards for each rank, including being the first to the top of the current months rank.
                </p>
                <p className='mb-4'>
                    Characters will need to make use of {this.buildLink('Reincarnation', 'reincarnation')},
                    {this.buildLink('Class Ranks and Class Specials', 'class-ranks')}.
                </p>
            </Fragment>
        )
    }
}
