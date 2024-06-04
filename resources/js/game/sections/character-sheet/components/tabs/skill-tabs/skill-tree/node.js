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
var Node = (function (_super) {
    __extends(Node, _super);
    function Node(props) {
        return _super.call(this, props) || this;
    }
    Node.prototype.isMaxLevel = function () {
        return (
            this.props.passive.current_level === this.props.passive.max_level
        );
    };
    Node.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            null,
            React.createElement(
                "button",
                {
                    onClick: function () {
                        return _this.props.show_passive_modal(
                            _this.props.passive,
                        );
                    },
                    disabled: this.props.is_automation_running,
                },
                React.createElement(
                    "h4",
                    {
                        className: clsx({
                            "text-red-500 dark:text-red-400":
                                this.props.passive.parent_skill_id !== null &&
                                this.props.passive.is_locked,
                            "text-blue-500 dark:text-blue-400":
                                this.props.passive.parent_skill_id === null,
                            "text-green-700 dark:text-green-600":
                                this.props.passive.current_level ===
                                this.props.passive.max_level,
                        }),
                    },
                    React.createElement("i", {
                        className: clsx("ra ra-wooden-sign", {
                            hidden:
                                this.props.passive.quest_name === null ||
                                this.props.passive.is_quest_complete,
                            "text-yellow-500 dark:text-yellow-400":
                                this.props.passive.quest_name !== null &&
                                !this.props.passive.is_quest_complete,
                        }),
                    }),
                    " ",
                    this.props.passive.name,
                ),
            ),
            React.createElement(
                "p",
                { className: "mt-3" },
                "Level: ",
                this.props.passive.current_level,
                "/",
                this.props.passive.max_level,
            ),
            !this.isMaxLevel()
                ? React.createElement(
                      "p",
                      { className: "mt-3" },
                      "Hours till next: ",
                      this.props.passive.hours_to_next,
                  )
                : React.createElement(
                      "p",
                      { className: "text-green-700 dark:text-green-600 mt-3" },
                      "Skill is maxed out!",
                  ),
        );
    };
    return Node;
})(React.Component);
export default Node;
//# sourceMappingURL=node.js.map
