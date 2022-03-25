import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {BuildInventoryTableColumns} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import PopOverContainer from "../../../../../components/ui/popover/pop-over-container";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import DangerButton from "../../../../../components/ui/buttons/danger-button";

export default class EquippedTable extends React.Component<any, any> implements ActionsInterface  {
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

    actions(row: InventoryDetails): JSX.Element {
        return <DangerButton button_label={'Unequip'} on_click={() => this.unequip(row.id)} />
    }

    unequipAll() {

    }

    unequip(id: number) {

    }

    saveAsSet() {

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
                            <input type='text' name='search' className='form-control' onChange={this.search.bind(this)} placeholder={'search'}/>
                        </div>
                        <div className='ml-2'>
                            <DangerButton button_label={'Unequip All'} on_click={this.unequipAll.bind(this)} />
                        </div>
                        <div className='ml-2'>
                            <PrimaryButton button_label={'Save as Set'} on_click={this.saveAsSet.bind(this)} />
                        </div>
                    </div>
                </div>
                <Table data={this.state.data} columns={BuildInventoryTableColumns(this)} dark_table={this.props.dark_table}/>
            </Fragment>
        );
    }
}
