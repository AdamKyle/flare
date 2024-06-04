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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React, { Fragment } from "react";
import Select from "react-select";
var ArmourTypeSelection = (function (_super) {
    __extends(ArmourTypeSelection, _super);
    function ArmourTypeSelection(props) {
        var _this = _super.call(this, props) || this;
        _this.selectableTypes = [
            {
                label: "Helmet",
                value: "helmet",
            },
            {
                label: "Body",
                value: "body",
            },
            {
                label: "Sleeves",
                value: "sleeves",
            },
            {
                label: "Gloves",
                value: "gloves",
            },
            {
                label: "Shields",
                value: "shield",
            },
            {
                label: "Leggings",
                value: "leggings",
            },
            {
                label: "Feet",
                value: "feet",
            },
        ];
        return _this;
    }
    ArmourTypeSelection.prototype.defaultCraftingType = function () {
        return { label: "Please select armour type to craft", value: "" };
    };
    ArmourTypeSelection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(Select, {
                onChange: this.props.select_armour_type_to_craft.bind(this),
                options: this.selectableTypes,
                menuPosition: "absolute",
                menuPlacement: "bottom",
                styles: {
                    menuPortal: function (base) {
                        return __assign(__assign({}, base), {
                            zIndex: 9999,
                            color: "#000000",
                        });
                    },
                },
                menuPortalTarget: document.body,
                value: this.defaultCraftingType(),
            }),
            React.createElement(
                "p",
                { className: "mt-3 text-sm" },
                "Selecting any armour type will count towards Armour Crafting skill.",
            ),
        );
    };
    return ArmourTypeSelection;
})(React.Component);
export default ArmourTypeSelection;
//# sourceMappingURL=armour-type-selection.js.map
