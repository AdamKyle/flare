var __decorate =
    (this && this.__decorate) ||
    function (decorators, target, key, desc) {
        var c = arguments.length,
            r =
                c < 3
                    ? target
                    : desc === null
                      ? (desc = Object.getOwnPropertyDescriptor(target, key))
                      : desc,
            d;
        if (
            typeof Reflect === "object" &&
            typeof Reflect.decorate === "function"
        )
            r = Reflect.decorate(decorators, target, key, desc);
        else
            for (var i = decorators.length - 1; i >= 0; i--)
                if ((d = decorators[i]))
                    r =
                        (c < 3
                            ? d(r)
                            : c > 3
                              ? d(target, key, r)
                              : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
var __metadata =
    (this && this.__metadata) ||
    function (k, v) {
        if (
            typeof Reflect === "object" &&
            typeof Reflect.metadata === "function"
        )
            return Reflect.metadata(k, v);
    };
var __param =
    (this && this.__param) ||
    function (paramIndex, decorator) {
        return function (target, key) {
            decorator(target, key, paramIndex);
        };
    };
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
var FactionLoyaltyListeners = (function () {
    function FactionLoyaltyListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    FactionLoyaltyListeners.prototype.initialize = function (
        component,
        userId,
    ) {
        this.component = component;
        this.userId = userId;
    };
    FactionLoyaltyListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.factionLoyaltyUpdate = echo.private(
                "faction-loyalty-update-" + this.userId,
            );
        } catch (e) {
            throw new Error(e);
        }
    };
    FactionLoyaltyListeners.prototype.listen = function () {
        this.listenForFactionLoyaltyUpdate();
    };
    FactionLoyaltyListeners.prototype.listenForFactionLoyaltyUpdate =
        function () {
            var _this = this;
            if (!this.factionLoyaltyUpdate) {
                return;
            }
            this.factionLoyaltyUpdate.listen(
                "Game.Factions.FactionLoyalty.Events.FactionLoyaltyUpdate",
                function (event) {
                    if (!_this.component) {
                        return;
                    }
                    _this.component.setState(
                        {
                            npcs: event.factionLoyalty.npcs,
                            game_map_name: event.factionLoyalty.map_name,
                            faction_loyalty:
                                event.factionLoyalty.faction_loyalty,
                        },
                        function () {
                            if (!_this.component) {
                                return;
                            }
                            _this.component.setInitialSelectedFactionInfo(
                                event.factionLoyalty.faction_loyalty,
                                event.factionLoyalty.npcs,
                            );
                        },
                    );
                },
            );
        };
    FactionLoyaltyListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        FactionLoyaltyListeners,
    );
    return FactionLoyaltyListeners;
})();
export default FactionLoyaltyListeners;
//# sourceMappingURL=faction-loyalty-listeners.js.map
