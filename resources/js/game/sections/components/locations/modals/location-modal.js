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
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import { fetchCost } from "../../../map/lib/teleportion-costs";
import { formatNumber } from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import SpecialLocationHelpModal from "./special-location-help-modal";
import LocationDetails from "./location-details";
var LocationModal = (function (_super) {
    __extends(LocationModal, _super);
    function LocationModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            can_afford: false,
            open_help_dialogue: false,
            distance: 0,
            cost: 0,
            time_out: 0,
            x: _this.props.location.x,
            y: _this.props.location.y,
        };
        return _this;
    }
    LocationModal.prototype.componentDidMount = function () {
        if (
            typeof this.props.character_position !== "undefined" &&
            typeof this.props.currencies !== "undefined"
        ) {
            this.setState(
                fetchCost(
                    this.props.location.x,
                    this.props.location.y,
                    this.props.character_position,
                    this.props.currencies,
                ),
            );
        }
    };
    LocationModal.prototype.handleTeleport = function () {
        if (typeof this.props.teleport_player !== "undefined") {
            this.props.teleport_player({
                x: this.state.x,
                y: this.state.y,
                cost: this.state.cost,
                timeout: this.state.time_out,
            });
        }
        this.props.handle_close();
    };
    LocationModal.prototype.teleportDisabled = function () {
        return (
            this.state.cost === 0 ||
            !this.state.can_afford ||
            !this.props.can_move ||
            this.props.is_automation_running ||
            this.props.is_dead
        );
    };
    LocationModal.prototype.manageHelpDialogue = function () {
        this.setState({
            open_help_dialogue: !this.state.open_help_dialogue,
        });
    };
    LocationModal.prototype.underwaterCavesHouse = function () {
        if (this.props.location.type_name === "Underwater Caves") {
            return React.createElement(
                Fragment,
                null,
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
                React.createElement(
                    "h5",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "Underwater Caves",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "Players in this location cannot explore, instead they will find their attack section has been updated to allow them to select a",
                    " ",
                    React.createElement(
                        "a",
                        {
                            href: "/information/ranked-fights",
                            target: "_blank",
                        },
                        "Rank ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                    " ",
                    "and a monster, from here the fights take place on the server.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-4" },
                    "Ranked fights are tracked and reset every month. Players can see who is at the top of the rank fights by opening the side menu and selecting Tops. From here you can see who is #1 in each of the ranks. Being #1 means killing the last creature in the list for that rank.",
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block",
                }),
            );
        }
        return null;
    };
    LocationModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title:
                    this.props.title +
                    " (X/Y): " +
                    this.props.location.x +
                    "/" +
                    this.props.location.y,
                secondary_actions: this.props.hide_secondary_button
                    ? null
                    : {
                          secondary_button_disabled: this.teleportDisabled(),
                          secondary_button_label: "Teleport",
                          handle_action: this.handleTeleport.bind(this),
                      },
            },
            React.createElement(LocationDetails, {
                location: this.props.location,
            }),
            this.underwaterCavesHouse(),
            this.state.cost > 0
                ? React.createElement(
                      "dl",
                      null,
                      React.createElement(
                          "dt",
                          null,
                          "Cost to teleport (gold):",
                      ),
                      React.createElement(
                          "dd",
                          {
                              className: clsx(
                                  { "text-gray-700": this.state.cost === 0 },
                                  {
                                      "text-green-600":
                                          this.state.can_afford &&
                                          this.state.cost > 0,
                                  },
                                  {
                                      "text-red-600":
                                          !this.state.can_afford &&
                                          this.state.cost > 0,
                                  },
                              ),
                          },
                          formatNumber(this.state.cost),
                      ),
                      React.createElement(
                          "dt",
                          null,
                          "Can afford to teleport:",
                      ),
                      React.createElement(
                          "dd",
                          null,
                          this.state.can_afford ? "Yes" : "No",
                      ),
                      React.createElement("dt", null, "Distance (miles):"),
                      React.createElement("dd", null, this.state.distance),
                      React.createElement("dt", null, "Timeout (minutes):"),
                      React.createElement("dd", null, this.state.time_out),
                  )
                : React.createElement(
                      WarningAlert,
                      null,
                      "You are too close to the location to be able to teleport.",
                  ),
            this.state.open_help_dialogue
                ? React.createElement(SpecialLocationHelpModal, {
                      manage_modal: this.manageHelpDialogue.bind(this),
                  })
                : null,
        );
    };
    return LocationModal;
})(React.Component);
export default LocationModal;
//# sourceMappingURL=location-modal.js.map
