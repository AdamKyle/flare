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
        const state = this.component.state;
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
