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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React from "react";
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import { formatNumber } from "../../../../../lib/game/format-number";
import Select from "react-select";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../../lib/ajax/ajax";
import SkillHelpModal from "./skill-help-modal";
var TrainSkill = (function (_super) {
    __extends(TrainSkill, _super);
    function TrainSkill(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_value: 0.0,
            error_message: null,
            loading: false,
            show_help: false,
        };
        return _this;
    }
    TrainSkill.prototype.setSkillToTrain = function (data) {
        this.setState({
            selected_value: data.value,
            error_message: null,
        });
    };
    TrainSkill.prototype.trainSkill = function () {
        var _this = this;
        if (this.state.selected_value === 0.0) {
            return this.setState({
                error_message: "You must select a % of XP tp sacrifice.",
            });
        }
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute("skill/train/" + _this.props.character_id)
                    .setParameters({
                        skill_id: _this.props.skill.id,
                        xp_percentage: _this.state.selected_value,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.set_success_message(
                                        result.data.message,
                                    );
                                    _this.props.update_skills(
                                        result.data.skills,
                                    );
                                    _this.props.manage_modal();
                                },
                            );
                        },
                        function (error) {},
                    );
            },
        );
    };
    TrainSkill.prototype.buildItems = function () {
        return [
            {
                label: "10%",
                value: 0.1,
            },
            {
                label: "20%",
                value: 0.2,
            },
            {
                label: "30%",
                value: 0.3,
            },
            {
                label: "40%",
                value: 0.4,
            },
            {
                label: "50%",
                value: 0.5,
            },
            {
                label: "60%",
                value: 0.6,
            },
            {
                label: "70%",
                value: 0.7,
            },
            {
                label: "80%",
                value: 0.8,
            },
            {
                label: "90%",
                value: 0.9,
            },
            {
                label: "100%",
                value: 1.0,
            },
        ];
    };
    TrainSkill.prototype.defaultItem = function () {
        if (this.state.selected_value === 0.0) {
            return {
                label: "Please Select",
                value: 0.0,
            };
        }
        return {
            label: this.state.selected_value * 100 + "%",
            value: this.state.selected_value,
        };
    };
    TrainSkill.prototype.manageHelpDialogue = function () {
        this.setState({
            show_help: !this.state.show_help,
        });
    };
    TrainSkill.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.skill.name,
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    secondary_button_label: "Train Skill",
                    secondary_button_disabled:
                        this.state.loading || this.state.error_message !== null,
                    handle_action: this.trainSkill.bind(this),
                },
            },
            this.props.skill.is_locked
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "mb-4 mt-4" },
                      "This skill is locked. You will need to complete a quest to unlock it.",
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "mb-4 mt-4" },
                      this.state.error_message,
                  )
                : null,
            React.createElement(
                "p",
                { className: "mb-4" },
                this.props.skill.description,
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Current Level"),
                React.createElement("dd", null, this.props.skill.level),
                React.createElement("dt", null, "Max Level"),
                React.createElement("dd", null, this.props.skill.max_level),
                React.createElement(
                    "dt",
                    { className: "flex items-center" },
                    React.createElement("span", null, "XP Towards"),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "div",
                            { className: "ml-2" },
                            React.createElement(
                                "button",
                                {
                                    type: "button",
                                    onClick: function () {
                                        return _this.manageHelpDialogue();
                                    },
                                    className:
                                        "text-blue-500 dark:text-blue-300",
                                },
                                React.createElement("i", {
                                    className: "fas fa-info-circle",
                                }),
                                " ",
                                "Help",
                            ),
                        ),
                    ),
                ),
                React.createElement(
                    "dd",
                    null,
                    React.createElement(Select, {
                        onChange: this.setSkillToTrain.bind(this),
                        options: this.buildItems(),
                        menuPosition: "absolute",
                        menuPlacement: "bottom",
                        styles: {
                            menuPortal: function (base) {
                                return __assign(__assign({}, base), {
                                    zIndex: 9999,
                                    color: "#000000",
                                });
                            },
                        },
                        menuPortalTarget: document.body,
                        value: this.defaultItem(),
                    }),
                ),
                React.createElement("dt", null, "XP"),
                React.createElement(
                    "dd",
                    null,
                    formatNumber(this.props.skill.xp),
                    " /",
                    " ",
                    formatNumber(this.props.skill.xp_max),
                ),
            ),
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            this.state.show_help
                ? React.createElement(SkillHelpModal, {
                      manage_modal: this.manageHelpDialogue.bind(this),
                  })
                : null,
        );
    };
    return TrainSkill;
})(React.Component);
export default TrainSkill;
//# sourceMappingURL=train-skill.js.map
