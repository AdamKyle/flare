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
import HelpDialogue from "../../../../../components/ui/dialogue/help-dialogue";
var SkillHelpModal = (function (_super) {
    __extends(SkillHelpModal, _super);
    function SkillHelpModal(props) {
        return _super.call(this, props) || this;
    }
    SkillHelpModal.prototype.render = function () {
        return React.createElement(
            HelpDialogue,
            {
                is_open: true,
                manage_modal: this.props.manage_modal,
                title: "Sacrifice XP",
            },
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "By setting this to a percentage, any XP gained from monsters or Exploration will be reduced ",
                React.createElement("strong", null, "BEFORE"),
                " ",
                "additional modifiers and quest items are applied. This amount will then be applied to the skill XP.",
            ),
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "As you level these skills the XP will go up by 100 per skill level, that is at level 1, you need 100XP, but at level 10, you need 1000.",
            ),
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "Because skills xp will get out of control fast, there are various books and quests that increase the XP bonus per kill, players are also suggested to use Exploration for levels 5 and beyond. Do not try and master a skill, instead rely on quest items and enchantments while leveling other skills.",
            ),
        );
    };
    return SkillHelpModal;
})(React.Component);
export default SkillHelpModal;
//# sourceMappingURL=skill-help-modal.js.map
