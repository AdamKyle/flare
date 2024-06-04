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
import CoreEventListener from "../../../../game/lib/game/event-listeners/core-event-listener";
import GuideButton from "../guide-button";
var GuideQuestListener = (function () {
    function GuideQuestListener(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    GuideQuestListener.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
    };
    GuideQuestListener.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.guideQuestButton = echo.private(
                "guide-quest-button-" + this.userId,
            );
        } catch (e) {
            throw new Error(e);
        }
    };
    GuideQuestListener.prototype.listen = function () {
        this.listenForGuideQuestUpdates();
    };
    GuideQuestListener.prototype.listenForGuideQuestUpdates = function () {
        var _this = this;
        if (!this.guideQuestButton) {
            return;
        }
        this.guideQuestButton.listen(
            "Game.GuideQuests.Events.RemoveGuideQuestButton",
            function (event) {
                if (!_this.component) {
                    return;
                }
                if (_this.component instanceof GuideButton) {
                    _this.component.setState({
                        show_button: false,
                    });
                }
            },
        );
    };
    GuideQuestListener = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        GuideQuestListener,
    );
    return GuideQuestListener;
})();
export default GuideQuestListener;
//# sourceMappingURL=guide-quest-listener.js.map
