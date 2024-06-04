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
var ChangeNameModal = (function (_super) {
    __extends(ChangeNameModal, _super);
    function ChangeNameModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            name: _this.props.name,
            loading: false,
            error_message: "",
        };
        return _this;
    }
    ChangeNameModal.prototype.setName = function (e) {
        this.setState({
            name: e.target.value,
            error_message: "",
        });
    };
    ChangeNameModal.prototype.rename = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setParameters({
                        name: _this.state.name,
                    })
                    .setRoute("kingdom/" + _this.props.kingdom_id + "/rename")
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.handle_close();
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
                            console.error(error);
                        },
                    );
            },
        );
    };
    ChangeNameModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Re-name Kingdom",
                secondary_actions: {
                    handle_action: this.rename.bind(this),
                    secondary_button_disabled:
                        this.state.name.length === 0 ||
                        this.state.name === this.props.name,
                    secondary_button_label: "Rename Kingdom",
                },
            },
            React.createElement(
                "div",
                { className: "flex items-center mb-5" },
                React.createElement("label", { className: "w-[50px]" }, "Name"),
                React.createElement(
                    "div",
                    { className: "w-2/3" },
                    React.createElement("input", {
                        type: "text",
                        value: this.state.name,
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
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return ChangeNameModal;
})(React.Component);
export default ChangeNameModal;
//# sourceMappingURL=change-name-modal.js.map
