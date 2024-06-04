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
import { renderIcons } from "./helpers/render-icons";
var StationarySpinSection = (function (_super) {
    __extends(StationarySpinSection, _super);
    function StationarySpinSection(props) {
        return _super.call(this, props) || this;
    }
    StationarySpinSection.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "max-h-[150px] overflow-hidden mt-4" },
            React.createElement(
                "div",
                { className: "grid grid-cols-3" },
                React.createElement(
                    "div",
                    null,
                    renderIcons(
                        this.props.roll.length > 0 ? this.props.roll[0] : 0,
                        this.props.icons,
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    renderIcons(
                        this.props.roll.length > 0 ? this.props.roll[1] : 0,
                        this.props.icons,
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    renderIcons(
                        this.props.roll.length > 0 ? this.props.roll[2] : 0,
                        this.props.icons,
                    ),
                ),
            ),
        );
    };
    return StationarySpinSection;
})(React.Component);
export default StationarySpinSection;
//# sourceMappingURL=stationary-spin-section.js.map
