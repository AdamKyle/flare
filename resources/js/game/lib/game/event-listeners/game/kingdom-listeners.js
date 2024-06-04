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
import CoreEventListener from "../core-event-listener";
var KingdomListeners = (function () {
    function KingdomListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    KingdomListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    KingdomListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.kingdomLogUpdate = echo.private(
                "update-new-kingdom-logs-" + this.userId,
            );
            this.kingdomsUpdate = echo.private(
                "add-kingdom-to-map-" + this.userId,
            );
            this.kingdomsTableUpdate = echo.private(
                "kingdoms-list-data-" + this.userId,
            );
            this.npcKingdomsUpdate = echo.join("npc-kingdoms-update");
            this.globalMapUpdate = echo.join("global-map-update");
        } catch (e) {
            throw new Error(e);
        }
    };
    KingdomListeners.prototype.listen = function () {
        var _this = this;
        this.listenForKingdomLogUpdates();
        this.listenToPlayerKingdomsTableUpdate();
        setTimeout(function () {
            _this.listenToPlayerKingdomUpdates();
        }, 1000);
        setTimeout(function () {
            _this.listenForNPCKingdomUpdates();
        }, 1100);
        setTimeout(function () {
            _this.listenToGlobalKingdomUpdates();
        }, 1200);
    };
    KingdomListeners.prototype.listenForKingdomLogUpdates = function () {
        var _this = this;
        if (!this.kingdomLogUpdate) {
            return;
        }
        this.kingdomLogUpdate.listen(
            "Game.Kingdoms.Events.UpdateKingdomLogs",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState(
                    {
                        kingdom_logs: event.logs,
                    },
                    function () {
                        if (!_this.component) {
                            return;
                        }
                        _this.component.updateLogIcon();
                    },
                );
            },
        );
    };
    KingdomListeners.prototype.listenToPlayerKingdomUpdates = function () {
        var _this = this;
        if (!this.kingdomsUpdate) {
            return;
        }
        this.kingdomsUpdate.listen(
            "Game.Kingdoms.Events.AddKingdomToMap",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var mapData = JSON.parse(
                    JSON.stringify(_this.component.state.map_data),
                );
                mapData.player_kingdoms = event.myKingdoms;
                _this.component.setState({
                    map_data: mapData,
                });
            },
        );
    };
    KingdomListeners.prototype.listenToPlayerKingdomsTableUpdate = function () {
        var _this = this;
        if (!this.kingdomsTableUpdate) {
            return;
        }
        this.kingdomsTableUpdate.listen(
            "Game.Kingdoms.Events.UpdateKingdomTable",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState({
                    kingdoms: event.kingdoms,
                });
            },
        );
    };
    KingdomListeners.prototype.listenForNPCKingdomUpdates = function () {
        var _this = this;
        if (!this.npcKingdomsUpdate) {
            return;
        }
        this.npcKingdomsUpdate.listen(
            "Game.Kingdoms.Events.UpdateNPCKingdoms",
            function (event) {
                if (!_this.component) {
                    return;
                }
                if (_this.component.state.map_data === null) {
                    return;
                }
                if (_this.component.state.map_data.map_name === event.mapName) {
                    var mapData = JSON.parse(
                        JSON.stringify(_this.component.state.map_data),
                    );
                    mapData.npc_kingdoms = event.npcKingdoms;
                    _this.component.setState({
                        map_data: mapData,
                    });
                }
            },
        );
    };
    KingdomListeners.prototype.listenToGlobalKingdomUpdates = function () {
        var _this = this;
        if (!this.globalMapUpdate) {
            return;
        }
        this.globalMapUpdate.listen(
            "Game.Kingdoms.Events.UpdateGlobalMap",
            function (event) {
                if (!_this.component) {
                    return;
                }
                if (_this.component.state.character === null) {
                    return;
                }
                var mapData = JSON.parse(
                    JSON.stringify(_this.component.state.map_data),
                );
                var playerKingdomsFilter = mapData.player_kingdoms.filter(
                    function (playerKingdom) {
                        if (
                            !event.npcKingdoms.some(function (kingdom) {
                                return kingdom.id === playerKingdom.id;
                            })
                        ) {
                            return playerKingdom;
                        }
                    },
                );
                var enemyKingdoms = event.otherKingdoms.filter(
                    function (kingdom) {
                        if (!_this.component) {
                            return false;
                        }
                        if (_this.component.state.character === null) {
                            return false;
                        }
                        return (
                            kingdom.character_id !==
                            _this.component.state.character.id
                        );
                    },
                );
                mapData.enemy_kingdoms.concat(enemyKingdoms);
                mapData.npc_kingdoms.concat(event.npcKingdoms);
                mapData.player_kingdoms.concat(playerKingdomsFilter);
                _this.component.setState({
                    map_data: mapData,
                });
            },
        );
    };
    KingdomListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        KingdomListeners,
    );
    return KingdomListeners;
})();
export default KingdomListeners;
//# sourceMappingURL=kingdom-listeners.js.map
