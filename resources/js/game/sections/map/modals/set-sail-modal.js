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
import SetSailComponent from "../types/set-sail-component";
var SetSailModal = (function (_super) {
    __extends(SetSailModal, _super);
    function SetSailModal(props) {
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
            current_port: null,
            current_location: null,
            current_player_kingdom: null,
            current_enemy_kingdom: null,
            view_port: null,
            show_help: false,
        };
        _this.setSailComponent = new SetSailComponent(_this);
        return _this;
    }
    SetSailModal.prototype.componentDidMount = function () {
        viewPortWatcher(this);
        this.setSailComponent.setInitialCurrentSelectedPort();
    };
    SetSailModal.prototype.componentDidUpdate = function () {
        if (this.props.ports === null) {
            return;
        }
        this.setSailComponent.updateSelectedCurrentPort();
        if (this.state.view_port !== null) {
            if (this.state.view_port < 1600) {
                this.props.handle_close();
            }
        }
    };
    SetSailModal.prototype.setPortData = function (data) {
        this.setSailComponent.setSelectedPortData(data);
    };
    SetSailModal.prototype.setSail = function () {
        this.setSailComponent.setSail();
    };
    SetSailModal.prototype.manageHelpDialogue = function () {
        this.setState({
            show_help: !this.state.show_help,
        });
    };
    SetSailModal.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: this.props.title,
                secondary_actions: {
                    handle_action: this.setSail.bind(this),
                    secondary_button_disabled: !this.state.can_afford,
                    secondary_button_label: "Set Sail",
                },
            },
            React.createElement(
                "div",
                { className: "flex items-center" },
                React.createElement(
                    "label",
                    { className: "w-[50px]" },
                    "Ports",
                ),
                React.createElement(
                    "div",
                    { className: "w-2/3" },
                    React.createElement(Select, {
                        onChange: this.setPortData.bind(this),
                        options: this.setSailComponent.buildSetSailOptions(),
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
                        value: this.setSailComponent.getDefaultPortValue(),
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
    return SetSailModal;
})(React.Component);
export default SetSailModal;
//# sourceMappingURL=set-sail-modal.js.map
