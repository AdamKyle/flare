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
var AddGemsToItemActions = (function (_super) {
    __extends(AddGemsToItemActions, _super);
    function AddGemsToItemActions(props) {
        return _super.call(this, props) || this;
    }
    AddGemsToItemActions.prototype.viewGem = function () {
        this.props.do_action("view-gem");
    };
    AddGemsToItemActions.prototype.compareGem = function () {
        this.props.do_action("compare-gem");
    };
    AddGemsToItemActions.prototype.attachGem = function () {
        this.props.do_action("attach-gem");
    };
    AddGemsToItemActions.prototype.closeAction = function () {
        this.props.do_action("close-seer-action");
    };
    AddGemsToItemActions.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(PrimaryButton, {
                button_label: "Attach Gem",
                on_click: this.attachGem.bind(this),
                disabled: this.props.is_disabled || this.props.is_loading,
                additional_css: "ml-2",
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
    return AddGemsToItemActions;
})(React.Component);
export default AddGemsToItemActions;
//# sourceMappingURL=add-gems-to-item-actions.js.map
