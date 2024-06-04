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
import { formatNumber } from "../../../../lib/game/format-number";
var ManageItemSocketsCost = (function (_super) {
    __extends(ManageItemSocketsCost, _super);
    function ManageItemSocketsCost(props) {
        return _super.call(this, props) || this;
    }
    ManageItemSocketsCost.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "mt-4 mb-2" },
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Gold Bar Cost:"),
                React.createElement(
                    "dd",
                    null,
                    formatNumber(this.props.socket_cost),
                ),
                React.createElement("dt", null, "Items Socket Amount:"),
                React.createElement(
                    "dd",
                    null,
                    this.props.get_item_info("socket_amount"),
                ),
            ),
        );
    };
    return ManageItemSocketsCost;
})(React.Component);
export default ManageItemSocketsCost;
//# sourceMappingURL=manage-item-sockets-cost.js.map
