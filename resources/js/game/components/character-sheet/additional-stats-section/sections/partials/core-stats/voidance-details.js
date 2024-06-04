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
var VoidanceDetails = (function (_super) {
    __extends(VoidanceDetails, _super);
    function VoidanceDetails(props) {
        return _super.call(this, props) || this;
    }
    VoidanceDetails.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(
                "p",
                { className: "mt-3" },
                "Devouring Light and Devouring Darkness are considered Void and Devoid. These come from Quest items and Enchantments. Some planes will completely remove your ability to void and devoid enemies.",
            ),
            React.createElement(
                "p",
                { className: "my-3" },
                "Voiding (Devouring Light) means none of your or the enemies enchantments can fire. This can completely wreck a player if they get voided by a mid game to late game creature.",
            ),
            React.createElement(
                "p",
                { className: "mb-6" },
                "Devoiding (Devouring Darkness) means that you or the enemy have stopped the other from being able to void you. For example if you are devoided, you cannot void the enemy and vice versa.",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Devouring Light:"),
                React.createElement(
                    "dt",
                    null,
                    (this.props.stat_details.devouring_light * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Devouring Light Res.:"),
                React.createElement(
                    "dt",
                    null,
                    (this.props.stat_details.devouring_light_res * 100).toFixed(
                        2,
                    ),
                    "%",
                ),
                React.createElement("dt", null, "Devouring Darkness:"),
                React.createElement(
                    "dt",
                    null,
                    (this.props.stat_details.devouring_darkness * 100).toFixed(
                        2,
                    ),
                    "%",
                ),
                React.createElement("dt", null, "Devouring Darkness Res.:"),
                React.createElement(
                    "dt",
                    null,
                    (
                        this.props.stat_details.devouring_darkness_res * 100
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
                    { href: "/information/voidance", target: "_blank" },
                    "Voidance Help",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return VoidanceDetails;
})(React.Component);
export default VoidanceDetails;
//# sourceMappingURL=voidance-details.js.map
