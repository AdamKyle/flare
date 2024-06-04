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
import React, { Fragment } from "react";
import Ajax from "../../../../game/lib/ajax/ajax";
import { Chart } from "react-charts";
import ComponentLoading from "../../../../game/components/ui/loading/component-loading";
import ResizableBox from "../../../../game/components/ui/resizable-box";
import { formatNumber } from "../../../../game/lib/game/format-number";
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
var CharacterTotalGold = (function (_super) {
    __extends(CharacterTotalGold, _super);
    function CharacterTotalGold(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: [],
            loading: true,
        };
        return _this;
    }
    CharacterTotalGold.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("admin/site-statistics/character-total-gold")
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        data: _this.createDataSet(
                            result.data.data,
                            result.data.labels,
                        ),
                        loading: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    CharacterTotalGold.prototype.createDataSet = function (data, labels) {
        var chartData = [];
        data.forEach(function (data, index) {
            chartData.push({
                gold: formatNumber(data),
                character_name: labels[index],
            });
        });
        return chartData;
    };
    CharacterTotalGold.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(ComponentLoading, null);
        }
        if (this.state.data.length === 0) {
            return React.createElement(
                "p",
                { className: "text-center p-4 text-red-700 dark:text-red-400" },
                "No Character Told Gold Data",
            );
        }
        var dataForChart = [
            {
                label: "Character Total Gold",
                data: this.state.data,
            },
        ];
        if (this.state.data.length === 0) {
            return React.createElement(
                "div",
                { className: "p-4 text-center" },
                React.createElement(
                    "p",
                    null,
                    "There is no information to display at this time.",
                ),
            );
        }
        return React.createElement(
            Fragment,
            null,
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
            React.createElement(
                "p",
                { className: "my-4" },
                "This includes characters who have more then 2 trillion in gold through kingdoms.",
            ),
        );
    };
    return CharacterTotalGold;
})(React.Component);
export default CharacterTotalGold;
//# sourceMappingURL=character-total-gold.js.map
