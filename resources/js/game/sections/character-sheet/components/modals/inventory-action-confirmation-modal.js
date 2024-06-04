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
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
var InventoryActionConfirmationModal = (function (_super) {
    __extends(InventoryActionConfirmationModal, _super);
    function InventoryActionConfirmationModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            error_message: null,
        };
        return _this;
    }
    InventoryActionConfirmationModal.prototype.confirm = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                var ajax = new Ajax().setRoute(_this.props.url);
                if (_this.props.ajax_params) {
                    ajax = ajax.setParameters(_this.props.ajax_params);
                }
                ajax.doAjaxCall(
                    "post",
                    function (result) {
                        _this.setState(
                            {
                                loading: false,
                            },
                            function () {
                                if (result.data.hasOwnProperty("inventory")) {
                                    _this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                }
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
            },
        );
    };
    InventoryActionConfirmationModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.title,
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    secondary_button_disabled: this.state.loading,
                    secondary_button_label: "Yes. I understand.",
                    handle_action: this.confirm.bind(this),
                },
                large_modal: this.props.is_large_modal,
            },
            this.props.children,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      null,
                      this.state.error_message,
                  )
                : null,
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return InventoryActionConfirmationModal;
})(React.Component);
export default InventoryActionConfirmationModal;
//# sourceMappingURL=inventory-action-confirmation-modal.js.map
