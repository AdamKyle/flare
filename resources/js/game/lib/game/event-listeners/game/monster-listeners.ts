import GameListener from "../game-listener";
import {inject, injectable} from "tsyringe";
import CoreEventListener from "../core-event-listener";
import Game from "../../../../game";
import {Channel} from "laravel-echo";

@injectable()
export default class MonsterListeners implements GameListener {

    private component?: Game;
    private userId?: number;

    private monsterUpdate?: Channel;
    private raidMonsterUpdate?: Channel;

    constructor(@inject(CoreEventListener) private coreEventListener: CoreEventListener) {}

    initialize(component: Game, userId: number): void {
        this.component = component;
        this.userId    = userId
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.monsterUpdate = echo.private(
                "update-monsters-list-" + this.userId
            );

            this.raidMonsterUpdate = echo.private(
                "update-raid-monsters-list-" + this.userId
            );
        } catch (e: any|unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenForMonsterUpdates();
        this.listenForRaidMonsterUpdates();
    }

    /**
     * Listen for monster updates.
     *
     * @protected
     */
    protected listenForMonsterUpdates() {
        if (!this.monsterUpdate) {
            return;
        }

        this.monsterUpdate.listen(
            "Game.Maps.Events.UpdateMonsterList",
            (event: any) => {

                if (!this.component) {
                    return;
                }

                if (this.component.state.action_data === null) {
                    return;
                }

                const actionData = JSON.parse(JSON.stringify(this.component.state.action_data));

                actionData.monsters = event.monsters;

                this.component.setState({
                    action_data: actionData,
                });
            }
        );
    }

    /**
     * Listen for raid monster updates.
     *
     * @protected
     */
    protected listenForRaidMonsterUpdates() {

        if (!this.raidMonsterUpdate) {
            return;
        }

        this.raidMonsterUpdate.listen(
            "Game.Maps.Events.UpdateRaidMonsters",
            (event: any) => {

                if (!this.component) {
                    return;
                }

                const self = this;

                setTimeout(function() {

                    if (!self.component) {
                        return;
                    }

                    if (self.component.state.action_data === null) {
                        return;
                    }

                    const actionData = JSON.parse(JSON.stringify(self.component.state.action_data));

                    actionData.raid_monsters = event.raidMonsters;

                    self.component.setState({
                        action_data: actionData
                    });
                }, 1000);
            }
        );

    }
}
