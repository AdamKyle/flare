import React, {Fragment} from "react";
import DropDown from "../../../../components/ui/drop-down/drop-down";
import InventoryTable from "./inventory-tabs/inventory-table";
import UsableItemsTable from "./inventory-tabs/usable-items-table";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import InventoryDetails from "../../../../lib/game/character-sheet/types/inventory/inventory-details";

export default class InventoryTabSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            table: 'Inventory',
            data: this.props.inventory,
        }
    }

    switchTable(type: string) {
        this.setState({
            table: type,
            data: type === 'Inventory' ? this.props.inventory : this.props.usable_items
        });
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        if (this.state.table === 'Inventory') {
            this.setState({
                data: this.props.inventory.filter((item: InventoryDetails) => {
                    return item.item_name.includes(value) || item.type.includes(value);
                }),
            });
        } else {
            this.setState({
                data: this.props.usable_items.filter((item: InventoryDetails) => {
                    return item.item_name.includes(value) || item.description.includes(value);
                }),
            });
        }
    }

    render() {
        return (
            <Fragment>
                <div className='flex items-center'>
                    <div>
                    <DropDown menu_items={[
                        {
                            name: 'Inventory',
                            icon_class: 'fas fa-shopping-bag',
                            on_click: () => this.switchTable('Inventory')
                        },
                        {
                            name: 'Usable',
                            icon_class: 'ra ra-bubbling-potion',
                            on_click: () => this.switchTable('Usable')
                        },
                    ]} button_title={'Type'} selected_name={this.state.table} />
                    </div>
                    <div className='ml-4 md:ml-0 my-4 md:my-0 md:absolute md:right-0'>
                        <div className='flex items-center'>
                            <div>
                                <input type='text' name='search' className='form-control' onChange={this.search.bind(this)}/>
                            </div>
                            <div className='mt-2'>
                                <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[14px] md:left-0'} make_small={true} >
                                    <h3>Searching</h3>
                                    <p className='my-2'>
                                       This will only search within the current selected type. To find other items, change types and search.
                                    </p>
                                </PopOverContainer>
                            </div>
                        </div>
                    </div>
                </div>

                {
                    this.state.table === 'Inventory' ?
                        <InventoryTable dark_table={this.props.dark_tables} character_id={this.props.character_id} inventory={this.state.data} />
                        :
                        <UsableItemsTable dark_table={this.props.dark_tables} usable_items={this.state.data} />
                }
            </Fragment>
        )
    }
}
