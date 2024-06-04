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
var ReincarnationCheckModal = (function (_super) {
    __extends(ReincarnationCheckModal, _super);
    function ReincarnationCheckModal(props) {
        return _super.call(this, props) || this;
    }
    ReincarnationCheckModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: true,
                handle_close: this.props.manage_modal,
                title: "Are you sure?",
                secondary_actions: {
                    secondary_button_disabled: false,
                    secondary_button_label: "Yes I am sure",
                    handle_action: this.props.handle_reincarnate,
                },
            },
            React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    React.createElement(
                        "strong",
                        null,
                        "This action cannot be undone!",
                    ),
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "Are you sure you want to reincarnate? Here's what will happen:",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "Your level will be reset to one, you will",
                    " ",
                    React.createElement("strong", null, "lose nothing else"),
                    ". 20% of your current raw stats will be applied to your level 1 base raw stats. You will then level back up to max level and reincarnate again to get even stronger.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "You can reincarnate at anytime, for the cost of 50,000 Copper Coins. Each time you do we add 5%, which stacks with how many times you have reincarnated, to your XP required to level up, which over time can make it take longer and longer to level up, but your character gets even stronger.",
                ),
            ),
        );
    };
    return ReincarnationCheckModal;
})(React.Component);
export default ReincarnationCheckModal;
//# sourceMappingURL=reincarnation-check-modal.js.map
