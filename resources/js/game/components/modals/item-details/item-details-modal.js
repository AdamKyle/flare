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
import Dialogue from "../../ui/dialogue/dialogue";
import ItemDetailsModalTitle from "./item-details-modal-title";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import ItemView from "./item-view";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import ItemComparisonAjax from "./ajax/item-comparison-ajax";
var ItemDetailsModal = (function (_super) {
    __extends(ItemDetailsModal, _super);
    function ItemDetailsModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            comparison_details: null,
            usable_sets: [],
            action_loading: false,
            loading: true,
            dark_charts: false,
            error_message: null,
            is_showing_expanded_details: false,
            secondary_actions: null,
        };
        _this.ajax = serviceContainer().fetch(ItemComparisonAjax);
        return _this;
    }
    ItemDetailsModal.prototype.componentDidMount = function () {
        this.ajax.fetchChatComparisonData(this);
    };
    ItemDetailsModal.prototype.manageShowingExpandedDetails = function () {
        var _this = this;
        this.setState(
            {
                is_showing_expanded_details:
                    !this.state.is_showing_expanded_details,
            },
            function () {
                if (!_this.state.is_showing_expanded_details) {
                    return _this.setState({ secondary_actions: null });
                }
                var secondaryAction = {
                    secondary_button_disabled: false,
                    secondary_button_label: "Back to comparison",
                    handle_action:
                        _this.manageShowingExpandedDetails.bind(_this),
                };
                return _this.setState({
                    secondary_actions: secondaryAction,
                });
            },
        );
    };
    ItemDetailsModal.prototype.buildTitle = function () {
        if (this.state.error_message !== null) {
            return "Uh oh. Something went wrong.";
        }
        if (this.state.comparison_details === null) {
            return "Loading comparison data ...";
        }
        return React.createElement(ItemDetailsModalTitle, {
            itemToEquip: this.state.comparison_details.itemToEquip,
        });
    };
    ItemDetailsModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.buildTitle(),
                large_modal: true,
                primary_button_disabled: this.state.action_loading,
                secondary_actions: this.state.secondary_actions,
            },
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : this.state.error_message !== null
                  ? React.createElement(
                        DangerAlert,
                        null,
                        this.state.error_message,
                    )
                  : this.state.comparison_details !== null
                    ? React.createElement(ItemView, {
                          comparison_details: this.state.comparison_details,
                          usable_sets: this.state.usable_sets,
                          manage_showing_expanded_section:
                              this.manageShowingExpandedDetails.bind(this),
                          is_showing_expanded_section:
                              this.state.is_showing_expanded_details,
                          manage_modal: this.props.manage_modal,
                          set_success_message: this.props.set_success_message,
                          update_inventory: this.props.update_inventory,
                          is_automation_running:
                              this.props.is_automation_running,
                          is_dead: this.props.is_dead,
                      })
                    : null,
        );
    };
    return ItemDetailsModal;
})(React.Component);
export default ItemDetailsModal;
//# sourceMappingURL=item-details-modal.js.map
