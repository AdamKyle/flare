import { capitalize } from "lodash";
import { CraftingOptions } from "../../../components/crafting/base-components/types/crafting-type-options";
import Actions from "../../../sections/game-actions-section/actions";
import { DateTime } from "luxon";

export default class ActionsManager {
    /**
     * Actions Conmponent.
     *
     * @private
     */
    private component: Actions;

    /**
     *
     * @param component
     */
    constructor(component: Actions) {
        this.component = component;
    }

    /**
     * When the component updates let's update the state.
     */
    public updateStateOnComponentUpdate() {
        this.setCraftingTypeOnUpdate();
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
     * Set the crafting type upon component update.
     */
    private setCraftingTypeOnUpdate() {
        const state = this.component.state;
        const props = this.component.props;

        if (state.crafting_type !== null) {
            if (
                state.crafting_type === "queen" &&
                !props.character.can_access_queen
            ) {
                this.component.setState({ crafting_type: null });
            }

            if (
                state.crafting_type === "workbench" &&
                !props.character.can_use_work_bench
            ) {
                this.component.setState({ crafting_type: null });
            }

            if (
                state.crafting_type === "labyrinth-oracle" &&
                !props.character?.can_access_labyrinth_oracle
            ) {
                this.component.setState({ crafting_type: null });
            }
        }
    }

    /**
     * Set the crafting type.
     *
     * @param type
     */
    setCraftingType(type: CraftingOptions) {
        this.component.setState({
            crafting_type: type,
        });
    }

    /**
     * Remove Crafting
     */
    removeCraftingSection() {
        this.component.setState({
            crafting_type: null,
        });
    }

    /**
     * Update the crafting timeout to 0
     */
    updateCraftingTimer() {
        this.component.setState({
            crafting_time_out: 0,
        });
    }

    /**
     * Get the selected crafting type.
     */
    getSelectedCraftingOption() {
        if (this.component.state.crafting_type !== null) {
            return capitalize(this.component.state.crafting_type);
        }

        return "";
    }

    /**
     * Are we not allowed to craft?
     */
    cannotCraft(): boolean {
        const state = this.component.state;
        const props = this.component.props;

        return (
            state.crafting_time_out > 0 ||
            !props.character_status.can_craft ||
            props.character_status.is_dead
        );
    }

    /**
     * Build the crafting list.
     *
     * @param handler
     */
    buildCraftingList(handler: (type: CraftingOptions) => void) {
        const options = [
            {
                name: "Craft",
                icon_class: "ra ra-hammer",
                on_click: () => handler("craft"),
            },
            {
                name: "Enchant",
                icon_class: "ra ra-burning-embers",
                on_click: () => handler("enchant"),
            },
            {
                name: "Trinketry",
                icon_class: "ra ra-anvil",
                on_click: () => handler("trinketry"),
            },
            {
                name: "Gem Crafting",
                icon_class: "fas fa-gem",
                on_click: () => handler("gem-crafting"),
            },
        ];

        if (!this.component.props.character.is_alchemy_locked) {
            options.splice(2, 0, {
                name: "Alchemy",
                icon_class: "ra ra-potion",
                on_click: () => handler("alchemy"),
            });
        }

        if (this.component.props.character.can_use_work_bench) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    name: "Workbench",
                    icon_class: "ra ra-brandy-bottle",
                    on_click: () => handler("workbench"),
                });
            } else {
                options.splice(2, 0, {
                    name: "Workbench",
                    icon_class: "ra ra-brandy-bottle",
                    on_click: () => handler("workbench"),
                });
            }
        }

        if (this.component.props.character.can_access_purgatory_chains) {
            if (typeof options[3] !== "undefined") {
                options.splice(4, 0, {
                    name: "Seer Camp",
                    icon_class: "fas fa-campground",
                    on_click: () => handler("seer-camp"),
                });
            } else {
                options.splice(3, 0, {
                    name: "Seer Camp",
                    icon_class: "fas fa-campground",
                    on_click: () => handler("seer-camp"),
                });
            }
        }

        if (this.component.props.character.can_access_labyrinth_oracle) {
            if (typeof options[3] !== "undefined") {
                options.splice(4, 0, {
                    name: "Labyrinth Oracle",
                    icon_class: "ra ra-crystal-ball",
                    on_click: () => handler("labyrinth-oracle"),
                });
            } else {
                options.splice(4, 0, {
                    name: "Labyrinth Oracle",
                    icon_class: "ra ra-crystal-ball",
                    on_click: () => handler("labyrinth-oracle"),
                });
            }
        }

        if (this.component.props.character.can_access_queen) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    name: "Queen of Hearts",
                    icon_class: "ra  ra-hearts",
                    on_click: () => handler("queen"),
                });
            } else {
                options.splice(2, 0, {
                    name: "Queen of Hearts",
                    icon_class: "ra ra-hearts",
                    on_click: () => handler("queen"),
                });
            }
        }

        return options;
    }
}
