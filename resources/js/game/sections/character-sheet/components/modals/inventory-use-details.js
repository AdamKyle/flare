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
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import AlchemyItemHoly from "../../../../components/modals/item-details/item-views/alchemy-item-holy";
import AlchemyItemUsable from "../../../../components/modals/item-details/item-views/alchemy-item-usable";
var InventoryUseDetails = (function (_super) {
    __extends(InventoryUseDetails, _super);
    function InventoryUseDetails(props) {
        return _super.call(this, props) || this;
    }
    InventoryUseDetails.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: React.createElement(
                    "span",
                    { className: "text-pink-500 dark:text-pink-300" },
                    this.props.item.item_name,
                ),
            },
            React.createElement(
                "div",
                { className: "mb-5" },
                this.props.item.usable || this.props.item.damages_kingdoms
                    ? React.createElement(AlchemyItemUsable, {
                          item: this.props.item,
                      })
                    : React.createElement(AlchemyItemHoly, {
                          item: this.props.item,
                      }),
            ),
        );
    };
    return InventoryUseDetails;
})(React.Component);
export default InventoryUseDetails;
//# sourceMappingURL=inventory-use-details.js.map
