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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Select from "react-select";
import clsx from "clsx";
import { formatNumber } from "../../../lib/game/format-number";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import TeleportHelpModal from "./teleport-help-modal";
import ManageTeleportModalState from "../lib/state/manage-teleport-modal-state";
import TeleportComponent from "../types/teleport-component";
var TeleportModal = (function (_super) {
    __extends(TeleportModal, _super);
    function TeleportModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            x_position: _this.props.character_position.x,
            y_position: _this.props.character_position.y,
            character_position: {
                x: _this.props.character_position.x,
                y: _this.props.character_position.y,
            },
            cost: 0,
            can_afford: false,
            distance: 0,
            time_out: 0,
            current_location: null,
            current_player_kingdom: null,
            current_enemy_kingdom: null,
            current_npc_kingdom: null,
            view_port: null,
            show_help: false,
        };
        _this.teleportComponent = new TeleportComponent(_this);
        return _this;
    }
    TeleportModal.prototype.componentDidMount = function () {
        viewPortWatcher(this);
        new ManageTeleportModalState(this).updateTeleportModalState();
    };
    TeleportModal.prototype.componentDidUpdate = function () {
        if (this.props.locations === null) {
            return;
        }
        new ManageTeleportModalState(this).updateTeleportModalState();
    };
    TeleportModal.prototype.manageHelpDialogue = function () {
        this.setState({
            show_help: !this.state.show_help,
        });
    };
    TeleportModal.prototype.setXPosition = function (data) {
        this.teleportComponent.setSelectedXPosition(data);
    };
    TeleportModal.prototype.setYPosition = function (data) {
        this.teleportComponent.setSelectedYPosition(data);
    };
    TeleportModal.prototype.setLocationData = function (data) {
        this.teleportComponent.setSelectedLocationData(data);
    };
    TeleportModal.prototype.setMyKingdomData = function (data) {
        this.teleportComponent.setSelectedMyKingdomData(data);
    };
    TeleportModal.prototype.setEnemyKingdomData = function (data) {
        this.teleportComponent.setSelectedEnemyKingdomData(data);
    };
    TeleportModal.prototype.setNPCKingdomData = function (data) {
        this.teleportComponent.setSelectedNPCKingdomData(data);
    };
    TeleportModal.prototype.convertToSelectable = function (data) {
        return this.teleportComponent.buildCoordinatesOptions(data);
    };
    TeleportModal.prototype.showMyKingdomSelect = function () {
        if (this.props.player_kingdoms === null) {
            return false;
        }
        return this.props.player_kingdoms.length > 0;
    };
    TeleportModal.prototype.teleportPlayer = function () {
        this.props.teleport_player({
            x: this.state.x_position,
            y: this.state.y_position,
            cost: this.state.cost,
            timeout: this.state.time_out,
        });
        this.props.handle_close();
    };
    TeleportModal.prototype.render = function () {
        var _this = this;
        if (this.props.coordinates === null) {
            return null;
        }
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: this.props.title,
                secondary_actions: {
                    handle_action: this.teleportPlayer.bind(this),
                    secondary_button_disabled: !this.state.can_afford,
                    secondary_button_label: "Teleport",
                },
            },
            React.createElement(
                "div",
                { className: "grid grid-cols-2" },
                React.createElement(
                    "div",
                    { className: "flex items-center" },
                    React.createElement(
                        "label",
                        { className: "w-[20px]" },
                        "X",
                    ),
                    React.createElement(
                        "div",
                        { className: "w-2/3" },
                        React.createElement(Select, {
                            onChange: this.setXPosition.bind(this),
                            options: this.convertToSelectable(
                                this.props.coordinates.x,
                            ),
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
                            value: {
                                label: this.state.x_position,
                                value: this.state.x_position,
                            },
                        }),
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "flex items-center" },
                    React.createElement(
                        "label",
                        { className: "w-[20px]" },
                        "Y",
                    ),
                    React.createElement(
                        "div",
                        { className: "w-2/3" },
                        React.createElement(Select, {
                            onChange: this.setYPosition.bind(this),
                            options: this.convertToSelectable(
                                this.props.coordinates.y,
                            ),
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
                            value: {
                                label: this.state.y_position,
                                value: this.state.y_position,
                            },
                        }),
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                { className: "grid gap-2 md:grid-cols-2" },
                React.createElement(
                    "div",
                    {
                        className: clsx("flex items-center", {
                            "col-start-1 col-span-2":
                                !this.showMyKingdomSelect(),
                        }),
                    },
                    React.createElement(
                        "label",
                        { className: "w-[100px]" },
                        "Locations:",
                    ),
                    React.createElement(
                        "div",
                        { className: "w-2/3" },
                        React.createElement(Select, {
                            onChange: this.setLocationData.bind(this),
                            options:
                                this.teleportComponent.buildLocationOptions(),
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
                            value: this.teleportComponent.getDefaultLocationValue(),
                        }),
                    ),
                ),
                this.showMyKingdomSelect()
                    ? React.createElement(
                          "div",
                          { className: "flex items-center" },
                          React.createElement(
                              "label",
                              { className: "w-[100px]" },
                              "My Kingdoms:",
                          ),
                          React.createElement(
                              "div",
                              { className: "w-2/3" },
                              React.createElement(Select, {
                                  onChange: this.setMyKingdomData.bind(this),
                                  options:
                                      this.teleportComponent.buildMyKingdomsOptions(),
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
                                  value: this.teleportComponent.getDefaultPlayerKingdomValue(),
                              }),
                          ),
                      )
                    : null,
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                { className: "flex items-center" },
                React.createElement(
                    "label",
                    { className: "w-[100px]" },
                    "Enemy Kingdoms",
                ),
                React.createElement(
                    "div",
                    { className: "w-2/3" },
                    React.createElement(Select, {
                        onChange: this.setEnemyKingdomData.bind(this),
                        options:
                            this.teleportComponent.buildEnemyKingdomOptions(),
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
                        value: this.teleportComponent.getDefaultEnemyKingdomValue(),
                    }),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                { className: "flex items-center" },
                React.createElement(
                    "label",
                    { className: "w-[100px]" },
                    "NPC Kingdoms",
                ),
                React.createElement(
                    "div",
                    { className: "w-2/3" },
                    React.createElement(Select, {
                        onChange: this.setNPCKingdomData.bind(this),
                        options:
                            this.teleportComponent.buildNpcKingdomOptions(),
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
                        value: this.teleportComponent.getDefaultNPCKingdomValue(),
                    }),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Cost in Gold:"),
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
                React.createElement("dt", null, "Can Afford:"),
                React.createElement(
                    "dd",
                    null,
                    this.state.can_afford ? "Yes" : "No",
                ),
                React.createElement("dt", null, "Distance:"),
                React.createElement("dd", null, this.state.distance, " Miles"),
                React.createElement("dt", null, "Timeout for:"),
                React.createElement(
                    "dd",
                    { className: "flex items-center" },
                    React.createElement(
                        "span",
                        null,
                        this.state.time_out,
                        " Minutes",
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
                                    className: "fas fa-info-circle",
                                }),
                                " ",
                                "Help",
                            ),
                        ),
                    ),
                ),
            ),
            this.state.show_help
                ? React.createElement(TeleportHelpModal, {
                      manage_modal: this.manageHelpDialogue.bind(this),
                  })
                : null,
        );
    };
    return TeleportModal;
})(React.Component);
export default TeleportModal;
//# sourceMappingURL=teleport-modal.js.map
