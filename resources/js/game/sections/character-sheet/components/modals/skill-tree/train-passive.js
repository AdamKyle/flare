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
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
import Ajax from "../../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
var TrainPassive = (function (_super) {
    __extends(TrainPassive, _super);
    function TrainPassive(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            error_message: "",
        };
        return _this;
    }
    TrainPassive.prototype.trainSkill = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "train/passive/" +
                            _this.props.skill.id +
                            "/" +
                            _this.props.character_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.manage_success_message(
                                        result.data.message,
                                    );
                                    _this.props.update_passives(
                                        result.data.kingdom_passives,
                                        result.data.passive_training,
                                    );
                                    _this.props.manage_modal();
                                },
                            );
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    TrainPassive.prototype.cancelTrainingSkill = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "stop-training/passive/" +
                            _this.props.skill.id +
                            "/" +
                            _this.props.character_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.manage_success_message(
                                        result.data.message,
                                    );
                                    _this.props.update_passives(
                                        result.data.kingdom_passives,
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
    TrainPassive.prototype.isMaxed = function () {
        return this.props.skill.current_level === this.props.skill.max_level;
    };
    TrainPassive.prototype.isTraining = function () {
        return this.props.skill.started_at !== null;
    };
    TrainPassive.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.skill.name,
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    secondary_button_disabled:
                        this.isMaxed() ||
                        this.props.skill.is_locked ||
                        this.state.loading ||
                        this.state.is_dead,
                    secondary_button_label: this.isTraining()
                        ? "Stop Training"
                        : "Train",
                    handle_action: this.isTraining()
                        ? this.cancelTrainingSkill.bind(this)
                        : this.trainSkill.bind(this),
                },
            },
            React.createElement(
                "p",
                { className: "mt-4 mb-4" },
                this.props.skill.passive_skill.description,
            ),
            this.props.is_dead
                ? React.createElement(
                      "p",
                      { className: "mb-4 text-red-700 dark:text-red-500" },
                      "No no child! You dead! You ain't training nothing, till you head to the Game tab and click revive.",
                  )
                : null,
            this.props.skill.is_locked &&
                this.props.skill.passive_skill.unlocks_at_level !== null
                ? React.createElement(
                      "p",
                      {
                          className:
                              "mb-4 text-orange-700 dark:text-orange-400",
                      },
                      "This skill requires it's parent to be trained to be able to train this skill. This skill will unlock at (parent) level: ",
                      this.props.skill.passive_skill.unlocks_at_level,
                  )
                : null,
            this.props.skill.quest_name !== null &&
                !this.props.skill.is_quest_complete
                ? React.createElement(
                      "p",
                      {
                          className:
                              "mb-4 text-orange-700 dark:text-orange-400",
                      },
                      'You must complete the quest: "',
                      this.props.skill.quest_name,
                      '" before you can unlock this passive.',
                  )
                : null,
            this.isMaxed()
                ? React.createElement(
                      "p",
                      { className: "mb-4 text-green-600 dark:text-green-500" },
                      "This skill has been maxed out and cannot be trained any higher.",
                  )
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "dl",
                          null,
                          React.createElement("dt", null, "Level"),
                          React.createElement(
                              "dd",
                              null,
                              this.props.skill.current_level,
                              " /",
                              " ",
                              this.props.skill.max_level,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Hours till next level:",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              this.props.skill.hours_to_next,
                          ),
                      ),
                      React.createElement(
                          "p",
                          { className: "mt-4 mb-4" },
                          React.createElement("strong", null, "Caution:"),
                          " Canceling this skill before it is done training, will result in you having to start the progress all over again.",
                          " ",
                          React.createElement(
                              "strong",
                              null,
                              "We do not take into account, time elapsed when canceling",
                          ),
                          ".",
                      ),
                  ),
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            this.state.error_message !== ""
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "mt-4" },
                      this.state.error_message,
                  )
                : null,
        );
    };
    return TrainPassive;
})(React.Component);
export default TrainPassive;
//# sourceMappingURL=train-passive.js.map
