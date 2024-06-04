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
import clsx from "clsx";
import React from "react";
import { GemBagTable } from "../../../sections/character-sheet/components/tabs/inventory-tabs/gem-bag-table";
var ItemNameColorationButton = (function (_super) {
    __extends(ItemNameColorationButton, _super);
    function ItemNameColorationButton(props) {
        return _super.call(this, props) || this;
    }
    ItemNameColorationButton.prototype.viewItem = function () {
        if (typeof this.props.on_click !== "undefined") {
            return this.props.on_click(this.props.item);
        }
        return;
    };
    ItemNameColorationButton.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "button",
            {
                className: clsx(
                    {
                        "text-lime-600 dark:text-lime-500":
                            this.props.item instanceof GemBagTable,
                    },
                    {
                        "text-artifact-colors-800 dark:text-artifact-colors-200":
                            this.props.item.type === "artifact" &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-red-700 dark:text-red-400":
                            this.props.item.type === "trinket" &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-green-700 dark:text-green-600":
                            this.props.item.is_unique &&
                            this.props.item.type !== "trinket" &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-sky-700 dark:text-sky-300":
                            this.props.item.has_holy_stacks_applied > 0 &&
                            !this.props.item.is_unique &&
                            this.props.item.type !== "trinket" &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-orange-400 dark:text-orange-300":
                            this.props.item.type === "quest" &&
                            !this.props.item.is_unique &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-pink-500 dark:text-pink-300":
                            this.props.item.type === "alchemy" &&
                            !this.props.item.is_unique &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-gray-600 dark:text-gray-300":
                            this.props.item.attached_affixes_count === 0 &&
                            !this.props.item.is_unique &&
                            this.props.item.type !== "alchemy" &&
                            this.props.item.type !== "quest" &&
                            this.props.item.has_holy_stacks_applied === 0 &&
                            this.props.item.type !== "trinket" &&
                            !this.props.item.is_mythic &&
                            this.props.item.type !== "artifact" &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-blue-500":
                            this.props.item.attached_affixes_count === 1 &&
                            !this.props.item.is_unique &&
                            this.props.item.type !== "alchemy" &&
                            this.props.item.type !== "quest" &&
                            this.props.item.has_holy_stacks_applied === 0 &&
                            this.props.item.type !== "trinket" &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-fuchsia-800 dark:text-fuchsia-300":
                            this.props.item.attached_affixes_count === 2 &&
                            !this.props.item.is_unique &&
                            this.props.item.type !== "alchemy" &&
                            this.props.item.type !== "quest" &&
                            this.props.item.has_holy_stacks_applied === 0 &&
                            this.props.item.type !== "trinket" &&
                            !this.props.item.is_mythic &&
                            !this.props.item.is_cosmic,
                    },
                    {
                        "text-amber-600 dark:text-amber-500":
                            this.props.item.is_mythic,
                    },
                    {
                        "text-cosmic-colors-700 dark:text-cosmic-colors-700":
                            this.props.item.is_cosmic,
                    },
                ),
                onClick: function () {
                    return _this.viewItem();
                },
            },
            this.props.item.item_name,
        );
    };
    return ItemNameColorationButton;
})(React.Component);
export default ItemNameColorationButton;
//# sourceMappingURL=item-name-coloration-button.js.map
