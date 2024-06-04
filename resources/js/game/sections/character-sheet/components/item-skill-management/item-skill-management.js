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
import ItemSkillTree from "./item-skill-tree";
import ItemSkillDetails from "./item-skill-details";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import { isSkillLocked } from "./helpers/is-skill-locked";
var ItemSkillManagement = (function (_super) {
    __extends(ItemSkillManagement, _super);
    function ItemSkillManagement(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            skill_progression: null,
            skill: null,
        };
        return _this;
    }
    ItemSkillManagement.prototype.showSkillSection = function (
        skill,
        progression,
    ) {
        this.setState({
            skill_progression: progression,
            skill: skill,
        });
    };
    ItemSkillManagement.prototype.componentDidUpdate = function (prevProps) {
        var _this = this;
        if (this.state.skill_progression !== null) {
            var updatedSkillProgressionInfo =
                this.props.skill_progression_data.find(function (data) {
                    var _a;
                    return (
                        data.id ===
                        ((_a = _this.state.skill_progression) === null ||
                        _a === void 0
                            ? void 0
                            : _a.id)
                    );
                });
            if (typeof updatedSkillProgressionInfo === "undefined") {
                return;
            }
            if (updatedSkillProgressionInfo !== this.state.skill_progression) {
                this.setState({
                    skill_progression: updatedSkillProgressionInfo,
                });
            }
        }
    };
    ItemSkillManagement.prototype.render = function () {
        var _this = this;
        if (
            this.state.skill_progression !== null &&
            this.state.skill !== null
        ) {
            return React.createElement(ItemSkillDetails, {
                skill_progression_data: this.state.skill_progression,
                skills: this.props.skill_data,
                manage_skill_details: this.showSkillSection.bind(this),
                character_id: this.props.character_id,
                is_skill_locked: isSkillLocked(
                    this.state.skill,
                    this.props.skill_data,
                    this.props.skill_progression_data,
                ),
            });
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "p-4 text-center font-thin text-xl" },
                React.createElement("h3", null, "Ancestral Skill Tree"),
            ),
            React.createElement(
                "p",
                {
                    className:
                        "text-center font-thin text-sm text-gray-600 dark:text-gray-300 italic mt-1",
                },
                "All the skills here will stack together. For more info please refer to:",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/ancestral-items", target: "_blank" },
                    "Ancestral items help docs",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                ".",
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(DangerButton, {
                button_label: "Close Skill Tree",
                on_click: function () {
                    return _this.props.close_skill_tree();
                },
                additional_css: "mb-4",
            }),
            React.createElement(ItemSkillTree, {
                skill_data: this.props.skill_data,
                progression_data: this.props.skill_progression_data,
                show_skill_management: this.showSkillSection.bind(this),
            }),
        );
    };
    return ItemSkillManagement;
})(React.Component);
export default ItemSkillManagement;
//# sourceMappingURL=item-skill-management.js.map
