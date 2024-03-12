
import {inject, injectable} from "tsyringe";
import {Channel} from "laravel-echo";
import CoreEventListener from "../../../../game/lib/game/event-listeners/core-event-listener";
import GuideButton from "../guide-button";
import GuideQuestListenerDefinition from "./guide-quest-listener-definition";

@injectable()
export default class GuideQuestListener implements GuideQuestListenerDefinition {

    private component?: GuideButton;
    private userId?: number;

    private guideQuestButton?: Channel;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    initialize(component: GuideButton, userId: number): void {
        this.component = component;
        this.userId    = userId
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.guideQuestButton = echo.private(
                "guide-quest-button-" + this.userId
            );
        } catch (e: any|unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForGuideQuestUpdates();
    }

    /**
     * Listen to the guide quest update - if we should show it or not.
     *
     * @protected
     */
    protected listenForGuideQuestUpdates() {
        if (!this.guideQuestButton) {
            return;
        }

        this.guideQuestButton.listen(
            "Game.GuideQuests.Events.RemoveGuideQuestButton",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    show_button: false,
                });
            }
        );
    }
}
