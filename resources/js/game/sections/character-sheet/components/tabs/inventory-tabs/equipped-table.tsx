import React, { ReactNode } from "react";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import Ajax from "../../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { isEqual } from "lodash";
import EquippedInventoryTabProps from "../../../../../lib/game/character-sheet/types/tabs/equipped-inventory-tab-props";
import EquippedTableState from "../../../../../lib/game/character-sheet/types/tables/equipped-table-state";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import {
    defaultPositionImage,
    defaultPositionImageAlt,
    Position,
    positionTypeMap,
} from "./equipped-section/enums/position-paths";
import { determineEquipmentType } from "./equipped-section/enums/equipment-types";
import { getEquipmentImagePath } from "./equipped-section/enums/equipment-position-paths";
import { borderStyles } from "./equipped-section/styles/border-styles";

export default class EquippedTable
    extends React.Component<EquippedInventoryTabProps, EquippedTableState>
    implements ActionsInterface
{
    constructor(props: EquippedInventoryTabProps) {
        super(props);

        this.state = {
            data: this.props.equipped_items,
            loading: false,
            search_string: "",
            success_message: null,
            error_message: null,
            item_id: null,
            view_item: false,
        };
    }

    componentDidUpdate(
        prevProps: Readonly<EquippedInventoryTabProps>,
        prevState: Readonly<any>,
        snapshot?: any,
    ) {
        if (
            !isEqual(prevState.data, this.props.equipped_items) &&
            this.state.search_string.length === 0
        ) {
            this.setState({
                data: this.props.equipped_items,
            });
        }
    }

    viewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            item_id: typeof item !== "undefined" ? item.item_id : null,
            view_item: !this.state.view_item,
        });
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        this.setState({
            data: this.props.equipped_items.filter((item: InventoryDetails) => {
                return (
                    item.item_name.includes(value) || item.type.includes(value)
                );
            }),
            search_string: value,
        });
    }

    actions(row: InventoryDetails): ReactNode {
        return (
            <DangerButton
                button_label={"Remove"}
                on_click={() => this.unequip(row.slot_id)}
                disabled={
                    this.props.is_dead ||
                    this.props.is_automation_running ||
                    this.state.loading
                }
            />
        );
    }

    assignToSet(label: string) {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character/" +
                            this.props.character_id +
                            "/inventory/save-equipped-as-set",
                    )
                    .setParameters({
                        move_to_set: this.props.sets[label].set_id,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                },
                            );
                        },
                        (error: AxiosError) => {},
                    );
            },
        );
    }

    hasEmptySet() {
        if (this.props.is_set_equipped) {
            return false;
        }

        if (this.state.data.length === 0) {
            return false;
        }

        const dropDownLabels = Object.keys(this.props.sets);

        // @ts-ignore
        return (
            dropDownLabels.filter(
                (key) => this.props.sets[key].items.length === 0,
            ).length > 0
        );
    }

    buildMenuItems() {
        let dropDownLabels = Object.keys(this.props.sets);

        dropDownLabels = dropDownLabels.filter(
            (key) => this.props.sets[key].items.length === 0,
        );

        return dropDownLabels.map((label: string) => {
            return {
                name: label,
                icon_class: "ra ra-crossed-swords",
                on_click: () => this.assignToSet(label),
            };
        });
    }

    manageSuccessMessage() {
        this.setState({
            success_message: null,
        });
    }

    manageErrorMessage() {
        this.setState({
            error_message: null,
        });
    }

    unequipAll() {
        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            () => {
                this.props.disable_tabs();

                new Ajax()
                    .setRoute(
                        "character/" +
                            this.props.character_id +
                            "/inventory/unequip-all",
                    )
                    .setParameters({
                        is_set_equipped: this.props.is_set_equipped,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.props.update_inventory(
                                        result.data.inventory,
                                    );

                                    this.props.disable_tabs();
                                },
                            );
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState(
                                    {
                                        loading: false,
                                        error_message: response.data.message,
                                    },
                                    () => {
                                        this.props.disable_tabs();
                                    },
                                );
                            }
                        },
                    );
            },
        );
    }

    unequip(id: number) {
        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character/" +
                            this.props.character_id +
                            "/inventory/unequip",
                    )
                    .setParameters({
                        inventory_set_equipped: this.props.is_set_equipped,
                        item_to_remove: id,
                    })
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                () => {
                                    this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                },
                            );
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response: AxiosResponse = error.response;

                                this.setState(
                                    {
                                        loading: false,
                                        error_message: response.data.message,
                                    },
                                    () => {
                                        this.props.disable_tabs();
                                    },
                                );
                            }
                        },
                    );
            },
        );
    }

    handleClick(itemName: string) {
        console.log(itemName);
    }

    renderSlot(name: string, position: Position) {
        let path = defaultPositionImage[position];
        const altText = defaultPositionImageAlt[position];

        const itemType = positionTypeMap[position];

        console.log(itemType);

        const item = this.state.data.find((item: InventoryDetails) => {
            return item.type === itemType;
        });

        let itemName = "Sample";

        if (!item) {
            return (
                <div
                    className={`w-16 h-16 text-white flex items-center justify-center border border-gray-600 rounded`}
                    onClick={() => this.handleClick(itemName)}
                    onMouseOver={(e: React.MouseEvent<HTMLDivElement>) => {
                        e.currentTarget.title = `${name}: ${itemName}`;
                    }}
                    aria-label={`${name}: ${itemName}`}
                >
                    <img src={path} width={64} alt={altText} />
                </div>
            );
        }

        const itemEquipmentType = determineEquipmentType(item);

        path = getEquipmentImagePath(position, itemEquipmentType);
        itemName = item.item_name;

        const borderClasses = borderStyles(itemEquipmentType);

        return (
            <div
                className={`w-16 h-16 text-white flex items-center justify-center border border-gray-600 rounded ${borderClasses}`}
                onClick={() => this.handleClick(itemName)}
                onMouseOver={(e: React.MouseEvent<HTMLDivElement>) => {
                    e.currentTarget.title = `${name}: ${itemName}`;
                }}
                aria-label={`${name}: ${itemName}`}
            >
                <img src={path} width={64} alt={altText} />
            </div>
        );
    }

    render() {
        console.log(this.state.data);
        return (
            <div className="flex justify-center">
                <div className="flex items-center p-4 space-x-8">
                    <div className="flex flex-col items-center space-y-4">
                        <div>{this.renderSlot("Head", Position.HELMET)}</div>

                        <div className="grid grid-cols-3 gap-4">
                            {this.renderSlot("Sleeves", Position.SLEEVES_LEFT)}
                            {this.renderSlot("Body", Position.BODY)}
                            {this.renderSlot("Sleeves", Position.SLEEVES_RIGHT)}
                        </div>

                        <div className="grid grid-cols-3 gap-4">
                            {this.renderSlot("Gloves", Position.GLOVES_LEFT)}
                            {this.renderSlot("Leggings", Position.LEGGINGS)}
                            {this.renderSlot("Gloves", Position.GLOVES_RIGHT)}
                        </div>

                        <div>{this.renderSlot("Feet", Position.FEET)}</div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {this.renderSlot("Weapon", Position.WEAPON_LEFT)}
                        {this.renderSlot("Weapon", Position.WEAPON_RIGHT)}
                        {this.renderSlot("Ring", Position.RING_LEFT)}
                        {this.renderSlot("Ring", Position.RING_RIGHT)}
                        {this.renderSlot("Spell", Position.SPELL_LEFT)}
                        {this.renderSlot("Spell", Position.SPELL_RIGHT)}
                        {this.renderSlot("Trinket", Position.TRINKET)}
                    </div>
                </div>
            </div>
        );
    }
}
