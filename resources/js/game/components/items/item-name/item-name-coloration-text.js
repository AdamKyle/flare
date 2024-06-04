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
var ItemNameColorationText = (function (_super) {
    __extends(ItemNameColorationText, _super);
    function ItemNameColorationText(props) {
        return _super.call(this, props) || this;
    }
    ItemNameColorationText.prototype.getColorClass = function () {
        switch (this.props.item.type) {
            case "alchemy":
                return "text-pink-500 dark:text-pink-300";
            case "quest":
                return "text-orange-400 dark:text-orange-300";
            case "trinket":
                return "text-red-700 dark:text-red-400";
            case "artifact":
                return "text-artifact-colors-800 dark:text-artifact-colors-200";
            default:
                return this.getColorClassFromType();
        }
    };
    ItemNameColorationText.prototype.getColorClassFromType = function () {
        var item = this.props.item;
        if (item.is_cosmic) {
            return "text-cosmic-colors-700 dark:text-cosmic-colors-600";
        }
        if (item.is_mythic) {
            return "text-amber-600 dark:text-amber-500";
        }
        if (item.is_mythic) {
            return "text-amber-600 dark:text-amber-500";
        }
        if (item.is_unique) {
            return "text-green-700 dark:text-green-600";
        }
        if (item.holy_stacks_applied > 0) {
            return "text-sky-700 dark:text-sky-300";
        }
        if (item.affix_count === 1) {
            return "text-blue-500";
        }
        if (item.affix_count == 2) {
            return "text-fuchsia-800 dark:text-fuchsia-300";
        }
        return "text-gray-600 dark:text-white";
    };
    ItemNameColorationText.prototype.render = function () {
        return React.createElement(
            "span",
            {
                className:
                    this.getColorClass() +
                    (this.props.custom_width
                        ? " max-w-[75%] sm:max-w-full"
                        : "") +
                    " " +
                    this.props.additional_css,
            },
            this.props.item.name,
        );
    };
    return ItemNameColorationText;
})(React.Component);
export default ItemNameColorationText;
//# sourceMappingURL=item-name-coloration-text.js.map
