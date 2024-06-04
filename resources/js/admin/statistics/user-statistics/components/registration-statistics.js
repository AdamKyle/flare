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
import SiteStatisticsAjax from "../helpers/site-statistics-ajax";
var primaryAxis = {
    getValue: function (datum) {
        return datum.date;
    },
};
var secondaryAxes = [
    {
        getValue: function (datum) {
            return datum.registration_count;
        },
        elementType: "line",
    },
];
var RegistrationStatistics = (function (_super) {
    __extends(RegistrationStatistics, _super);
    function RegistrationStatistics(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: [],
            loading: true,
            days_past: 0,
        };
        _this.siteStatisticsAjax = new SiteStatisticsAjax(_this);
        return _this;
    }
    RegistrationStatistics.prototype.componentDidMount = function () {
        this.siteStatisticsAjax.fetchStatisticalData("all-time-register", 0);
    };
    RegistrationStatistics.prototype.createDataSet = function (data, labels) {
        var chartData = [];
        data.forEach(function (data, index) {
            chartData.push({
                registration_count: data,
                date: labels[index],
            });
        });
        return chartData;
    };
    RegistrationStatistics.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(ComponentLoading, null);
        }
        if (this.state.data.length === 0) {
            return React.createElement(
                "p",
                { className: "text-center p-4 text-red-700 dark:text-red-400" },
                "No Registration data",
            );
        }
        var dataForChart = [
            {
                label: "Registration Count",
                data: this.state.data,
            },
        ];
        return React.createElement(
            "div",
            null,
            React.createElement(DropDown, {
                menu_items:
                    this.siteStatisticsAjax.createActionsDropDown(
                        "all-time-register",
                    ),
                button_title: "Date Filter",
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
        );
    };
    return RegistrationStatistics;
})(React.Component);
export default RegistrationStatistics;
//# sourceMappingURL=registration-statistics.js.map
