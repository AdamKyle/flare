var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React from "react";
import { Chart } from "react-charts";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import DropDown from "../../../../game/components/ui/drop-down/drop-down";
import Ajax from "../../../../game/lib/ajax/ajax";
import DangerButton from "../../../../game/components/ui/buttons/danger-button";
import SuccessButton from "../../../../game/components/ui/buttons/success-button";
import { capitalize, startCase } from "lodash";
var primaryAxis = {
    getValue: function (datum) {
        return datum.date;
    },
};
var secondaryAxes = [
    {
        getValue: function (datum) {
            return datum.login_count;
        },
        elementType: "line",
    },
];
var CharacterQuestCompletion = (function (_super) {
    __extends(CharacterQuestCompletion, _super);
    function CharacterQuestCompletion(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: [],
            loading: true,
            filter_options: {
                type: "quest",
                limit: undefined,
                filter: undefined,
            },
        };
        return _this;
    }
    CharacterQuestCompletion.prototype.componentDidMount = function () {
        this.fetchChartData("quest");
    };
    CharacterQuestCompletion.prototype.filterChart = function () {
        var filter = this.state.filter_options;
        this.fetchChartData(filter.type, filter.limit, filter.filter);
    };
    CharacterQuestCompletion.prototype.fetchChartData = function (
        type,
        limit,
        filter,
    ) {
        var _this = this;
        new Ajax()
            .setRoute("admin/site-statistics/quest-completion")
            .setParameters({
                type: type,
                limit: limit,
                filter: filter,
            })
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        data: _this.createDataSet(
                            result.data.stats.data,
                            result.data.stats.labels,
                        ),
                        loading: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    CharacterQuestCompletion.prototype.clearFilters = function () {
        var _this = this;
        this.setState(
            {
                filter_options: {
                    type: "quest",
                    limit: undefined,
                    filter: undefined,
                },
            },
            function () {
                _this.fetchChartData("quest");
            },
        );
    };
    CharacterQuestCompletion.prototype.setFilterType = function (type) {
        var filters = JSON.parse(JSON.stringify(this.state.filter_options));
        filters.type = type;
        this.setState({
            filter_options: filters,
        });
    };
    CharacterQuestCompletion.prototype.setLimit = function (limit) {
        var filters = JSON.parse(JSON.stringify(this.state.filter_options));
        filters.limit = limit;
        this.setState({
            filter_options: filters,
        });
    };
    CharacterQuestCompletion.prototype.setFilter = function (filter) {
        var filters = JSON.parse(JSON.stringify(this.state.filter_options));
        filters.filter = filter;
        this.setState({
            filter_options: filters,
        });
    };
    CharacterQuestCompletion.prototype.createDataSet = function (data, labels) {
        var chartData = [];
        data.forEach(function (data, index) {
            chartData.push({
                login_count: data,
                date: labels[index],
            });
        });
        return chartData;
    };
    CharacterQuestCompletion.prototype.createTypeFilterDropDown = function () {
        var _this = this;
        return [
            {
                name: "Completed Quests",
                icon_class: "ra ra-bottle-vapors",
                on_click: function () {
                    return _this.setFilterType("quest");
                },
            },
            {
                name: "Completed Guide Quests",
                icon_class: "far fa-trash-alt",
                on_click: function () {
                    return _this.setFilterType("guide_quest");
                },
            },
        ];
    };
    CharacterQuestCompletion.prototype.createAmountFilterDropDown =
        function () {
            var _this = this;
            return [
                {
                    name: "Most",
                    icon_class: "ra ra-bottle-vapors",
                    on_click: function () {
                        return _this.setFilter("most");
                    },
                },
                {
                    name: "Some",
                    icon_class: "far fa-trash-alt",
                    on_click: function () {
                        return _this.setFilter("some");
                    },
                },
                {
                    name: "Least",
                    icon_class: "far fa-trash-alt",
                    on_click: function () {
                        return _this.setFilter("least");
                    },
                },
            ];
        };
    CharacterQuestCompletion.prototype.createLimitFilterDropDown = function () {
        var _this = this;
        return [
            {
                name: "10",
                icon_class: "ra ra-bottle-vapors",
                on_click: function () {
                    return _this.setLimit(10);
                },
            },
            {
                name: "25",
                icon_class: "far fa-trash-alt",
                on_click: function () {
                    return _this.setLimit(25);
                },
            },
            {
                name: "50",
                icon_class: "far fa-trash-alt",
                on_click: function () {
                    return _this.setLimit(50);
                },
            },
        ];
    };
    CharacterQuestCompletion.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(ComponentLoading, null);
        }
        if (this.state.data.length === 0) {
            return React.createElement(
                "p",
                { className: "text-center p-4 text-red-700 dark:text-red-400" },
                "No quest data.",
            );
        }
        var dataForChart = [
            {
                label: "Quest Completion Data",
                data: this.state.data,
            },
        ];
        return React.createElement(
            ResizableBox,
            { height: 650 },
            React.createElement(
                "div",
                null,
                React.createElement(
                    "div",
                    { className: "flex flex-wrap justify-between mb-4" },
                    React.createElement(
                        "div",
                        {
                            className:
                                "flex items-center space-x-4 mb-2 md:mb-0",
                        },
                        React.createElement(DropDown, {
                            menu_items: this.createTypeFilterDropDown(),
                            button_title: "Quest Type",
                        }),
                        React.createElement(DropDown, {
                            menu_items: this.createAmountFilterDropDown(),
                            button_title: "Quest Completion Amount",
                        }),
                        React.createElement(DropDown, {
                            menu_items: this.createLimitFilterDropDown(),
                            button_title: "Character Limit",
                        }),
                        React.createElement(SuccessButton, {
                            button_label: "Filter Chart",
                            on_click: this.filterChart.bind(this),
                        }),
                        React.createElement(DangerButton, {
                            button_label: "Clear filters",
                            on_click: this.clearFilters.bind(this),
                        }),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement("h3", null, "Filters Selected"),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    "dl",
                    null,
                    React.createElement("dt", null, "Type"),
                    React.createElement(
                        "dd",
                        null,
                        capitalize(this.state.filter_options.type),
                    ),
                    React.createElement("dt", null, "Completion Amount"),
                    React.createElement(
                        "dd",
                        null,
                        this.state.filter_options.filter
                            ? startCase(
                                  this.state.filter_options.filter,
                              ).replace("_", " ")
                            : "N/A",
                    ),
                    React.createElement("dt", null, "Limit"),
                    React.createElement(
                        "dd",
                        null,
                        this.state.filter_options.limit
                            ? this.state.filter_options.limit
                            : 10,
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    ResizableBox,
                    { height: 350 },
                    React.createElement(Chart, {
                        options: {
                            data: dataForChart,
                            primaryAxis: primaryAxis,
                            secondaryAxes: secondaryAxes,
                        },
                    }),
                ),
            ),
        );
    };
    return CharacterQuestCompletion;
})(React.Component);
export default CharacterQuestCompletion;
//# sourceMappingURL=character-quest-completion.js.map
