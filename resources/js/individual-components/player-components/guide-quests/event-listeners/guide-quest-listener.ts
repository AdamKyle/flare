import { inject, injectable } from "tsyringe";
import { Channel } from "laravel-echo";
import CoreEventListener from "../../../../game/lib/game/event-listeners/core-event-listener";
import GuideButton from "../guide-button";
import GuideQuestListenerDefinition from "./guide-quest-listener-definition";
import Game from "../../../../game/game";

@injectable()
export default class GuideQuestListener
    implements GuideQuestListenerDefinition
{
    private component?: Game | GuideButton;
    private userId?: number;

    private guideQuestButton?: Channel;

    private openGuideQuestButton?: Channel;

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

            this.guideQuestButton = echo.private(
                "guide-quest-button-" + this.userId,
            );

            this.openGuideQuestButton = echo.private(
                "force-open-guide-quest-modal-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForGuideQuestUpdates();
        this.listenForForceOpenGuideQuestModal();
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

                if (this.component instanceof GuideButton) {
                    this.component.setState({
                        show_button: false,
                    });
                }
            },
        );
    }

    protected listenForForceOpenGuideQuestModal() {
        if (!this.openGuideQuestButton) {
            return;
        }

        this.openGuideQuestButton.listen(
            "Game.GuideQuests.Events.OpenGuideQuestModal",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                setTimeout(
                    () => {
                        if (this.component instanceof GuideButton) {
                            this.component.setState({
                                is_modal_open: event.openButton,
                            });
                        }
                    },
                    (
                        import.meta as unknown as {
                            env: { VITE_APP_ENV: string };
                        }
                    ).env.VITE_APP_ENV === "production"
                        ? 3500
                        : 500,
                );
            },
        );
    }
}
