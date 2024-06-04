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
import Ajax from "../../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
import KingdomPassiveTree from "./skill-tree/kingdom-passive-tree";
import TimerProgressBar from "../../../../../components/ui/progress-bars/timer-progress-bar";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import { DateTime } from "luxon";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
var KingdomPassives = (function (_super) {
    __extends(KingdomPassives, _super);
    function KingdomPassives(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            kingdom_passives: [],
            success_message: null,
            skill_in_training: null,
        };
        return _this;
    }
    KingdomPassives.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("character/kingdom-passives/" + this.props.character_id)
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        kingdom_passives: result.data.kingdom_passives,
                        skill_in_training: result.data.passive_training,
                    });
                },
                function (error) {},
            );
    };
    KingdomPassives.prototype.manageSuccessMessage = function (message) {
        this.setState({
            success_message: message,
        });
    };
    KingdomPassives.prototype.closeSuccessAlert = function () {
        this.setState({
            success_message: null,
        });
    };
    KingdomPassives.prototype.updatePassives = function (
        passives,
        passiveInTraining,
    ) {
        this.setState({
            kingdom_passives: passives,
            skill_in_training: passiveInTraining,
        });
    };
    KingdomPassives.prototype.findSkillInTraining = function (passive) {
        if (this.updatePassiveTrainingState(passive)) {
            return;
        }
        if (passive.children.length > 0) {
            for (var i = 0; i < passive.children.length; i++) {
                var child = passive.children[i];
                if (child.children.length > 0) {
                    this.findSkillInTraining(child);
                }
                if (this.updatePassiveTrainingState(child)) {
                    return;
                }
            }
        }
    };
    KingdomPassives.prototype.updatePassiveTrainingState = function (passive) {
        if (passive.started_at !== null) {
            this.setState({
                skill_in_training: passive,
            });
            return true;
        } else {
            this.setState({
                skill_in_training: null,
            });
            return false;
        }
    };
    KingdomPassives.prototype.getTimeLeftInSeconds = function () {
        if (this.state.skill_in_training !== null) {
            var start = DateTime.now();
            var end = DateTime.fromISO(
                this.state.skill_in_training.completed_at,
            );
            var diff = end.diff(start, ["seconds"]).toObject();
            if (diff.hasOwnProperty("seconds")) {
                if (typeof diff.seconds !== "undefined") {
                    return diff.seconds;
                }
            }
            return 0;
        }
        return 0;
    };
    KingdomPassives.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "relative p-10" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      "div",
                      null,
                      this.state.success_message !== null
                          ? React.createElement(
                                "div",
                                { className: "mb-4" },
                                React.createElement(
                                    SuccessAlert,
                                    {
                                        close_alert:
                                            this.closeSuccessAlert.bind(this),
                                    },
                                    this.state.success_message,
                                ),
                            )
                          : null,
                      React.createElement(
                          "div",
                          { className: "mb-4" },
                          React.createElement(
                              InfoAlert,
                              null,
                              "Click The skill name for additional actions. The timer will show below the tree when a skill is in progress.",
                          ),
                      ),
                      this.props.is_automation_running
                          ? React.createElement(
                                "div",
                                { className: "mb-4" },
                                React.createElement(
                                    WarningAlert,
                                    null,
                                    "Automation is running. You cannot manage your passive skills.",
                                ),
                            )
                          : null,
                      React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                      }),
                      this.state.skill_in_training != null
                          ? React.createElement(
                                "div",
                                { className: "my-4" },
                                React.createElement(TimerProgressBar, {
                                    time_out_label:
                                        "Skill In Training: " +
                                        this.state.skill_in_training.name,
                                    time_remaining: this.getTimeLeftInSeconds(),
                                }),
                            )
                          : null,
                      React.createElement(KingdomPassiveTree, {
                          passives: this.state.kingdom_passives[0],
                          manage_success_message:
                              this.manageSuccessMessage.bind(this),
                          update_passives: this.updatePassives.bind(this),
                          character_id: this.props.character_id,
                          is_dead: this.props.is_dead,
                          is_automation_running:
                              this.props.is_automation_running,
                      }),
                  ),
        );
    };
    return KingdomPassives;
})(React.Component);
export default KingdomPassives;
//# sourceMappingURL=kingdom-passives.js.map
