import React from "react";
import { AxisOptions, Chart } from "react-charts";
import Ajax from "../../../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { capitalize, startCase } from "lodash";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import { ResizableBox } from "react-resizable";
import DropDown from "../../../components/ui/drop-down/drop-down";
import SuccessButton from "../../../components/ui/buttons/success-button";
import DangerButton from "../../../components/ui/buttons/danger-button";

type QuestCompleted = {
    name: string;
    times: number;
};

type Series = {
    label: string;
    data: QuestCompleted[];
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

export default class CharacterQuestCompletion extends React.Component<
    any,
    any
> {
    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            loading: true,
            filter_options: {
                type: "quest",
                limit: undefined,
                filter: undefined,
            },
        };
    }

    componentDidMount() {
        this.fetchChartData("quest");
    }

    filterChart() {
        const filter = this.state.filter_options;

        this.fetchChartData(filter.type, filter.limit, filter.filter);
    }

    fetchChartData(
        type: "quest" | "guide_quest",
        limit?: number | undefined,
        filter?: "most" | "some" | "least" | undefined,
    ) {
        new Ajax()
            .setRoute("admin/site-statistics/quest-completion")
            .setParameters({
                type: type,
                limit: limit,
                filter: filter,
            })
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        data: this.createDataSet(
                            result.data.stats.data,
                            result.data.stats.labels,
                        ),
                        loading: false,
                    });
                },
                (error: AxiosError) => {
                    console.error(error);
                },
            );
    }

    clearFilters() {
        this.setState(
            {
                filter_options: {
                    type: "quest",
                    limit: undefined,
                    filter: undefined,
                },
            },
            () => {
                this.fetchChartData("quest");
            },
        );
    }

    setFilterType(type: "quest" | "guide_quest") {
        const filters = JSON.parse(JSON.stringify(this.state.filter_options));

        filters.type = type;

        this.setState({
            filter_options: filters,
        });
    }

    setLimit(limit: number) {
        const filters = JSON.parse(JSON.stringify(this.state.filter_options));

        filters.limit = limit;

        this.setState({
            filter_options: filters,
        });
    }

    setFilter(filter: "most" | "some" | "least") {
        const filters = JSON.parse(JSON.stringify(this.state.filter_options));

        filters.filter = filter;

        this.setState({
            filter_options: filters,
        });
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

    createTypeFilterDropDown() {
        return [
            {
                name: "Completed Quests",
                icon_class: "ra ra-bottle-vapors",
                on_click: () => this.setFilterType("quest"),
            },
            {
                name: "Completed Guide Quests",
                icon_class: "far fa-trash-alt",
                on_click: () => this.setFilterType("guide_quest"),
            },
        ];
    }

    createAmountFilterDropDown() {
        return [
            {
                name: "Most",
                icon_class: "ra ra-bottle-vapors",
                on_click: () => this.setFilter("most"),
            },
            {
                name: "Some",
                icon_class: "far fa-trash-alt",
                on_click: () => this.setFilter("some"),
            },
            {
                name: "Least",
                icon_class: "far fa-trash-alt",
                on_click: () => this.setFilter("least"),
            },
        ];
    }

    createLimitFilterDropDown() {
        return [
            {
                name: "10",
                icon_class: "ra ra-bottle-vapors",
                on_click: () => this.setLimit(10),
            },
            {
                name: "25",
                icon_class: "far fa-trash-alt",
                on_click: () => this.setLimit(25),
            },
            {
                name: "50",
                icon_class: "far fa-trash-alt",
                on_click: () => this.setLimit(50),
            },
        ];
    }

    render() {
        if (this.state.loading) {
            return <ComponentLoading />;
        }

        if (this.state.data.length === 0) {
            return (
                <p className="text-center p-4 text-red-700 dark:text-red-400">
                    No quest data.
                </p>
            );
        }

        const dataForChart: Series[] = [
            {
                label: "Quest Completion Data",
                data: this.state.data,
            },
        ];

        // @ts-ignore
        return (
            <ResizableBox height={650}>
                <div>
                    <div className="flex flex-wrap justify-between mb-4">
                        <div className="flex items-center space-x-4 mb-2 md:mb-0">
                            <DropDown
                                menu_items={this.createTypeFilterDropDown()}
                                button_title={"Quest Type"}
                            />
                            <DropDown
                                menu_items={this.createAmountFilterDropDown()}
                                button_title={"Quest Completion Amount"}
                            />
                            <DropDown
                                menu_items={this.createLimitFilterDropDown()}
                                button_title={"Character Limit"}
                            />
                            <SuccessButton
                                button_label={"Filter Chart"}
                                on_click={this.filterChart.bind(this)}
                            />
                            <DangerButton
                                button_label={"Clear filters"}
                                on_click={this.clearFilters.bind(this)}
                            />
                        </div>
                    </div>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                    <h3>Filters Selected</h3>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                    <dl>
                        <dt>Type</dt>
                        <dd>{capitalize(this.state.filter_options.type)}</dd>
                        <dt>Completion Amount</dt>
                        <dd>
                            {this.state.filter_options.filter
                                ? startCase(
                                      this.state.filter_options.filter,
                                  ).replace("_", " ")
                                : "N/A"}
                        </dd>
                        <dt>Limit</dt>
                        <dd>
                            {this.state.filter_options.limit
                                ? this.state.filter_options.limit
                                : 10}
                        </dd>
                    </dl>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
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
