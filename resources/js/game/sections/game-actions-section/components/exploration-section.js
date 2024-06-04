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
import React, { Fragment } from "react";
import DangerButton from "../../../components/ui/buttons/danger-button";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import { startCase } from "lodash";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
var ExplorationSection = (function (_super) {
    __extends(ExplorationSection, _super);
    function ExplorationSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            monster_selected: null,
            time_selected: null,
            attack_type: null,
            move_down_monster_list: null,
            error_message: null,
        };
        return _this;
    }
    ExplorationSection.prototype.setMonsterToFight = function (data) {
        var foundMonster = this.props.monsters.filter(function (monster) {
            return monster.id === parseInt(data.value);
        });
        if (foundMonster.length > 0) {
            this.setState({
                monster_selected: foundMonster[0],
            });
        } else {
            this.setState({
                monster_selected: null,
            });
        }
    };
    ExplorationSection.prototype.monsterOptions = function () {
        var monsters = this.props.monsters.map(function (monster) {
            return { label: monster.name, value: monster.id };
        });
        monsters.unshift({
            label: "Please Select",
            value: 0,
        });
        return monsters;
    };
    ExplorationSection.prototype.defaultSelectedMonster = function () {
        if (this.state.monster_selected !== null) {
            return {
                label: this.state.monster_selected.name,
                value: this.state.monster_selected.id,
            };
        }
        return {
            label: "Please Select Monster",
            value: "",
        };
    };
    ExplorationSection.prototype.setLengthOfTime = function (data) {
        this.setState({
            time_selected: data.value !== "" ? data.value : null,
        });
    };
    ExplorationSection.prototype.setAttackType = function (data) {
        this.setState({
            attack_type: data.value !== "" ? data.value : null,
        });
    };
    ExplorationSection.prototype.setMoveDownList = function (data) {
        this.setState({
            move_down_monster_list: data.value !== "" ? data.value : null,
        });
    };
    ExplorationSection.prototype.timeOptions = function () {
        return [
            {
                label: "1 Hour(s)",
                value: 1,
            },
            {
                label: "2 Hour(s)",
                value: 2,
            },
            {
                label: "4 Hour(s)",
                value: 4,
            },
            {
                label: "6 Hour(s)",
                value: 6,
            },
            {
                label: "8 Hour(s)",
                value: 8,
            },
        ];
    };
    ExplorationSection.prototype.attackTypes = function () {
        return [
            {
                label: "Attack",
                value: "attack",
            },
            {
                label: "Cast",
                value: "cast",
            },
            {
                label: "Attack and Cast",
                value: "attack_and_cast",
            },
            {
                label: "Cast and Attack",
                value: "cast_and_attack",
            },
            {
                label: "Defend",
                value: "defend",
            },
        ];
    };
    ExplorationSection.prototype.moveDownTheListEvery = function () {
        return [
            {
                label: "5 Levels",
                value: 5,
            },
            {
                label: "10 Levels",
                value: 10,
            },
            {
                label: "20 Levels",
                value: 20,
            },
        ];
    };
    ExplorationSection.prototype.defaultSelectedTime = function () {
        if (this.state.time_selected != null) {
            return [
                {
                    label: this.state.time_selected + " Hour(s)",
                    value: this.state.time_selected,
                },
            ];
        }
        return [
            {
                label: "Please select length of time",
                value: "",
            },
        ];
    };
    ExplorationSection.prototype.defaultAttackType = function () {
        if (this.state.attack_type !== null) {
            return {
                label: startCase(this.state.attack_type),
                value: this.state.attack_type,
            };
        }
        return {
            label: "Please select attack type",
            value: "",
        };
    };
    ExplorationSection.prototype.defaultMoveDownList = function () {
        if (this.state.move_down_monster_list !== null) {
            return {
                label: this.state.move_down_monster_list + " levels",
                value: this.state.move_down_monster_list,
            };
        }
        return {
            label: "Move down the list (optional)",
            value: "",
        };
    };
    ExplorationSection.prototype.startExploration = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "exploration/" + _this.props.character.id + "/start",
                    )
                    .setParameters({
                        auto_attack_length: _this.state.time_selected,
                        move_down_the_list_every:
                            _this.state.move_down_monster_list,
                        selected_monster_id: _this.state.monster_selected.id,
                        attack_type: _this.state.attack_type,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                            });
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
    ExplorationSection.prototype.stopExploration = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "exploration/" + _this.props.character.id + "/stop",
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                            });
                        },
                        function (error) {},
                    );
            },
        );
    };
    ExplorationSection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.props.character.is_automation_running
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "div",
                          {
                              className:
                                  "mb-4 lg:ml-[120px] text-center lg:text-left",
                          },
                          "Automation is running. You can cancel it below.",
                          " ",
                          React.createElement(
                              "a",
                              {
                                  href: "/information/automation",
                                  target: "_blank",
                              },
                              "See Exploration Help",
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                          " ",
                          "for more details.",
                      ),
                      this.state.loading
                          ? React.createElement(LoadingProgressBar, null)
                          : null,
                      React.createElement(
                          "div",
                          { className: "text-center" },
                          React.createElement(DangerButton, {
                              button_label: "Stop Exploration",
                              on_click: this.stopExploration.bind(this),
                              disabled: this.state.loading,
                              additional_css: "mr-2 mb-4",
                          }),
                          React.createElement(PrimaryButton, {
                              button_label: "Close Exploration",
                              on_click: this.props.manage_exploration,
                              disabled: this.state.loading,
                          }),
                      ),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "div",
                          {
                              className:
                                  "mt-2 grid lg:grid-cols-3 gap-2 lg:ml-[120px]",
                          },
                          React.createElement(
                              "div",
                              { className: "cols-start-1 col-span-2" },
                              React.createElement(
                                  "div",
                                  { className: "mb-3" },
                                  React.createElement(Select, {
                                      onChange:
                                          this.setMonsterToFight.bind(this),
                                      options: this.monsterOptions(),
                                      menuPosition: "absolute",
                                      menuPlacement: "bottom",
                                      styles: {
                                          menuPortal: function (base) {
                                              return __assign(
                                                  __assign({}, base),
                                                  {
                                                      zIndex: 9999,
                                                      color: "#000000",
                                                  },
                                              );
                                          },
                                      },
                                      menuPortalTarget: document.body,
                                      value: this.defaultSelectedMonster(),
                                  }),
                              ),
                              React.createElement(
                                  "div",
                                  { className: "mb-3" },
                                  React.createElement(Select, {
                                      onChange: this.setLengthOfTime.bind(this),
                                      options: this.timeOptions(),
                                      menuPosition: "absolute",
                                      menuPlacement: "bottom",
                                      styles: {
                                          menuPortal: function (base) {
                                              return __assign(
                                                  __assign({}, base),
                                                  {
                                                      zIndex: 9999,
                                                      color: "#000000",
                                                  },
                                              );
                                          },
                                      },
                                      menuPortalTarget: document.body,
                                      value: this.defaultSelectedTime(),
                                  }),
                              ),
                              React.createElement(
                                  "div",
                                  { className: "mb-3" },
                                  React.createElement(Select, {
                                      onChange: this.setMoveDownList.bind(this),
                                      options: this.moveDownTheListEvery(),
                                      menuPosition: "absolute",
                                      menuPlacement: "bottom",
                                      styles: {
                                          menuPortal: function (base) {
                                              return __assign(
                                                  __assign({}, base),
                                                  {
                                                      zIndex: 9999,
                                                      color: "#000000",
                                                  },
                                              );
                                          },
                                      },
                                      menuPortalTarget: document.body,
                                      value: this.defaultMoveDownList(),
                                  }),
                              ),
                              React.createElement(
                                  "div",
                                  null,
                                  React.createElement(Select, {
                                      onChange: this.setAttackType.bind(this),
                                      options: this.attackTypes(),
                                      menuPosition: "absolute",
                                      menuPlacement: "bottom",
                                      styles: {
                                          menuPortal: function (base) {
                                              return __assign(
                                                  __assign({}, base),
                                                  {
                                                      zIndex: 9999,
                                                      color: "#000000",
                                                  },
                                              );
                                          },
                                      },
                                      menuPortalTarget: document.body,
                                      value: this.defaultAttackType(),
                                  }),
                              ),
                          ),
                      ),
                      React.createElement(
                          "div",
                          {
                              className:
                                  "lg:text-center lg:ml-[-100px] mt-3 mb-3",
                          },
                          React.createElement(PrimaryButton, {
                              button_label: "Explore",
                              on_click: this.startExploration.bind(this),
                              disabled:
                                  this.state.monster_selected === null ||
                                  this.state.time_selected === null ||
                                  this.state.attack_type === null ||
                                  this.state.loading ||
                                  this.props.character.is_dead ||
                                  !this.props.character.can_attack,
                              additional_css: "mr-2 mb-4",
                          }),
                          React.createElement(DangerButton, {
                              button_label: "Close",
                              on_click: this.props.manage_exploration,
                              disabled: this.state.loading,
                          }),
                          this.state.loading
                              ? React.createElement(
                                    "div",
                                    { className: "w-1/2 ml-auto mr-auto" },
                                    React.createElement(
                                        LoadingProgressBar,
                                        null,
                                    ),
                                )
                              : null,
                          this.state.error_message !== null
                              ? React.createElement(
                                    "div",
                                    { className: "w-1/2 ml-auto mr-auto mt-4" },
                                    React.createElement(
                                        DangerAlert,
                                        null,
                                        this.state.error_message,
                                    ),
                                )
                              : null,
                          React.createElement(
                              "div",
                              { className: "relative top-[24px] italic" },
                              React.createElement(
                                  "p",
                                  null,
                                  "For more help please the",
                                  " ",
                                  React.createElement(
                                      "a",
                                      {
                                          href: "/information/exploration",
                                          target: "_blank",
                                      },
                                      "Exploration",
                                      " ",
                                      React.createElement("i", {
                                          className: "fas fa-external-link-alt",
                                      }),
                                  ),
                                  " ",
                                  "help docs.",
                              ),
                          ),
                      ),
                  ),
        );
    };
    return ExplorationSection;
})(React.Component);
export default ExplorationSection;
//# sourceMappingURL=exploration-section.js.map
