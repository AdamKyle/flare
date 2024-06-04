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
import { mergeLocations } from "../../../../sections/map/helpers/merge-locations";
var MapListeners = (function () {
    function MapListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    MapListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    MapListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.traverseUpdate = echo.private("update-plane-" + this.userId);
            this.updateCharacterBasePosition = echo.private(
                "update-character-position-" + this.userId,
            );
            this.updateCraftingTypes = echo.private(
                "update-location-base-crafting-options-" + this.userId,
            );
            this.updateSpecialShopsAccess = echo.private(
                "update-location-base-shops-" + this.userId,
            );
            this.updateSpecialEventGoals = echo.private(
                "update-location-base-event-goals-" + this.userId,
            );
            this.corruptedLocations = echo.join("corrupt-locations");
        } catch (e) {
            throw new Error(e);
        }
    };
    MapListeners.prototype.listen = function () {
        this.listenToTraverse();
        this.listenToBasePositionUpdate();
        this.listForLocationBasedCraftingTypes();
        this.listForUpdatesToSpecialShopsAccess();
        this.listenForEventGoalUpdates();
        this.listenForCorruptedLocationUpdates();
    };
    MapListeners.prototype.listenToTraverse = function () {
        var _this = this;
        if (!this.traverseUpdate) {
            return;
        }
        this.traverseUpdate.listen(
            "Game.Maps.Events.UpdateMap",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setStateFromData(event.mapDetails);
                _this.component.updateQuestPlane(
                    event.mapDetails.character_map.game_map.name,
                );
            },
        );
    };
    MapListeners.prototype.listenToBasePositionUpdate = function () {
        var _this = this;
        if (!this.updateCharacterBasePosition) {
            return;
        }
        this.updateCharacterBasePosition.listen(
            "Game.Maps.Events.UpdateCharacterBasePosition",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var character = JSON.parse(
                    JSON.stringify(_this.component.state.character),
                );
                character.base_position = event.basePosition;
                _this.component.setState({
                    character: character,
                });
            },
        );
    };
    MapListeners.prototype.listForLocationBasedCraftingTypes = function () {
        var _this = this;
        if (!this.updateCraftingTypes) {
            return;
        }
        this.updateCraftingTypes.listen(
            "Game.Maps.Events.UpdateLocationBasedCraftingOptions",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var character = JSON.parse(
                    JSON.stringify(_this.component.state.character),
                );
                character.can_use_work_bench = event.canUseWorkBench;
                character.can_access_queen = event.canUseQueenOfHearts;
                character.can_access_labyrinth_oracle =
                    event.canAccessLabyrinthOracle;
                _this.component.setState({
                    character: character,
                });
            },
        );
    };
    MapListeners.prototype.listForUpdatesToSpecialShopsAccess = function () {
        var _this = this;
        if (!this.updateSpecialShopsAccess) {
            return;
        }
        this.updateSpecialShopsAccess.listen(
            "Game.Maps.Events.UpdateLocationBasedSpecialShops",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var character = JSON.parse(
                    JSON.stringify(_this.component.state.character),
                );
                character.can_access_hell_forged =
                    event.canAccessHellForgedShop;
                character.can_access_purgatory_chains =
                    event.canAccessPurgatoryChainsShop;
                character.can_access_twisted_memories =
                    event.camAccessTwistedEarthShop;
                _this.component.setState({
                    character: character,
                });
            },
        );
    };
    MapListeners.prototype.listenForEventGoalUpdates = function () {
        var _this = this;
        if (!this.updateSpecialEventGoals) {
            return;
        }
        this.updateSpecialEventGoals.listen(
            "Game.Maps.Events.UpdateLocationBasedEventGoals",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var character = JSON.parse(
                    JSON.stringify(_this.component.state.character),
                );
                character.can_use_event_goals_button = event.canSeeEventGoals;
                _this.component.setState({
                    character: character,
                });
            },
        );
    };
    MapListeners.prototype.listenForCorruptedLocationUpdates = function () {
        var _this = this;
        if (!this.corruptedLocations) {
            return;
        }
        this.corruptedLocations.listen(
            "Game.Raids.Events.CorruptLocations",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var mapState = JSON.parse(
                    JSON.stringify(_this.component.state.map_data),
                );
                var locations = mapState.locations;
                if (locations === null) {
                    return;
                }
                var mergedLocations = mergeLocations(
                    locations,
                    event.corruptedLocations,
                );
                mapState.locations = mergedLocations;
                _this.component.setState({
                    map_data: mapState,
                });
            },
        );
    };
    MapListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        MapListeners,
    );
    return MapListeners;
})();
export default MapListeners;
//# sourceMappingURL=map-listeners.js.map
