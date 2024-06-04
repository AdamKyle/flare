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
var ActionListeners = (function () {
    function ActionListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    ActionListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    ActionListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.unlockAlchemySkill = echo.private(
                "unlock-skill-" + this.userId,
            );
        } catch (e) {
            throw new Error(e);
        }
    };
    ActionListeners.prototype.listen = function () {
        this.listenToAlchemySkill();
    };
    ActionListeners.prototype.listenToAlchemySkill = function () {
        var _this = this;
        if (!this.unlockAlchemySkill) {
            return;
        }
        this.unlockAlchemySkill.listen(
            "Game.Quests.Events.UnlockSkillEvent",
            function () {
                if (!_this.component) {
                    return;
                }
                var character = JSON.parse(
                    JSON.stringify(_this.component.state.character),
                );
                character.is_alchemy_locked = false;
                _this.component.setState({
                    character: character,
                });
            },
        );
    };
    ActionListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        ActionListeners,
    );
    return ActionListeners;
})();
export default ActionListeners;
//# sourceMappingURL=action-listeners.js.map
