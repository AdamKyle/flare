import React from "react";
import { AxisOptions, Chart } from "react-charts";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import SiteStatisticsAjax from "../helpers/site-statistics-ajax";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";

type LogInStats = {
    login_count: number;
    date: string;
};

type Series = {
    label: string;
    data: LogInStats[];
};

const primaryAxis: AxisOptions<any> = {
    getValue: (datum) => datum.date,
};

const secondaryAxes: AxisOptions<any>[] = [
    {
        getValue: (datum) => datum.login_count,
        elementType: "line",
    },
];

export default class LoginStatistics extends React.Component<any, any> {
    private siteStatisticsAjax: SiteStatisticsAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
        };

        this.siteStatisticsAjax = new SiteStatisticsAjax(this);
    }

    componentDidMount() {
        this.siteStatisticsAjax.fetchStatisticalData("all-time-sign-in", 0);
    }

    createDataSet(
        data: number[] | [],
        labels: string[] | [],
    ): { login_count: number; date: string }[] {
        const chartData: { login_count: number; date: string }[] = [];

        data.forEach((data: number, index: number) => {
            chartData.push({
                login_count: data,
                date: labels[index],
            });
        });

        return chartData;
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />;
        }

        if (this.state.data.length === 0) {
            return (
                <p className="text-center p-4 text-red-700 dark:text-red-400">
                    No Login Data
                </p>
            );
        }

        const dataForChart: Series[] = [
            {
                label: "Login Count",
                data: this.state.data,
            },
        ];

        // @ts-ignore
        return (
            <ResizableBox height={350}>
                <div>
                    <DropDown
                        menu_items={this.siteStatisticsAjax.createActionsDropDown(
                            "all-time-sign-in",
                        )}
                        button_title={"Date Filter"}
                    />
                    <ResizableBox height={350}>
                        <Chart
                            options={{
                                data: dataForChart,
                                primaryAxis: primaryAxis,
                                secondaryAxes: secondaryAxes,
                            }}
                        />
                    </ResizableBox>
                </div>
            </ResizableBox>
        );
    }
}
