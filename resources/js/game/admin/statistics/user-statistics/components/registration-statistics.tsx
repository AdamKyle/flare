import React from "react";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {AxisOptions, Chart} from "react-charts";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import ResizableBox from "../../../../components/ui/resizable-box";

type RegistrationInStats = {
    registration_count: number,
    date: string,
}

type Series = {
    label: string,
    data: RegistrationInStats[]
}

const primaryAxis: AxisOptions<any> = ({
    getValue: datum => datum.date,
});

const secondaryAxes: AxisOptions<any>[] = [
    {
        getValue: datum => datum.registration_count,
        elementType: 'line',
    },
];

export default class RegistrationStatistics extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('admin/site-statistics/all-time-register').doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                data: this.createDataSet(result.data.stats.data, result.data.stats.labels),
                loading: false,
            });
        }, (error: AxiosError) => {
            console.error(error);
        })
    }

    createDataSet(data: number[]|[], labels: string[]|[]): {registration_count: number, date: string}[] {
        const chartData: {registration_count: number, date: string}[] = [];

        data.forEach((data: number, index: number) => {
            chartData.push({
                registration_count: data,
                date: labels[index],
            })
        });

        return chartData;
    }

    render() {

        if (this.state.loading) {
            return <ComponentLoading />
        }

        if (this.state.data.length === 0) {
            return (
                <p className="text-center p-4 text-red-700 dark:text-red-400">
                    No Registration data
                </p>
            );
        }

        const dataForChart: Series[] = [{
            label: 'Registration Count',
            data: this.state.data,
        }];

        return (
            <ResizableBox height={350}>
                <Chart options={{data: dataForChart, primaryAxis: primaryAxis, secondaryAxes: secondaryAxes}} />
            </ResizableBox>
        )
    }
}
