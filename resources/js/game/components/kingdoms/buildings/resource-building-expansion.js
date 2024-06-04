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
import React from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import clsx from "clsx";
import Ajax from "../../../lib/ajax/ajax";
import { formatNumber } from "../../../lib/game/format-number";
import { serviceContainer } from "../../../lib/containers/core-container";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
var ResourceBuildingExpansion = (function (_super) {
    __extends(ResourceBuildingExpansion, _super);
    function ResourceBuildingExpansion(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            expanding: false,
            error_message: null,
            success_message: null,
            time_remaining_for_expansion: 0,
            expansion_details: null,
        };
        _this.gameEventListener = serviceContainer().fetch(CoreEventListener);
        _this.gameEventListener.initialize();
        _this.updateExpansionDetails = _this.gameEventListener
            .getEcho()
            .private(
                "update-building-expansion-details-" + _this.props.user_id,
            );
        return _this;
    }
    ResourceBuildingExpansion.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "kingdom/building-expansion/details/" +
                    this.props.building.id +
                    "/" +
                    this.props.character_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        expansion_details: result.data.expansion_details,
                        time_remaining_for_expansion: result.data.time_left,
                    });
                },
                function (error) {
                    _this.setState({ loading: false });
                    if (typeof error.response != "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
        this.updateExpansionDetails.listen(
            "Game.Kingdoms.Events.UpdateBuildingExpansion",
            function (event) {
                _this.setState({
                    expansion_details: event.kingdomBuildingExpansion,
                    time_remaining_for_expansion: event.timeLeft,
                });
            },
        );
    };
    ResourceBuildingExpansion.prototype.canNotExpand = function (building) {
        if (!building.is_maxed) {
            return true;
        }
        if (this.props.building_needs_to_be_repaired) {
            return true;
        }
        if (this.state.loading) {
            return true;
        }
        if (this.state.expansion_details !== null) {
            if (this.state.expansion_details.expansions_left <= 0) {
                return true;
            }
        }
        if (this.state.time_remaining_for_expansion > 0) {
            return true;
        }
        return this.state.expanding;
    };
    ResourceBuildingExpansion.prototype.expandBuilding = function () {
        var _this = this;
        this.setState(
            {
                error_message: null,
                success_message: null,
                expanding: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdom/building-expansion/expand/" +
                            _this.props.building.id +
                            "/" +
                            _this.props.character_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                expanding: false,
                                success_message: result.data.message,
                                time_remaining_for_expansion:
                                    result.data.time_left,
                            });
                        },
                        function (error) {
                            _this.setState({ expanding: false });
                            if (typeof error.response != "undefined") {
                                var response = error.response;
                                var message = response.data.message;
                                if (response.data.error) {
                                    message = response.data.error;
                                }
                                _this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    };
    ResourceBuildingExpansion.prototype.getResourceType = function () {
        if (this.props.building.wood_increase > 0) {
            return "wood";
        }
        if (this.props.building.iron_increase > 0) {
            return "iron";
        }
        if (this.props.building.clay_increase > 0) {
            return "clay";
        }
        if (this.props.building.stone_increase > 0) {
            return "stone";
        }
        if (this.props.building.is_farm) {
            return "population";
        }
        return "UNKNOWN";
    };
    ResourceBuildingExpansion.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
            }),
            React.createElement("h3", null, "Expand"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
            }),
            React.createElement(
                "p",
                { className: "my-2" },
                "Once a building reaches it max level and does not need to be repaired, one can expand its production to produce more resources allowing you to recruit more units.",
            ),
            React.createElement(
                "p",
                { className: "my-2" },
                React.createElement(
                    "a",
                    {
                        href: "/information/kingdom-resource-expansion",
                        target: "_blank",
                    },
                    "Learn more about expanding resource buildings",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            this.state.expansion_details !== null
                ? this.state.expansion_details.expansions_left === 0
                    ? React.createElement(
                          "p",
                          {
                              className:
                                  "my-4 text-green-700 dark:text-green-500",
                          },
                          "This building cannot be expanded anymore.",
                      )
                    : React.createElement(
                          React.Fragment,
                          null,
                          React.createElement(
                              "dl",
                              { className: "my-4" },
                              React.createElement(
                                  "dt",
                                  null,
                                  "Current expansions",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  this.state.expansion_details.expansion_count,
                              ),
                              React.createElement(
                                  "dt",
                                  null,
                                  "Expansions left",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  this.state.expansion_details.expansions_left,
                              ),
                              React.createElement(
                                  "dt",
                                  null,
                                  "Time required (Minutes)",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  this.state.expansion_details
                                      .minutes_until_next_expansion,
                              ),
                              React.createElement(
                                  "dt",
                                  null,
                                  "Will gain additional:",
                                  " ",
                                  this.getResourceType(),
                                  " per expansion",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  formatNumber(
                                      this.state.expansion_details
                                          .resource_increases,
                                  ),
                              ),
                          ),
                          React.createElement("div", {
                              className:
                                  "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                          }),
                          React.createElement(
                              "h3",
                              null,
                              "Cost for next expansion",
                          ),
                          React.createElement("div", {
                              className:
                                  "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                          }),
                          React.createElement(
                              "dl",
                              { className: "my-4" },
                              React.createElement("dt", null, "Stone cost"),
                              React.createElement(
                                  "dd",
                                  null,
                                  formatNumber(
                                      this.state.expansion_details
                                          .resource_costs.stone,
                                  ),
                              ),
                              React.createElement("dt", null, "Clay cost"),
                              React.createElement(
                                  "dd",
                                  null,
                                  formatNumber(
                                      this.state.expansion_details
                                          .resource_costs.clay,
                                  ),
                              ),
                              React.createElement("dt", null, "Iron cost"),
                              React.createElement(
                                  "dd",
                                  null,
                                  formatNumber(
                                      this.state.expansion_details
                                          .resource_costs.iron,
                                  ),
                              ),
                              React.createElement("dt", null, "Steel cost"),
                              React.createElement(
                                  "dd",
                                  null,
                                  formatNumber(
                                      this.state.expansion_details
                                          .resource_costs.steel,
                                  ),
                              ),
                              React.createElement(
                                  "dt",
                                  null,
                                  "Population cost",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  formatNumber(
                                      this.state.expansion_details
                                          .resource_costs.population,
                                  ),
                              ),
                              React.createElement("dt", null, "Gold bars cost"),
                              React.createElement(
                                  "dd",
                                  null,
                                  formatNumber(
                                      this.state.expansion_details
                                          .gold_bars_cost,
                                  ),
                              ),
                          ),
                      )
                : null,
            React.createElement(
                DangerAlert,
                {
                    additional_css: clsx({
                        hidden: this.state.error_message === null,
                        "my-4": this.state.error_message !== null,
                    }),
                },
                this.state.error_message,
            ),
            React.createElement(
                SuccessAlert,
                {
                    additional_css: clsx({
                        hidden: this.state.success_message === null,
                        "my-4": this.state.error_message !== null,
                    }),
                },
                this.state.success_message,
            ),
            this.state.expanding
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(PrimaryOutlineButton, {
                button_label: "Expand Production",
                on_click: this.expandBuilding.bind(this),
                disabled: this.canNotExpand(this.props.building),
                additional_css: "my-2",
            }),
            React.createElement(TimerProgressBar, {
                time_remaining: this.state.time_remaining_for_expansion,
                time_out_label: "Expanding",
            }),
        );
    };
    return ResourceBuildingExpansion;
})(React.Component);
export default ResourceBuildingExpansion;
//# sourceMappingURL=resource-building-expansion.js.map
