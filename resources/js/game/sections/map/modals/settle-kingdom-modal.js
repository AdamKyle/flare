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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
var SettleKingdomModal = (function (_super) {
    __extends(SettleKingdomModal, _super);
    function SettleKingdomModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            kingdom_name: "",
            error_message: "",
            success_message: "",
            loading: false,
        };
        return _this;
    }
    SettleKingdomModal.prototype.setName = function (e) {
        this.setState({
            kingdom_name: e.target.value,
        });
    };
    SettleKingdomModal.prototype.canSettleHere = function () {
        return (
            this.state.kingdom_name.length < 5 ||
            this.state.kingdom_name.length > 30 ||
            this.state.loading ||
            this.props.can_settle
        );
    };
    SettleKingdomModal.prototype.settleKingdom = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: "",
                success_message: "",
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdoms/" + _this.props.character_id + "/settle",
                    )
                    .setParameters({
                        name: _this.state.kingdom_name,
                    })
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState({
                                loading: false,
                                success_message: response.data.message,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    loading: false,
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    SettleKingdomModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Settle New Kingdom",
                secondary_actions: {
                    handle_action: this.settleKingdom.bind(this),
                    secondary_button_disabled: this.canSettleHere(),
                    secondary_button_label: "Settle",
                },
            },
            React.createElement(
                "p",
                { className: "mb-4 mt-2" },
                "Checkout",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/kingdoms", target: "_blank" },
                    "Kingdom's Help",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                " ",
                "for more info. ",
                React.createElement("br", null),
                React.createElement("br", null),
                "Kingdom names can be 5-30 characters long.",
            ),
            React.createElement(
                "p",
                { className: "mb-4" },
                "Each additional kingdom beyond the first costs 10,000 Gold. This includes switching Planes.",
                React.createElement("br", null),
                "Losing all your kingdoms to war or neglect or abandonment - across all planes, resets the cost to 0.",
            ),
            React.createElement(
                "div",
                { className: "flex items-center mb-5" },
                React.createElement("label", { className: "w-[50px]" }, "Name"),
                React.createElement(
                    "div",
                    { className: "w-2/3" },
                    React.createElement("input", {
                        type: "text",
                        value: this.state.kingdom_name,
                        onChange: this.setName.bind(this),
                        className: "form-control",
                        disabled: this.state.loading,
                        minLength: 5,
                        maxLength: 30,
                    }),
                ),
            ),
            this.state.error_message !== ""
                ? React.createElement(
                      DangerAlert,
                      null,
                      this.state.error_message,
                  )
                : null,
            this.state.success_message !== ""
                ? React.createElement(
                      SuccessAlert,
                      null,
                      this.state.success_message,
                  )
                : null,
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return SettleKingdomModal;
})(React.Component);
export default SettleKingdomModal;
//# sourceMappingURL=settle-kingdom-modal.js.map
