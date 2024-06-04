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
import Ajax from "../../../../game/lib/ajax/ajax";
import { Chart } from "react-charts";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import ResizableBox from "../../../../game/components/ui/resizable-box";
var primaryAxis = {
    getValue: function (datum) {
        return datum.character_name;
    },
};
var secondaryAxes = [
    {
        getValue: function (datum) {
            return datum.gold;
        },
        elementType: "line",
    },
];
var CharacterReincarnationStatistics = (function (_super) {
    __extends(CharacterReincarnationStatistics, _super);
    function CharacterReincarnationStatistics(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: [],
            loading: true,
        };
        return _this;
    }
    CharacterReincarnationStatistics.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax().setRoute("admin/site-statistics/reincarnation").doAjaxCall(
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
    CharacterReincarnationStatistics.prototype.createDataSet = function (
        data,
        labels,
    ) {
        var chartData = [];
        data.forEach(function (data, index) {
            chartData.push({
                gold: data,
                character_name: labels[index],
            });
        });
        return chartData;
    };
    CharacterReincarnationStatistics.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(ComponentLoading, null);
        }
        if (this.state.data.length === 0) {
            return React.createElement(
                "p",
                { className: "text-center p-4 text-red-700 dark:text-red-400" },
                "No Character Reincarnation Stats",
            );
        }
        var dataForChart = [
            {
                label: "Character Reincarnation",
                data: this.state.data,
            },
        ];
        return React.createElement(
            ResizableBox,
            { height: 350 },
            React.createElement(Chart, {
                options: {
                    data: dataForChart,
                    primaryAxis: primaryAxis,
                    secondaryAxes: secondaryAxes,
                },
            }),
        );
    };
    return CharacterReincarnationStatistics;
})(React.Component);
export default CharacterReincarnationStatistics;
//# sourceMappingURL=character-reincarnation-statistics.js.map
