import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {BuildInventoryTableColumns} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import PopOverContainer from "../../../../../components/ui/popover/pop-over-container";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";

export default class EquippedTable extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            data: this.props.equipped_items,
        }
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        this.setState({
            data: this.props.equipped_items.filter((item: InventoryDetails) => {
                return item.item_name.includes(value) || item.type.includes(value);
            }),
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
                            <input type='text' name='search' className='form-control' onChange={this.search.bind(this)}/>
                        </div>
                    </div>
                </div>
                <Table data={this.state.data} columns={BuildInventoryTableColumns()} dark_table={this.props.dark_table}/>
            </Fragment>
        );
    }
}
