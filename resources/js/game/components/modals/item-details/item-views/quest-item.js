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
var QuestItem = (function (_super) {
    __extends(QuestItem, _super);
    function QuestItem(props) {
        return _super.call(this, props) || this;
    }
    QuestItem.prototype.shouldRenderColumns = function () {
        return (
            this.props.item.skill_name !== null &&
            (this.props.item.devouring_light > 0 ||
                this.props.item.devouring_darkness > 0)
        );
    };
    QuestItem.prototype.renderXpBonus = function () {
        if (this.props.item.xp_bonus <= 0) {
            return;
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "h4",
                { className: "text-sky-600 dark:text-sky-300" },
                "XP Bonus",
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Xp Bonus"),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    (this.props.item.xp_bonus * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Ignores Caps?"),
                React.createElement(
                    "dd",
                    null,
                    this.props.item.ignores_caps ? "Yes" : "No",
                ),
            ),
        );
    };
    QuestItem.prototype.renderDevouringBonus = function () {
        if (
            !(
                this.props.item.devouring_light > 0 ||
                this.props.item.devouring_darkness > 0
            )
        ) {
            return;
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "h4",
                { className: "text-sky-600 dark:text-sky-300" },
                "Devouring Chance",
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Devouring Light"),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    (this.props.item.devouring_light * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Devouring Darkness"),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    (this.props.item.devouring_darkness * 100).toFixed(2),
                    "%",
                ),
            ),
        );
    };
    QuestItem.prototype.renderSkillModifierSection = function () {
        if (this.props.item.skill_name === null) {
            return;
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "h4",
                { className: "text-sky-600 dark:text-sky-300" },
                "Skill Modifiers",
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Effects Skill"),
                React.createElement("dd", null, this.props.item.skill_name),
                React.createElement("dt", null, "Skill Bonus"),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    (this.props.item.skill_bonus * 100).toFixed(2),
                    "%",
                ),
                React.createElement("dt", null, "Skill XP Bonus"),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    (this.props.item.skill_training_bonus * 100).toFixed(2),
                    "%",
                ),
            ),
        );
    };
    QuestItem.prototype.renderColumns = function () {
        if (this.props.item.skill_name === null) {
            return;
        }
        return React.createElement(
            "div",
            null,
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 ",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    null,
                    this.renderXpBonus(),
                    this.props.item.xp_bonus > 0 &&
                        (this.props.item.devouring_light > 0 ||
                            this.props.item.devouring_darkness > 0)
                        ? React.createElement("div", {
                              className:
                                  "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                          })
                        : null,
                    this.renderDevouringBonus(),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden",
                }),
                React.createElement(
                    "div",
                    null,
                    this.renderSkillModifierSection(),
                ),
            ),
        );
    };
    QuestItem.prototype.renderSingleItem = function () {
        if (
            this.props.item.skill_name === null &&
            !(
                this.props.item.devouring_light > 0 ||
                this.props.item.devouring_darkness > 0
            ) &&
            this.props.item.xp_bonus <= 0
        ) {
            return;
        }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 ",
            }),
            React.createElement(
                "div",
                {
                    className: clsx("mb-4", {
                        hidden: this.props.item.xp_bonus <= 0,
                    }),
                },
                this.renderXpBonus(),
            ),
            React.createElement(
                "div",
                {
                    className: clsx("mb-4", {
                        hidden: !(
                            this.props.item.devouring_light > 0 ||
                            this.props.item.devouring_darkness > 0
                        ),
                    }),
                },
                this.renderDevouringBonus(),
            ),
            React.createElement(
                "div",
                {
                    className: clsx("mb-4", {
                        hidden: this.props.item.skill_name === null,
                    }),
                },
                this.renderSkillModifierSection(),
            ),
        );
    };
    QuestItem.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "max-h-[400px] overflow-y-auto" },
            React.createElement("div", {
                className: "mb-4 mt-4 text-sky-700 dark:text-sky-300",
                dangerouslySetInnerHTML: {
                    __html: this.props.item.description,
                },
            }),
            this.shouldRenderColumns()
                ? this.renderColumns()
                : this.renderSingleItem(),
        );
    };
    return QuestItem;
})(React.Component);
export default QuestItem;
//# sourceMappingURL=quest-item.js.map
