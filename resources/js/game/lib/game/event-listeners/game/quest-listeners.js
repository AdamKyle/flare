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
var QuestListeners = (function () {
    function QuestListeners(coreEventListener) {
        this.coreEventListener = coreEventListener;
    }
    QuestListeners.prototype.initialize = function (component, userId) {
        this.component = component;
    };
    QuestListeners.prototype.register = function () {
        this.coreEventListener.initialize();
        try {
            var echo = this.coreEventListener.getEcho();
            this.questUpdate = echo.join("update-quests");
            this.raidQuestUpdate = echo.join("update-raid-quests");
        } catch (e) {
            throw new Error(e);
        }
    };
    QuestListeners.prototype.listen = function () {
        this.listenForQuestUpdates();
        this.listenForRaidQuestUpdates();
    };
    QuestListeners.prototype.listenForQuestUpdates = function () {
        var _this = this;
        if (!this.questUpdate) {
            return;
        }
        this.questUpdate.listen(
            "Game.Quests.Events.UpdateQuests",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var quests = JSON.parse(
                    JSON.stringify(_this.component.state.quests),
                );
                if (quests === null) {
                    return;
                }
                quests.quests = event.quests;
                quests.is_winter_event = event.isWinterEvent;
                _this.component.setState({
                    quests: quests,
                });
            },
        );
    };
    QuestListeners.prototype.listenForRaidQuestUpdates = function () {
        var _this = this;
        if (!this.raidQuestUpdate) {
            return;
        }
        this.raidQuestUpdate.listen(
            "Game.Quests.Events.UpdateRaidQuests",
            function (event) {
                if (!_this.component) {
                    return;
                }
                var quests = JSON.parse(
                    JSON.stringify(_this.component.state.quests),
                );
                if (quests === null) {
                    return;
                }
                quests.raid_quests = event.raidQuests;
                _this.component.setState({
                    quests: quests,
                });
            },
        );
    };
    QuestListeners = __decorate(
        [
            injectable(),
            __param(0, inject(CoreEventListener)),
            __metadata("design:paramtypes", [CoreEventListener]),
        ],
        QuestListeners,
    );
    return QuestListeners;
})();
export default QuestListeners;
//# sourceMappingURL=quest-listeners.js.map
