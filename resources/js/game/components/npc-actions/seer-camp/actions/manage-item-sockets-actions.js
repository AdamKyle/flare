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
import PrimaryButton from "../../../ui/buttons/primary-button";
var ManageItemSocketsActions = (function (_super) {
    __extends(ManageItemSocketsActions, _super);
    function ManageItemSocketsActions(props) {
        return _super.call(this, props) || this;
    }
    ManageItemSocketsActions.prototype.doAction = function () {
        this.props.do_action("roll-sockets");
    };
    ManageItemSocketsActions.prototype.closeAction = function () {
        this.props.do_action("close-seer-action");
    };
    ManageItemSocketsActions.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(PrimaryButton, {
                button_label: "Roll Sockets",
                on_click: this.doAction.bind(this),
                disabled: this.props.is_disabled || this.props.is_loading,
            }),
            this.props.children,
            React.createElement(
                "a",
                {
                    href: "/information/seer",
                    target: "_blank",
                    className: "relative top-[20px] md:top-[0px] ml-2",
                },
                "Help ",
                React.createElement("i", {
                    className: "fas fa-external-link-alt",
                }),
            ),
        );
    };
    return ManageItemSocketsActions;
})(React.Component);
export default ManageItemSocketsActions;
//# sourceMappingURL=manage-item-sockets-actions.js.map
