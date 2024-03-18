import React from "react";
import ItemActionsState from "./types/item-actions-state";
import clsx from "clsx";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import EquipModal from "../../../sections/components/item-details/comparison/actions/equip-modal";
import MoveItemModal from "../../../sections/components/item-details/comparison/actions/move-item-modal";
import SellItemModal from "../../../sections/components/item-details/comparison/actions/sell-item-modal";
import ListItemModal from "../../../sections/components/item-details/comparison/actions/list-item-modal";
import InventoryUseDetails from "../../../sections/character-sheet/components/modals/inventory-item-details";
import InventoryComparisonActions
    from "../../../sections/components/item-details/comparison/ajax/inventory-comparison-actions";
import ItemToEquip from "../../item-comparison/deffinitions/item-to-equip";
import ItemActionsProps from "./types/item-actions-props";

export default class ItemActions extends React.Component<ItemActionsProps, ItemActionsState> {

    constructor(props: ItemActionsProps) {
        super(props);

        this.state = {
            show_equip_modal: false,
            show_move_modal: false,
            show_sell_modal: false,
            show_list_item_modal: false,
            item_to_sell: null,
            item_to_show: null,
            show_item_details: false,
            show_loading_label: false,
            loading_label: null,
            error_message: null
        }
    }

    isGridSize(
        size: number,
        itemToEquip: ItemToEquip
    ): boolean {
        switch (size) {
            case 5:
                return (
                    itemToEquip.affix_count === 0 &&
                    itemToEquip.holy_stacks_applied === 0 &&
                    !itemToEquip.is_unique
                );
            case 7:
                return (
                    itemToEquip.affix_count > 0 ||
                    itemToEquip.holy_stacks_applied > 0 ||
                    itemToEquip.is_unique
                );
            default:
                return false;
        }
    }

    manageEquipModal() {
        this.setState({
            show_equip_modal: !this.state.show_equip_modal,
        });
    }

    manageMoveModalModal() {
        this.setState({
            show_move_modal: !this.state.show_move_modal,
        });
    }

    manageSellModal(item?: ItemToEquip) {

        if (!item) {
            this.setState({
                show_sell_modal: !this.state.show_sell_modal,
                item_to_sell: null,
            });

            return;
        }

        this.setState({
            show_sell_modal: !this.state.show_sell_modal,
            item_to_sell: item,
        });
    }

    manageViewItemDetails(item: ItemToEquip) {
        this.setState({
            show_item_details: !this.state.show_item_details,
            item_to_show: item,
        });
    }

    manageListItemModal(item: ItemToEquip) {
        this.setState({
            show_list_item_modal: !this.state.show_list_item_modal,
            item_to_sell: item,
        });
    }

    equipItem(type: string, position?: string) {
        this.setState(
            {
                show_loading_label: true,
                loading_label:
                    "Equipping set and recalculating your stats (this can take a few seconds) ...",
            },
            () => {
                const params = {
                    position: position,
                    slot_id: this.props.slot_id,
                    equip_type: type,
                };

                new InventoryComparisonActions().equipItem(this, params);
            }
        );
    }

