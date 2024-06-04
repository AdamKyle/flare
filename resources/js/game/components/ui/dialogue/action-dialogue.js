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
import Dialogue from "./dialogue";
import LoadingProgressBar from "../progress-bars/loading-progress-bar";
var ActionDialogue = (function (_super) {
    __extends(ActionDialogue, _super);
    function ActionDialogue(props) {
        return _super.call(this, props) || this;
    }
    ActionDialogue.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.title,
                primary_button_disabled: this.props.loading,
                secondary_actions: {
                    secondary_button_disabled: this.props.loading,
                    secondary_button_label: "Yes. I understand.",
                    handle_action: this.props.do_action.bind(this),
                },
            },
            this.props.children,
            this.props.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return ActionDialogue;
})(React.Component);
export default ActionDialogue;
//# sourceMappingURL=action-dialogue.js.map
