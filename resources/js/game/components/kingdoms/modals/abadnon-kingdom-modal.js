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
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Ajax from "../../../lib/ajax/ajax";
var AbandonKingdomModal = (function (_super) {
    __extends(AbandonKingdomModal, _super);
    function AbandonKingdomModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            error_message: "",
            loading: false,
        };
        return _this;
    }
    AbandonKingdomModal.prototype.abandonKingdom = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute("kingdoms/abandon/" + _this.props.kingdom_id)
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.handle_close();
                                    _this.props.handle_kingdom_close();
                                },
                            );
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                var message = response.data.message;
                                if (response.data.error) {
                                    message = response.data.error;
                                }
                                _this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    };
    AbandonKingdomModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Abandon Kingdom",
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    handle_action: this.abandonKingdom.bind(this),
                    secondary_button_disabled: false,
                    secondary_button_label: "Abandon",
                },
            },
            React.createElement(
                "p",
                { className: "mt-4" },
                React.createElement("strong", null, "Are you sure"),
                " you want to do this? You won't be able to abandon the kingdom if:",
            ),
            React.createElement(
                "ul",
                { className: "my-4 list-disc ml-5" },
                React.createElement("li", null, "You have units in queue"),
                React.createElement("li", null, "You have buildings in queue"),
                React.createElement(
                    "li",
                    null,
                    "You have units in movement or are under attack or units are traveling to your kingdom",
                ),
                React.createElement(
                    "li",
                    null,
                    "You have gold bars in the kingdom",
                ),
                React.createElement(
                    "li",
                    null,
                    "You have already abandoned a kingdom",
                ),
            ),
            React.createElement(
                "p",
                { className: "my-4" },
                "Abandoning kingdoms turns it into an NPC kingdom (yellow on the map). You cannot settle or purchase another kingdom for 15 minutes AFTER you have abandoned the kingdom.",
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
    return AbandonKingdomModal;
})(React.Component);
export default AbandonKingdomModal;
//# sourceMappingURL=abadnon-kingdom-modal.js.map
