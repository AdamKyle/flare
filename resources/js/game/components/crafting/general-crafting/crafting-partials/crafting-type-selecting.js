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
var CraftingTypeSelection = (function (_super) {
    __extends(CraftingTypeSelection, _super);
    function CraftingTypeSelection(props) {
        var _this = _super.call(this, props) || this;
        _this.selectableTypes = [
            {
                label: "General Weapons",
                value: "weapon",
            },
            {
                label: "Staves",
                value: "stave",
            },
            {
                label: "Hammers",
                value: "hammer",
            },
            {
                label: "Bows",
                value: "bow",
            },
            {
                label: "Guns",
                value: "gun",
            },
            {
                label: "Fans",
                value: "fan",
            },
            {
                label: "Maces",
                value: "mace",
            },
            {
                label: "Scratch Awls",
                value: "scratch-awl",
            },
            {
                label: "Armour",
                value: "armour",
            },
            {
                label: "Rings",
                value: "ring",
            },
            {
                label: "Spells",
                value: "spell",
            },
        ];
        return _this;
    }
    CraftingTypeSelection.prototype.defaultCraftingType = function () {
        return { label: "Please select type to craft", value: "" };
    };
    CraftingTypeSelection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(Select, {
                onChange: this.props.select_type_to_craft.bind(this),
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
                'When it comes to weapons there are general "weapons" that any one can use, then there are specialty weapons: Hammers, Staves, Bows, Guns, Fans, Maces and Scratch Awls for Weapon Crafting. You can craft ANY of these types to gain levels.',
            ),
        );
    };
    return CraftingTypeSelection;
})(React.Component);
export default CraftingTypeSelection;
//# sourceMappingURL=crafting-type-selecting.js.map
