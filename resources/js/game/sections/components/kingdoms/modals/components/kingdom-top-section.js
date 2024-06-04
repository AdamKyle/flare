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
import { formatNumber } from "../../../../../lib/game/format-number";
var KingdomTopSection = (function (_super) {
    __extends(KingdomTopSection, _super);
    function KingdomTopSection(props) {
        return _super.call(this, props) || this;
    }
    KingdomTopSection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "w-full lg:grid lg:grid-cols-3 lg:gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Wood:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_wood),
                            " ",
                            "/ ",
                            formatNumber(this.props.kingdom.max_wood),
                        ),
                        React.createElement("dt", null, "Clay:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_clay),
                            " ",
                            "/ ",
                            formatNumber(this.props.kingdom.max_clay),
                        ),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Stone:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_stone),
                            " ",
                            "/ ",
                            formatNumber(this.props.kingdom.max_stone),
                        ),
                        React.createElement("dt", null, "Iron:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_iron),
                            " ",
                            "/ ",
                            formatNumber(this.props.kingdom.max_iron),
                        ),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Pop.:"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_population),
                            " ",
                            "/",
                            " ",
                            formatNumber(this.props.kingdom.max_population),
                        ),
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
        );
    };
    return KingdomTopSection;
})(React.Component);
export default KingdomTopSection;
//# sourceMappingURL=kingdom-top-section.js.map
