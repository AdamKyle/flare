import { singleton, inject } from "tsyringe";
import Game from "../../../game";
import { serviceContainer } from "../../containers/core-container";
import GameListener from "./game-listener";
import MapListeners from "./game/map-listeners";
import CharacterListeners from "./game/character-listeners";
import MonsterListeners from "./game/monster-listeners";
import KingdomListeners from "./game/kingdom-listeners";
import ActionListeners from "./game/action-listeners";
import QuestListeners from "./game/quest-listeners";
import GuideQuestListener from "../../../../individual-components/player-components/guide-quests/event-listeners/guide-quest-listener";
import GuideQuestListenerDefinition from "../../../../individual-components/player-components/guide-quests/event-listeners/guide-quest-listener-definition";
import CompletedGuideQuestListener from "../../../../individual-components/player-components/guide-quests/event-listeners/completed-guide-quest-listener";

@singleton()
export default class GameEventListeners {
    private component?: Game;

    private userId?: number;

    private mapListeners?: GameListener;

    private characterListeners?: GameListener;

    private monsterListeners?: GameListener;

    private kingdomListener?: GameListener;

    private actionListeners?: GameListener;

    private questListeners?: GameListener;

    private guideQuestCompletedListener?: GuideQuestListenerDefinition;

    public initialize(component: Game, userId: number): void {
        this.component = component;
        this.userId = userId;

        this.mapListeners =
            serviceContainer().fetch<GameListener>(MapListeners);
        this.characterListeners =
            serviceContainer().fetch<GameListener>(CharacterListeners);
        this.monsterListeners =
            serviceContainer().fetch<GameListener>(MonsterListeners);
        this.kingdomListener =
            serviceContainer().fetch<GameListener>(KingdomListeners);
        this.actionListeners =
            serviceContainer().fetch<GameListener>(ActionListeners);
        this.questListeners =
            serviceContainer().fetch<GameListener>(QuestListeners);
        this.guideQuestCompletedListener =
            serviceContainer().fetch<GuideQuestListenerDefinition>(
                CompletedGuideQuestListener,
            );
    }

    public registerEvents(): void {
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
    }

    public listenToEvents(): void {
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
    }
}
