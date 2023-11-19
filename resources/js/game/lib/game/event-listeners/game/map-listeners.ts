import GameListener from "../game-listener";
import Game from "../../../../game";
import {inject, injectable} from "tsyringe";
import CoreEventListener from "../core-event-listener";
import {Channel} from "laravel-echo";

@injectable()
export default class MapListeners implements GameListener {

    private component?: Game;

    private userId?: number;

    private traverseUpdate?: Channel;

    private updateCharacterBasePosition?: Channel;

    private updateCraftingTypes?: Channel;

    private updateSpecialShopsAccess?: Channel;

    private updateSpecialEventGoals?: Channel;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    public initialize(component: Game, userId: number): void {
        this.component = component;
        this.userId    = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.traverseUpdate = echo.private("update-plane-" + this.userId);
            this.updateCharacterBasePosition = echo.private("update-character-position-" + this.userId);
            this.updateCraftingTypes = echo.private("update-location-base-crafting-options-" + this.userId);
            this.updateSpecialShopsAccess = echo.private("update-location-base-shops-" + this.userId);
            this.updateSpecialEventGoals = echo.private("update-location-base-event-goals-" + this.userId);
        } catch (e: any|unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenToTraverse();
        this.listenToBasePositionUpdate();
        this.listForLocationBasedCraftingTypes();
        this.listForUpdatesToSpecialShopsAccess();
        this.listenForEventGoalUpdates();
    }

    /**
     * Listen to traverse updates.
     *
     * @protected
     */
    protected listenToTraverse() {
        if (!this.traverseUpdate) {
            return
        }

        this.traverseUpdate.listen(
            "Game.Maps.Events.UpdateMap",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setStateFromData(event.mapDetails);

                this.component.updateQuestPlane(
                    event.mapDetails.character_map.game_map.name
                );
            }
        );
    }

    /**
     * Listen to base position update.
     *
     * @protected
     */
    protected listenToBasePositionUpdate() {
        if (!this.updateCharacterBasePosition) {
            return
        }

        this.updateCharacterBasePosition.listen(
            "Game.Maps.Events.UpdateCharacterBasePosition",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character)
                );

                character.base_position = event.basePosition;

                this.component.setState({
                    character: character,
                });
            }
        );
    }

    /**
     * Listen for specific location based or plane based crafting types that unlock.
     *
     * @protected
     */
    protected listForLocationBasedCraftingTypes() {
        if (!this.updateCraftingTypes) {
            return
        }

        this.updateCraftingTypes.listen(
            "Game.Maps.Events.UpdateLocationBasedCraftingOptions",
            (event: any) => {

                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character)
                );

                character.can_use_work_bench = event.canUseWorkBench;
                character.can_access_queen = event.canUseQueenOfHearts;

                this.component.setState({
                    character: character,
                });
            }
        );
    }

    /**
     * Listen for when players should see specific special shop buttons.
     *
     * @protected
     */
    protected listForUpdatesToSpecialShopsAccess() {

        if (!this.updateSpecialShopsAccess) {
            return;
        }

        this.updateSpecialShopsAccess.listen(
            "Game.Maps.Events.UpdateLocationBasedSpecialShops",
            (event: any) => {

                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character)
                );

                character.can_access_hell_forged =
                    event.canAccessHellForgedShop;
                character.can_access_purgatory_chains =
                    event.canAccessPurgatoryChainsShop;

                this.component.setState({
                    character: character,
                });
            }
        );
    }

    /**
     * Listen for global event goal updates.
     *
     * @protected
     */
    protected listenForEventGoalUpdates() {

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
                    JSON.stringify(this.component.state.character)
                );

                character.can_use_event_goals_button = event.canSeeEventGoals;

                this.component.setState({
                    character: character,
                });
            }
        );
    }
}
