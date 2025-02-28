import React from "react";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";
import InfoAlert from "../../../../game/components/ui/alerts/simple-alerts/info-alert";
import LoadingProgressBar from "../../../../game/components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../game/components/ui/alerts/simple-alerts/danger-alert";
import UserLoginDuration from "../ajax/user-login-duration";
import { charactersOnlineContainer } from "../container/characters-online-container";
import { AllowedFilters } from "../deffinitions/allowed-filter-types";
import { LoginDurationChartProps } from "../types/login-duration-chart-props";
import { LoginDurationChartState } from "../types/login-duration-charts-state";
import { Series } from "../deffinitions/chart-series";
import { primaryAxis, secondaryAxes } from "../deffinitions/chart-axis";
import { Chart } from "react-charts";

export default class LoginDurationChart extends React.Component<
    LoginDurationChartProps,
    LoginDurationChartState
> {
    private userLoginDuration: UserLoginDuration;

    constructor(props: any) {
        super(props);

        this.state = {
            error_message: "",
            chart_data: [],
            loading: true,
        };

        this.userLoginDuration =
            charactersOnlineContainer().fetch(UserLoginDuration);
    }

    componentDidMount() {
        this.userLoginDuration.fetchLoginDurationData(this, 0);
    }

    fetchOnlineChartData(filter: AllowedFilters) {
        this.userLoginDuration.fetchLoginDurationData(this, filter);
    }

    dropDownOptions() {
        return [
            {
                name: "Today",
                icon_class: "fas fa-calendar-day",
                on_click: () => this.fetchOnlineChartData(0),
            },
            {
                name: "Last 7 Days",
                icon_class: "fas fa-calendar",
                on_click: () => this.fetchOnlineChartData(7),
            },
            {
                name: "Last 14 Days",
                icon_class: "fas fa-calendar-alt",
                on_click: () => this.fetchOnlineChartData(14),
            },
            {
                name: "Last Month",
                icon_class: "fas fa-calendar-alt",
                on_click: () => this.fetchOnlineChartData(31),
            },
        ];
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        if (this.state.chart_data.length === 0) {
            return (
                <p className="p-4 text-center text-red-700 dark:text-red-400">
                    No Login Duration Data
                </p>
            );
        }

        const dataForChart: Series[] = [
            {
                label: "Login Duration",
                data: this.state.chart_data,
            },
        ];

        return (
            <ResizableBox height={560} small_height={850}>
                <div>
                    <DropDown
                        menu_items={this.dropDownOptions()}
                        button_title={"Date Filter"}
                    />
                    <InfoAlert additional_css="my-3">
                        <p>
                            <strong>Please note:</strong> This is an average of
                            players login time in <strong>minutes</strong> over
                            the period of a day, a week, two weeks or a month
                            based on the drop down. This is not real time and
                            only counts those who did log in at one time and
                            their contibution to player login. If you want to
                            know who is online, see the list to the right.
                        </p>
                    </InfoAlert>
                    {this.state.error_message !== "" ? (
                        <DangerAlert additional_css="my-2">
                            {this.state.error_message}
                        </DangerAlert>
                    ) : null}
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
