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
import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import Select from "react-select";
import UseManyItems from "../../../../lib/game/character-sheet/ajax/use-many-items";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
var InventoryUseManyItems = (function (_super) {
    __extends(InventoryUseManyItems, _super);
    function InventoryUseManyItems(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            selected_items: [],
            error_message: null,
            using_item: null,
            item_progress: 0,
        };
        return _this;
    }
    InventoryUseManyItems.prototype.useManyItems = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            function () {
                var items = _this.props.items
                    .filter(function (item) {
                        return _this.state.selected_items.includes(
                            item.slot_id,
                        );
                    })
                    .map(function (item) {
                        return item.slot_id;
                    });
                new UseManyItems(items, _this).useAllItems(
                    _this.props.character_id,
                );
            },
        );
    };
    InventoryUseManyItems.prototype.setItemsToUse = function (data) {
        if (data.length > 10) {
            this.setState({
                error_message: "You may only apply 10 boons.",
            });
        } else {
            this.setState({
                error_message: null,
                selected_items: data.map(function (data) {
                    return data.value;
                }),
            });
        }
    };
    InventoryUseManyItems.prototype.buildItems = function () {
        return this.props.items
            .filter(function (item) {
                return !item.damages_kingdoms && item.usable && item.can_stack;
            })
            .map(function (item) {
                return {
                    label:
                        item.item_name +
                        " Lasts for: " +
                        item.lasts_for +
                        " minutes",
                    value: item.slot_id,
                };
            });
    };
    InventoryUseManyItems.prototype.defaultItem = function () {
        var _this = this;
        if (this.state.selected_items.length === 0) {
            return [];
        }
        return this.props.items
            .filter(function (item) {
                return _this.state.selected_items.includes(item.slot_id);
            })
            .map(function (item) {
                return {
                    label:
                        item.item_name +
                        " Lasts for: " +
                        item.lasts_for +
                        " minutes",
                    value: item.slot_id,
                };
            });
    };
    InventoryUseManyItems.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "Use many items",
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    secondary_button_disabled: this.state.loading,
                    secondary_button_label: "Use selected",
                    handle_action: function () {
                        return _this.useManyItems();
                    },
                },
            },
            React.createElement(
                "div",
                { className: "mb-5" },
                React.createElement(
                    "p",
                    { className: "mt-4 mb-4 text-sky-700 dark:text-sky-500" },
                    "You may select up to 10 boons to apply to your self. Only usable items will be listed below.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "Boons stack on to of each other, making applying multiple beneficial. When a character switches to a plane like Shadow Plane, Hell or Purgatory, we recalculate your stats based on plane stat reductions based off your surface level stats. The more boons you use, the more stats you have for harder planes of existence.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "Only items that can stack will be allowed to be selected.",
                ),
                React.createElement(Select, {
                    onChange: this.setItemsToUse.bind(this),
                    options: this.buildItems(),
                    menuPosition: "absolute",
                    menuPlacement: "bottom",
                    isMulti: true,
                    styles: {
                        menuPortal: function (base) {
                            return __assign(__assign({}, base), {
                                zIndex: 9999,
                                color: "#000000",
                            });
                        },
                    },
                    menuPortalTarget: document.body,
                    value: this.defaultItem(),
                }),
                this.state.error_message !== null
                    ? React.createElement(
                          DangerAlert,
                          { additional_css: "my-4" },
                          this.state.error_message,
                      )
                    : null,
                this.state.loading
                    ? React.createElement(
                          "div",
                          { className: "mt-4 mb-4" },
                          React.createElement(LoadingProgressBar, null),
                      )
                    : null,
            ),
        );
    };
    return InventoryUseManyItems;
})(React.Component);
export default InventoryUseManyItems;
//# sourceMappingURL=inventory-use-many-items.js.map
