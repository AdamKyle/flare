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
import Dialogue from "../../../ui/dialogue/dialogue";
import ItemNameColorationText from "../../../items/item-name/item-name-coloration-text";
import { formatNumber } from "../../../../lib/game/format-number";
var SellItemModal = (function (_super) {
    __extends(SellItemModal, _super);
    function SellItemModal(props) {
        return _super.call(this, props) || this;
    }
    SellItemModal.prototype.sellItem = function () {
        this.props.sell_item();
        this.props.manage_modal();
    };
    SellItemModal.prototype.getCost = function () {
        return formatNumber(this.props.item.cost);
    };
    SellItemModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Sell item",
                secondary_actions: {
                    secondary_button_disabled: false,
                    secondary_button_label: "sell",
                    handle_action: this.sellItem.bind(this),
                },
            },
            React.createElement(
                "h3",
                { className: "mb-3" },
                "Sell",
                " ",
                React.createElement(ItemNameColorationText, {
                    custom_width: false,
                    item: {
                        name: this.props.item.affix_name,
                        type: this.props.item.type,
                        affix_count: this.props.item.affix_count,
                        is_unique: this.props.item.is_unique,
                        is_mythic: this.props.item.is_mythic,
                        is_cosmic: this.props.item.is_cosmic,
                        holy_stacks_applied:
                            this.props.item.holy_stacks_applied,
                    },
                }),
            ),
            React.createElement(
                "p",
                { className: "mb-4 mt-4 text-orange-700 dark:text-orange-500" },
                "Item will sell for: ",
                this.getCost(),
                " gold, after 5% tax (rounded down).",
            ),
            React.createElement(
                "p",
                { className: "mb-4 mt-4" },
                React.createElement("strong", null, "Note"),
                ": This will not take into account prices for Holy Items and Uniques. In those cases you only get the base item cost, even in the case of holy items, if there are affixes attached. These items are best sold on the market to make your gold invested and time invested worth it.",
            ),
            React.createElement(
                "p",
                { className: "mb-4" },
                "Finally, no item will sell for more then 2 billion gold before the 5% tax. For example selling a max level crafted item with two max level enchants will only be sold for 2 billion gold before the 5% tax even if the item would sell for 36+ billion gold. Again the market is where these items are best sold to make your money back.",
            ),
        );
    };
    return SellItemModal;
})(React.Component);
export default SellItemModal;
//# sourceMappingURL=sell-item-modal.js.map
