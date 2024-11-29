import React, { Fragment } from "react";
import Table from "../../../../../components/ui/data-tables/table";
import { buildLimitedColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import QuestItemsInventoryTabProps from "../../../../../lib/game/character-sheet/types/tabs/quest-items-inventory-tab-props";
import QuestItemsTableState from "../../../../../lib/game/character-sheet/types/tables/quest-items-table-state";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseDetails from "../../modals/inventory-item-details";

export default class QuestItemsTable extends React.Component<
    QuestItemsInventoryTabProps,
    QuestItemsTableState
> {
    constructor(props: QuestItemsInventoryTabProps) {
        super(props);

        this.state = {
            data: this.props.quest_items,
            item_id: null,
            view_item: false,
        };
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        this.setState({
            data: this.props.quest_items.filter((item: InventoryDetails) => {
                return (
                    item.item_name.includes(value) ||
                    item.description.includes(value)
                );
            }),
        });
    }

    viewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            item_id: typeof item !== "undefined" ? item.item_id : null,
            view_item: !this.state.view_item,
        });
    }

    render() {
        return (
            <Fragment>
                <div className="mb-5">
                    <div className="flex flex-col md:flex-row flex-wrap items-center md:space-x-4">
                        <div className="w-full md:w-1/2">
                            <input
                                type="text"
                                name="search"
                                className="form-control w-full"
                                placeholder="Enter search terms..."
                                onChange={this.search.bind(this)}
                            />
                        </div>
                        <div className="w-full md:w-auto mt-2 md:mt-0">
                            <a
                                href="/information/quests"
                                target="_blank"
                                className="inline-flex items-center text-blue-600 hover:text-blue-800"
                            >
                                Quests help{" "}
                                <i className="fas fa-external-link-alt ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div
                    className={"max-w-[390px] md:max-w-full overflow-y-hidden"}
                >
                    <Table
                        data={this.state.data}
                        columns={buildLimitedColumns(
                            this.props.view_port,
                            undefined,
                            this.viewItem.bind(this),
                        )}
                        dark_table={this.props.dark_table}
                    />
                </div>

                {this.state.view_item && this.state.item_id !== null ? (
                    <InventoryUseDetails
                        character_id={this.props.character_id}
                        item_id={this.state.item_id}
                        is_open={this.state.view_item}
                        manage_modal={this.viewItem.bind(this)}
                    />
                ) : null}
            </Fragment>
        );
    }
}
