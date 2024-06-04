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
var MonsterListeners = (function () {
    function MonsterListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    MonsterListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    MonsterListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.monsterUpdate = echo.private(
                "update-monsters-list-" + this.userId,
            );
            this.raidMonsterUpdate = echo.private(
                "update-raid-monsters-list-" + this.userId,
            );
        } catch (e) {
            throw new Error(e);
        }
    };
    MonsterListeners.prototype.listen = function () {
        this.listenForMonsterUpdates();
        this.listenForRaidMonsterUpdates();
    };
    MonsterListeners.prototype.listenForMonsterUpdates = function () {
        var _this = this;
        if (!this.monsterUpdate) {
            return;
        }
        this.monsterUpdate.listen(
            "Game.Maps.Events.UpdateMonsterList",
            function (event) {
                if (!_this.component) {
                    return;
                }
                if (_this.component.state.action_data === null) {
                    return;
                }
                var actionData = JSON.parse(
                    JSON.stringify(_this.component.state.action_data),
                );
                actionData.monsters = event.monsters;
                _this.component.setState({
                    action_data: actionData,
                });
            },
        );
    };
    MonsterListeners.prototype.listenForRaidMonsterUpdates = function () {
        var _this = this;
        if (!this.raidMonsterUpdate) {
            return;
        }
        this.raidMonsterUpdate.listen(
            "Game.Maps.Events.UpdateRaidMonsters",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var self = _this;
                setTimeout(function () {
                    if (!self.component) {
                        return;
                    }
                    if (self.component.state.action_data === null) {
                        return;
                    }
                    var actionData = JSON.parse(
                        JSON.stringify(self.component.state.action_data),
                    );
                    actionData.raid_monsters = event.raidMonsters;
                    self.component.setState({
                        action_data: actionData,
                    });
                }, 1000);
            },
        );
    };
    MonsterListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        MonsterListeners,
    );
    return MonsterListeners;
})();
export default MonsterListeners;
//# sourceMappingURL=monster-listeners.js.map
