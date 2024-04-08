import React, { Fragment } from "react";
import {
    craftingGetEndPoints,
    craftingPostEndPoints,
} from "../../../../lib/game/actions/crafting-type-url";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { formatNumber } from "../../../../lib/game/format-number";
import Select from "react-select";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import { getCraftingType } from "../../../../lib/game/actions/crafting-types";
import { isEqual } from "lodash";
import { generateServerMessage } from "../../../../lib/ajax/generate-server-message";
import CraftingXp from "../crafting-xp";
import CraftingTypeSelection from "../crafting-partials/crafting-type-selecting";
import CraftingActionButtons from "../crafting-partials/crafting-action-buttons";
import ArmourTypeSelection from "../crafting-partials/armour-type-selection";
import SelectItemToCraft from "../crafting-partials/select-item-to-craft";
import {FameTasks} from "../../../faction-loyalty/deffinitions/faction-loaylaty";

export default class Crafting extends React.Component<any, any> {

    private characterStatus: any;

    constructor(props: any) {
        super(props);

        this.state = {
            selected_item: null,
            selected_type: null,
            armour_craft_type: null,
            loading: false,
            craftable_items: [],
            sorted_armour: [],
            show_craft_for_event: false,
            selected_armour_type: null,
            skill_xp: {
                curent_xp: 0,
                next_level_xp: 0,
                skill_name: "Unknown",
                level: 1,
            },
        };

        // @ts-ignore
        this.characterStatus = Echo.private(
            "update-character-status-" + this.props.user_id
        );
    }

    componentDidMount() {
        this.characterStatus.listen(
            "Game.Battle.Events.UpdateCharacterStatus",
            (event: any) => {
                this.setState({
                    show_craft_for_event: event.characterStatuses.show_craft_for_event
                });
            }
        );
    }

    showCraftForNpc() {
        if (!this.state.selected_type) {
            return false;
        }

        if (!this.state.selected_item) {
            return false;
        }

        if (!this.props.fame_tasks) {
            return false;
        }

        return this.props.fame_tasks.filter((task: FameTasks) => {
            return task.item_id === this.state.selected_item.id;
        }).length > 0;
    }

    setItemToCraft(data: any) {
        const foundItem = this.state.craftable_items.filter((item: any) => {
            return item.id === parseInt(data.value);
        });

        if (foundItem.length > 0) {
            this.setState({
                selected_item: foundItem[0],
            });
        }
    }

    setTypeToCraft(data: any) {
        this.setState(
            {
                selected_type: data.value,
                loading: true,
            },
            () => {
                if (
                    this.state.selected_type !== null &&
                    this.state.selected_type !== ""
                ) {
                    const url = craftingGetEndPoints(
                        "craft",
                        this.props.character_id
                    );

                    new Ajax()
                        .setRoute(url)
                        .setParameters({
                            crafting_type: this.state.selected_type,
                        })
                        .doAjaxCall(
                            "get",
                            (result: AxiosResponse) => {
                                this.setState({
                                    loading: false,
                                    craftable_items: result.data.items,
                                    skill_xp: result.data.xp,
                                    show_craft_for_event: result.data.show_craft_for_event
                                });
                            },
                            (error: AxiosError) => {}
                        );
                }
            }
        );
    }

    changeType() {
        if (
            this.state.sorted_armour.length > 0 &&
            this.state.armour_craft_type !== null
        ) {
            this.setState({
                sorted_armour: [],
                selected_item: null,
            });
        } else if (
            this.state.sorted_armour.length === 0 &&
            this.state.armour_craft_type !== null
        ) {
            this.setState({
                sorted_armour: [],
                armour_craft_type: null,
                selected_type: null,
                selected_item: null,
                craftable_items: [],
            });
        } else {
            this.setState({
                selected_type: null,
                selected_item: null,
                craftable_items: [],
            });
        }
    }

    buildItems() {
        if (this.state.sorted_armour.length > 0) {
            return this.state.sorted_armour.map((item: any) => {
                return {
                    label: item.name + " Gold Cost: " + formatNumber(item.cost),
                    value: item.id,
                };
            });
        }

        return this.state.craftable_items.map((item: any) => {
            return {
                label: item.name + " Gold Cost: " + formatNumber(item.cost),
                value: item.id,
            };
        });
    }

    defaultItem() {
        if (this.state.selected_item !== null) {
            return {
                label:
                    this.state.selected_item.name +
                    " Gold Cost: " +
                    formatNumber(this.state.selected_item.cost),
                value: this.state.selected_item.id,
            };
        }

        return { label: "Please select item to craft", value: 0 };
    }

