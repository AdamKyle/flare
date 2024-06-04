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
import clsx from "clsx";
var GemComparisonDetails = (function (_super) {
    __extends(GemComparisonDetails, _super);
    function GemComparisonDetails(props) {
        return _super.call(this, props) || this;
    }
    GemComparisonDetails.prototype.render = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Tier"),
            React.createElement("dd", null, this.props.gem.tier),
            React.createElement(
                "dt",
                null,
                this.props.gem.primary_atonement_type,
            ),
            React.createElement(
                "dl",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.props.gem.primary_atonement_amount > 0,
                        "text-red-700 dark:text-red-500":
                            this.props.gem.primary_atonement_amount < 0,
                    }),
                },
                this.props.gem.primary_atonement_amount > 0 ? "+" : "",
                (this.props.gem.primary_atonement_amount * 100).toFixed(2),
                "%",
            ),
            React.createElement(
                "dt",
                null,
                this.props.gem.secondary_atonement_type,
            ),
            React.createElement(
                "dl",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.props.gem.secondary_atonement_amount > 0,
                        "text-red-700 dark:text-red-500":
                            this.props.gem.secondary_atonement_amount < 0,
                    }),
                },
                this.props.gem.secondary_atonement_amount > 0 ? "+" : "",
                (this.props.gem.secondary_atonement_amount * 100).toFixed(2),
                "%",
            ),
            React.createElement(
                "dt",
                null,
                this.props.gem.tertiary_atonement_type,
            ),
            React.createElement(
                "dl",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.props.gem.tertiary_atonement_amount > 0,
                        "text-red-700 dark:text-red-500":
                            this.props.gem.tertiary_atonement_amount < 0,
                    }),
                },
                this.props.gem.tertiary_atonement_amount > 0 ? "+" : "",
                (this.props.gem.tertiary_atonement_amount * 100).toFixed(2),
                "%",
            ),
        );
    };
    return GemComparisonDetails;
})(React.Component);
export default GemComparisonDetails;
//# sourceMappingURL=gem-comparison-details.js.map
