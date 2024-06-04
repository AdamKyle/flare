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
import BasicCard from "../../../game/components/ui/cards/basic-card";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import PrimaryOutlineButton from "../../../game/components/ui/buttons/primary-outline-button";
import ShopAjax, { SHOP_ACTIONS } from "./ajax/shop-ajax";
import { shopServiceContainer } from "./container/shop-container";
import ItemComparison from "../../../game/components/item-comparison/item-comparison";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import { ItemType } from "../../../game/components/items/enums/item-type";
var BuyAndCompare = (function (_super) {
    __extends(BuyAndCompare, _super);
    function BuyAndCompare(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            comparison_data: null,
            error_message: null,
            success_message: null,
            is_showing_expanded_comparison: false,
        };
        _this.ajax = shopServiceContainer().fetch(ShopAjax);
        return _this;
    }
    BuyAndCompare.prototype.componentDidMount = function () {
        var weaponTypes = [
            ItemType.WEAPON,
            ItemType.GUN,
            ItemType.FAN,
            ItemType.MACE,
            ItemType.SCRATCH_AWL,
            ItemType.BOW,
            ItemType.HAMMER,
        ];
        var type = weaponTypes.includes(this.props.item.type)
            ? ItemType.WEAPON
            : this.props.item.type;
        this.ajax.doShopAction(this, SHOP_ACTIONS.COMPARE, {
            item_name: this.props.item.name,
            item_type: type,
        });
    };
    BuyAndCompare.prototype.buyAndReplaceItem = function (positionSelected) {
        var _this = this;
        if (this.state.comparison_data === null) {
            return;
        }
        this.setState(
            {
                error_message: null,
                success_message: null,
            },
            function () {
                var _a;
                if (_this.state.comparison_data === null) {
                    return;
                }
                var position =
                    (_a = _this.state.comparison_data.slotPosition) !== null &&
                    _a !== void 0
                        ? _a
                        : _this.state.comparison_data.itemToEquip.type;
                if (positionSelected) {
                    position = positionSelected;
                }
                _this.ajax.doShopAction(_this, SHOP_ACTIONS.BUY_AND_REPLACE, {
                    position: position,
                    item_id_to_buy: _this.state.comparison_data.itemToEquip.id,
                    equip_type: _this.state.comparison_data.itemToEquip.type,
                    slot_id: _this.state.comparison_data.slotId,
                });
            },
        );
    };
    BuyAndCompare.prototype.updateIsShowingExpandedLocation = function () {
        this.setState({
            is_showing_expanded_comparison:
                !this.state.is_showing_expanded_comparison,
        });
    };
    BuyAndCompare.prototype.render = function () {
        if (this.state.comparison_data === null) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(PrimaryOutlineButton, {
                button_label: this.state.is_showing_expanded_comparison
                    ? "Back to Comparison"
                    : "Back to shop",
                on_click: this.state.is_showing_expanded_comparison
                    ? this.updateIsShowingExpandedLocation.bind(this)
                    : this.props.close_view_buy_and_compare,
            }),
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
            React.createElement(
                BasicCard,
                { additionalClasses: "my-4" },
                React.createElement(ItemComparison, {
                    comparison_info: this.state.comparison_data,
                    is_showing_expanded_comparison:
                        this.state.is_showing_expanded_comparison,
                    manage_show_expanded_comparison:
                        this.updateIsShowingExpandedLocation.bind(this),
                    handle_replace_action: this.buyAndReplaceItem.bind(this),
                    replace_button_text: "Buy and Replace",
                }),
            ),
        );
    };
    return BuyAndCompare;
})(React.Component);
export default BuyAndCompare;
//# sourceMappingURL=buy-and-compare.js.map
