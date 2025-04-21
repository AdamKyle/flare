import GameListener from "../game-listener";
import Game from "../../../../game";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../core-event-listener";
import { Channel } from "laravel-echo";
import { mergeLocations } from "../../../../sections/map/helpers/merge-locations";
import MapState from "../../../../sections/map/types/map-state";

@injectable()
export default class MapListeners implements GameListener {
    private component?: Game;

    private userId?: number;

    private traverseUpdate?: Channel;

    private updateCharacterBasePosition?: Channel;

    private updateCraftingTypes?: Channel;

    private updateSpecialEventGoals?: Channel;

    private corruptedLocations?: Channel;

    private pctCommandUpdate?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    public initialize(component: Game, userId?: number): void {
        this.component = component;
        this.userId = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.traverseUpdate = echo.private("update-plane-" + this.userId);
            this.updateCharacterBasePosition = echo.private(
                "update-character-position-" + this.userId,
            );
            this.updateCraftingTypes = echo.private(
                "update-location-base-crafting-options-" + this.userId,
            );
            this.updateSpecialEventGoals = echo.private(
                "update-location-base-event-goals-" + this.userId,
            );
            this.pctCommandUpdate = echo.private("update-map-" + this.userId);

            this.corruptedLocations = echo.join("corrupt-locations");
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenToTraverse();
        this.listenToBasePositionUpdate();
        this.listForLocationBasedCraftingTypes();
        this.listenForEventGoalUpdates();
        this.listenForCorruptedLocationUpdates();
        this.listenForPctCommand();
    }

    /**
     * Listen to traverse updates.
     *
     * @private
     */
    private listenToTraverse() {
        if (!this.traverseUpdate) {
            return;
        }

        this.traverseUpdate.listen(
            "Game.Maps.Events.UpdateMap",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setStateFromData(event.mapDetails);

                this.component.updateQuestPlane(
                    event.mapDetails.character_map.game_map.name,
                );
            },
        );
    }

    /**
     * Listen to base position update.
     *
     * @private
     */
    private listenToBasePositionUpdate() {
        if (!this.updateCharacterBasePosition) {
            return;
        }

        this.updateCharacterBasePosition.listen(
            "Game.Maps.Events.UpdateCharacterBasePosition",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character),
                );

                character.base_position = event.basePosition;

                this.component.setState({
                    character: character,
                });
            },
        );
    }

    /**
     * Listen for specific location based or plane based crafting types that unlock.
     *
     * @private
     */
    private listForLocationBasedCraftingTypes() {
        if (!this.updateCraftingTypes) {
            return;
        }

        this.updateCraftingTypes.listen(
            "Game.Maps.Events.UpdateLocationBasedCraftingOptions",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character),
                );

                character.can_use_work_bench = event.canUseWorkBench;
                character.can_access_queen = event.canUseQueenOfHearts;
                character.can_access_labyrinth_oracle =
                    event.canAccessLabyrinthOracle;

                this.component.setState({
                    character: character,
                });
            },
        );
    }

    /**
     * Listen for global event goal updates.
     *
     * @private
     */
    private listenForEventGoalUpdates() {
        if (!this.updateSpecialEventGoals) {
            return;
        }

        this.updateSpecialEventGoals.listen(
            "Game.Maps.Events.UpdateLocationBasedEventGoals",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character),
                );

                character.can_use_event_goals_button = event.canSeeEventGoals;

                this.component.setState({
                    character: character,
                });
            },
        );
    }

    /**
     * Listen for corrupted location updates.
     *
     * @private
     */
    private listenForCorruptedLocationUpdates() {
        if (!this.corruptedLocations) {
            return;
        }

        this.corruptedLocations.listen(
            "Game.Raids.Events.CorruptLocations",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                let mapState: MapState = JSON.parse(
                    JSON.stringify(this.component.state.map_data),
                );

                const locations = mapState.locations;

                if (locations === null) {
                    return;
                }

                const mergedLocations = mergeLocations(
                    locations,
                    event.corruptedLocations,
                );

                mapState.locations = mergedLocations;

                this.component.setState({
                    map_data: mapState,
                });
            },
        );
    }

    /**
     * Listens for the player using PCT command.
     *
     * @private
     */
    private listenForPctCommand() {
        if (!this.pctCommandUpdate) {
            return;
        }

        this.pctCommandUpdate.listen(
            "Game.Maps.Events.UpdateMapDetailsBroadcast",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setStateFromData(event.map_data);

                this.component.updateQuestPlane(
                    event.map_data.character_map.game_map.name,
                );
            },
        );
    }
}
