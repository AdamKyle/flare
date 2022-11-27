import React from "react";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {AxisOptions, Chart} from "react-charts";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import ResizableBox from "../../../../components/ui/resizable-box";

type CharacterReincarnation = {
    times: number,
    character_name: string,
}

type Series = {
    label: string,
    data: CharacterReincarnation[]
}

const primaryAxis: AxisOptions<any> = ({
    getValue: datum => datum.character_name,
});

const secondaryAxes: AxisOptions<any>[] = [
    {
        getValue: datum => datum.gold,
        elementType: 'line',
    },
];

export default class CharacterReincarnationStatistics extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('admin/site-statistics/reincarnation').doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                data: this.createDataSet(result.data.stats.data, result.data.stats.labels),
                loading: false,
            });
        }, (error: AxiosError) => {
            console.error(error);
        })
    }

    createDataSet(data: number[]|[], labels: string[]|[]): {gold: number, character_name: string}[] {
        const chartData: {gold: number, character_name: string}[] = [];

        data.forEach((data: number, index: number) => {
            chartData.push({
                gold: data,
                character_name: labels[index],
            })
        });

        return chartData;
    }

    render() {

        if (this.state.loading) {
            return <ComponentLoading />
        }

        const dataForChart: Series[] = [{
            label: 'Character Reincarnation',
            data: this.state.data,
        }];

        return (
            <ResizableBox height={350}>
                <Chart options={{data: dataForChart, primaryAxis: primaryAxis, secondaryAxes: secondaryAxes}} />
            </ResizableBox>
        )
    }
}
