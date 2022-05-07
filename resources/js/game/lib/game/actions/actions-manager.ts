import Actions from "../../../sections/game-actions-section/actions";
import SmallerActions from "../../../sections/game-actions-section/smaller-actions";
import Ajax from "../../ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ActionsProps from "../types/actions/actions-props";
import {capitalize, isEqual} from "lodash";
import ActionsState from "../types/actions/actions-state";

export default class ActionsManager {

    private component: Actions | SmallerActions;

    constructor(component: Actions | SmallerActions) {
        this.component = component;
    }

    initialFetch(props: ActionsProps) {
        (new Ajax()).setRoute('actions/' + props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.component.setState({
                character: props.character,
                monsters: result.data.monsters,
                attack_time_out: props.character.can_attack_again_at !== null ? props.character.can_attack_again_at : 0,
                loading: false,
            })
        }, (error: AxiosError) => {

        });
    }

    actionComponentUpdated(state: ActionsState, props: ActionsProps) {
        if (state.character?.is_dead && !props.character.is_dead) {
            this.component.setState({
                character_revived: true,
            })
        }

        if (!isEqual(props.character, state.character)) {
            this.component.setState({
                character: props.character
            });
        }
    }

    setCraftingType(type: 'craft' | 'enchant' | 'alchemy' | 'workbench' | 'trinketry' | null) {
        this.component.setState({
            crafting_type: type,
        });
    }

    removeCraftingSection() {
        this.component.setState({
            crafting_type: null,
        });
    }

    setSelectedMonster(monster: any) {
        let isSameMonster = false;

        if (monster.id === this.component.state.monster_to_fight?.id) {
            isSameMonster = true;
        }

        this.component.setState({
            monster_to_fight: monster,
            is_same_monster: isSameMonster,
        });
    }

    resetSameMonster() {
        this.component.setState({
            is_same_monster: false,
        });
    }

    revive(characterId: number | null) {
        (new Ajax()).setRoute('battle-revive/' + characterId).doAjaxCall('post', (result: AxiosResponse) => {

        }, (error: AxiosError) => {

        });
    }

    setAttackTimeOut(attackTimeOut: number) {
        this.component.setState({
            attack_time_out: attackTimeOut
        });
    }

    updateTimer() {
        this.component.setState({
            attack_time_out: 0,
        })
    }

    updateCraftingTimer() {
        this.component.setState({
            crafting_time_out: 0,
        })
    }

    resetRevived() {
        this.component.setState({
            character_revived: false
        });
    }

    getSelectedCraftingOption() {
        if (this.component.state.crafting_type !== null) {
            return capitalize(this.component.state.crafting_type);
        }

        return '';
    }

    cannotCraft() {
        return this.component.state.crafting_time_out > 0 || !this.component.props.character_statuses?.can_craft || this.component.props.character_statuses?.is_dead
    }

    buildCraftingList(handler: (type: 'craft' | 'enchant' | 'alchemy' | 'workbench' | 'trinketry' | null) => void) {
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
                    icon_class: 'ra ra-anvil',
                    on_click: () => handler('workbench'),
                })
            } else {
                options.splice(2, 0, {
                    name: 'Workbench',
                    icon_class: 'ra ra-anvil',
                    on_click: () => handler('workbench'),
                });
            }
        }

        return options;
    }
}
