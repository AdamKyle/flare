import { injectable } from "tsyringe";
import TopsListener from "../../shared/event-listeners/tops-listener";
import { topsServiceContainer } from "../../shared/container/tops-container";
import CharacterTops from "../character-tops";
import CharacterTopsListenerDefinition from "./character-tops-listener-definition";
import TopsEventPayload from "../../shared/types/tops-event-payload";
import CharacterProfile from "../types/character-profile";

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

        if (this.selectedCharacterId !== null) {
            this.profileListener.initialize(
                "tops-character-inspection-" + this.selectedCharacterId,
                "Game.Tops.Events.CharacterTopsInspectionUpdated",
                (event: TopsEventPayload) => {
                    if (this.component && event.profile) {
                        this.component.applyProfile(
                            event.profile as CharacterProfile,
                        );
                    }
                },
            );
            this.profileListener.register();
        }
    }

    listen(): void {
        this.leaderboardListener.listen();
        this.profileListener.listen();
    }
}
