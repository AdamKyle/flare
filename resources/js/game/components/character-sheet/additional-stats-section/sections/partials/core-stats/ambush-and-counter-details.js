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
var AmbushAndCounterDetails = (function (_super) {
    __extends(AmbushAndCounterDetails, _super);
    function AmbushAndCounterDetails(props) {
        return _super.call(this, props) || this;
    }
    AmbushAndCounterDetails.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(
                "p",
                { className: "my-3" },
                "Ambush and Counter chance come from trinkets, which raise your chance to ambush an enemy before the fight begins or to counter an enemies attack.",
            ),
            React.createElement(
                "p",
                { className: "mb-6" },
                "Ambush and Counter resistance also come from trinkets and increase your chance to resist late game creatures ability to ambush you before the battle starts or counter your attacks when you attack.",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Ambush Chance"),
                React.createElement(
                    "dd",
                    null,
                    (this.props.stat_details.ambush_chance * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Ambush Resistance"),
                React.createElement(
                    "dd",
                    null,
                    (
                        this.props.stat_details.ambush_resistance_chance * 100
                    ).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Counter Chance"),
                React.createElement(
                    "dd",
                    null,
                    (this.props.stat_details.counter_chance * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Counter Resistance"),
                React.createElement(
                    "dd",
                    null,
                    (
                        this.props.stat_details.counter_resistance_chance * 100
                    ).toFixed(2),
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
                    {
                        href: "/information/ambush-and-counter",
                        target: "_blank",
                    },
                    "Ambush and Counter Help",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return AmbushAndCounterDetails;
})(React.Component);
export default AmbushAndCounterDetails;
//# sourceMappingURL=ambush-and-counter-details.js.map
