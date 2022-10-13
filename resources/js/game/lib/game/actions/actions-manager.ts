import Ajax from "../../ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {capitalize} from "lodash";
import {CraftingOptions} from "../types/actions/crafting-type-options";
import Actions from "../../../sections/game-actions-section/actions";
import PvpCharactersType from "../types/pvp-characters-type";
import {CharacterType} from "../character/character-type";
import {DateTime} from "luxon";

export default class ActionsManager {

    private component: Actions;

    constructor(component: Actions) {
        this.component = component;
    }

    /**
     * Initial Ajax call for the actions component.
     *
     * @param props
     */
    initialFetch() {
        const props = this.component.props;

        (new Ajax()).setRoute('map-actions/' + props.character.id).doAjaxCall('get', (result: AxiosResponse) => {
            this.component.setState({
                monsters: result.data.monsters,
                attack_time_out: props.character.can_attack_again_at !== null ? this.calculateTimeLeft(props.character.can_attack_again_at) : 0,
                crafting_time_out: props.character.can_craft_again_at !== null ? this.calculateTimeLeft(props.character.can_craft_again_at) : 0,
                loading: false,
            })
        }, (error: AxiosError) => {
            console.error(error);
        });
    }

    protected calculateTimeLeft(timeLeft: string): number {

        const future = DateTime.fromISO(timeLeft);
        const now    = DateTime.now();

        const diff       = future.diff(now, ['seconds']);
        const objectDiff = diff.toObject();

        if (typeof objectDiff.seconds === 'undefined') {
             return 0;
        }

        return parseInt(objectDiff.seconds.toFixed(0));
    }

    /**
     * When the component updates let's update the state.
     */
    public updateStateOnComponentUpdate() {
        this.setCraftingTypeOnUpdate();
        this.setDuelingStateOnUpdate();
    }

    public setCharactersForDueling(eventCharactersForDueling: PvpCharactersType[]) {
        let charactersForDueling: PvpCharactersType[]|[] = [];
        const props = this.component.props;

        if (props.character_position !== null) {
            charactersForDueling = eventCharactersForDueling.filter((character: PvpCharactersType) => {
                if (character.id !== props.character.id &&
                    character.character_position_x === props.character.base_position.x &&
                    character.character_position_y === props.character.base_position.y)
                {
                    return character;
                }
            });

            if (charactersForDueling.length === 0) {
                return;
            }

            this.component.setState({
                characters_for_dueling: charactersForDueling,
            })
        }
    }

    /**
     * Set the crafting type upon component update.
     */
    private setCraftingTypeOnUpdate() {
        const state = this.component.state;
        const props = this.component.props;

        if (state.crafting_type !== null) {
            if (state.crafting_type === 'queen' && !props.character.can_access_queen) {
                this.component.setState({crafting_type: null});
            }

            if (state.crafting_type === 'workbench' && !props.character.can_use_work_bench) {
                this.component.setState({crafting_type: null});
            }
        }
    }

    /**
     * Set the dueling state upon component update.
     *
     * @private
     */
    private setDuelingStateOnUpdate() {
        const props = this.component.props;
        const state = this.component.state;

        if (props.character_position !== null && state.characters_for_dueling.length > 0 && state.characters_for_dueling.length == 0) {
            if (typeof props.character_position.game_map_id === 'undefined') {
                return;
            }

            const characters = state.characters_for_dueling.filter((character: any) => {
                return character.character_position_x === props.character_position?.x &&
                    character.character_position_y === props.character_position?.y &&
                    character.game_map_id === props.character_position?.game_map_id
            });

            if (characters.length === 0) {
                return;
            }

            this.component.setState({
                characters_for_dueling: characters,
            });
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
        })
    }

    /**
     * Get the selected crafting type.
     */
    getSelectedCraftingOption() {
        if (this.component.state.crafting_type !== null) {
            return capitalize(this.component.state.crafting_type);
        }

        return '';
    }

    /**
     * Are we not allowed to craft?
     */
    cannotCraft(): boolean {
        const state = this.component.state;
        const props = this.component.props;

        return state.crafting_time_out > 0 || !props.character_status.can_craft || props.character_status.is_dead;
    }

    /**
     * Build the crafting list.
     *
     * @param handler
     */
    buildCraftingList(handler: (type: CraftingOptions) => void) {
        const options = [
            {
                name: 'Craft',
                icon_class: 'ra ra-hammer',
                on_click: () => handler('craft'),
            },
            {
                name: 'Enchant',
                icon_class: 'ra ra-burning-embers',
                on_click: () => handler('enchant'),
            },
            {
                name: 'Trinketry',
                icon_class: 'ra ra-anvil',
                on_click: () => handler('trinketry'),
            }
        ];

        if (!this.component.props.character.is_alchemy_locked) {
            options.splice(2, 0, {
                name: 'Alchemy',
                icon_class: 'ra ra-potion',
                on_click: () => handler('alchemy'),
            });
        }

        if (this.component.props.character.can_use_work_bench) {
            if (typeof options[2] !== 'undefined') {
                options.splice(3, 0, {
                    name: 'Workbench',
                    icon_class: 'ra ra-brandy-bottle',
                    on_click: () => handler('workbench'),
                })
            } else {
                options.splice(2, 0, {
                    name: 'Workbench',
                    icon_class: 'ra ra-brandy-bottle',
                    on_click: () => handler('workbench'),
                });
            }
        }

        if (this.component.props.character.can_access_queen) {
            if (typeof options[2] !== 'undefined') {
                options.splice(3, 0, {
                    name: 'Queen of Hearts',
                    icon_class: 'ra  ra-hearts',
                    on_click: () => handler('queen'),
                })
            } else {
                options.splice(2, 0, {
                    name: 'Queen of Hearts',
                    icon_class: 'ra ra-hearts',
                    on_click: () => handler('queen'),
                })
            }
        }

        return options;
    }
}
