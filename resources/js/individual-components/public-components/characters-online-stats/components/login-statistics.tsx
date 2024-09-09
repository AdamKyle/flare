import React from "react";
import { AxisOptions, Chart } from "react-charts";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import SiteStatisticsAjax from "../ajax/site-statistics-ajax";
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
        this.siteStatisticsAjax.fetchStatisticalData("character-logins", 0);
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

        return (
            <ResizableBox height={550} small_height={850}>
                <div>
                    <InfoAlert additional_css={"my-4"}>
                        This chart is not real time and shows the amount of
                        logins over the course of a day or days depending on the
                        dropdown selection. Each players login is only counted
                        once, even if they login twenty times over the course of
                        a day.
                    </InfoAlert>
                    <DropDown
                        menu_items={this.siteStatisticsAjax.createActionsDropDown(
                            "character-logins",
                        )}
                        button_title={"Date Filter"}
                    />
                    <ResizableBox height={350} small_height={550}>
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
