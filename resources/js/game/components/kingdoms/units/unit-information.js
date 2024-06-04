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
import { parseInt } from "lodash";
import React, { Fragment } from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import BasicCard from "../../../components/ui/cards/basic-card";
import { formatNumber } from "../../../lib/game/format-number";
import TimeHelpModal from "../modals/time-help-modal";
import RecruitWithResources from "./recruit-with-resources";
var UnitInformation = (function (_super) {
    __extends(UnitInformation, _super);
    function UnitInformation(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            upgrade_section: null,
            success_message: null,
            error_message: null,
            amount_to_recruit: "",
            loading: false,
            show_time_help: false,
            cost_in_gold: 0,
            time_needed: 0,
        };
        return _this;
    }
    UnitInformation.prototype.calculateCostsForUnit = function (
        baseCost,
        amount,
        is_iron,
    ) {
        if (typeof this.props.unit_cost_reduction === "undefined") {
            console.error("unit_cost_reduction is undefined (prop)");
            return "ERROR";
        }
        var cost = baseCost * amount;
        if (is_iron && cost > 1) {
            return cost - cost * this.props.kingdom_iron_cost_reduction;
        }
        return cost;
    };
    UnitInformation.prototype.setResourceAmount = function (
        amount,
        timeNeeded,
    ) {
        this.setState({
            amount_to_recruit: amount,
            time_needed: parseInt(timeNeeded.toFixed(0)) || 0,
        });
    };
    UnitInformation.prototype.getAmount = function () {
        return parseInt(this.state.amount_to_recruit) || 1;
    };
    UnitInformation.prototype.showSelectedForm = function (type) {
        this.setState({
            upgrade_section: type,
        });
    };
    UnitInformation.prototype.removeSelection = function () {
        this.setState({
            upgrade_section: null,
            success_message: null,
            error_message: null,
            amount_to_recruit: "",
            loading: false,
            cost_in_gold: 0,
            time_needed: 0,
        });
    };
    UnitInformation.prototype.manageHelpDialogue = function (timeNeeded) {
        this.setState({
            show_time_help: !this.state.show_time_help,
            time_needed:
                typeof timeNeeded !== "undefined"
                    ? timeNeeded
                    : this.state.time_needed,
        });
    };
    UnitInformation.prototype.cannotBeRecruited = function (unit) {
        var building = this.props.buildings.filter(function (building) {
            return (
                building.game_building_id ===
                unit.recruited_from.game_building_id
            );
        });
        if (building.length === 0) {
            return false;
        }
        var foundBuilding = building[0];
        return (
            foundBuilding.level < unit.required_building_level ||
            foundBuilding.is_locked
        );
    };
    UnitInformation.prototype.renderSelectedSection = function () {
        if (this.state.upgrade_section === "resources") {
            return React.createElement(RecruitWithResources, {
                kingdom_id: this.props.kingdom_id,
                character_id: this.props.character_id,
                unit: this.props.unit,
                unit_cost_reduction: this.props.unit_cost_reduction,
                kingdom_unit_time_reduction:
                    this.props.kingdom_unit_time_reduction,
                manage_help_dialogue: this.manageHelpDialogue.bind(this),
                remove_selection: this.removeSelection.bind(this),
                set_resource_amount: this.setResourceAmount.bind(this),
            });
        }
        return null;
    };
    UnitInformation.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                BasicCard,
                null,
                React.createElement(
                    "div",
                    { className: "text-right cursor-pointer text-red-500" },
                    React.createElement(
                        "button",
                        {
                            onClick: function () {
                                return _this.props.close();
                            },
                        },
                        React.createElement("i", {
                            className: "fas fa-minus-circle",
                        }),
                    ),
                ),
                this.cannotBeRecruited(this.props.unit)
                    ? React.createElement(
                          "div",
                          { className: "mt-4 mb-4" },
                          React.createElement(
                              DangerAlert,
                              null,
                              "You must Train:",
                              " ",
                              this.props.unit.recruited_from.building_name,
                              " ",
                              "to level:",
                              " ",
                              this.props.unit.required_building_level,
                              " before you can recruit these units. Check the buildings tab. If the building is red, you must unlock the building before leveling it.",
                          ),
                      )
                    : null,
                React.createElement(
                    "div",
                    { className: "grid md:grid-cols-2 gap-4 mb-4 mt-4" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement("h3", null, "Basic Info"),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Name"),
                            React.createElement(
                                "dd",
                                null,
                                this.props.unit.name,
                            ),
                            React.createElement("dt", null, "Attack"),
                            React.createElement(
                                "dd",
                                null,
                                this.props.unit.attack,
                            ),
                            React.createElement("dt", null, "Defence"),
                            React.createElement(
                                "dd",
                                null,
                                this.props.unit.defence,
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Heal % (For one unit. Stacks.)",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                this.props.unit.heal_percentage !== null
                                    ? (
                                          this.props.unit.heal_percentage * 100
                                      ).toFixed(0)
                                    : 0,
                                "%",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Good for attacking?",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                this.props.unit.attacker ? "Yes" : "No",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Good for defending?",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                this.props.unit.defender ? "Yes" : "No",
                            ),
                        ),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "h3",
                            null,
                            "Upgrade Costs (For 1 Unit)",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                        }),
                        React.createElement(
                            "dl",
                            { className: "mb-5" },
                            React.createElement("dt", null, "Stone Cost:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.calculateCostsForUnit(
                                        this.props.unit.stone_cost,
                                        this.getAmount(),
                                        false,
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Clay Cost:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.calculateCostsForUnit(
                                        this.props.unit.clay_cost,
                                        this.getAmount(),
                                        false,
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Wood Cost:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.calculateCostsForUnit(
                                        this.props.unit.wood_cost,
                                        this.getAmount(),
                                        false,
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Iron Cost:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.calculateCostsForUnit(
                                        this.props.unit.iron_cost,
                                        this.getAmount(),
                                        true,
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Steel Cost:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.calculateCostsForUnit(
                                        this.props.unit.steel_cost,
                                        this.getAmount(),
                                        false,
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Population Cost:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.calculateCostsForUnit(
                                        this.props.unit.required_population,
                                        this.getAmount(),
                                        false,
                                    ),
                                ),
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Base Time For One (Seconds):",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(this.props.unit.time_to_recruit),
                            ),
                            this.state.upgrade_section === "resources"
                                ? React.createElement(
                                      Fragment,
                                      null,
                                      React.createElement(
                                          "dt",
                                          null,
                                          "Time Required (Seconds):",
                                      ),
                                      React.createElement(
                                          "dd",
                                          { className: "flex items-center" },
                                          React.createElement(
                                              "span",
                                              null,
                                              formatNumber(
                                                  this.state.time_needed,
                                              ),
                                          ),
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
                                                          className:
                                                              "fas fa-info-circle",
                                                      }),
                                                      " ",
                                                      "Help",
                                                  ),
                                              ),
                                          ),
                                      ),
                                  )
                                : null,
                        ),
                        this.cannotBeRecruited(this.props.unit)
                            ? null
                            : this.props.is_in_queue
                              ? React.createElement(
                                    "p",
                                    { className: "mb-5 mt-5" },
                                    "You must wait for the units recruitment to end.",
                                )
                              : this.props.kingdom_current_population === 0
                                ? React.createElement(
                                      "p",
                                      { className: "mb-5 mt-5" },
                                      "You have no population to recruit units with.",
                                  )
                                : this.state.upgrade_section !== null
                                  ? this.renderSelectedSection()
                                  : React.createElement(
                                        Fragment,
                                        null,
                                        React.createElement(
                                            PrimaryOutlineButton,
                                            {
                                                button_label: "Recruit Units",
                                                on_click: function () {
                                                    return _this.showSelectedForm(
                                                        "resources",
                                                    );
                                                },
                                            },
                                        ),
                                    ),
                    ),
                ),
            ),
            this.state.show_time_help
                ? React.createElement(TimeHelpModal, {
                      is_in_minutes: false,
                      is_in_seconds: true,
                      manage_modal: this.manageHelpDialogue.bind(this),
                      time: this.state.time_needed,
                  })
                : null,
        );
    };
    return UnitInformation;
})(React.Component);
export default UnitInformation;
//# sourceMappingURL=unit-information.js.map
