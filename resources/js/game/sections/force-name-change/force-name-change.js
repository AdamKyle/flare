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
import Dialogue from "../../components/ui/dialogue/dialogue";
import Ajax from "../../lib/ajax/ajax";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
var ForceNameChange = (function (_super) {
    __extends(ForceNameChange, _super);
    function ForceNameChange(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            new_name: "",
            error_message: "",
            loading: false,
        };
        return _this;
    }
    ForceNameChange.prototype.changeName = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            _this.props.character_id +
                            "/name-change",
                    )
                    .setParameters({
                        name: _this.state.new_name,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            location.reload();
                        },
                        function (error) {
                            var response = null;
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                response = error.response;
                                _this.setState({
                                    error_message: response.data.errors.name[0],
                                });
                            }
                        },
                    );
            },
        );
    };
    ForceNameChange.prototype.closeErrorMessage = function () {
        this.setState({
            error_message: "",
        });
    };
    ForceNameChange.prototype.updateName = function (e) {
        var name = e.target.value;
        if (name.length > 15) {
            this.setState({
                error_message: "Name is above the 15 character limit.",
                new_name: name,
            });
        } else if (name.length < 5) {
            this.setState({
                error_message: "Name is below the 5 character limit.",
                new_name: name,
            });
        } else {
            this.setState({
                new_name: name,
                error_message: "",
            });
        }
    };
    ForceNameChange.prototype.manageModal = function () {};
    ForceNameChange.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: true,
                handle_close: this.manageModal,
                primary_button_disabled: true,
                title: "Force Name Change",
                secondary_actions: {
                    secondary_button_label: "Change Name",
                    secondary_button_disabled:
                        this.state.loading ||
                        this.state.new_name === "" ||
                        this.state.error_message !== "",
                    handle_action: this.changeName.bind(this),
                },
            },
            React.createElement(
                "div",
                { className: "mb-5 relative" },
                this.state.error_message !== ""
                    ? React.createElement(
                          DangerAlert,
                          {
                              close_alert: this.closeErrorMessage.bind(this),
                              additional_css: "mb-5",
                          },
                          this.state.error_message,
                      )
                    : null,
                React.createElement(
                    "p",
                    { className: "mb-5" },
                    "The Creator has decided that your name violates the rules of the game or is offensive to other players. You are being forced to change your name. Even if you logout and back in, you will still see this modal. Failure to change your name, or use of third party tools to get around this, will result in an immediate ban.",
                ),
                React.createElement(
                    "div",
                    { className: "mb-5" },
                    React.createElement(
                        "label",
                        { className: "label block mb-2", htmlFor: "set-name" },
                        "New Character Name",
                    ),
                    React.createElement("input", {
                        id: "set-name",
                        type: "text",
                        className: "form-control",
                        name: "set-name",
                        value: this.state.new_name,
                        autoFocus: true,
                        onChange: this.updateName.bind(this),
                    }),
                    React.createElement(
                        "p",
                        {
                            className:
                                "text-xs text-gray-600 dark:text-gray-400",
                        },
                        "Character names may not contain spaces an can only be 15 characters long (5 characters min) and only contain letters and numbers (of any case).",
                    ),
                ),
                this.state.loading
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
            ),
        );
    };
    return ForceNameChange;
})(React.Component);
export default ForceNameChange;
//# sourceMappingURL=force-name-change.js.map
