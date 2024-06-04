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
import clsx from "clsx";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import SuccessButton from "../../../../components/ui/buttons/success-button";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import Ajax from "../../../../lib/ajax/ajax";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import { findParentSkill } from "./helpers/is-skill-locked";
var ItemSkillDetails = (function (_super) {
    __extends(ItemSkillDetails, _super);
    function ItemSkillDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            success_message: null,
            error_message: null,
        };
        return _this;
    }
    ItemSkillDetails.prototype.trainSkill = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "item-skills/train/" +
                            _this.props.character_id +
                            "/" +
                            _this.props.skill_progression_data.item_id +
                            "/" +
                            _this.props.skill_progression_data.item_skill_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
                            });
                        },
                        function (error) {
                            _this.setState({
                                loading: false,
                            });
                            var response = error.response;
                            if (typeof response != "undefined") {
                                _this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    ItemSkillDetails.prototype.stopTrainingSkipp = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "item-skills/stop-training/" +
                            _this.props.character_id +
                            "/" +
                            _this.props.skill_progression_data.item_id +
                            "/" +
                            _this.props.skill_progression_data.item_skill_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
                            });
                        },
                        function (error) {
                            _this.setState({
                                loading: false,
                            });
                            var response = error.response;
                            if (typeof response != "undefined") {
                                _this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    ItemSkillDetails.prototype.render = function () {
        var _this = this;
        var skillData = this.props.skill_progression_data;
        var parentSkill = findParentSkill(
            this.props.skill_progression_data.item_skill,
            this.props.skills,
        );
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "h2",
                null,
                this.props.skill_progression_data.item_skill.name,
            ),
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "my-4" },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
            this.props.is_skill_locked
                ? React.createElement(
                      InfoAlert,
                      { additional_css: "my-4" },
                      "This skill is locked and cannot be trained until you meet the requirements below.",
                  )
                : null,
            React.createElement(
                "p",
                { className: "my-4" },
                this.props.skill_progression_data.item_skill.description,
            ),
            React.createElement(
                "p",
                { className: "mb-4" },
                "For more infomatio please refer to the",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/item-skills", target: "_blank" },
                    "Item Skills help docs",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                ".",
            ),
            parentSkill
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                      }),
                      React.createElement(
                          "div",
                          {
                              className:
                                  "bg-yellow-200 border border-yellow-700 dark:border-yellow-400 rounded-md py-3 px-4 text-yellow-900 dark:text-yellow-700",
                          },
                          React.createElement(
                              "h3",
                              {
                                  className:
                                      "my-4 font-bold text-yellow-900 dark:text-yellow-700",
                              },
                              "Requirements",
                          ),
                          React.createElement(
                              "dl",
                              null,
                              React.createElement(
                                  "dt",
                                  null,
                                  "Parent Skill Name:",
                              ),
                              React.createElement("dd", null, parentSkill.name),
                              React.createElement(
                                  "dt",
                                  null,
                                  "Required Level:",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  this.props.skill_progression_data.item_skill
                                      .parent_level_needed,
                              ),
                          ),
                      ),
                  )
                : null,
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "h4",
                { className: "my-4 font-bold" },
                "Progression Data",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Level (Current/Max):"),
                React.createElement(
                    "dd",
                    null,
                    skillData.current_level,
                    "/",
                    skillData.item_skill.max_level,
                ),
                React.createElement("dt", null, "Kill Count (Current/Needed):"),
                React.createElement(
                    "dd",
                    null,
                    skillData.current_level === skillData.item_skill.max_level
                        ? "You have maxed this skill"
                        : skillData.current_kill +
                              "/" +
                              skillData.item_skill.total_kills_needed,
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                { className: "grid lg:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Str Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.str_mod > 0,
                                }),
                            },
                            (skillData.str_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.str_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.str_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "Dex Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.dex_mod > 0,
                                }),
                            },
                            (skillData.dex_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.dex_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.dex_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "Dur Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.dur_mod > 0,
                                }),
                            },
                            (skillData.dur_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.dur_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.dur_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "Agi Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.agi_mod > 0,
                                }),
                            },
                            (skillData.agi_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.agi_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.agi_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "Int Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.int_mod > 0,
                                }),
                            },
                            (skillData.int_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.int_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.int_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "Chr Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.chr_mod > 0,
                                }),
                            },
                            (skillData.chr_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.chr_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.chr_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "Focus Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.focus_mod > 0,
                                }),
                            },
                            (skillData.focus_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.focus_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.focus_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "block lg:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Attack Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.base_attack_mod >
                                        0,
                                }),
                            },
                            (skillData.base_attack_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.base_attack_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.base_attack_mod *
                                          100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "AC Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.base_ac_mod > 0,
                                }),
                            },
                            (skillData.base_ac_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.base_ac_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill.base_ac_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                        React.createElement("dt", null, "Healing Modifier"),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-600 dark:text-green-400":
                                        skillData.item_skill.base_healing_mod >
                                        0,
                                }),
                            },
                            (skillData.base_healing_mod * 100).toFixed(0),
                            "%",
                            " ",
                            skillData.item_skill.base_healing_mod > 0
                                ? "(+" +
                                      (
                                          skillData.item_skill
                                              .base_healing_mod * 100
                                      ).toFixed(0) +
                                      "%/Lv)"
                                : "",
                        ),
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(
                "div",
                { className: "flex space-x-4 flex-row justify-start" },
                this.props.skill_progression_data.is_training
                    ? React.createElement(PrimaryButton, {
                          button_label: "Stop Training Skill",
                          on_click: this.stopTrainingSkipp.bind(this),
                          disabled: this.state.loading,
                      })
                    : React.createElement(SuccessButton, {
                          button_label: "Train Skill",
                          on_click: this.trainSkill.bind(this),
                          disabled:
                              this.state.loading || this.props.is_skill_locked,
                      }),
                React.createElement(DangerButton, {
                    button_label: "Close Skill Management",
                    on_click: function () {
                        return _this.props.manage_skill_details(null, null);
                    },
                    disabled: this.state.loading,
                }),
            ),
        );
    };
    return ItemSkillDetails;
})(React.Component);
export default ItemSkillDetails;
//# sourceMappingURL=item-skill-details.js.map
