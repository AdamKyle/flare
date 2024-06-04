import { Chart } from "react-charts";
import React from "react";
import ResizableBox from "../resizable-box";
export var MarketBoardLineChart = function (props) {
    var primaryAxis = React.useMemo(function () {
        return {
            getValue: function (datum) {
                return datum.date;
            },
        };
    }, []);
    var secondaryAxes = React.useMemo(function () {
        return [
            {
                getValue: function (datum) {
                    return datum[props.key_for_value];
                },
                elementType: "line",
            },
        ];
    }, []);
    var getSeriesStyle = React.useCallback(function (series) {
        return {
            fill: "#5597e2",
            stroke: "#5597e2",
        };
    }, []);
    var data = props.data;
    return React.createElement(
        ResizableBox,
        {
            height: 350,
            width: 720,
            style: {
                background: props.dark_chart ? "#1e293b" : "#475569",
                padding: ".5rem",
                borderRadius: "5px",
            },
        },
        React.createElement(Chart, {
            options: {
                data: data,
                primaryAxis: primaryAxis,
                secondaryAxes: secondaryAxes,
                dark: true,
                getSeriesStyle: getSeriesStyle,
                tooltip: false,
            },
        }),
    );
};
//# sourceMappingURL=line-chart.js.map
