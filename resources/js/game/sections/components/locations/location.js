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
import LocationPin from "./location-pin";
import LocationModal from "./modals/location-modal";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
var Location = (function (_super) {
    __extends(Location, _super);
    function Location(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            open_location_modal: false,
            location: null,
            view_port: null,
        };
        return _this;
    }
    Location.prototype.componentDidMount = function () {
        viewPortWatcher(this);
    };
    Location.prototype.componentDidUpdate = function () {
        if (this.state.view_port !== null) {
            if (this.state.view_port < 600 && this.state.open_location_modal) {
                this.setState({
                    location: null,
                    open_location_modal: false,
                });
            }
        }
    };
    Location.prototype.closeLocationDetails = function () {
        this.setState({
            open_location_modal: false,
            location: null,
        });
    };
    Location.prototype.openLocationDetails = function (locationId) {
        if (this.props.locations === null) {
            return;
        }
        var location = this.props.locations.filter(function (location) {
            return location.id === locationId;
        });
        if (location.length > 0) {
            this.setState({
                open_location_modal: true,
                location: location[0],
            });
        }
    };
    Location.prototype.renderLocationPins = function () {
        var _this = this;
        if (this.props.locations === null) {
            return;
        }
        var locations = this.props.locations.filter(function (location) {
            return (
                location.game_map_id ===
                _this.props.character_position.game_map_id
            );
        });
        return locations.map(function (location) {
            if (location.pin_css_class !== null) {
                return React.createElement(LocationPin, {
                    key: "port-pin-" + location.id,
                    location: location,
                    openLocationDetails: _this.openLocationDetails.bind(_this),
                    pin_class: location.is_corrupted
                        ? "location-corrupted-pin"
                        : location.pin_css_class,
                });
            } else if (location.is_port) {
                return React.createElement(LocationPin, {
                    key: "port-pin-" + location.id,
                    location: location,
                    openLocationDetails: _this.openLocationDetails.bind(_this),
                    pin_class: location.is_corrupted
                        ? "location-corrupted-pin"
                        : "port-x-pin",
                });
            } else {
                return React.createElement(LocationPin, {
                    key: "location-pin-" + location.id,
                    location: location,
                    openLocationDetails: _this.openLocationDetails.bind(_this),
                    pin_class: location.is_corrupted
                        ? "location-corrupted-pin"
                        : "location-x-pin",
                });
            }
        });
    };
    Location.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.renderLocationPins(),
            this.state.open_location_modal &&
                typeof this.state.location !== "undefined" &&
                this.state.location !== null
                ? React.createElement(LocationModal, {
                      is_open: this.state.open_location_modal,
                      handle_close: this.closeLocationDetails.bind(this),
                      title: this.state.location.name,
                      location: this.state.location,
                      character_position: this.props.character_position,
                      currencies: this.props.currencies,
                      teleport_player: this.props.teleport_player,
                      hide_secondary_button: false,
                      can_move: this.props.can_move,
                      is_automation_running: this.props.is_automation_running,
                      is_dead: this.props.is_dead,
                  })
                : null,
        );
    };
    return Location;
})(React.Component);
export default Location;
//# sourceMappingURL=location.js.map
