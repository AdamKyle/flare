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
import { formatNumber } from "../../../../game/lib/game/format-number";
var RewardListItem = (function (_super) {
    __extends(RewardListItem, _super);
    function RewardListItem(props) {
        return _super.call(this, props) || this;
    }
    RewardListItem.prototype.render = function () {
        return React.createElement(
            "li",
            { className: "text-green-600 dark:text-green-400" },
            this.props.label,
            ": ",
            formatNumber(this.props.value),
        );
    };
    return RewardListItem;
})(React.Component);
export default RewardListItem;
//# sourceMappingURL=reward-list-item.js.map
