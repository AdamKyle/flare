import React, { Fragment } from "react";
import DropDown from "../../../../components/ui/drop-down/drop-down";
import InventoryTable from "./inventory-tabs/inventory-table";
import UsableItemsTable from "./inventory-tabs/usable-items-table";
import InventoryDetails from "../../../../lib/game/character-sheet/types/inventory/inventory-details";
import InventoryActionConfirmationModal from "../modals/inventory-action-confirmation-modal";
import { isEqual } from "lodash";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import InventoryTabSectionProps from "../../../../lib/game/character-sheet/types/tabs/inventory-tab-section-props";
import InventoryTabSectionState, {
    SelectItems,
} from "../../../../lib/game/character-sheet/types/tabs/inventory-tab-section-state";
import clsx from "clsx";
import UsableItemsDetails from "../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseManyItems from "../modals/inventory-use-many-items";
import { GemBagTable } from "./inventory-tabs/gem-bag-table";
import { InventoryActionConfirmationType } from "../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/enums/inventory-action-confirmation-type";
import BaseInventoryActionConfirmationModal from "../../../../components/character-sheet/inventory-action-confirmation-modal/modals/base-inventory-action-confirmation-modal";
import ModalPropsBuilder from "../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/modal-props-builder";
import { serviceContainer } from "../../../../lib/containers/core-container";

export default class InventoryTabSection extends React.Component<
    InventoryTabSectionProps,
    InventoryTabSectionState
