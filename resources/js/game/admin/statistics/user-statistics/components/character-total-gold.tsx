import React, {Fragment} from "react";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {AxisOptions, Chart} from "react-charts";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import ResizableBox from "../../../../components/ui/resizable-box";
import {formatNumber} from "../../../../lib/game/format-number";

type CharacterTotalGoldType = {
    times: number,
    character_name: string,
}

type Series = {
    label: string,
    data: CharacterTotalGoldType[]
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

export default class CharacterTotalGold extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('admin/site-statistics/character-total-gold').doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                data: this.createDataSet(result.data.data, result.data.labels),
                loading: false,
            });
        }, (error: AxiosError) => {
            console.error(error);
        })
    }

    createDataSet(data: number[]|[], labels: string[]|[]): {gold: string, character_name: string}[] {
        const chartData: {gold: string, character_name: string}[] = [];

        data.forEach((data: number, index: number) => {
            chartData.push({
                gold: formatNumber(data),
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
            label: 'Character Total Gold',
            data: this.state.data,
        }];

        if (this.state.data.length === 0) {
            return (
                <div className="p-4 text-center">
                    <p>
                        There is no information to display at this time.
                    </p>
                </div>
            )
        }

        return (
            <Fragment>
                <ResizableBox height={350}>
                    <Chart options={{data: dataForChart, primaryAxis: primaryAxis, secondaryAxes: secondaryAxes}} />
                </ResizableBox>
                <p className={'my-4'}>
                    This includes characters who have more then 2 trillion in gold through kingdoms.
                </p>
            </Fragment>
        )
    }
}
