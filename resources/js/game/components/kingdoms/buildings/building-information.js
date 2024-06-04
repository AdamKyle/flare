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
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerButton from "../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import BasicCard from "../../../components/ui/cards/basic-card";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { formatNumber } from "../../../lib/game/format-number";
import BuildingTimeCalculation from "../helpers/calculations/building-time-calculation";
import TimeHelpModal from "../modals/time-help-modal";
import ResourceBuildingExpansion from "./resource-building-expansion";
import UpgradeWithResources from "./upgrade-with-resources";
var BuildingInformation = (function (_super) {
    __extends(BuildingInformation, _super);
    function BuildingInformation(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            upgrade_section: null,
            success_message: "",
            error_message: "",
            loading: false,
        };
        _this.buildingTimeCalculation = new BuildingTimeCalculation();
        return _this;
    }
    BuildingInformation.prototype.componentDidMount = function () {
        if (
            this.props.building.current_durability <
            this.props.building.max_durability
        ) {
            this.setState({
                upgrade_section: "repair-building",
            });
        }
    };
    BuildingInformation.prototype.componentDidUpdate = function () {
        if (
            this.props.building.current_durability <
                this.props.building.max_durability &&
            this.state.upgrade_section !== "repair-building"
        ) {
            this.setState({
                upgrade_section: "repair-building",
            });
        }
    };
    BuildingInformation.prototype.showSelectedForm = function (type) {
        this.setState({
            upgrade_section: type,
        });
    };
    BuildingInformation.prototype.manageHelpDialogue = function () {
        this.setState({
            show_time_help: !this.state.show_time_help,
        });
    };
    BuildingInformation.prototype.removeSelection = function () {
        this.setState({
            upgrade_section: null,
        });
    };
    BuildingInformation.prototype.buildingNeedsToBeRepaired = function () {
        return (
            this.props.building.current_durability <
            this.props.building.max_durability
        );
    };
    BuildingInformation.prototype.repairBuilding = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                success_message: "",
                error_message: "",
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdoms/" +
                            _this.props.character_id +
                            "/rebuild-building/" +
                            _this.props.building.id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                success_message: result.data.message,
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
                            console.error(error);
                        },
                    );
            },
        );
    };
    BuildingInformation.prototype.calculateResourceCostWithReductions =
        function (cost, is_population, is_iron) {
            if (
                typeof this.props.kingdom_building_cost_reduction ===
                "undefined"
            ) {
                console.error(
                    "this.props.kingdom_building_cost_reduction is undefined.",
                );
                return "ERROR";
            }
            if (is_iron) {
                return formatNumber(
                    (
                        cost -
                        cost *
                            (this.props.kingdom_building_cost_reduction +
                                this.props.kingdom_iron_cost_reduction)
                    ).toFixed(0),
                );
            }
            if (is_population) {
                return formatNumber(
                    (
                        cost -
                        cost * this.props.kingdom_population_cost_reduction
                    ).toFixed(0),
                );
            }
            return formatNumber(
                (
                    cost -
                    cost * this.props.kingdom_building_cost_reduction
                ).toFixed(0),
            );
        };
    BuildingInformation.prototype.renderSelectedSection = function () {
        switch (this.state.upgrade_section) {
            case "upgrade":
                return React.createElement(UpgradeWithResources, {
                    character_id: this.props.character_id,
                    building: this.props.building,
                    remove_section: this.removeSelection.bind(this),
                    is_in_queue: this.props.is_in_queue,
                });
            case "repair-building":
                return React.createElement(
                    Fragment,
                    null,
                    this.buildingNeedsToBeRepaired()
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(PrimaryButton, {
                                  button_label: "Repair",
                                  on_click: this.repairBuilding.bind(this),
                                  additional_css: "mr-2",
                              }),
                              React.createElement(DangerButton, {
                                  button_label: "Close section",
                                  disabled: this.buildingNeedsToBeRepaired(),
                                  on_click: this.removeSelection.bind(this),
                              }),
                              this.state.loading
                                  ? React.createElement(
                                        LoadingProgressBar,
                                        null,
                                    )
                                  : null,
                          )
                        : React.createElement(
                              Fragment,
                              null,
                              React.createElement(
                                  "p",
                                  { className: "my-2" },
                                  "Building does not need to be repaired.",
                              ),
                              React.createElement(DangerButton, {
                                  button_label: "Close section",
                                  on_click: this.removeSelection.bind(this),
                              }),
                          ),
                );
                return "repair settings";
            default:
                return null;
        }
    };
    BuildingInformation.prototype.getRebuildTime = function () {
        var rebuildTime = this.buildingTimeCalculation.calculateRebuildTime(
            this.props.building,
            this.props.kingdom_building_time_reduction,
        );
        if (rebuildTime > 60) {
            rebuildTime = rebuildTime / 60;
            return rebuildTime.toFixed(0) + " Hours";
        }
        return rebuildTime.toFixed(0) + " Minutes";
    };
    BuildingInformation.prototype.renderCosts = function () {
        return React.createElement(
            "dl",
            { className: "mb-5" },
            React.createElement("dt", null, "Stone Cost:"),
            React.createElement(
                "dd",
                null,
                this.calculateResourceCostWithReductions(
                    this.props.building.stone_cost,
                    false,
                    false,
                ),
            ),
            React.createElement("dt", null, "Clay Cost:"),
            React.createElement(
                "dd",
                null,
                this.calculateResourceCostWithReductions(
                    this.props.building.clay_cost,
                    false,
                    false,
                ),
            ),
            React.createElement("dt", null, "Wood Cost:"),
            React.createElement(
                "dd",
                null,
                this.calculateResourceCostWithReductions(
                    this.props.building.wood_cost,
                    false,
                    false,
                ),
            ),
            React.createElement("dt", null, "Iron Cost:"),
            React.createElement(
                "dd",
                null,
                this.calculateResourceCostWithReductions(
                    this.props.building.iron_cost,
                    false,
                    true,
                ),
            ),
            React.createElement("dt", null, "Steel Cost:"),
            React.createElement(
                "dd",
                null,
                this.calculateResourceCostWithReductions(
                    this.props.building.steel_cost,
                    false,
                    false,
                ),
            ),
            React.createElement("dt", null, "Population Cost:"),
            React.createElement(
                "dd",
                null,
                this.calculateResourceCostWithReductions(
                    this.props.building.population_required,
                    true,
                    false,
                ),
            ),
            React.createElement("dt", null, "Time till next level:"),
            React.createElement(
                "dd",
                null,
                this.state.upgrade_section !== "repair-building"
                    ? formatNumber(
                          this.buildingTimeCalculation
                              .calculateViewTime(
                                  this.props.building,
                                  this.state.to_level,
                                  this.props.kingdom_building_time_reduction,
                              )
                              .toFixed(2),
                      )
                    : this.getRebuildTime(),
            ),
        );
    };
    BuildingInformation.prototype.render = function () {
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
                this.props.building.is_locked
                    ? React.createElement(
                          DangerAlert,
                          null,
                          "You must train the appropriate Kingdom Passive skill to unlock this building. The skill name is the same as this building name.",
                      )
                    : null,
                this.state.success_message !== ""
                    ? React.createElement(
                          SuccessAlert,
                          null,
                          this.state.success_message,
                      )
                    : null,
                this.state.error_message !== ""
                    ? React.createElement(
                          DangerAlert,
                          null,
                          this.state.error_message,
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
                            React.createElement("dt", null, "Level:"),
                            React.createElement(
                                "dd",
                                null,
                                this.props.building.level,
                                "/",
                                this.props.building.max_level,
                            ),
                            React.createElement("dt", null, "Durability:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.props.building.current_durability,
                                ),
                                "/",
                                formatNumber(
                                    this.props.building.max_durability,
                                ),
                            ),
                            React.createElement("dt", null, "Defence:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.props.building.current_defence,
                                ),
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Morale Loss (per hour):",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.building.morale_decrease * 100
                                ).toFixed(2),
                                "%",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Morale Gain (per hour):",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.building.morale_increase * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                        this.props.building.is_resource_building
                            ? React.createElement(ResourceBuildingExpansion, {
                                  building: this.props.building,
                                  building_needs_to_be_repaired:
                                      this.buildingNeedsToBeRepaired(),
                                  character_id: this.props.character_id,
                                  user_id: this.props.user_id,
                              })
                            : null,
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
                            this.state.upgrade_section === "repair-building"
                                ? "Repair Costs"
                                : "Upgrade Costs (For 1 Level)",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                        }),
                        this.props.building.is_maxed &&
                            !this.buildingNeedsToBeRepaired()
                            ? React.createElement(
                                  "p",
                                  null,
                                  "Building is already max level.",
                              )
                            : this.props.is_in_queue
                              ? React.createElement(
                                    "p",
                                    null,
                                    "Building is currently in queue",
                                )
                              : React.createElement(
                                    Fragment,
                                    null,
                                    this.renderCosts(),
                                    this.state.upgrade_section !== null
                                        ? this.renderSelectedSection()
                                        : !this.props.is_in_queue &&
                                            !this.props.building.is_locked
                                          ? React.createElement(
                                                Fragment,
                                                null,
                                                React.createElement(
                                                    PrimaryButton,
                                                    {
                                                        button_label: "Upgrade",
                                                        on_click: function () {
                                                            return _this.showSelectedForm(
                                                                "upgrade",
                                                            );
                                                        },
                                                        additional_css: "mr-2",
                                                    },
                                                ),
                                                React.createElement(
                                                    PrimaryButton,
                                                    {
                                                        button_label: "Repair",
                                                        on_click: function () {
                                                            return _this.showSelectedForm(
                                                                "repair-building",
                                                            );
                                                        },
                                                    },
                                                ),
                                                this.props.building.is_special
                                                    ? React.createElement(
                                                          "p",
                                                          {
                                                              className:
                                                                  "my-4 text-sm",
                                                          },
                                                          "This building cannot be upgraded with gold.",
                                                      )
                                                    : null,
                                            )
                                          : null,
                                ),
                    ),
                ),
            ),
            this.state.show_time_help
                ? React.createElement(TimeHelpModal, {
                      is_in_minutes: true,
                      is_in_seconds: false,
                      manage_modal: this.manageHelpDialogue.bind(this),
                      time: this.state.time_needed,
                  })
                : null,
        );
    };
    return BuildingInformation;
})(React.Component);
export default BuildingInformation;
//# sourceMappingURL=building-information.js.map
