import SmallerActions from "../../../sections/game-actions-section/smaller-actions";
import { capitalize } from "lodash";
import Ajax from "../../ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
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
                if (data.value === "map-movement") {
                    this.component.props.update_show_map_mobile(true);
                }
            },
        );
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
            props.celestial_id !== 0 &&
            props.celestial_id !== null &&
            props.character.can_engage_celestials
        ) {
            options.push({
                label: "Celestial Fight",
                value: "celestial-fight",
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
