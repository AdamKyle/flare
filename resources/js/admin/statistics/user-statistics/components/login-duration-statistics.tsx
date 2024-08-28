import React from "react";
import { AxisOptions, Chart } from "react-charts";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import SiteStatisticsAjax from "../helpers/site-statistics-ajax";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import LoadingProgressBar from "../../../../game/components/ui/progress-bars/loading-progress-bar";

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
            return <LoadingProgressBar />;
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
                        <p>
                            <strong>Please note:</strong> This is an average of
                            playrs login time in <strong>minutes</strong> over
                            the period of a day, a week, two weeks or a month
                            based on the drop down. This is not real time and
                            only counts those who did log in at one time and
                            their contibution to player login. If you want to
                            know who is online, see the list to the right.
                        </p>
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
