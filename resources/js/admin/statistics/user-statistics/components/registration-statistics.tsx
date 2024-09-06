import React from "react";
import { AxisOptions, Chart } from "react-charts";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";
import SiteStatisticsAjax from "../helpers/site-statistics-ajax";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";

type RegistrationInStats = {
    registration_count: number;
    date: string;
};

type Series = {
    label: string;
    data: RegistrationInStats[];
};

const primaryAxis: AxisOptions<any> = {
    getValue: (datum) => datum.date,
};

const secondaryAxes: AxisOptions<any>[] = [
    {
        getValue: (datum) => datum.registration_count,
        elementType: "line",
    },
];

export default class RegistrationStatistics extends React.Component<any, any> {
    private siteStatisticsAjax: SiteStatisticsAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
            days_past: 0,
        };

        this.siteStatisticsAjax = new SiteStatisticsAjax(this);
    }

    componentDidMount() {
        this.siteStatisticsAjax.fetchStatisticalData("all-time-register", 0);
    }

    createDataSet(
        data: number[] | [],
        labels: string[] | [],
    ): { registration_count: number; date: string }[] {
        const chartData: { registration_count: number; date: string }[] = [];

        data.forEach((data: number, index: number) => {
            chartData.push({
                registration_count: data,
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
                    No Registration data
                </p>
            );
        }

        const dataForChart: Series[] = [
            {
                label: "Registration Count",
                data: this.state.data,
            },
        ];

        return (
            <ResizableBox height={550}>
                <InfoAlert additional_css={"my-4"}>
                    This chart is not real time and shows the amount of players
                    who registered over the course of a day or set of days
                    depending on the dropdown selection.
                </InfoAlert>
                <div>
                    <DropDown
                        menu_items={this.siteStatisticsAjax.createActionsDropDown(
                            "all-time-register",
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
