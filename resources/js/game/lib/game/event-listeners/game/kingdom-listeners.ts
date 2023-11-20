import GameListener from "../game-listener";
import Game from "../../../../game";
import {inject, injectable} from "tsyringe";
import CoreEventListener from "../core-event-listener";
import {Channel} from "laravel-echo";
import KingdomLogDetails from "../../kingdoms/kingdom-log-details";
import KingdomDetails from "../../kingdoms/kingdom-details";
import NpcKingdomsDetails from "../../../../sections/map/types/map/npc-kingdoms-details";
import PlayerKingdomsDetails from "../../../../sections/map/types/map/player-kingdoms-details";

@injectable()
export default class KingdomListeners implements GameListener {

    private component?: Game;

    private userId?: number;

    private kingdomLogUpdate?: Channel;

    private kingdomUpdates?: Channel;

    private kingdomsUpdate?: Channel;

    private npcKingdomsUpdate?: Channel;

    private globalMapUpdate?: Channel;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    public initialize(component: Game, userId: number): void {
        this.component = component;
        this.userId    = userId;
    }

    public register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.kingdomUpdates = echo.private("update-kingdom-" + this.userId);

            this.kingdomLogUpdate = echo.private("update-new-kingdom-logs-" + this.userId);

            this.kingdomsUpdate = echo.private("add-kingdom-to-map-" + this.userId);

            this.npcKingdomsUpdate = echo.join("npc-kingdoms-update");

            this.globalMapUpdate = echo.join("global-map-update");
        } catch (e: any|unknown) {
            throw new Error(e);
        }
    }

    public listen(): void {
        this.listenForKingdomLogUpdates();
        this.listenToKingdomUpdates();
        this.listenToPlayerKingdomUpdates();
        this.listenForNPCKingdomUpdates();
        this.listenToGlobalKingdomUpdates();
    }

    /**
     * Listen to traverse updates.
     *
     * @protected
     */
    protected listenForKingdomLogUpdates() {
        if (!this.kingdomLogUpdate) {
            return
        }

        this.kingdomLogUpdate.listen(
            "Game.Kingdoms.Events.UpdateKingdomLogs",
            (event: { logs: KingdomLogDetails[] | [] }) => {

                if (!this.component) {
                    return;
                }

                this.component.setState(
                    {
                        kingdom_logs: event.logs,
                    },
                    () => {
                        if (!this.component) {
                            return;
                        }

                        this.component.updateLogIcon();
                    }
                );
            }
        );
    }

    /**
     * Listen to when one more kingdoms update.
     *
     * @protected
     */
    protected listenToKingdomUpdates() {
        if (!this.kingdomUpdates) {
            return
        }

        this.kingdomUpdates.listen(
            "Game.Kingdoms.Events.UpdateKingdom",
            (event: { kingdom: KingdomDetails }) => {

                if (!this.component) {
                    return;
                }

                const eventKingdom = event.kingdom;

                if (Array.isArray(eventKingdom)) {
                    this.component.setState({
                        kingdoms: eventKingdom,
                    });

                    return;
                }

                let currentKingdoms = JSON.parse(
                    JSON.stringify(this.component.state.kingdoms)
                );

                const index = currentKingdoms.findIndex(
                    (kingdom: KingdomDetails) => kingdom.id === eventKingdom.id
                );

                if (index > -1) {
                    currentKingdoms[index] = eventKingdom;
                }

                this.component.setState({
                    kingdoms: currentKingdoms,
                });
            }
        );
    }

    /**
     * Listen to when a players kingdoms update.
     *
     * @protected
     */
    protected listenToPlayerKingdomUpdates() {
        if (!this.kingdomsUpdate) {
            return
        }

        this.kingdomsUpdate.listen(
            "Game.Kingdoms.Events.AddKingdomToMap",
            (event: any) => {

                if (!this.component) {
                    return;
                }

                let mapData = JSON.parse(
                    JSON.stringify(this.component.state.map_data)
                )

                mapData.player_kingdoms = event.myKingdoms;

                this.component.setState({
                    map_data: mapData,
                });
            }
        );
    }

    /**
     * Listen for when NPC kingdom updates happen.
     *
     * @protected
     */
    protected listenForNPCKingdomUpdates() {
        if (!this.npcKingdomsUpdate) {
            return
        }

        this.npcKingdomsUpdate.listen(
            "Game.Kingdoms.Events.UpdateNPCKingdoms",
            (event: {
                npcKingdoms: NpcKingdomsDetails[] | [];
                mapName: string;
            }) => {

                if (!this.component) {
                    return;
                }

                if (this.component.state.map_data === null) {
                    return;
                }

                if (this.component.state.map_data.map_name === event.mapName) {

                    let mapData = JSON.parse(
                        JSON.stringify(this.component.state.map_data)
                    );

                    mapData.npc_kingdoms = event.npcKingdoms;

                    this.component.setState({
                        map_data: mapData,
                    });
                }
            }
        );
    }

    /**
     * Listen to the global kingdom update event.
     *
     * @protected
     */
    protected listenToGlobalKingdomUpdates() {
        if (!this.globalMapUpdate) {
            return
        }

        this.globalMapUpdate.listen(
            "Game.Kingdoms.Events.UpdateGlobalMap",
            (event: any) => {

                if (!this.component) {
                    return;
                }

                if (this.component.state.character === null) {
                    return;
                }

                let mapData = JSON.parse(
                    JSON.stringify(this.component.state.map_data)
                );

                const playerKingdomsFilter = mapData.player_kingdoms.filter(
                    (playerKingdom: PlayerKingdomsDetails) => {
                        if (
                            !event.npcKingdoms.some(
                                (kingdom: NpcKingdomsDetails) =>
                                    kingdom.id === playerKingdom.id
                            )
                        ) {
                            return playerKingdom;
                        }
                    }
                );

                const enemyKingdoms = event.otherKingdoms.filter(
                    (kingdom: PlayerKingdomsDetails) => {

                        if (!this.component) {
                            return false;
                        }

                        if (this.component.state.character === null) {
                            return false;
                        }

                        return kingdom.character_id !== this.component.state.character.id
                    }
                );

                mapData.enemy_kingdoms = enemyKingdoms;

                mapData.npc_kingdoms = event.npcKingdoms;
                mapData.player_kingdoms = playerKingdomsFilter;

                this.component.setState({
                    map_data: mapData
                });
            }
        );
    }
}
