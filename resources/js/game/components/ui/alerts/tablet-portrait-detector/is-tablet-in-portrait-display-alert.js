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
import WarningAlert from "../simple-alerts/warning-alert";
var IsTabletInPortraitMode = (function (_super) {
    __extends(IsTabletInPortraitMode, _super);
    function IsTabletInPortraitMode(props) {
        var _this = _super.call(this, props) || this;
        _this.checkIsTabletPortrait = function () {
            var isPortrait = window.matchMedia(
                "(orientation: portrait)",
            ).matches;
            var isTabletWidth = window.matchMedia(
                "(min-width: 820px) and (max-width: 1024px)",
            ).matches;
            _this.setState({ isTabletPortrait: isPortrait && isTabletWidth });
        };
        _this.state = {
            isTabletPortrait: false,
        };
        return _this;
    }
    IsTabletInPortraitMode.prototype.componentDidMount = function () {
        this.checkIsTabletPortrait();
        window.addEventListener("resize", this.checkIsTabletPortrait);
        window.addEventListener(
            "orientationchange",
            this.checkIsTabletPortrait,
        );
    };
    IsTabletInPortraitMode.prototype.componentWillUnmount = function () {
        window.removeEventListener("resize", this.checkIsTabletPortrait);
        window.removeEventListener(
            "orientationchange",
            this.checkIsTabletPortrait,
        );
    };
    IsTabletInPortraitMode.prototype.render = function () {
        var isTabletPortrait = this.state.isTabletPortrait;
        if (isTabletPortrait) {
            return React.createElement(
                WarningAlert,
                null,
                "You might have a better experience switching to portrait or landscape mode. For example an iPad Mini is best in portrait and an iPad Pro is best in landscape mode.",
            );
        }
        return null;
    };
    return IsTabletInPortraitMode;
})(React.Component);
export default IsTabletInPortraitMode;
//# sourceMappingURL=is-tablet-in-portrait-display-alert.js.map
