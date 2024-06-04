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
import React, { Fragment } from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
var ItemHolyDetails = (function (_super) {
    __extends(ItemHolyDetails, _super);
    function ItemHolyDetails(props) {
        return _super.call(this, props) || this;
    }
    ItemHolyDetails.prototype.renderStacks = function () {
        return this.props.holy_stacks.map(function (stack, index, stacks) {
            return React.createElement(
                Fragment,
                null,
                React.createElement(
                    "div",
                    { className: "mb-4" },
                    React.createElement(
                        "h4",
                        {
                            className:
                                "text-orange-600 dark:text-orange-500 mb-4",
                        },
                        "Holy Stack: ",
                        index + 1,
                    ),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "All Stat Boost %"),
                        React.createElement(
                            "dd",
                            null,
                            (stack.stat_increase_bonus * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement(
                            "dt",
                            null,
                            "Voidance (Devouring Dark/Light) Resistance Bonus %",
                        ),
                        React.createElement(
                            "dd",
                            null,
                            (stack.devouring_darkness_bonus * 100).toFixed(2),
                            "%",
                        ),
                    ),
                ),
                index !== stacks.length - 1
                    ? React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                      })
                    : null,
            );
        });
    };
    ItemHolyDetails.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Holy Break Down",
            },
            React.createElement(
                "div",
                { className: "max-h-[350px] overflow-y-auto" },
                this.renderStacks(),
            ),
        );
    };
    return ItemHolyDetails;
})(React.Component);
export default ItemHolyDetails;
//# sourceMappingURL=item-holy-details.js.map
