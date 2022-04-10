import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    BuildInventoryTableColumns,
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import PopOverContainer from "../../../../../components/ui/popover/pop-over-container";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import SetsInventoryTabProps from "../../../../../lib/game/character-sheet/types/tabs/sets-inventory-tab-props";

export default class SetsTable extends React.Component<SetsInventoryTabProps, any> implements ActionsInterface {
    constructor(props: SetsInventoryTabProps) {
        super(props);

        this.state = {
            data: [],
            drop_down_labels: [],
            selected_set: null,
            loading: false,
        }
    }

    componentDidMount() {
        const sets = Object.keys(this.props.sets);

        // @ts-ignore
        const data = this.props.sets[sets[0]];

        this.setState({
            data: data,
            drop_down_labels: sets,
            selected_set: sets[0],
        })
    }

    switchTable(set: string) {

        // @ts-ignore
        const data = this.props.sets[set];

        this.setState({
            data: data,
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

        // @ts-ignore
        const data = this.props.sets[this.state.selected_set].filter((item: InventoryDetails) => {
            return item.item_name.includes(value)
        });

        this.setState({
            data: data
        });
    }

    actions(row: InventoryDetails): JSX.Element {
        return <DangerButton button_label={'Remove'} on_click={() => this.removeFromSet(row.id)} disabled={this.props.is_dead} />
    }

    emptySet() {

    }

    equipSet() {
        let setId: any = this.props.savable_sets.filter((set) => {
            return set.name === this.state.selected_set;
        });

        if (setId.length > 0) {
            setId = setId[0].id;
        }

        this.setState({
            loading: true
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory-set/equip/' + setId).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false
                }, () => {
                    this.props.update_inventory(result.data.inventory);
                });
            }, (error: AxiosError) => {

            })
        })
    }

    removeFromSet(id: number) {

    }

    render() {
        return (
            <Fragment>
                <div className='flex items-center'>
                    <div>
                        <DropDown menu_items={this.buildMenuItems()} button_title={'Set'} selected_name={this.state.selected_set} secondary_selected={this.props.set_name_equipped} disabled={this.props.is_dead} />
                    </div>
                    <div className='ml-2'>
                        <DangerButton button_label={'Empty Set'} on_click={this.emptySet.bind(this)} disabled={this.props.is_dead} />
                    </div>
                    <div className='ml-2'>
                        {
                            this.state.selected_set === this.props.set_name_equipped ?
                                <span className={'text-green-600 dark:text-green-700'}>Equipped!</span>
                            :
                                <PrimaryButton button_label={'Equip Set'} on_click={this.equipSet.bind(this)} disabled={this.props.is_dead} />
                        }
                    </div>
                    <div className='ml-4 md:ml-0 my-4 md:my-0 md:absolute md:right-0'>
                        <div className='flex items-center'>
                            <div>
                                <input type='text' name='search' className='form-control' onChange={this.search.bind(this)} placeholder={'Search'}/>
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

                {
                    this.state.loading ?
                        <LoadingProgressBar />
                        : null
                }

                <Table data={this.state.data} columns={BuildInventoryTableColumns(this)} dark_table={this.props.dark_tables}/>
            </Fragment>
        );
    }
}
