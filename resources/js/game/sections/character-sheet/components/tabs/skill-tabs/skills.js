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
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import SkillInformation from "../../modals/skills/skill-information";
import { formatNumber } from "../../../../../lib/game/format-number";
import TrainSkill from "../../modals/skills/train-skill";
import Ajax from "../../../../../lib/ajax/ajax";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";
var Skills = (function (_super) {
    __extends(Skills, _super);
    function Skills(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_skill_details: false,
            show_train_skill: false,
            skill: null,
            stopping: false,
            success_message: null,
        };
        return _this;
    }
    Skills.prototype.manageTrainSkill = function (row) {
        this.setState({
            show_train_skill: !this.state.show_train_skill,
            skill: typeof row !== "undefined" ? row : null,
        });
    };
    Skills.prototype.stopTraining = function (row) {
        var _this = this;
        this.setState(
            {
                stopping: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "skill/cancel-train/" +
                            _this.props.character_id +
                            "/" +
                            row.id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    stopping: false,
                                    success_message: result.data.message,
                                },
                                function () {
                                    _this.props.update_skills(
                                        result.data.skills,
                                    );
                                },
                            );
                        },
                        function (error) {},
                    );
            },
        );
    };
    Skills.prototype.manageSkillDetails = function (row) {
        this.setState({
            show_skill_details: !this.state.show_skill_details,
            skill: typeof row !== "undefined" ? row : null,
        });
    };
    Skills.prototype.setSuccessMessage = function (message) {
        this.setState({
            success_message: message,
        });
    };
    Skills.prototype.closeSuccessMessage = function () {
        this.setState({
            success_message: null,
        });
    };
    Skills.prototype.isAnySkillTraining = function () {
        return (
            this.props.trainable_skills.filter(function (skill) {
                return skill.is_training;
            }).length > 0
        );
    };
    Skills.prototype.buildColumns = function () {
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
                                className: clsx("underline", {
                                    "text-orange-600 dark:text-orange-300":
                                        row.is_class_skill,
                                }),
                            },
                            React.createElement("i", {
                                className: clsx({
                                    "ra ra-player-pyromaniac":
                                        row.is_class_skill,
                                }),
                            }),
                            " ",
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
                        formatNumber(row.xp),
                        "/",
                        formatNumber(row.xp_max),
                    );
                },
            },
            {
                name: "Training?",
                selector: function (row) {
                    return row.is_training ? "Yes" : "No";
                },
                sortable: true,
            },
            {
                name: "Actions",
                selector: function (row) {
                    return "";
                },
                sortable: false,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        {
                            key:
                                row.id +
                                "-" +
                                (Math.random() + 1).toString(36).substring(7),
                        },
                        row.is_training
                            ? React.createElement(DangerButton, {
                                  button_label: _this.state.stopping
                                      ? React.createElement(
                                            "span",
                                            null,
                                            "Stopping",
                                            " ",
                                            React.createElement("i", {
                                                className:
                                                    "fas fa-spinner fa-pulse",
                                            }),
                                        )
                                      : "Stop training",
                                  on_click: function () {
                                      return _this.stopTraining(row);
                                  },
                                  disabled:
                                      _this.props.is_dead ||
                                      _this.state.stopping ||
                                      _this.props.is_automation_running,
                              })
                            : React.createElement(PrimaryButton, {
                                  button_label: "Train",
                                  on_click: function () {
                                      return _this.manageTrainSkill(row);
                                  },
                                  disabled:
                                      _this.props.is_dead ||
                                      _this.isAnySkillTraining() ||
                                      _this.props.is_automation_running,
                              }),
                    );
                },
            },
        ];
    };
    Skills.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.props.is_automation_running
                ? React.createElement(
                      "div",
                      { className: "mb-4" },
                      React.createElement(
                          WarningAlert,
                          null,
                          "Automation is running. You cannot train or stop training skills.",
                      ),
                  )
                : null,
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
                    data: this.props.trainable_skills,
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
            this.state.show_train_skill && this.state.skill !== null
                ? React.createElement(TrainSkill, {
                      is_open: this.state.show_train_skill,
                      manage_modal: this.manageTrainSkill.bind(this),
                      skill: this.state.skill,
                      set_success_message: this.setSuccessMessage.bind(this),
                      update_skills: this.props.update_skills,
                      character_id: this.props.character_id,
                  })
                : null,
        );
    };
    return Skills;
})(React.Component);
export default Skills;
//# sourceMappingURL=skills.js.map
