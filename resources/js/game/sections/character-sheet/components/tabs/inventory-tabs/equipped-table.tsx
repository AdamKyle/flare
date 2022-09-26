import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {BuildInventoryTableColumns} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {isEqual} from "lodash";
import EquippedInventoryTabProps from "../../../../../lib/game/character-sheet/types/tabs/equipped-inventory-tab-props";
import EquippedTableState from "../../../../../lib/game/character-sheet/types/tables/equipped-table-state";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseDetails from "../../modals/inventory-item-details";

export default class EquippedTable extends React.Component<EquippedInventoryTabProps, EquippedTableState> implements ActionsInterface  {
    constructor(props: EquippedInventoryTabProps) {
        super(props);

        this.state = {
            data: this.props.equipped_items,
            loading: false,
            search_string: '',
            success_message: null,
            item_id: null,
            view_item: false,
        }
    }

    componentDidUpdate(prevProps: Readonly<EquippedInventoryTabProps>, prevState: Readonly<any>, snapshot?: any) {
        if (!isEqual(prevState.data, this.props.equipped_items) && this.state.search_string.length === 0) {
            this.setState({
                data: this.props.equipped_items
            });
        }
    }

    viewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            item_id: typeof item !== 'undefined' ? item.item_id : null,
            view_item: !this.state.view_item
        })
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        this.setState({
            data: this.props.equipped_items.filter((item: InventoryDetails) => {
                return item.item_name.includes(value) || item.type.includes(value);
            }),
            search_string: value,
        });
    }

    actions(row: InventoryDetails): JSX.Element {
        return <DangerButton button_label={'Unequip'} on_click={() => this.unequip(row.slot_id)} disabled={this.props.is_dead || this.props.is_automation_running || this.state.loading} />
    }

    assignToSet(label: string) {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/save-equipped-as-set').setParameters({
                move_to_set: this.props.sets[label].set_id,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: result.data.message,
                }, () => {
                    this.props.update_inventory(result.data.inventory);
                })
            }, (error: AxiosError) => {

            });
        });

    }

    hasEmptySet() {
        if (this.props.is_set_equipped) {
            return false;
        }

        if (this.state.data.length === 0) {
            return false;
        }

        const dropDownLabels = Object.keys(this.props.sets);

        // @ts-ignore
        return dropDownLabels.filter((key) => this.props.sets[key].items.length === 0).length > 0;
    }

    buildMenuItems() {
        let dropDownLabels = Object.keys(this.props.sets);

        dropDownLabels = dropDownLabels.filter((key) => this.props.sets[key].items.length === 0);

        return dropDownLabels.map((label: string) => {
            return {
                name: label,
                icon_class: 'ra ra-crossed-swords',
                on_click: () => this.assignToSet(label)
            }
        });
    }

    manageSuccessMessage() {
        this.setState({
            success_message: null
        });
    }

    unequipAll() {
        this.setState({
            loading: true,
        }, () => {
            this.props.disable_tabs();

            (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/unequip-all').setParameters({
                is_set_equipped: this.props.is_set_equipped,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: result.data.message
                }, () => {
                    this.props.update_inventory(result.data.inventory);

                    this.props.disable_tabs();
                });
            }, (error: AxiosError) => {

            });
        })
    }

    unequip(id: number) {
        this.setState({
            loading: true,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/unequip').setParameters({
                inventory_set_equipped: this.props.is_set_equipped,
                item_to_remove: id,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: result.data.message
                }, () => {
                    this.props.update_inventory(result.data.inventory);
                });
            }, (error: AxiosError) => {

            });
        });
    }

    saveAsSet() {

    }

    render() {
        return (
            <Fragment>
                {
                    this.state.success_message !== null ?
                        <SuccessAlert additional_css={'mb-4 mt-4'} close_alert={this.manageSuccessMessage.bind(this)}>
                            {this.state.success_message}
                        </SuccessAlert>
                     : null
                }
                <div className='mb-5'>
                    <div className='flex items-center'>
                        <div>
                            <label className="label block mb-2 mr-3" htmlFor="search">Search</label>
                        </div>
                        <div>
                            <input type='text' name='search' className='form-control' onChange={this.search.bind(this)} placeholder={'search'}/>
                        </div>
                        <div className='ml-2'>
                            <DangerButton button_label={'Unequip All'} on_click={this.unequipAll.bind(this)} disabled={this.props.is_dead || this.state.data.length === 0 || this.props.is_automation_running || this.state.loading} />
                        </div>
                        {
                            this.hasEmptySet() ?
                                <div className='ml-2'>
                                    <DropDown menu_items={this.buildMenuItems()} button_title={'Assign to Set'} disabled={this.props.is_dead || this.props.is_automation_running || this.state.loading} />
                                </div>
                            : null
                        }

                        {
                            this.props.is_set_equipped ?
                                <div className='ml-2 text-green-700 dark:text-green-500'>
                                    Set Equipped.
                                </div>
                            : null
                        }
                    </div>
                    {
                        this.state.loading ?
                            <LoadingProgressBar show_label={true} label={'Unequipping items and recalculating your stats (this can take a few seconds) ...'} />
                        : null
                    }
                </div>

                {
                    this.state.view_item && this.state.item_id !== null ?
                        <InventoryUseDetails character_id={this.props.character_id} item_id={this.state.item_id} is_open={this.state.view_item} manage_modal={this.viewItem.bind(this)} />
                        : null
                }

                <Table data={this.state.data} columns={BuildInventoryTableColumns(this, this.viewItem.bind(this), 'equipped')} dark_table={this.props.dark_tables}/>
            </Fragment>
        );
    }
}
