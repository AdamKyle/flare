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
var RenderAtonementDetails = (function (_super) {
    __extends(RenderAtonementDetails, _super);
    function RenderAtonementDetails(props) {
        return _super.call(this, props) || this;
    }
    RenderAtonementDetails.prototype.renderAtonements = function (
        atonementData,
    ) {
        var atonements = atonementData.atonements;
        var atonementNames = Object.keys(atonements);
        return atonementNames.map(function (name) {
            return React.createElement(
                Fragment,
                { key: name },
                React.createElement("dt", null, name),
                React.createElement(
                    "dd",
                    null,
                    (atonements[name] * 100).toFixed(0),
                    "%",
                ),
            );
        });
    };
    RenderAtonementDetails.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement("h3", { className: "my-4" }, this.props.title),
            React.createElement(
                "dl",
                null,
                this.renderAtonements(this.props.original_atonement),
            ),
        );
    };
    return RenderAtonementDetails;
})(React.Component);
export default RenderAtonementDetails;
//# sourceMappingURL=render-atonement-details.js.map