    moveItem(setId: number) {

        const params = {
            move_to_set: setId,
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        new InventoryComparisonActions().moveItem(this, params);
    }

    sellItem() {

        const params = {
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        new InventoryComparisonActions().sellItem(this, params);
    }

    listItem(price: number) {

        const params = {
            list_for: price,
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        new InventoryComparisonActions().listItem(this, params);
    }

    disenchantItem() {

        new InventoryComparisonActions().disenchantItem(this);
    }

    destroyItem() {

        const params = {
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };

        new InventoryComparisonActions().destroyItem(this, params);
    }

    showReplaceMessage() {
        if (!this.props.is_automation_running) {
            const twoHandedEquipped =
                this.props.comparison_details.hammerEquipped ||
                this.props.comparison_details.bowEquipped ||
                this.props.comparison_details.staveEquipped;
            return (
                ["hammer", "bow", "stave"].includes(
                    this.props.comparison_details.itemToEquip.type
                ) && twoHandedEquipped
            );
        }
    }

    render() {
        return (
            <div>
                <div
                    className={clsx(
                        "mt-6 grid grid-cols-1 w-full gap-2 md:m-auto md:w-3/4",
                        {
                            "md:grid-cols-7": this.isGridSize(
                                7,
                                this.props.comparison_details.itemToEquip
                            ),
                            "md:grid-cols-5": this.isGridSize(
                                5,
                                this.props.comparison_details.itemToEquip
                            ),
                            'hidden':
                                this.props.comparison_details.itemToEquip
                                    .type === "quest",
                        }
                    )}
                >
                    <PrimaryOutlineButton
                        button_label={"Details"}
                        on_click={() =>
                            this.manageViewItemDetails(
                                this.props.comparison_details.itemToEquip
                            )
                        }
                        disabled={this.state.show_loading_label}
                    />
                    <PrimaryOutlineButton
                        button_label={"Equip"}
                        on_click={this.manageEquipModal.bind(this)}
                        disabled={
                            this.state.show_loading_label ||
                            this.props.is_automation_running
                        }
                    />
                    <PrimaryOutlineButton
                        button_label={"Move"}
                        on_click={this.manageMoveModalModal.bind(this)}
                        disabled={this.state.show_loading_label}
                    />

                    {this.props.comparison_details.itemToEquip.type !==
                    "trinket" &&
                    this.props.comparison_details.itemToEquip.type !==
                    "artifact" ? (
                        <SuccessOutlineButton
                            button_label={"Sell"}
                            on_click={() =>
                                this.manageSellModal(
                                    this.props.comparison_details.itemToEquip
                                )
                            }
                            disabled={this.state.show_loading_label}
                        />
                    ) : null}

                    {this.props.comparison_details.itemToEquip.affix_count >
                    0 ||
                    this.props.comparison_details.itemToEquip
                        .holy_stacks_applied > 0 ||
                    this.props.comparison_details.itemToEquip.type ===
                    "trinket" ? (
                        <SuccessOutlineButton
                            button_label={"List"}
                            on_click={() =>
                                this.manageListItemModal(
                                    this.props.comparison_details.itemToEquip
                                )
                            }
                            disabled={
                                this.state.show_loading_label ||
                                this.props.is_automation_running
                            }
                        />
                    ) : null}

                    {this.props.comparison_details.itemToEquip.affix_count >
                    0 ? (
                        <DangerOutlineButton
                            button_label={"Disenchant"}
                            on_click={this.disenchantItem.bind(this)}
                            disabled={this.state.show_loading_label}
                        />
                    ) : null}

                    <DangerOutlineButton
                        button_label={"Destroy"}
                        on_click={this.destroyItem.bind(this)}
                        disabled={this.state.show_loading_label}
                    />
                </div>

                {this.state.show_loading_label ? (
                    <LoadingProgressBar
                        show_label={this.state.show_loading_label}
                        label={this.state.loading_label}
                    />
                ) : null}

                {this.state.show_equip_modal ? (
                    <EquipModal
                        is_open={this.state.show_equip_modal}
                        manage_modal={this.manageEquipModal.bind(this)}
                        item_to_equip={
                            this.props.comparison_details.itemToEquip
                        }
                        equip_item={this.equipItem.bind(this)}
                        is_bow_equipped={
                            this.props.comparison_details.bowEquipped
                        }
                        is_hammer_equipped={
                            this.props.comparison_details.hammerEquipped
                        }
                        is_stave_equipped={
                            this.props.comparison_details.staveEquipped
                        }
                    />
                ) : null}

                {this.state.show_move_modal ? (
                    <MoveItemModal
                        is_open={this.state.show_move_modal}
                        manage_modal={this.manageMoveModalModal.bind(this)}
                        usable_sets={this.props.usable_sets}
                        move_item={this.moveItem.bind(this)}
                    />
                ) : null}

                {this.state.show_sell_modal &&
                this.state.item_to_sell !== null ? (
                    <SellItemModal
                        is_open={this.state.show_sell_modal}
                        manage_modal={this.manageSellModal.bind(this)}
                        sell_item={this.sellItem.bind(this)}
                        item={this.state.item_to_sell}
                    />
                ) : null}

                {this.state.show_list_item_modal ? (
                    <ListItemModal
                        is_open={this.state.show_list_item_modal}
                        manage_modal={this.manageListItemModal.bind(this)}
                        list_item={this.listItem.bind(this)}
                        item={this.state.item_to_sell}
                        dark_charts={this.props.dark_charts}
                    />
                ) : null}

                {this.state.show_item_details &&
                this.state.item_to_show !== null ? (
                    <InventoryUseDetails
                        character_id={this.props.character_id}
                        item_id={this.state.item_to_show.id}
                        is_open={this.state.show_item_details}
                        manage_modal={this.manageViewItemDetails.bind(this)}
                    />
                ) : null}
            </div>
        )
    }
}
