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
import Game from "../../../../game/game";
var CompletedGuideQuestListener = (function () {
    function CompletedGuideQuestListener(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    CompletedGuideQuestListener.prototype.initialize = function (
        component,
        userId,
    ) {
        this.component = component;
        this.userId = userId;
    };
    CompletedGuideQuestListener.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.guideQuestCompleted = echo.private(
                "guide-quest-completed-toast-" + this.userId,
            );
        } catch (e) {
            throw new Error(e);
        }
    };
    CompletedGuideQuestListener.prototype.listen = function () {
        this.listForGuideQuestToasts();
    };
    CompletedGuideQuestListener.prototype.listForGuideQuestToasts =
        function () {
            var _this = this;
            if (!this.guideQuestCompleted) {
                return;
            }
            this.guideQuestCompleted.listen(
                "Game.GuideQuests.Events.ShowGuideQuestCompletedToast",
                function (event) {
                    if (!_this.component) {
                        return;
                    }
                    if (_this.component instanceof Game) {
                        _this.component.setState({
                            show_guide_quest_completed:
                                event.showQuestCompleted,
                        });
                    }
                    if (_this.component instanceof GuideButton) {
                        _this.component.setState({
                            show_guide_quest_completed:
                                event.showQuestCompleted,
                        });
                    }
                },
            );
        };
    CompletedGuideQuestListener = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        CompletedGuideQuestListener,
    );
    return CompletedGuideQuestListener;
})();
export default CompletedGuideQuestListener;
//# sourceMappingURL=completed-guide-quest-listener.js.map
