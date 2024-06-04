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
var EquipSelectedInformation = (function (_super) {
    __extends(EquipSelectedInformation, _super);
    function EquipSelectedInformation(props) {
        return _super.call(this, props) || this;
    }
    EquipSelectedInformation.prototype.renderSelectedItemNames = function () {
        return this.props.item_names.map(function (name) {
            return React.createElement("li", null, name);
        });
    };
    EquipSelectedInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                { className: "mb-3" },
                "Below are a list of items you have selected to equip. Each of these items will replace the item of that type in your inventory. Should you have two weapons, shields, spells (of the same type) or rings equipped, and you only choose one of the two things you have equipped, we will choose the first or left hand to replace by default.",
            ),
            React.createElement(
                "p",
                { className: "mb-3" },
                "For example, lets say you have two weapons equipped, and you select one weapon to equip, we will replace the left hand by default.",
            ),
            React.createElement(
                "p",
                null,
                "If you would like more control over the position to equip, please close this window, select the desired item and click equip.",
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "span",
                { className: "mb-3" },
                React.createElement("strong", null, "Items to Equip"),
            ),
            React.createElement(
                "ul",
                { className: "my-3 pl-4 list-disc ml-4" },
                this.renderSelectedItemNames(),
            ),
        );
    };
    return EquipSelectedInformation;
})(React.Component);
export default EquipSelectedInformation;
//# sourceMappingURL=equip-selected-information.js.map
