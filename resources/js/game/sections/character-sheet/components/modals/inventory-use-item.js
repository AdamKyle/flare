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
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../lib/ajax/ajax";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import AlchemyItemUsable from "../../../../components/modals/item-details/item-views/alchemy-item-usable";
var InventoryUseItem = (function (_super) {
    __extends(InventoryUseItem, _super);
    function InventoryUseItem(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            error_message: null,
        };
        return _this;
    }
    InventoryUseItem.prototype.useItem = function () {
        var _this = this;
        this.setState({
            loading: true,
            error_message: null,
        });
        new Ajax()
            .setRoute(
                "character/" +
                    this.props.character_id +
                    "/inventory/use-item/" +
                    this.props.item.item_id,
            )
            .doAjaxCall(
                "post",
                function (result) {
                    _this.setState(
                        {
                            loading: false,
                        },
                        function () {
                            _this.props.update_inventory(result.data.inventory);
                            _this.props.set_success_message(
                                result.data.message,
                            );
                            _this.props.manage_modal();
                        },
                    );
                },
                function (error) {
                    _this.setState({ loading: false });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    InventoryUseItem.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: React.createElement(
                    "span",
                    { className: "text-pink-500 dark:text-pink-300" },
                    this.props.item.item_name,
                ),
                secondary_actions: {
                    secondary_button_disabled: false,
                    secondary_button_label: "Use item",
                    handle_action: function () {
                        return _this.useItem();
                    },
                },
            },
            React.createElement(
                "div",
                { className: "mb-5" },
                React.createElement(AlchemyItemUsable, {
                    item: this.props.item,
                }),
                this.state.error_message !== null
                    ? React.createElement(
                          DangerAlert,
                          { additional_css: "my-4" },
                          this.state.error_message,
                      )
                    : null,
                this.state.loading
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
            ),
        );
    };
    return InventoryUseItem;
})(React.Component);
export default InventoryUseItem;
//# sourceMappingURL=inventory-use-item.js.map
