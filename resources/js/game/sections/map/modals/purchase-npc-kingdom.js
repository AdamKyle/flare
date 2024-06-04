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
var PurchaseNpcKingdom = (function (_super) {
    __extends(PurchaseNpcKingdom, _super);
    function PurchaseNpcKingdom(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            kingdom_name: "",
            error_message: "",
            loading: false,
        };
        return _this;
    }
    PurchaseNpcKingdom.prototype.componentDidMount = function () {
        this.setState({
            kingdom_name: this.props.kingdom_name,
        });
    };
    PurchaseNpcKingdom.prototype.setName = function (e) {
        this.setState({
            kingdom_name: e.target.value,
        });
    };
    PurchaseNpcKingdom.prototype.purchaseKingdom = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdoms/" +
                            _this.props.character_id +
                            "/purchase-npc-kingdom",
                    )
                    .setParameters({
                        name: _this.state.kingdom_name,
                        kingdom_id: _this.props.kingdom_id,
                    })
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.handle_close(true);
                                },
                            );
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
    PurchaseNpcKingdom.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: function () {
                    return _this.props.handle_close(false);
                },
                title: "Purchase NPC Kingdom",
                secondary_actions: {
                    handle_action: this.purchaseKingdom.bind(this),
                    secondary_button_disabled:
                        this.state.kingdom_name.length < 5 ||
                        this.state.kingdom_name.length > 30 ||
                        this.state.loading,
                    secondary_button_label: "Purchase",
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
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return PurchaseNpcKingdom;
})(React.Component);
export default PurchaseNpcKingdom;
//# sourceMappingURL=purchase-npc-kingdom.js.map
