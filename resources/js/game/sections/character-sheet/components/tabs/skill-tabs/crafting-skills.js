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
import Table from "../../../../../components/ui/data-tables/table";
import SkillInformation from "../../modals/skills/skill-information";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
var CraftingSkills = (function (_super) {
    __extends(CraftingSkills, _super);
    function CraftingSkills(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_skill_details: false,
            skill: null,
        };
        return _this;
    }
    CraftingSkills.prototype.manageSkillDetails = function (row) {
        this.setState({
            show_skill_details: !this.state.show_skill_details,
            skill: typeof row !== "undefined" ? row : null,
        });
    };
    CraftingSkills.prototype.buildColumns = function () {
        var _this = this;
        return [
            {
                name: "Name",
                selector: function (row) {
                    return row.name;
                },
                sortable: true,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        {
                            key:
                                row.id +
                                "-" +
                                (Math.random() + 1).toString(36).substring(7),
                            className: "m-auto",
                        },
                        React.createElement(
                            "button",
                            {
                                onClick: function () {
                                    return _this.manageSkillDetails(row);
                                },
                                className: "underline",
                            },
                            row.name,
                        ),
                    );
                },
            },
            {
                name: "Level",
                selector: function (row) {
                    return row.level;
                },
                sortable: true,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        {
                            key:
                                row.id +
                                "-" +
                                (Math.random() + 1).toString(36).substring(7),
                        },
                        row.level,
                        "/",
                        row.max_level,
                    );
                },
            },
            {
                name: "XP",
                selector: function (row) {
                    return row.xp;
                },
                sortable: true,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        {
                            key:
                                row.id +
                                "-" +
                                (Math.random() + 1).toString(36).substring(7),
                        },
                        row.xp,
                        "/",
                        row.xp_max,
                    );
                },
            },
        ];
    };
    CraftingSkills.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "mb-4" },
                React.createElement(
                    InfoAlert,
                    null,
                    "This section will not update in real time.",
                ),
            ),
            React.createElement(
                "div",
                { className: "max-w-[390px] md:max-w-full overflow-y-hidden" },
                React.createElement(Table, {
                    columns: this.buildColumns(),
                    data: this.props.crafting_skills,
                    dark_table: this.props.dark_table,
                }),
            ),
            this.state.show_skill_details && this.state.skill !== null
                ? React.createElement(SkillInformation, {
                      skill: this.state.skill,
                      manage_modal: this.manageSkillDetails.bind(this),
                      is_open: this.state.show_skill_details,
                  })
                : null,
        );
    };
    return CraftingSkills;
})(React.Component);
export default CraftingSkills;
//# sourceMappingURL=crafting-skills.js.map
