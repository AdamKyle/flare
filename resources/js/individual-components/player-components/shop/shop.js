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
import { shopServiceContainer } from "./container/shop-container";
import ShopAjax, { SHOP_ACTIONS } from "./ajax/shop-ajax";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import ShopTableColumns from "./shop-table/colums/shop-table-columns";
import ItemTable from "../../../game/components/items/item-table";
import BuyMultiple from "./buy-multiple";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../game/components/ui/alerts/simple-alerts/success-alert";
import BuyAndCompare from "./buy-and-compare";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import { formatNumber } from "../../../game/lib/game/format-number";
import ShopListener from "./event-listeners/shop-listener";
var Shop = (function (_super) {
    __extends(Shop, _super);
    function Shop(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            items: [],
            item_to_view: null,
            item_to_buy_many: null,
            item_to_compare: null,
            gold: 0,
            inventory_count: 0,
            inventory_max: 0,
            is_merchant: false,
        };
        _this.ajax = shopServiceContainer().fetch(ShopAjax);
        _this.shopColumns = shopServiceContainer()
            .fetch(ShopTableColumns)
            .setComponent(_this);
        _this.shopListener = shopServiceContainer().fetch(ShopListener);
        _this.shopListener.initialize(_this, _this.props.user_id);
        _this.shopListener.register();
        return _this;
    }
    Shop.prototype.componentDidMount = function () {
        this.ajax.doShopAction(this, SHOP_ACTIONS.FETCH);
        this.shopListener.listen();
    };
    Shop.prototype.viewItem = function (itemId) {
        this.setState({
            item_to_view: this.state.items.filter(function (item) {
                return item.id === itemId;
            })[0],
        });
    };
    Shop.prototype.closeViewSection = function () {
        this.setState({
            item_to_view: null,
            item_to_buy_many: null,
            item_to_compare: null,
        });
    };
    Shop.prototype.viewBuyMany = function (item) {
        this.setState({
            item_to_buy_many: item,
        });
    };
    Shop.prototype.viewComparison = function (item) {
        this.setState({
            item_to_compare: item,
        });
    };
    Shop.prototype.setSuccessMessage = function (message) {
        this.setState({
            success_message: message,
        });
    };
    Shop.prototype.render = function () {
        if (this.state.items.length === 0) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            React.Fragment,
            null,
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(
                BasicCard,
                { additionalClasses: "my-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement("strong", null, "Your Gold:"),
                    " ",
                    formatNumber(this.state.gold),
                ),
                React.createElement(
                    "div",
                    { className: "my-3" },
                    React.createElement(
                        "strong",
                        null,
                        "Current inventory space:",
                    ),
                    " ",
                    formatNumber(this.state.inventory_count),
                    "/",
                    formatNumber(this.state.inventory_max),
                ),
            ),
            this.state.item_to_compare !== null
                ? React.createElement(BuyAndCompare, {
                      character_id: this.props.character_id,
                      item: this.state.item_to_compare,
                      close_view_buy_and_compare:
                          this.closeViewSection.bind(this),
                      set_success_message: this.setSuccessMessage.bind(this),
                  })
                : this.state.item_to_buy_many !== null
                  ? React.createElement(BuyMultiple, {
                        character_id: this.props.character_id,
                        close_view_buy_many: this.closeViewSection.bind(this),
                        inventory_count: this.state.inventory_count,
                        inventory_max: this.state.inventory_max,
                        character_gold: this.state.gold,
                        is_merchant: this.state.is_merchant,
                        item: this.state.item_to_buy_many,
                    })
                  : React.createElement(
                        "div",
                        null,
                        this.state.error_message !== null
                            ? React.createElement(
                                  DangerAlert,
                                  { additional_css: "my-4" },
                                  this.state.error_message,
                              )
                            : null,
                        this.state.success_message !== null
                            ? React.createElement(
                                  SuccessAlert,
                                  { additional_css: "my-4" },
                                  this.state.success_message,
                              )
                            : null,
                        React.createElement(ItemTable, {
                            items: this.state.items,
                            item_to_view: this.state.item_to_view,
                            close_view_item_action:
                                this.closeViewSection.bind(this),
                            close_view_item_label: "Back to Shop",
                            table_columns: this.shopColumns.buildColumns(
                                this.viewItem.bind(this),
                                this.viewBuyMany.bind(this),
                                this.viewComparison.bind(this),
                            ),
                        }),
                    ),
        );
    };
    return Shop;
})(React.Component);
export default Shop;
//# sourceMappingURL=shop.js.map
