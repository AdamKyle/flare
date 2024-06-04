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
import clsx from "clsx";
var RenderAtonementAdjustment = (function (_super) {
    __extends(RenderAtonementAdjustment, _super);
    function RenderAtonementAdjustment(props) {
        return _super.call(this, props) || this;
    }
    RenderAtonementAdjustment.prototype.renderDifference = function (
        atonementData,
        originalAtonement,
    ) {
        var _this = this;
        var atonementKeys = Object.keys(atonementData);
        return atonementKeys.map(function (atonementName) {
            var atonementValue = _this.findElementAtonement(
                originalAtonement,
                atonementName,
            );
            return React.createElement(
                Fragment,
                null,
                React.createElement("dt", null, atonementName),
                React.createElement(
                    "dd",
                    {
                        className: clsx({
                            "text-green-700 dark:text-green-500":
                                atonementData[atonementName] > atonementValue,
                            "text-red-700 dark:text-red-500":
                                atonementData[atonementName] < atonementValue,
                        }),
                    },
                    (atonementData[atonementName] * 100).toFixed(0),
                    "%",
                ),
            );
        });
    };
    RenderAtonementAdjustment.prototype.findElementAtonement = function (
        atonements,
        elementName,
    ) {
        var atonementsKeys = Object.keys(atonements);
        var element = atonementsKeys.filter(function (atonementsName) {
            return atonementsName === elementName;
        });
        if (element.length > 0) {
            return atonements[element[0]];
        }
        return 0;
    };
    RenderAtonementAdjustment.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "h3",
                { className: "my-4" },
                "Adjusted Atonement",
            ),
            React.createElement(
                "dl",
                null,
                this.renderDifference(
                    this.props.atonement_for_comparison,
                    this.props.original_atonement.atonements,
                ),
            ),
        );
    };
    return RenderAtonementAdjustment;
})(React.Component);
export default RenderAtonementAdjustment;
//# sourceMappingURL=render-atonement-adjustment.js.map
