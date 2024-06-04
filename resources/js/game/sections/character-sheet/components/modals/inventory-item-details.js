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
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ItemNameColorationText from "../../../../components/items/item-name/item-name-coloration-text";
import Ajax from "../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import ItemDetails from "./components/item-details";
import QuestItem from "../../../../components/modals/item-details/item-views/quest-item";
var InventoryUseDetails = (function (_super) {
    __extends(InventoryUseDetails, _super);
    function InventoryUseDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            item: null,
            error_message: null,
        };
        return _this;
    }
    InventoryUseDetails.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "character/" +
                    this.props.character_id +
                    "/inventory/item/" +
                    this.props.item_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        item: result.data,
                    });
                },
                function (error) {
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            loading: false,
                            error_message: response.data.hasOwnProperty(
                                "message",
                            )
                                ? response.data.message
                                : response.data.error,
                        });
                    }
                },
            );
    };
    InventoryUseDetails.prototype.modalTitle = function () {
        if (this.state.loading) {
            return "Fetching item details ...";
        }
        if (this.state.error_message !== null) {
            return "There was an error!";
        }
        return React.createElement(ItemNameColorationText, {
            custom_width: false,
            item: this.state.item,
        });
    };
    InventoryUseDetails.prototype.largeModal = function () {
        if (this.state.item !== null) {
            return this.state.item.type !== "quest";
        }
        return false;
    };
    InventoryUseDetails.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.modalTitle(),
                large_modal: this.largeModal(),
                additional_dialogue_css: "top-[110px]",
            },
            React.createElement(
                "div",
                { className: "mb-5 relative" },
                this.state.loading
                    ? React.createElement(
                          "div",
                          { className: "py-10" },
                          React.createElement(ComponentLoading, null),
                      )
                    : this.state.error_message !== null
                      ? React.createElement(
                            Fragment,
                            null,
                            React.createElement(
                                "p",
                                {
                                    className:
                                        "my-4 text-red-500 dark:text-red-400",
                                },
                                this.state.error_message,
                            ),
                        )
                      : React.createElement(
                            Fragment,
                            null,
                            this.state.item.type === "quest"
                                ? React.createElement(QuestItem, {
                                      item: this.state.item,
                                  })
                                : React.createElement(ItemDetails, {
                                      item: this.state.item,
                                      character_id: this.props.character_id,
                                  }),
                        ),
            ),
        );
    };
    return InventoryUseDetails;
})(React.Component);
export default InventoryUseDetails;
//# sourceMappingURL=inventory-item-details.js.map
