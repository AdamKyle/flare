import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    BuildInventoryTableColumns,
    buildLimitedColumns
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import PrimaryOutlineButtonWithPopOver from "../../../../../components/ui/buttons/primary-outline-button-with-pop-over";
import PopOverContainer from "../../../../../components/ui/popover/pop-over-container";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";

export default class SetsTable extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            data: [],
            drop_down_labels: [],
            selected_set: null,
        }
    }

    componentDidMount() {
        const sets = Object.keys(this.props.sets);

        this.setState({
            data: this.props.sets[sets[0]],
            drop_down_labels: sets,
            selected_set: sets[0],
        })
    }

    switchTable(set: string) {
        this.setState({
            data: this.props.sets[set],
            selected_set: set,
        });
    }

    buildMenuItems() {
        return this.state.drop_down_labels.map((label: string) => {
            return {
                name: label,
                icon_class: 'ra ra-crossed-swords',
                on_click: () => this.switchTable(label)
            }
        });
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        this.setState({
            data: this.props.sets[this.state.selected_set].filter((item: InventoryDetails) => {
                return item.item_name.includes(value)
            })
        });
    }

    render() {
        return (
            <Fragment>
                <div className='flex items-center'>
                    <div>
                        <DropDown menu_items={this.buildMenuItems()} button_title={'Set'} selected_name={this.state.selected_set} />
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
                                        This will only search in the currently selected set. If you are looking for items in other sets, you must select the set, then search.
                                    </p>
                                </PopOverContainer>
                            </div>
                        </div>
                    </div>
                </div>

                <Table data={this.state.data} columns={BuildInventoryTableColumns()} dark_table={this.props.dark_table}/>
            </Fragment>
        );
    }
}
