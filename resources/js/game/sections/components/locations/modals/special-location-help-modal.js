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
import HelpDialogue from "../../../../components/ui/dialogue/help-dialogue";
var SpecialLocationHelpModal = (function (_super) {
    __extends(SpecialLocationHelpModal, _super);
    function SpecialLocationHelpModal(props) {
        return _super.call(this, props) || this;
    }
    SpecialLocationHelpModal.prototype.render = function () {
        return React.createElement(
            HelpDialogue,
            {
                is_open: true,
                manage_modal: this.props.manage_modal,
                title: "Speical Locations",
            },
            React.createElement(
                "p",
                { className: "my-2" },
                "This is a special location which contains the same monsters you have been fighting but they are much stronger here. Players will want to have",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/voidance", target: "_blank" },
                    "Devouring Darkness and Light",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                " ",
                React.createElement(
                    "a",
                    { href: "/information/quest-items", target: "_blank" },
                    "Quest items ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                    " ",
                ),
                "Which you can get from completing various:",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/quests", target: "_blank" },
                    "Quests ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                " ",
                "with in the game.",
            ),
            React.createElement(
                "p",
                null,
                "These places offer specific quest items that drop. You cannot explore here to have them drop for you. You must manually fight the monsters. You will want your looting skill raised as high as you can as we only use a max of 45% of the skill, even if the skill bonus is 100%. You can read more about special locations and see their drops by reading:",
                " ",
                React.createElement(
                    "a",
                    {
                        href: "/information/special-locations",
                        target: "_blank",
                    },
                    "Special Locations",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                ".",
            ),
        );
    };
    return SpecialLocationHelpModal;
})(React.Component);
export default SpecialLocationHelpModal;
//# sourceMappingURL=special-location-help-modal.js.map
