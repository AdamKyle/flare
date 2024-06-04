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
var AddGemsToItem = (function (_super) {
    __extends(AddGemsToItem, _super);
    function AddGemsToItem(props) {
        return _super.call(this, props) || this;
    }
    AddGemsToItem.prototype.createItemsOptions = function () {
        if (this.props.items.length === 0) {
            return [
                {
                    label: "Please select item",
                    value: 0,
                },
            ];
        }
        var items = this.props.items
            .filter(function (item) {
                return item.socket_amount > 0;
            })
            .map(function (item) {
                return {
                    label: item.name,
                    value: item.slot_id,
                };
            });
        items.unshift({
            label: "Please select item",
            value: 0,
        });
        return items;
    };
    AddGemsToItem.prototype.createGemOptions = function () {
        var gems = this.props.gems.map(function (gem) {
            return {
                label:
                    gem.name +
                    " (Amount: " +
                    gem.amount +
                    ", Tier: " +
                    gem.tier +
                    ")",
                value: gem.slot_id,
            };
        });
        gems.unshift({
            label: "Please select gem",
            value: 0,
        });
        return gems;
    };
    AddGemsToItem.prototype.setItemToUse = function (data) {
        this.props.update_parent(data.value, "item_selected");
    };
    AddGemsToItem.prototype.setGemToUse = function (data) {
        this.props.update_parent(data.value, "gem_selected");
    };
    AddGemsToItem.prototype.defaultValueForItems = function () {
        var _this = this;
        var item = this.props.items.filter(function (item) {
            return item.slot_id === _this.props.item_selected;
        });
        if (item.length > 0) {
            return { label: item[0].name, value: item[0].slot_id };
        }
        return {
            label: "Please select item",
            value: 0,
        };
    };
    AddGemsToItem.prototype.defaultValueForGems = function () {
        var _this = this;
        var gem = this.props.gems.filter(function (gem) {
            return gem.slot_id === _this.props.gem_selected;
        });
        if (gem.length > 0) {
            return {
                label:
                    gem[0].name +
                    " (Amount: " +
                    gem[0].amount +
                    ", Tier: " +
                    gem[0].tier +
                    ")",
                value: gem[0].slot_id,
            };
        }
        return {
            label: "Please select gem",
            value: 0,
        };
    };
    AddGemsToItem.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "mb-3" },
                React.createElement(Select, {
                    onChange: this.setItemToUse.bind(this),
                    options: this.createItemsOptions(),
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
                    value: this.defaultValueForItems(),
                }),
            ),
            React.createElement(Select, {
                onChange: this.setGemToUse.bind(this),
                options: this.createGemOptions(),
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
                value: this.defaultValueForGems(),
            }),
        );
    };
    return AddGemsToItem;
})(React.Component);
export default AddGemsToItem;
//# sourceMappingURL=add-gems-to-item.js.map
