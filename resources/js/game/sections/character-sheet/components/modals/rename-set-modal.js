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
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
var RenameSetModal = (function (_super) {
    __extends(RenameSetModal, _super);
    function RenameSetModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            new_set_name: _this.props.current_set_name,
            error_message: null,
        };
        return _this;
    }
    RenameSetModal.prototype.updateName = function (e) {
        var value = e.target.value;
        if (value.length > 20) {
            this.setState({
                error_message:
                    "Name must be shorter then 20 characters (including spaces)",
                new_set_name: value,
            });
        } else {
            this.setState({
                new_set_name: value,
                error_message: null,
            });
        }
    };
    RenameSetModal.prototype.renameSet = function () {
        this.props.rename_set(this.state.new_set_name);
        this.props.manage_modal();
    };
    RenameSetModal.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.title,
                secondary_actions: {
                    secondary_button_disabled:
                        this.state.error_message !== null,
                    secondary_button_label: "Rename",
                    handle_action: function () {
                        return _this.renameSet();
                    },
                },
            },
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "mt-4 mb-4" },
                      this.state.error_message,
                  )
                : null,
            React.createElement(
                "div",
                { className: "mb-5" },
                React.createElement(
                    "label",
                    { className: "label block mb-2", htmlFor: "set-name" },
                    "Set Name",
                ),
                React.createElement("input", {
                    id: "set-name",
                    type: "text",
                    className: "form-control",
                    name: "set-name",
                    value: this.state.new_set_name,
                    autoFocus: true,
                    onChange: this.updateName.bind(this),
                }),
                React.createElement(
                    "p",
                    { className: "text-xs text-gray-600 dark:text-gray-400" },
                    "Names can only be 20 characters long (including spaces)",
                ),
            ),
        );
    };
    return RenameSetModal;
})(React.Component);
export default RenameSetModal;
//# sourceMappingURL=rename-set-modal.js.map
