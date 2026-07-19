import { injectable } from "tsyringe";
import TopsListener from "../../shared/event-listeners/tops-listener";
import { topsServiceContainer } from "../../shared/container/tops-container";
import CharacterTops from "../character-tops";
import CharacterTopsListenerDefinition from "./character-tops-listener-definition";
import TopsEventPayload from "../../shared/types/tops-event-payload";

@injectable()
export default class CharacterTopsListener
    implements CharacterTopsListenerDefinition
{
    private leaderboardListener: TopsListener;
    private profileListener: TopsListener;
    private component?: CharacterTops;
    private selectedCharacterId: number | null = null;

    constructor() {
        this.leaderboardListener = topsServiceContainer().fetch(TopsListener);
        this.profileListener = topsServiceContainer().fetch(TopsListener);
    }

    initialize(
        component: CharacterTops,
        selectedCharacterId: number | null,
    ): void {
        this.component = component;
        this.selectedCharacterId = selectedCharacterId;
    }

    register(): void {
        if (this.selectedCharacterId !== null) {
            this.profileListener.initialize(
                "tops-character-inspection-" + this.selectedCharacterId,
                "Game.Tops.Events.CharacterTopsInspectionUpdated",
                (event: TopsEventPayload) => {
                    if (this.component && event.profile !== undefined) {
                        this.component.fetchProfile(
                            this.selectedCharacterId as number,
                        );
                    }
                },
            );
            this.profileListener.register();

            return;
        }

        this.leaderboardListener.initialize(
            "tops-character-leaderboard",
            "Game.Tops.Events.CharacterTopsUpdated",
            (event: TopsEventPayload) => {
                if (this.component && event.leaderboard) {
                    this.component.fetchLeaderboard();
                }
            },
        );
        this.leaderboardListener.register();
    }

    listen(): void {
        if (this.selectedCharacterId !== null) {
            this.profileListener.listen();

            return;
        }

        this.leaderboardListener.listen();
    }
}
