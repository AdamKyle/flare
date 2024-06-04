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
import clsx from "clsx";
var SkillTreeNode = (function (_super) {
    __extends(SkillTreeNode, _super);
    function SkillTreeNode(props) {
        return _super.call(this, props) || this;
    }
    SkillTreeNode.prototype.isActive = function () {
        if (this.props.skill.parent_level_needed === null) {
            return true;
        }
        return !this.props.is_locked;
    };
    SkillTreeNode.prototype.isMaxLevel = function () {
        return (
            this.props.skill_progression.current_level ===
            this.props.skill_progression.item_skill.max_level
        );
    };
    SkillTreeNode.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            null,
            React.createElement(
                "button",
                {
                    onClick: function () {
                        return _this.props.show_passive_modal(
                            _this.props.skill,
                            _this.props.skill_progression,
                        );
                    },
                },
                React.createElement(
                    "h4",
                    {
                        className: clsx({
                            "text-item-skill-training-300 dark:text-item-skill-training-600":
                                this.props.skill_progression.is_training,
                            "text-red-500 dark:text-red-400":
                                this.props.is_locked,
                            "text-green-700 dark:text-green-600":
                                this.props.skill_progression.current_level ===
                                this.props.skill.max_level,
                            "text-blue-500 dark:text-blue-400":
                                this.isActive() &&
                                this.props.skill_progression.current_level <
                                    this.props.skill.max_level,
                        }),
                    },
                    this.props.skill_progression.is_training
                        ? React.createElement("i", {
                              className: "ra ra-broadsword",
                          })
                        : null,
                    " ",
                    this.props.skill.name,
                ),
            ),
            React.createElement(
                "p",
                { className: "mt-3" },
                "Level: ",
                this.props.skill_progression.current_level,
                "/",
                this.props.skill_progression.item_skill.max_level,
            ),
            !this.isMaxLevel()
                ? React.createElement(
                      "p",
                      { className: "mt-3" },
                      "Kills till next level:",
                      " ",
                      this.props.skill_progression.current_kill,
                      "/",
                      this.props.skill_progression.item_skill
                          .total_kills_needed,
                  )
                : React.createElement(
                      "p",
                      { className: "text-green-700 dark:text-green-600 mt-3" },
                      "Skill is maxed out!",
                  ),
        );
    };
    return SkillTreeNode;
})(React.Component);
export default SkillTreeNode;
//# sourceMappingURL=skill-tree-node.js.map
