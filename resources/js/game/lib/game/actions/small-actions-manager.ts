import SmallerActions from "../../../sections/game-actions-section/smaller-actions";
import { capitalize } from "lodash";
import Ajax from "../../ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import PvpCharactersType from "../types/pvp-characters-type";
import { DateTime } from "luxon";

type SelectedData = {
    label: string;
    value: string;
};

export default class SmallActionsManager {
    private component: SmallerActions;

    constructor(component: SmallerActions) {
        this.component = component;
    }

    /**
     * Initial Ajax Call for the component.
     */
    public initialFetch() {
        const props = this.component.props;

        new Ajax().setRoute("map-actions/" + props.character.id).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                this.component.setState({
                    monsters: result.data.monsters,
                    attack_time_out:
                        props.character.can_attack_again_at !== null
                            ? this.calculateTimeLeft(
                                  props.character.can_attack_again_at,
                              )
                            : 0,
                    crafting_time_out:
                        props.character.can_craft_again_at !== null
                            ? this.calculateTimeLeft(
                                  props.character.can_craft_again_at,
                              )
                            : 0,
                    automation_time_out:
                        props.character.automation_completed_at !== null
                            ? props.character.automation_completed_at
                            : 0,
                    celestial_time_out:
                        props.character.can_engage_celestials_again_at !== null
                            ? props.character.can_engage_celestials_again_at
                            : 0,
                    loading: false,
                });
            },
            (error: AxiosError) => {
                console.error(error);
            },
        );
    }

    /**
     * Set the characters for dueling.
     *
     * @param eventCharactersForDueling
     */
    public setCharactersForDueling(
        eventCharactersForDueling: PvpCharactersType[],
    ) {
        let charactersForDueling: PvpCharactersType[] | [] = [];
        const props = this.component.props;

        if (eventCharactersForDueling.length === 0) {
            return;
        }

        if (props.character_position !== null) {
            charactersForDueling = eventCharactersForDueling.filter(
                (character: PvpCharactersType) => {
                    if (props.character_position !== null) {
                        if (character.id !== props.character.id) {
                            return character;
                        }
                    }
                },
            );

            if (charactersForDueling.length === 0) {
                return;
            }

            this.component.setState({
                characters_for_dueling: charactersForDueling,
            });
        }
    }

    /**
     * Calculate the time left based on the can_x_again_at
     *
     * @param timeLeft
     * @protected
     */
    protected calculateTimeLeft(timeLeft: string): number {
        const future = DateTime.fromISO(timeLeft);
        const now = DateTime.now();

        const diff = future.diff(now, ["seconds"]);
        const objectDiff = diff.toObject();

        if (typeof objectDiff.seconds === "undefined") {
            return 0;
        }

        return parseInt(objectDiff.seconds.toFixed(0));
    }

    /**
     * Set the selected action
     *
     * @param data
     */
    setSelectedAction(data: SelectedData) {
        this.component.setState(
            {
                selected_action: data.value,
            },
            () => {
                if (data.value === "pvp-fight") {
                    this.component.setState({
                        show_duel_fight: true,
                    });
                }
            },
        );
    }

    /**
     * Set the characters to duel
     */
    setDuelCharacters() {
        const state = this.component.state;
        const props = this.component.props;

        if (typeof state.characters_for_dueling !== "undefined") {
            const characters = state.characters_for_dueling.filter(
                (character) => {
                    return (
                        character.character_position_x ===
                            props.character_position?.x &&
                        character.character_position_y ===
                            props.character_position?.y &&
                        character.game_map_id ===
                            props.character_position?.game_map_id &&
                        character.name !== props.character.name
                    );
                },
            );

            this.component.setState({
                characters_for_dueling: characters,
            });
        }
    }

    /**
     * Build Selectable options.
     */
    buildOptions(): SelectedData[] {
        const props = this.component.props;
        const state = this.component.state;

        const options = [
            {
                label: "Exploration",
                value: "explore",
            },
            {
                label: "Craft",
                value: "craft",
            },
        ];

        if (!props.character.is_automation_running) {
            options.unshift({
                label: "Fight",
                value: "fight",
            });
        }

        options.push({
            label: "Slots",
            value: "slots",
        });

        options.push({
            label: "Map Movement",
            value: "map-movement",
        });

        if (
            state.characters_for_dueling.length > 0 &&
            !props.character.killed_in_pvp
        ) {
            options.push({
                label: "Pvp Fight",
                value: "pvp-fight",
            });
        }

        if (
            props.celestial_id !== 0 &&
            props.celestial_id !== null &&
            props.character.can_engage_celestials
        ) {
            options.push({
                label: "Celestial Fight",
                value: "celestial-fight",
            });
        }

        if (props.character.can_register_for_pvp) {
            options.push({
                label: "Join Monthly PVP",
                value: "join-monthly-pvp",
            });
        }

        if (props.character.can_access_hell_forged) {
            options.push({
                label: "Hell Forged Gear",
                value: "hell-forged-gear",
            });
        }

        if (props.character.can_access_purgatory_chains) {
            options.push({
                label: "Purgatory Chains Gear",
                value: "purgatory-chains-gear",
            });
        }

        if (props.character.can_access_twisted_memories) {
            options.push({
                label: "Twisted Earth",
                value: "twisted-earth-gear",
            });
        }

        return options;
    }

    defaultSelectedAction(): SelectedData[] {
        const state = this.component.state;

        if (
            typeof state.selected_action !== "undefined" &&
            state.selected_action !== null
        ) {
            return [
                {
                    label: capitalize(state.selected_action),
                    value: state.selected_action,
                },
            ];
        }

        return [
            {
                label: "Please Select Action",
                value: "",
            },
        ];
    }
}