> {
    private modalPropsBuilder: ModalPropsBuilder;

    constructor(props: InventoryTabSectionProps) {
        super(props);

        this.state = {
            table: "inventory",
            data: this.props.inventory,
            usable_items: this.props.usable_items,
            show_action_confirmation_modal: false,
            action_confirmation_type: null,
            show_destroy_all: false,
            show_disenchant_all: false,
            show_sell_all: false,
            show_use_many: false,
            show_destroy_all_alchemy: false,
            show_equip_best: false,
            success_message: null,
            search_string: "",
            selected_items: [],
        };

        this.modalPropsBuilder = serviceContainer().fetch(ModalPropsBuilder);
    }

    componentDidUpdate() {
        if (
            !isEqual(this.state.data, this.props.inventory) &&
            this.state.search_string.length === 0 &&
            this.state.selected_items.length <= 0
        ) {
            this.setState({
                data: this.props.inventory,
            });
        }

        if (
            !isEqual(this.state.usable_items, this.props.usable_items) &&
            this.state.search_string.length === 0
        ) {
            this.setState({
                usable_items: this.props.usable_items,
            });
        }
    }

    setSuccessMessage(message: string) {
        this.setState({
            success_message: message,
        });
    }

    switchTable(type: string) {
        this.setState({
            table: type,
        });
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value: string = e.target.value;

        if (this.state.table === "Inventory") {
            this.setState({
                data: this.props.inventory
                    .filter((item: InventoryDetails) => {
                        const itemName = item.item_name.toLowerCase();
                        const itemType = item.type.toLowerCase();

                        if (
                            itemName.includes(value.toLowerCase()) ||
                            itemType.includes(value.toLowerCase())
                        ) {
                            return item;
                        }
                    })
                    .filter((item: InventoryDetails) => item),
                search_string: value,
            });
        } else {
            this.setState({
                usable_items: this.props.usable_items
                    .filter((item: UsableItemsDetails) => {
                        const itemName = item.item_name.toLowerCase();

                        if (itemName.includes(value.toLowerCase())) {
                            return item;
                        }
                    })
                    .filter((item: UsableItemsDetails) => item),
                search_string: value,
            });
        }
    }

    manageConfirmationModal(type?: InventoryActionConfirmationType) {
        let actionConfirmationType = null;

        if (!this.state.show_action_confirmation_modal && type) {
            actionConfirmationType = type;
        }

        let currentInventory = this.state.data;

        if (
            actionConfirmationType ===
            InventoryActionConfirmationType.DISENCHANT_SELECTED
        ) {
            currentInventory = currentInventory.filter(
                (inventory: InventoryDetails) => {
                    return !this.state.selected_items.some(
                        (selectedItem: SelectItems) =>
                            selectedItem.slot_id === inventory.slot_id,
                    );
                },
            );
        }

        this.setState({
            show_action_confirmation_modal:
                !this.state.show_action_confirmation_modal,
            action_confirmation_type: actionConfirmationType,
            data: currentInventory,
        });
    }

    manageUseManyItems() {
        this.setState({
            show_use_many: !this.state.show_use_many,
        });
    }

    closeSuccess() {
        this.setState({
            success_message: null,
        });
    }

    createActionsDropDown() {
        if (this.state.table === "inventory") {
            return [
                {
                    name: "Destroy All",
                    icon_class: "far fa-trash-alt",
                    on_click: () =>
                        this.manageConfirmationModal(
                            InventoryActionConfirmationType.DESTROY_ALL,
                        ),
                },
                {
                    name: "Disenchant All",
                    icon_class: "ra ra-fire",
                    on_click: () =>
                        this.manageConfirmationModal(
                            InventoryActionConfirmationType.DISENCHANT_ALL,
                        ),
                },
                {
                    name: "Sell All",
                    icon_class: "far fa-money-bill-alt",
                    on_click: () =>
                        this.manageConfirmationModal(
                            InventoryActionConfirmationType.SELL_ALL,
                        ),
                },
            ];
        }

        return [
            {
                name: "Use many",
                icon_class: "ra ra-bottle-vapors",
                on_click: () => this.manageUseManyItems(),
            },
            {
                name: "Destroy All",
                icon_class: "far fa-trash-alt",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.DESTROY_ALL_ALCHEMY_ITEMS,
                    ),
            },
        ];
    }

    createMultiSelectedItemDropDown() {
        return [
            {
                name: "Equip Selected",
                icon_class: "far fa-trash-alt",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.EQUIP_SELECTED,
                    ),
            },
            {
                name: "Move Selected",
                icon_class: "fas fa-truck-loading",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.MOVE_SELECTED,
                    ),
            },
            {
                name: "Destroy Selected",
                icon_class: "far fa-trash-alt",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.DESTROY_SELECTED,
                    ),
            },
            {
                name: "Disenchant Selected",
                icon_class: "ra ra-fire",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.DISENCHANT_SELECTED,
                    ),
            },
            {
                name: "Sell Selected",
                icon_class: "far fa-money-bill-alt",
                on_click: () =>
                    this.manageConfirmationModal(
                        InventoryActionConfirmationType.SELL_SELECTED,
                    ),
            },
        ];
    }

    isDropDownHidden() {
        if (this.state.table === "inventory") {
            return this.state.data.length === 0;
        } else {
            return this.props.usable_items.length === 0;
        }
    }

    isSelectedDropDownHidden() {
        return this.state.selected_items.length <= 0;
    }

    updateInventory(inventory: { [key: string]: InventoryDetails[] }) {
        this.setState(
            {
                search_string: "",
            },
            () => {
                this.props.update_inventory(inventory);
            },
        );
    }

    setSelectedItems(selectedItems: number[] | []) {
        if (this.state.table !== "inventory") {
            return;
        }

        if (this.state.table !== "inventory") {
            return;
        }

        const filteredSelectedItems: SelectItems[] = (selectedItems as number[])
            .map((slotId) => {
                const foundItem = this.state.data.find((slot) => {
                    return slot.slot_id === slotId;
                });

                if (typeof foundItem !== "undefined") {
                    return {
                        item_name: foundItem.item_name,
                        slot_id: foundItem.slot_id,
                    };
                }
                return null;
            })
            .filter((item): item is SelectItems => {
                return item !== null;
            });

        this.setState({
            selected_items: filteredSelectedItems,
        });
    }

    renderTables(): JSX.Element | null {
        switch (this.state.table) {
            case "inventory":
                return (
                    <InventoryTable
                        dark_table={this.props.dark_tables}
                        character_id={this.props.character_id}
                        inventory={this.state.data}
                        is_dead={this.props.is_dead}
                        update_inventory={this.updateInventory.bind(this)}
                        usable_sets={this.props.usable_sets}
                        set_success_message={this.setSuccessMessage.bind(this)}
                        is_automation_running={this.props.is_automation_running}
                        manage_skills={this.props.manage_skills}
                        manage_selected_items={this.setSelectedItems.bind(this)}
                        view_port={this.props.view_port}
                    />
                );
            case "usable-items":
                return (
                    <UsableItemsTable
                        dark_table={this.props.dark_tables}
                        character_id={this.props.character_id}
                        usable_items={this.state.usable_items}
                        is_dead={this.props.is_dead}
                        update_inventory={this.updateInventory.bind(this)}
                        set_success_message={this.setSuccessMessage.bind(this)}
                        is_automation_running={this.props.is_automation_running}
                        view_port={this.props.view_port}
                    />
                );
            case "gems":
                return (
                    <GemBagTable
                        dark_table={this.props.dark_tables}
                        character_id={this.props.character_id}
                        is_dead={this.props.is_dead}
                    />
                );
            default:
                return null;
        }
    }

    getSelectedNames(): string[] | [] {
        return this.state.selected_items.map(
            (selectedItem) => selectedItem.item_name,
        );
    }

    render() {
        let modalPropsBuilder = null;

        if (this.state.action_confirmation_type !== null) {
            modalPropsBuilder = this.modalPropsBuilder.setActionType(
                this.state.action_confirmation_type,
            );
        }

        return (
            <Fragment>
                {this.state.success_message !== null ? (
                    <SuccessAlert
                        close_alert={this.closeSuccess.bind(this)}
                        additional_css={"mt-4 mb-4"}
                    >
                        {this.state.success_message}
                    </SuccessAlert>
                ) : null}

                <div className="flex flex-row flex-wrap items-center">
                    <div>
                        <DropDown
                            menu_items={[
                                {
                                    name: "Inventory",
                                    icon_class: "fas fa-shopping-bag",
                                    on_click: () =>
                                        this.switchTable("inventory"),
                                },
                                {
                                    name: "Usable",
                                    icon_class: "ra ra-bubbling-potion",
                                    on_click: () =>
                                        this.switchTable("usable-items"),
                                },
                                {
                                    name: "Gem Bag",
                                    icon_class: "fas fa-gem",
                                    on_click: () => this.switchTable("gems"),
                                },
                            ]}
                            button_title={"Type"}
                            selected_name={this.state.table}
                            disabled={this.props.is_dead}
                        />
                    </div>
                    <div
                        className={clsx("ml-2", {
                            hidden: this.isDropDownHidden(),
                        })}
                    >
                        <DropDown
                            menu_items={this.createActionsDropDown()}
                            button_title={"Actions"}
                            selected_name={this.state.table}
                            disabled={this.props.is_dead}
                        />
                    </div>
                    <div
                        className={clsx("ml-2", {
                            hidden: this.isSelectedDropDownHidden(),
                        })}
                    >
                        <DropDown
                            menu_items={this.createMultiSelectedItemDropDown()}
                            button_title={"Selected Items (Actions)"}
                            selected_name={this.state.table}
                            disabled={this.props.is_dead}
                            greenButton={true}
                        />
                    </div>
                    <div className="sm:ml-4 md:ml-0 my-4 md:my-0 md:absolute md:right-[10px]">
                        <input
                            type="text"
                            name="search"
                            className="form-control"
                            onChange={this.search.bind(this)}
                            placeholder={"Search"}
                            value={this.state.search_string}
                        />
                    </div>
                </div>

                {this.renderTables()}

                {this.state.show_action_confirmation_modal &&
                this.state.action_confirmation_type !== null &&
                modalPropsBuilder !== null ? (
                    <BaseInventoryActionConfirmationModal
                        type={this.state.action_confirmation_type}
                        is_open={this.state.show_action_confirmation_modal}
                        manage_modal={this.manageConfirmationModal.bind(this)}
                        title={modalPropsBuilder.fetchModalName()}
                        update_inventory={this.props.update_inventory}
                        set_success_message={this.setSuccessMessage.bind(this)}
                        selected_item_names={this.state.selected_items.map(
                            (selectedItem) => selectedItem.item_name,
                        )}
                        data={{
                            url: modalPropsBuilder.fetchActionUrl(
                                this.props.character_id,
                            ),
                            params: {
                                slot_ids: this.state.selected_items.map(
                                    (selectedItem) => selectedItem.slot_id,
                                ),
                            },
                        }}
                        usable_sets={this.props.usable_sets}
                    />
                ) : null}

                {this.state.show_use_many &&
                this.state.usable_items.length > 0 ? (
                    <InventoryUseManyItems
                        is_open={this.state.show_use_many}
                        manage_modal={this.manageUseManyItems.bind(this)}
                        items={this.state.usable_items}
                        update_inventory={this.props.update_inventory}
                        character_id={this.props.character_id}
                        set_success_message={this.setSuccessMessage.bind(this)}
                    />
                ) : null}
            </Fragment>
        );
    }
}
