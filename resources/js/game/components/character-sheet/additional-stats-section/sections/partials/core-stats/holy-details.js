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
var HolyDetails = (function (_super) {
    __extends(HolyDetails, _super);
    function HolyDetails(props) {
        return _super.call(this, props) || this;
    }
    HolyDetails.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(
                "p",
                { className: "mt-3 mb-6" },
                "Holy comes from crafting Alchemy items such as Holy Oils which can then be applied to a characters item to increase the stats you see below, which then apply to your character over all.",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Holy Bonus:"),
                React.createElement(
                    "dt",
                    null,
                    (this.props.stat_details.holy_bonus * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Holy Stacks:"),
                React.createElement(
                    "dt",
                    null,
                    this.props.stat_details.current_stacks,
                    " /",
                    " ",
                    this.props.stat_details.max_holy_stacks,
                ),
                React.createElement("dt", null, "Holy Attack Bonus:"),
                React.createElement(
                    "dt",
                    null,
                    (this.props.stat_details.holy_attack_bonus * 100).toFixed(
                        2,
                    ),
                    "%",
                ),
                React.createElement("dt", null, "Holy AC Bonus:"),
                React.createElement(
                    "dt",
                    null,
                    (this.props.stat_details.holy_ac_bonus * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Holy Healing Bonus:"),
                React.createElement(
                    "dt",
                    null,
                    (this.props.stat_details.holy_healing_bonus * 100).toFixed(
                        2,
                    ),
                    "%",
                ),
            ),
            React.createElement(
                "p",
                { className: "mt-4" },
                "For more information please see",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/holy-items", target: "_blank" },
                    "Holy Items Help",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return HolyDetails;
})(React.Component);
export default HolyDetails;
//# sourceMappingURL=holy-details.js.map
