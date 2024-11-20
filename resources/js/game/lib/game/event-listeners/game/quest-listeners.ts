import GameListener from "../game-listener";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../core-event-listener";
import Game from "../../../../game";
import { Channel } from "laravel-echo";
import QuestType from "../../types/quests/quest-type";

@injectable()
export default class QuestListeners implements GameListener {
    private component?: Game;

    private questUpdate?: Channel;
    private raidQuestUpdate?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(component: Game): void {
        this.component = component;
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.questUpdate = echo.join("update-quests");

            this.raidQuestUpdate = echo.join("update-raid-quests");
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForQuestUpdates();
        this.listenForRaidQuestUpdates();
    }

    /**
     * Listen for quest updates.
     *
     * @protected
     */
    protected listenForQuestUpdates() {
        if (!this.questUpdate) {
            return;
        }

        this.questUpdate.listen(
            "Game.Quests.Events.UpdateQuests",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let quests: QuestType | null = JSON.parse(
                    JSON.stringify(this.component.state.quests),
                );

                if (quests === null) {
                    return;
                }

                quests.quests = event.quests;
                quests.is_winter_event = event.isWinterEvent;

                this.component.setState({
                    quests: quests,
                });
            },
        );
    }

    /**
     * Listen for raid quest updates
     *
     * @protected
     */
    protected listenForRaidQuestUpdates() {
        if (!this.raidQuestUpdate) {
            return;
        }

        this.raidQuestUpdate.listen(
            "Game.Quests.Events.UpdateRaidQuests",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let quests: QuestType | null = JSON.parse(
                    JSON.stringify(this.component.state.quests),
                );

                if (quests === null) {
                    return;
                }

                quests.raid_quests = event.raidQuests;

                this.component.setState({
                    quests: quests,
                });
            },
        );
    }
}
