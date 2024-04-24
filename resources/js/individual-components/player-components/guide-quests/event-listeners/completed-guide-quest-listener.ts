import { inject, injectable } from "tsyringe";
import { Channel } from "laravel-echo";
import CoreEventListener from "../../../../game/lib/game/event-listeners/core-event-listener";
import GuideButton from "../guide-button";
import GuideQuestListenerDefinition from "./guide-quest-listener-definition";
import Game from "../../../../game/game";

@injectable()
export default class CompletedGuideQuestListener
    implements GuideQuestListenerDefinition
{
    private component?: Game | GuideButton;
    private userId?: number;

    private guideQuestCompleted?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(component: Game | GuideButton, userId: number): void {
        this.component = component;
        this.userId = userId;
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.guideQuestCompleted = echo.private(
                "guide-quest-completed-toast-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listForGuideQuestToasts();
    }

    /**
     * Listen for when the guide quest toast should fire.
     *
     * @protected
     */
    protected listForGuideQuestToasts() {
        if (!this.guideQuestCompleted) {
            return;
        }

        this.guideQuestCompleted.listen(
            "Game.GuideQuests.Events.ShowGuideQuestCompletedToast",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                if (this.component instanceof Game) {
                    this.component.setState({
                        show_guide_quest_completed: event.showQuestCompleted,
                    });
                }

                if (this.component instanceof GuideButton) {
                    this.component.setState({
                        show_guide_quest_completed: event.showQuestCompleted,
                    });
                }
            },
        );
    }
}
