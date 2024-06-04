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
var ActionTimerListeners = (function () {
    function ActionTimerListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    ActionTimerListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    ActionTimerListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.attackTimeout = echo.private(
                "show-timeout-bar-" + this.userId,
            );
            this.craftingTimeout = echo.private(
                "show-crafting-timeout-bar-" + this.userId,
            );
        } catch (e) {
            throw new Error(e);
        }
    };
    ActionTimerListeners.prototype.listen = function () {
        this.listenForAttackTimerUpdate();
        this.listenForCraftingTimerUpdate();
    };
    ActionTimerListeners.prototype.listenForAttackTimerUpdate = function () {
        var _this = this;
        if (!this.attackTimeout) {
            return;
        }
        this.attackTimeout.listen(
            "Game.Core.Events.ShowTimeOutEvent",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState({
                    attack_time_out: event.forLength,
                });
            },
        );
    };
    ActionTimerListeners.prototype.listenForCraftingTimerUpdate = function () {
        var _this = this;
        if (!this.craftingTimeout) {
            return;
        }
        this.craftingTimeout.listen(
            "Game.Core.Events.ShowCraftingTimeOutEvent",
            function (event) {
                if (!_this.component) {
                    return;
                }
                _this.component.setState({
                    crafting_time_out: event.timeout,
                });
            },
        );
    };
    ActionTimerListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        ActionTimerListeners,
    );
    return ActionTimerListeners;
})();
export default ActionTimerListeners;
//# sourceMappingURL=action-timer-listeners.js.map
