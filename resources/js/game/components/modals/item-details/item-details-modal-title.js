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
import ItemNameColorationText from "../../items/item-name/item-name-coloration-text";
import { capitalize } from "lodash";
import { ItemType } from "../../items/enums/item-type";
var ItemDetailsModalTitle = (function (_super) {
    __extends(ItemDetailsModalTitle, _super);
    function ItemDetailsModalTitle(props) {
        return _super.call(this, props) || this;
    }
    ItemDetailsModalTitle.prototype.buildItemDetailsForTitle = function () {
        var name = "";
        if (!this.props.itemToEquip.affix_name) {
            name = this.props.itemToEquip.name;
        } else {
            name = this.props.itemToEquip.affix_name;
        }
        return {
            name: name,
            type: this.props.itemToEquip.type,
            affix_count: this.props.itemToEquip.affix_count,
            is_unique: this.props.itemToEquip.is_unique,
            is_mythic: this.props.itemToEquip.is_mythic,
            is_cosmic: this.props.itemToEquip.is_cosmic,
            holy_stacks_applied: this.props.itemToEquip.holy_stacks_applied,
        };
    };
    ItemDetailsModalTitle.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "grid grid-cols-2 gap-2" },
            this.props.itemToEquip.type === ItemType.GEM
                ? React.createElement(
                      "span",
                      { className: "text-lime-600 dark:text-lime-500" },
                      this.props.itemToEquip.item.gem.name,
                  )
                : React.createElement(ItemNameColorationText, {
                      custom_width: false,
                      item: this.buildItemDetailsForTitle(),
                  }),
            React.createElement(
                "div",
                { className: "absolute right-[-30px] md:right-0" },
                React.createElement(
                    "span",
                    { className: "pl-3 text-right mr-[70px]" },
                    "(Type:",
                    " ",
                    capitalize(this.props.itemToEquip.type)
                        .split("-")
                        .join(" "),
                    ")",
                ),
            ),
        );
    };
    return ItemDetailsModalTitle;
})(React.Component);
export default ItemDetailsModalTitle;
//# sourceMappingURL=item-details-modal-title.js.map
