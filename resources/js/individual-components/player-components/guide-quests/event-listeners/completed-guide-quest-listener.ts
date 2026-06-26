import { inject, injectable } from "tsyringe";
import { Channel } from "laravel-echo";
import CoreEventListener from "../../../../game/lib/game/event-listeners/core-event-listener";
import GuideQuestListenerDefinition, {
    GuideQuestCompletedHandler,
} from "./guide-quest-listener-definition";

@injectable()
export default class CompletedGuideQuestListener
    implements GuideQuestListenerDefinition
{
    private component?: GuideQuestCompletedHandler;
    private userId?: number;

    private guideQuestCompleted?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(component: GuideQuestCompletedHandler, userId: number): void {
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

                this.component.setState({
                    show_guide_quest_completed: event.showQuestCompleted,
                });
            },
        );
    }
}
