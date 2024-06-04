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
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import { canSettleHere } from "../lib/location-helpers";
import ViewLocationState from "../lib/state/view-location-state";
import TeleportModal from "../modals/teleport-modal";
import MovePlayer from "../lib/ajax/move-player";
import SetSailModal from "../modals/set-sail-modal";
import Conjuration from "../modals/conjuration";
import SettleKingdomModal from "../modals/settle-kingdom-modal";
import ViewLocationModal from "../modals/view-location-modal";
var MapActions = (function (_super) {
    __extends(MapActions, _super);
    function MapActions(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_location_details: false,
            open_teleport_modal: false,
            open_set_sail: false,
            open_conjure: false,
            open_settle_modal: false,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            npc_kingdom_id: null,
            location: null,
        };
        return _this;
    }
    MapActions.prototype.componentDidMount = function () {
        new ViewLocationState(this).updateActionState();
    };
    MapActions.prototype.componentDidUpdate = function () {
        new ViewLocationState(this).updateActionState();
    };
    MapActions.prototype.teleportPlayer = function (data) {
        new MovePlayer(this).teleportPlayer(
            data,
            this.props.character_id,
            this.props.update_map_state,
        );
    };
    MapActions.prototype.setSail = function (data) {
        new MovePlayer(this).setSail(
            data,
            this.props.character_id,
            this.props.view_port,
            this.props.update_map_state,
        );
    };
    MapActions.prototype.ports = function () {
        if (this.props.locations === null) {
            return [];
        }
        return this.props.locations.filter(function (location) {
            return location.is_port;
        });
    };
    MapActions.prototype.canSettleKingdom = function () {
        return (
            !this.props.can_move ||
            this.props.is_dead ||
            this.props.is_automation_running ||
            !canSettleHere(this)
        );
    };
    MapActions.prototype.canSetSail = function () {
        return (
            !this.props.can_move ||
            this.props.is_dead ||
            this.props.is_automation_running ||
            this.props.port_location === null
        );
    };
    MapActions.prototype.canDoAction = function () {
        return (
            !this.props.can_move ||
            this.props.is_dead ||
            this.props.is_automation_running
        );
    };
    MapActions.prototype.canViewLocation = function () {
        return (
            this.state.location !== null ||
            this.state.player_kingdom_id !== null ||
            this.state.enemy_kingdom_id !== null ||
            this.state.npc_kingdom_id !== null
        );
    };
    MapActions.prototype.manageViewLocation = function () {
        this.setState({
            show_location_details: !this.state.show_location_details,
        });
    };
    MapActions.prototype.manageTeleportModal = function () {
        this.setState({
            open_teleport_modal: !this.state.open_teleport_modal,
        });
    };
    MapActions.prototype.manageSetSailModal = function () {
        this.setState({
            open_set_sail: !this.state.open_set_sail,
        });
    };
    MapActions.prototype.manageConjureModal = function () {
        this.setState({
            open_conjure: !this.state.open_conjure,
        });
    };
    MapActions.prototype.manageSettleModal = function () {
        this.setState({
            open_settle_modal: !this.state.open_settle_modal,
        });
    };
    MapActions.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "grid md:grid-cols-5 gap-2" },
                React.createElement(PrimaryOutlineButton, {
                    button_label: "View Location Details",
                    on_click: this.manageViewLocation.bind(this),
                    disabled: !this.canViewLocation(),
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Settle Kingdom",
                    on_click: this.manageSettleModal.bind(this),
                    disabled: this.canSettleKingdom(),
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Set Sail",
                    on_click: this.manageSetSailModal.bind(this),
                    disabled: this.canSetSail(),
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Teleport",
                    on_click: this.manageTeleportModal.bind(this),
                    disabled: this.canDoAction(),
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Conjure",
                    on_click: this.manageConjureModal.bind(this),
                    disabled:
                        this.canDoAction() || !this.props.can_engage_celestial,
                }),
            ),
            this.state.open_conjure && this.props.can_engage_celestial
                ? React.createElement(Conjuration, {
                      is_open: this.state.open_conjure,
                      handle_close: this.manageConjureModal.bind(this),
                      title: "Conjuration",
                      character_id: this.props.character_id,
                  })
                : null,
            this.state.open_teleport_modal
                ? React.createElement(TeleportModal, {
                      is_open: this.state.open_teleport_modal,
                      title: "Teleport",
                      teleport_player: this.teleportPlayer.bind(this),
                      handle_close: this.manageTeleportModal.bind(this),
                      coordinates: this.props.coordinates,
                      character_position: this.props.character_position,
                      currencies: this.props.character_currencies,
                      view_port: this.props.view_port,
                      locations: this.props.locations,
                      player_kingdoms: this.props.player_kingdoms,
                      enemy_kingdoms: this.props.enemy_kingdoms,
                      npc_kingdoms: this.props.npc_kingdoms,
                  })
                : null,
            this.state.open_set_sail
                ? React.createElement(SetSailModal, {
                      is_open: this.state.open_set_sail,
                      set_sail: this.setSail.bind(this),
                      handle_close: this.manageSetSailModal.bind(this),
                      title: "Set Sail",
                      character_position: this.props.character_position,
                      currencies: this.props.character_currencies,
                      ports: this.ports(),
                  })
                : null,
            this.state.open_settle_modal
                ? React.createElement(SettleKingdomModal, {
                      is_open: this.state.open_settle_modal,
                      handle_close: this.manageSettleModal.bind(this),
                      character_id: this.props.character_id,
                      map_id: this.props.map_id,
                      can_settle: this.canSettleKingdom(),
                  })
                : null,
            this.state.show_location_details
                ? React.createElement(ViewLocationModal, {
                      player_kingdom_id: this.state.player_kingdom_id,
                      enemy_kingdom_id: this.state.enemy_kingdom_id,
                      npc_kingdom_id: this.state.npc_kingdom_id,
                      location: this.state.location,
                      handle_close: this.manageViewLocation.bind(this),
                      character_id: this.props.character_id,
                  })
                : null,
        );
    };
    return MapActions;
})(React.Component);
export default MapActions;
//# sourceMappingURL=map-actions.js.map
