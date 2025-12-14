import GameListener from "../game-listener";
import { inject, injectable } from "tsyringe";
import CoreEventListener from "../core-event-listener";
import Game from "../../../../game";
import { Channel } from "laravel-echo";
import { CharacterType } from "../../character/character-type";

@injectable()
export default class CharacterListeners implements GameListener {
    private component?: Game;
    private userId?: number;

    private characterTopBar?: Channel;
    private characterCurrencies?: Channel;
    private characterInventoryCount?: Channel;
    private characterAttacks?: Channel;
    private characterStatus?: Channel;
    private characterBaseStatus?: Channel;
    private characterRevive?: Channel;
    private characterAttackData?: Channel;
    private globalTimeOut?: Channel;

    constructor(
        @inject(CoreEventListener) private coreEventListener: CoreEventListener,
    ) {}

    initialize(component: Game, userId?: number): void {
        this.component = component;
        this.userId = userId;
    }

    register(): void {
        this.coreEventListener.initialize();

        try {
            const echo = this.coreEventListener.getEcho();

            this.characterTopBar = echo.private(
                "update-top-bar-" + this.userId,
            );
            this.characterCurrencies = echo.private(
                "update-currencies-" + this.userId,
            );
            this.characterInventoryCount = echo.private(
                "update-inventory-count-" + this.userId,
            );
            this.characterAttacks = echo.private(
                "update-character-attacks-" + this.userId,
            );
            this.characterStatus = echo.private(
                "update-character-status-" + this.userId,
            );
            this.characterBaseStatus = echo.private(
                "update-character-base-stats-" + this.userId,
            );
            this.characterRevive = echo.private(
                "character-revive-" + this.userId,
            );
            this.characterAttackData = echo.private(
                "update-character-attack-" + this.userId,
            );
            this.globalTimeOut = echo.private("global-timeout-" + this.userId);
        } catch (e: any | unknown) {
            throw new Error(e);
        }
    }

    listen(): void {
        this.listenToCharacterTopBar();
        this.listenToCurrencyUpdates();
        this.listenToInventoryCountUpdate();
        this.listenToAttackDataUpdates();
        this.listenForCharacterStatusUpdates();
        this.listenForCharacterBaseStatusUpdates();
        this.listenForCharacterRevive();
        this.listenForCharacterAttackDataUpdates();
        this.listenForGlobalUpdatesThatAffectTheCharacter();
    }

    /**
     * Listen to the character top bar event.
     *
     * @protected
     */
    protected listenToCharacterTopBar() {
        if (!this.characterTopBar) {
            return;
        }

        this.characterTopBar.listen(
            "Game.Core.Events.UpdateTopBarBroadcastEvent",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState(
                    {
                        character: {
                            ...this.component.state.character,
                            ...event.characterSheet,
                        },
                    },
                    () => {
                        if (event.characterSheet.is_banned) {
                            location.reload();
                        }
                    },
                );
            },
        );
    }

    /**
     * Listen to currency updates for the character.
     *
     * @protected
     */
    protected listenToCurrencyUpdates() {
        if (!this.characterCurrencies) {
            return;
        }

        this.characterCurrencies.listen(
            "Game.Core.Events.UpdateCharacterCurrenciesBroadcastEvent",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    character: {
                        ...this.component.state.character,
                        ...event.currencies,
                    },
                });
            },
        );
    }

    protected listenToInventoryCountUpdate() {
        if (!this.characterInventoryCount) {
            return;
        }

        this.characterInventoryCount.listen(
            "Game.Character.CharacterInventory.Events.CharacterInventoryCountUpdateBroadcaseEvent",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    character: Object.assign(
                        {},
                        this.component.state.character,
                        {
                            inventory_count: event.characterInventoryCount,
                        },
                    ),
                });
            },
        );
    }

    /**
     * Listen to attack data updates.
     *
     * @protected
     */
    protected listenToAttackDataUpdates() {
        if (!this.characterAttacks) {
            return;
        }

        this.characterAttacks.listen(
            "Game.Core.Events.UpdateCharacterAttacks",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    character: {
                        ...this.component.state.character,
                        ...event.characterAttacks,
                    },
                });
            },
        );
    }

    /**
     * Listen for character status updates.
     *
     * @protected
     */
    protected listenForCharacterStatusUpdates() {
        if (!this.characterStatus) {
            return;
        }

        this.characterStatus.listen(
            "Game.Battle.Events.UpdateCharacterStatus",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    character_status: event.characterStatuses,
                    character: {
                        ...this.component.state.character,
                        ...event.characterStatuses,
                    },
                });
            },
        );
    }

    /**
     * Listen for character base status updates.
     *
     * @protected
     */
    protected listenForCharacterBaseStatusUpdates() {
        if (!this.characterBaseStatus) {
            return;
        }

        this.characterBaseStatus.listen(
            "Game.Core.Events.UpdateBaseCharacterInformation",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    character: {
                        ...this.component.state.character,
                        ...event.baseStats,
                    },
                });
            },
        );
    }

    /**
     * Listen for when the character revives.
     *
     * @protected
     */
    protected listenForCharacterRevive() {
        if (!this.characterRevive) {
            return;
        }

        this.characterRevive.listen(
            "Game.Battle.Events.CharacterRevive",
            (event: { health: number }) => {
                if (!this.component) {
                    return;
                }

                const character = JSON.parse(
                    JSON.stringify(this.component.state.character),
                );

                character.health = event.health;

                this.component.setState({
                    character: character,
                });
            },
        );
    }

    /**
     * Listen for character attack data updates.
     *
     * @protected
     */
    protected listenForCharacterAttackDataUpdates() {
        if (!this.characterAttackData) {
            return;
        }

        this.characterAttackData.listen(
            "Game.Character.CharacterAttack.Events.UpdateCharacterAttackBroadcastEvent",
            (event: any) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    character: {
                        ...this.component.state.character,
                        ...event.attack,
                    },
                });
            },
        );
    }

    /**
     * Listen for global updates - such as timeout.
     *
     * @protected
     */
    protected listenForGlobalUpdatesThatAffectTheCharacter() {
        if (!this.globalTimeOut) {
            return;
        }

        this.globalTimeOut.listen(
            "Game.Core.Events.GlobalTimeOut",
            (event: { showTimeOut: boolean }) => {
                if (!this.component) {
                    return;
                }

                this.component.setState({
                    show_global_timeout: event.showTimeOut,
                });
            },
        );
    }
}
