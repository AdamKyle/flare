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
import NpcKingdomPin from "./npc-kingdom-pin";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import KingdomModal from "./modals/kingdom-modal";
var NpcKingdoms = (function (_super) {
    __extends(NpcKingdoms, _super);
    function NpcKingdoms(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            open_kingdom_modal: false,
            kingdom_id: 0,
            view_port: null,
        };
        return _this;
    }
    NpcKingdoms.prototype.componentDidMount = function () {
        viewPortWatcher(this);
    };
    NpcKingdoms.prototype.componentDidUpdate = function () {
        if (this.state.view_port !== null) {
            if (this.state.view_port < 600 && this.state.open_kingdom_modal) {
                this.setState({
                    kingdom_id: 0,
                    open_kingdom_modal: false,
                });
            }
        }
    };
    NpcKingdoms.prototype.openKingdomModal = function (kingdomId) {
        this.setState({
            open_kingdom_modal: true,
            kingdom_id: kingdomId,
        });
    };
    NpcKingdoms.prototype.closeKingdomModal = function () {
        this.setState({
            open_kingdom_modal: false,
            kingdom_id: 0,
        });
    };
    NpcKingdoms.prototype.renderKingdomPins = function () {
        var _this = this;
        if (this.props.kingdoms == null) {
            return;
        }
        return this.props.kingdoms.map(function (kingdom) {
            return React.createElement(NpcKingdomPin, {
                kingdom: kingdom,
                color: "#e3d60a",
                open_kingdom_modal: _this.openKingdomModal.bind(_this),
            });
        });
    };
    NpcKingdoms.prototype.teleportPlayer = function (data) {
        this.props.teleport_player(data);
    };
    NpcKingdoms.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.renderKingdomPins(),
            this.state.open_kingdom_modal
                ? React.createElement(KingdomModal, {
                      is_open: this.state.open_kingdom_modal,
                      kingdom_id: this.state.kingdom_id,
                      character_id: this.props.character_id,
                      currencies: this.props.currencies,
                      character_position: this.props.character_position,
                      teleport_player: this.teleportPlayer.bind(this),
                      handle_close: this.closeKingdomModal.bind(this),
                      can_move: this.props.can_move,
                      is_automation_running: this.props.is_automation_running,
                      is_dead: this.props.is_dead,
                      show_top_section: false,
                  })
                : null,
        );
    };
    return NpcKingdoms;
})(React.Component);
export default NpcKingdoms;
//# sourceMappingURL=npc-kingdoms.js.map
