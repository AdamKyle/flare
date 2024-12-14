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

            this.openGuideQuestButton = echo.private(
                "force-open-guide-quest-modal-" + this.userId,
            );
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForForceOpenGuideQuestModal();
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
