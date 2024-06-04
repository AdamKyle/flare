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
import HelpDialogue from "../../../components/ui/dialogue/help-dialogue";
var TeleportHelpModal = (function (_super) {
    __extends(TeleportHelpModal, _super);
    function TeleportHelpModal(props) {
        return _super.call(this, props) || this;
    }
    TeleportHelpModal.prototype.render = function () {
        return React.createElement(
            HelpDialogue,
            {
                is_open: true,
                manage_modal: this.props.manage_modal,
                title: "Timeout Help",
            },
            React.createElement(
                "p",
                { className: "my-2" },
                "When it comes to the teleport timeout, you have a skill called",
                " ",
                React.createElement(
                    "a",
                    {
                        href: "/information/skill-information",
                        target: "_blank",
                    },
                    "Quick Feet ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                ", which if raised over time, will reduce the movement time out of teleporting down from the current value to 1 minute, regardless of distance.",
            ),
            React.createElement(
                "p",
                null,
                "You can find this on your character sheet, under Skills. You can sacrifice a % of your XP from monsters in order to level the skill over time, by clicking train on Quick Feet and then selecting the amount of XP to sacrifice between 10-100%.",
            ),
        );
    };
    return TeleportHelpModal;
})(React.Component);
export default TeleportHelpModal;
//# sourceMappingURL=teleport-help-modal.js.map