    craft(craftForNpc: boolean, craftForEvent: boolean) {
        this.setState(
            {
                loading: true,
            },
            () => {
                const url = craftingPostEndPoints(
                    "craft",
                    this.props.character_id
                );

                new Ajax()
                    .setRoute(url)
                    .setParameters({
                        item_to_craft: this.state.selected_item.id,
                        type: getCraftingType(this.state.selected_item.type),
                        craft_for_npc: craftForNpc,
                        craft_for_event: craftForEvent,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            const oldCraftableItems = JSON.parse(
                                JSON.stringify(this.state.craftable_items)
                            );

                            console.log('After craft response:', result.data);

                            this.setState(
                                {
                                    loading: false,
                                    craftable_items: result.data.items,
                                    skill_xp: result.data.xp,
                                    show_craft_for_event: result.data.show_craft_for_event,
                                },
                                () => {
                                    if (
                                        !isEqual(
                                            oldCraftableItems,
                                            result.data.items
                                        )
                                    ) {
                                        this.updateSortedArmour();

                                        generateServerMessage(
                                            "new_items",
                                            "You have new items to craft. Check the list!"
                                        );
                                    }
                                }
                            );
                        },
                        (error: AxiosError) => {}
                    );
            }
        );
    }

    updateSortedArmour() {
        if (this.state.armour_craft_type != null) {
            const filteredArmour = this.state.craftable_items.filter(
                (item: any) => {
                    return item.type === this.state.armour_craft_type;
                }
            );

            this.setState({
                sorted_armour: filteredArmour,
            });
        }
    }

    clearCrafting() {
        this.props.remove_crafting();
    }

    canCraft(): boolean {
        return (
            this.state.loading ||
            this.state.selected_item === null ||
            this.props.cannot_craft
        );
    }

    canClose(): boolean {
        return this.state.loading;
    }

    canChangeType(): boolean {
        return this.state.loading;
    }

    selectedArmourType(data: any) {
        const filteredArmour = this.state.craftable_items.filter(
            (item: any) => {
                return item.type === data.value;
            }
        );

        this.setState({
            sorted_armour: filteredArmour,
            armour_craft_type: data.value,
        });
    }

    render() {
        return (
            <Fragment>
                <div className="mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]">
                    <div className="lg:cols-start-1 lg:col-span-2">
                        {this.state.selected_type === null ? (
                            <CraftingTypeSelection
                                select_type_to_craft={this.setTypeToCraft.bind(
                                    this
                                )}
                            />
                        ) : this.state.selected_type === "armour" ? (
                            this.state.sorted_armour.length > 0 ? (
                                <SelectItemToCraft
                                    set_item_to_craft={this.setItemToCraft.bind(
                                        this
                                    )}
                                    items={this.buildItems()}
                                    default_item={this.defaultItem()}
                                />
                            ) : (
                                <ArmourTypeSelection
                                    select_armour_type_to_craft={this.selectedArmourType.bind(
                                        this
                                    )}
                                />
                            )
                        ) : (
                            <SelectItemToCraft
                                set_item_to_craft={this.setItemToCraft.bind(
                                    this
                                )}
                                items={this.buildItems()}
                                default_item={this.defaultItem()}
                            />
                        )}

                        {this.state.loading ? <LoadingProgressBar /> : null}

                        {this.state.craftable_items.length > 0 ? (
                            <CraftingXp skill_xp={this.state.skill_xp} />
                        ) : null}
                    </div>
                </div>
                {this.props.is_small ? (
                    <div className={"mt-3 mb-3 grid text-center"}>
                        <CraftingActionButtons
                            can_craft={this.canCraft()}
                            can_close={this.canClose()}
                            can_change_type={this.canChangeType()}
                            craft={this.craft.bind(this)}
                            change_type={this.changeType.bind(this)}
                            clear_crafting={this.clearCrafting.bind(this)}
                            show_craft_for_npc={this.showCraftForNpc()}
                            show_craft_for_event={this.state.show_craft_for_event}
                        />
                    </div>
                ) : (
                    <div className={"text-center lg:ml-[-100px] mt-3 mb-3"}>
                        <CraftingActionButtons
                            can_craft={this.canCraft()}
                            can_close={this.canClose()}
                            can_change_type={this.canChangeType()}
                            craft={this.craft.bind(this)}
                            change_type={this.changeType.bind(this)}
                            clear_crafting={this.clearCrafting.bind(this)}
                            show_craft_for_npc={this.showCraftForNpc()}
                            show_craft_for_event={this.state.show_craft_for_event}
                        />
                    </div>
                )}
            </Fragment>
        );
    }
}
