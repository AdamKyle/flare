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
var RefreshComponent = (function (_super) {
    __extends(RefreshComponent, _super);
    function RefreshComponent(props) {
        var _this = _super.call(this, props) || this;
        _this.refreshListener = Echo.private(
            "refresh-listener-" + _this.props.user_id,
        );
        return _this;
    }
    RefreshComponent.prototype.componentDidMount = function () {
        this.refreshListener.listen(
            "Admin.Events.RefreshUserScreenEvent",
            function (event) {
                location.reload();
            },
        );
    };
    RefreshComponent.prototype.render = function () {
        return null;
    };
    return RefreshComponent;
})(React.Component);
export default RefreshComponent;
//# sourceMappingURL=screen-refresh.js.map
