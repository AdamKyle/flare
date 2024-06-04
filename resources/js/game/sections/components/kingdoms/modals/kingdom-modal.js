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
import { fetchCost } from "../../../map/lib/teleportion-costs";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import { formatNumber } from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import KingdomDetails from "./components/kingdom-details";
var KingdomModal = (function (_super) {
    __extends(KingdomModal, _super);
    function KingdomModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            can_afford: false,
            distance: 0,
            cost: 0,
            time_out: 0,
            x: 0,
            y: 0,
            loading: true,
            title: "",
            npc_owned: false,
            action_in_progress: false,
            can_attack_kingdom: false,
        };
        return _this;
    }
    KingdomModal.prototype.updateLoading = function (kingdomDetails) {
        var costState = fetchCost(
            kingdomDetails.x_position,
            kingdomDetails.y_position,
            this.props.character_position,
            this.props.currencies,
        );
        var newState = __assign(__assign({}, costState), {
            loading: false,
            x: kingdomDetails.x_position,
            y: kingdomDetails.y_position,
            title: this.buildTitle(kingdomDetails),
            can_attack_kingdom:
                kingdomDetails.is_npc_owned ||
                (kingdomDetails.is_enemy_kingdom &&
                    !kingdomDetails.is_protected),
            npc_owned: kingdomDetails.is_npc_owned,
        });
        var state = JSON.parse(JSON.stringify(this.state));
        this.setState(__assign(__assign({}, state), newState));
    };
    KingdomModal.prototype.teleportDisabled = function () {
        return (
            this.state.cost === 0 ||
            !this.state.can_afford ||
            !this.props.can_move ||
            this.props.is_automation_running ||
            this.props.is_dead
        );
    };
    KingdomModal.prototype.handleTeleport = function () {
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
    KingdomModal.prototype.buildTitle = function (kingdomDetails) {
        var title =
            kingdomDetails.name +
            " (X/Y): " +
            kingdomDetails.x_position +
            "/" +
            kingdomDetails.y_position;
        if (kingdomDetails.is_npc_owned) {
            return title + " [NPC Owned]";
        }
        if (kingdomDetails.is_enemy_kingdom) {
            return title + " [Enemy]";
        }
        return title;
    };
    KingdomModal.prototype.updateActionInProgress = function () {
        this.setState({
            action_in_progress: !this.state.action_in_progress,
        });
    };
    KingdomModal.prototype.closeModal = function () {
        this.props.handle_close();
    };
    KingdomModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: this.state.loading ? "One moment ..." : this.state.title,
                primary_button_disabled: this.state.action_in_progress,
                secondary_actions: {
                    secondary_button_disabled: this.teleportDisabled(),
                    secondary_button_label: "Teleport",
                    handle_action: this.handleTeleport.bind(this),
                },
            },
            React.createElement(
                Fragment,
                null,
                React.createElement(KingdomDetails, {
                    kingdom_id: this.props.kingdom_id,
                    character_id: this.props.character_id,
                    update_loading: this.updateLoading.bind(this),
                    show_top_section: this.props.show_top_section,
                    allow_purchase: this.state.npc_owned,
                    update_action_in_progress:
                        this.updateActionInProgress.bind(this),
                    close_modal: this.closeModal.bind(this),
                    can_attack_kingdom: this.state.can_attack_kingdom,
                }),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                this.state.cost > 0
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement("h4", null, "Teleport Details"),
                          React.createElement(
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
                                          {
                                              "text-gray-700":
                                                  this.state.cost === 0,
                                          },
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
                              React.createElement(
                                  "dt",
                                  null,
                                  "Distance (miles):",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  this.state.distance,
                              ),
                              React.createElement(
                                  "dt",
                                  null,
                                  "Timeout (minutes):",
                              ),
                              React.createElement(
                                  "dd",
                                  null,
                                  this.state.time_out,
                              ),
                          ),
                      )
                    : React.createElement(
                          WarningAlert,
                          null,
                          "You are too close to the location to be able to teleport.",
                      ),
            ),
        );
    };
    return KingdomModal;
})(React.Component);
export default KingdomModal;
//# sourceMappingURL=kingdom-modal.js.map
