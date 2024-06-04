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
import KingdomPin from "./kingdom-pin";
import KingdomModal from "./modals/kingdom-modal";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
var Kingdoms = (function (_super) {
    __extends(Kingdoms, _super);
    function Kingdoms(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            open_kingdom_modal: false,
            kingdom_id: 0,
            view_port: null,
        };
        return _this;
    }
    Kingdoms.prototype.componentDidMount = function () {
        viewPortWatcher(this);
    };
    Kingdoms.prototype.componentDidUpdate = function () {
        if (this.state.view_port !== null) {
            if (this.state.view_port < 600 && this.state.open_kingdom_modal) {
                this.setState({
                    kingdom_id: 0,
                    open_kingdom_modal: false,
                });
            }
        }
    };
    Kingdoms.prototype.openKingdomModal = function (kingdomId) {
        this.setState({
            open_kingdom_modal: true,
            kingdom_id: kingdomId,
        });
    };
    Kingdoms.prototype.closeKingdomModal = function () {
        this.setState({
            open_kingdom_modal: false,
            kingdom_id: 0,
        });
    };
    Kingdoms.prototype.teleportPlayer = function (data) {
        this.props.teleport_player(data);
    };
    Kingdoms.prototype.renderKingdomPins = function () {
        var _this = this;
        if (this.props.kingdoms == null) {
            return;
        }
        return this.props.kingdoms.map(function (kingdom) {
            return React.createElement(KingdomPin, {
                kingdom: kingdom,
                open_kingdom_modal: _this.openKingdomModal.bind(_this),
            });
        });
    };
    Kingdoms.prototype.render = function () {
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
                      show_top_section: true,
                  })
                : null,
        );
    };
    return Kingdoms;
})(React.Component);
export default Kingdoms;
//# sourceMappingURL=kingdoms.js.map
