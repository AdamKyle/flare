import React, { Fragment } from "react";
import Table from "../../../../../components/ui/data-tables/table";
import InventoryTabProps from "../../../../../lib/game/character-sheet/types/inventory-tab-props";
import { BuildInventoryTableColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import InventoryItemsTableState from "../../../../../lib/game/character-sheet/types/tables/inventory-items-table-state";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import ItemDetailsModal from "../../../../../components/modals/item-details/item-details-modal";
import { isEqual } from "lodash";

export default class InventoryTable extends React.Component<
    InventoryTabProps,
    InventoryItemsTableState
> {
    constructor(props: InventoryTabProps) {
        super(props);

        this.state = {
            view_comparison: false,
            slot_id: 0,
            item_type: "",
            selected_slots: [],
        };
    }

    componentDidUpdate(prevProps: InventoryTabProps) {
        if (this.props.selected_items !== prevProps.selected_items) {
            if (
                this.props.selected_items.length <= 0 &&
                this.state.selected_slots.length >= 0
            ) {
                if (this.state.selected_slots.length !== 0) {
                    this.setState({
                        selected_slots: [],
                    });
                }
                return;
            }

            const selectedSlots = this.props.selected_items.map(
                (selectedItem) => {
                    return selectedItem.slot_id;
                },
            );

            if (!isEqual(this.state.selected_slots, selectedSlots)) {
                this.setState({
                    selected_slots: selectedSlots,
                });
            }
        }
    }

    viewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            view_comparison: true,
            slot_id: typeof item !== "undefined" ? item.slot_id : 0,
            item_type: typeof item !== "undefined" ? item.type : "",
        });
    }

    manageSelectedItems(e: React.ChangeEvent<HTMLInputElement>): void {
        const isChecked = e.target.checked;
        const slotId = parseInt(e.target.dataset.slotId as string, 10) || 0; // Added radix 10

        if (slotId <= 0) {
            return;
        }

        const { selected_slots } = this.state;
        let updatedSlots: number[];

        if (selected_slots.length > 0) {
            // @ts-ignore
            const duplicateId = selected_slots.indexOf(slotId);

            if (isChecked && duplicateId !== -1) {
                return;
            }

            updatedSlots = isChecked
                ? [...selected_slots, slotId]
                : selected_slots.filter((id) => id !== slotId);
        } else {
            updatedSlots = [slotId];
        }

        this.setState(
            {
                selected_slots: updatedSlots,
            },
            () => {
                this.props.manage_selected_items(updatedSlots);
            },
        );
    }

    closeViewItem() {
        this.setState({
            view_comparison: false,
            slot_id: 0,
            item_type: "",
        });
    }

    render() {
        return (
            <Fragment>
                <InfoAlert additional_css={"mt-4 mb-4"}>
                    Click the item name to get additional actions. This table
                    only sometimes updates automatically, such as with mass
                    disenchanting items. Players will find their inventory fills
                    up with a lot of "colorful" items, you can learn more about
                    that{" "}
                    <a href="/information/equipment-types" target="_blank">
                        here. <i className="fas fa-external-link-alt"></i>
                    </a>
                </InfoAlert>
                <div className={"max-w-full overflow-x-hidden"}>
                    <Table
                        data={this.props.inventory}
                        columns={BuildInventoryTableColumns(
                            this.props.view_port,
                            undefined,
                            this.viewItem.bind(this),
                            this.props.manage_skills,
                            undefined,
                            this.manageSelectedItems.bind(this),
                            this.state.selected_slots,
                        )}
                        dark_table={this.props.dark_table}
                    />
                </div>

                {this.state.view_comparison ? (
                    <ItemDetailsModal
                        is_open={this.state.view_comparison}
                        manage_modal={this.closeViewItem.bind(this)}
                        slot_id={this.state.slot_id}
                        character_id={this.props.character_id}
                        update_inventory={this.props.update_inventory}
                        set_success_message={this.props.set_success_message}
                        is_dead={this.props.is_dead}
                        is_automation_running={this.props.is_automation_running}
                    />
                ) : null}
            </Fragment>
        );
    }
}
