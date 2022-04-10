import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    buildLimitedColumns
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import QuestItemsInventoryTabProps
    from "../../../../../lib/game/character-sheet/types/tabs/quest-items-inventory-tab-props";

export default class QuestItemsTable extends React.Component<QuestItemsInventoryTabProps, any> {
    constructor(props: QuestItemsInventoryTabProps) {
        super(props);

        this.state = {
            data: this.props.quest_items,
        }
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        this.setState({
            data: this.props.quest_items.filter((item: InventoryDetails) => {
                return item.item_name.includes(value) || item.description.includes(value)
            })
        });
    }

    render() {
        return (
            <Fragment>
                <div className='mb-5'>
                    <div className='flex items-center'>
                        <div>
                            <label className="label block mb-2 mr-3" htmlFor="search">Search</label>
                        </div>
                        <div>
                            <input type='text' name='search' className='form-control' onChange={this.search.bind(this)} />
                        </div>
                    </div>
                </div>
                <Table data={this.state.data} columns={buildLimitedColumns()} dark_table={this.props.dark_table}/>
            </Fragment>
        );
    }
}
