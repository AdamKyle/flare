import React from "react";
import { AxisOptions, Chart } from "react-charts";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import SiteStatisticsAjax from "../helpers/site-statistics-ajax";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";

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

export default class LoginDurationStatistics extends React.Component<any, any> {
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
        this.siteStatisticsAjax.fetchStatisticalData("login-duration", 0);
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
                <p className="p-4 text-center text-red-700 dark:text-red-400">
                    No Login Duration Data
                </p>
            );
        }

        const dataForChart: Series[] = [
            {
                label: "Login Duration",
                data: this.state.data,
            },
        ];

        return (
            <ResizableBox height={550}>
                <div>
                    <DropDown
                        menu_items={this.siteStatisticsAjax.createActionsDropDown(
                            "login-duration",
                        )}
                        button_title={"Date Filter"}
                    />
                    <InfoAlert additional_css="my-3">
                        <strong>Please note:</strong> This is an average of
                        playrs login time in minutes over the period of a day, a
                        week, two weeks or a month based on the drop down.
                    </InfoAlert>
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
