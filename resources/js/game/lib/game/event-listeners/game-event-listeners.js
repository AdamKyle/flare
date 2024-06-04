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
import { singleton } from "tsyringe";
import CompletedGuideQuestListener from "../../../../individual-components/player-components/guide-quests/event-listeners/completed-guide-quest-listener";
import { serviceContainer } from "../../containers/core-container";
import ActionListeners from "./game/action-listeners";
import CharacterListeners from "./game/character-listeners";
import KingdomListeners from "./game/kingdom-listeners";
import MapListeners from "./game/map-listeners";
import MonsterListeners from "./game/monster-listeners";
import QuestListeners from "./game/quest-listeners";
var GameEventListeners = (function () {
    function GameEventListeners() {}
    GameEventListeners.prototype.initialize = function (component, userId) {
        this.component = component;
        this.userId = userId;
        this.mapListeners = serviceContainer().fetch(MapListeners);
        this.characterListeners = serviceContainer().fetch(CharacterListeners);
        this.monsterListeners = serviceContainer().fetch(MonsterListeners);
        this.kingdomListener = serviceContainer().fetch(KingdomListeners);
        this.actionListeners = serviceContainer().fetch(ActionListeners);
        this.questListeners = serviceContainer().fetch(QuestListeners);
        this.guideQuestCompletedListener = serviceContainer().fetch(
            CompletedGuideQuestListener,
        );
    };
    GameEventListeners.prototype.registerEvents = function () {
        if (!this.component || !this.userId) {
            throw new Error(
                "Need to call initialize on GameEventListeners first.",
            );
        }
        if (this.mapListeners) {
            this.mapListeners.initialize(this.component, this.userId);
            this.mapListeners.register();
        }
        if (this.characterListeners) {
            this.characterListeners.initialize(this.component, this.userId);
            this.characterListeners.register();
        }
        if (this.monsterListeners) {
            this.monsterListeners.initialize(this.component, this.userId);
            this.monsterListeners.register();
        }
        if (this.kingdomListener) {
            this.kingdomListener.initialize(this.component, this.userId);
            this.kingdomListener.register();
        }
        if (this.actionListeners) {
            this.actionListeners.initialize(this.component, this.userId);
            this.actionListeners.register();
        }
        if (this.questListeners) {
            this.questListeners.initialize(this.component, this.userId);
            this.questListeners.register();
        }
        if (this.guideQuestCompletedListener) {
            this.guideQuestCompletedListener.initialize(
                this.component,
                this.userId,
            );
            this.guideQuestCompletedListener.register();
        }
    };
    GameEventListeners.prototype.listenToEvents = function () {
        if (this.mapListeners) {
            this.mapListeners.listen();
        }
        if (this.characterListeners) {
            this.characterListeners.listen();
        }
        if (this.monsterListeners) {
            this.monsterListeners.listen();
        }
        if (this.kingdomListener) {
            this.kingdomListener.listen();
        }
        if (this.actionListeners) {
            this.actionListeners.listen();
        }
        if (this.questListeners) {
            this.questListeners.listen();
        }
        if (this.guideQuestCompletedListener) {
            this.guideQuestCompletedListener.listen();
        }
    };
    GameEventListeners = __decorate([singleton()], GameEventListeners);
    return GameEventListeners;
})();
export default GameEventListeners;
//# sourceMappingURL=game-event-listeners.js.map
