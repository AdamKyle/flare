import SmallCraftingSection from "../../../../sections/game-actions-section/components/small-actions/small-crafting-section";
import {CraftingOptions} from "../../types/actions/crafting-type-options";
import {capitalize} from "lodash";

export default class CraftingSectionManager {

    private component: SmallCraftingSection;

    constructor(component: SmallCraftingSection) {
        this.component = component;
    }

    /**
     * Are we not allowed to craft?
     */
    public cannotCraft(): boolean {
        const props = this.component.props;

        return props.crafting_time_out > 0 || !props.character_status.can_craft || props.character_status.is_dead;
    }

    /**
     * Get the selected crafting type.
     */
    public getSelectedCraftingOption() {
        if (this.component.state.crafting_type !== null) {
            return capitalize(this.component.state.crafting_type);
        }

        return '';
    }

    public smallCraftingList() {
        const options = [{
            label: 'Craft',
            value: 'craft',
        },{
            label: 'Enchant',
            value: 'enchant',
        },{
            label: 'Trinketry',
            value: 'trinketry',
        }, {
            label: 'Gem Crafting',
            value: 'gem-crafting',
        }];

        if (!this.component.props.character.is_alchemy_locked) {
            options.splice(2, 0, {
                label: 'Alchemy',
                value: 'alchemy',
            });
        }

        if (this.component.props.character.can_use_work_bench) {
            if (typeof options[2] !== 'undefined') {
                options.splice(3, 0, {
                    label: 'Workbench',
                    value: 'workbench'
                })
            } else {
                options.splice(2, 0, {
                    label: 'Workbench',
                    value: 'workbench'
                });
            }
        }

        if (this.component.props.character.can_access_labyrinth_oracle) {
            if (typeof options[3] !== 'undefined') {
                options.splice(3, 0, {
                    label: 'Labyrinth Oracle',
                    value: 'labyrinth-oracle',
                })
            } else {
                options.splice(4, 0, {
                    label: 'Labyrinth Oracle',
                    value: 'labyrinth-oracle',
                })
            }
        }

        if (this.component.props.character.can_access_queen) {
            if (typeof options[2] !== 'undefined') {
                options.splice(3, 0, {
                    label: 'Queen of hearts',
                    value: 'queen',
                })
            } else {
                options.splice(2, 0, {
                    label: 'Queen of hearts',
                    value: 'queen',
                })
            }
        }

        return options;
    }

    public setCraftingTypeForSmallerActionsList(data: any) {
        this.component.setState({
            crafting_type: data.value,
        })
    }

    public getSelectedCraftingTypeForSmallerActionsList(): {label: string, value: string}[] {
        if (this.component.state.crafting_type === null) {
            return [{
                label: 'Please select type',
                value: '',
            }]
        }

        const options = this.smallCraftingList();

        const option = options.filter((option) => {
            return option.value === this.component.state.crafting_type
        })

        return option;
    }

    /**
     * Build the crafting list.
     *
     * @param handler
     */
    public buildCraftingList(handler: (type: CraftingOptions) => void) {
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
