import React, {Fragment} from "react";
import Table from "../../../../../components/ui/data-tables/table";
import {
    BuildInventoryTableColumns,
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import ActionsInterface from "../../../../../lib/game/character-sheet/helpers/inventory/actions-interface";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import SetsInventoryTabProps from "../../../../../lib/game/character-sheet/types/tabs/sets-inventory-tab-props";
import SetsTableState from "../../../../../lib/game/character-sheet/types/tables/sets-table-state";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import {isEqual} from "lodash";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
import RenameSetModal from "../../modals/rename-set-modal";
import clsx from "clsx";
import UsableItemsDetails from "../../../../../lib/game/character-sheet/types/inventory/usable-items-details";
import InventoryUseDetails from "../../modals/inventory-item-details";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class SetsTable extends React.Component<SetsInventoryTabProps, SetsTableState> implements ActionsInterface {
    constructor(props: SetsInventoryTabProps) {
        super(props);

        this.state = {
            data: [],
            drop_down_labels: [],
            selected_set: null,
            selected_set_index: null,
            loading: false,
            success_message: null,
            show_rename_set: false,
            search_string: '',
            item_id: null,
            view_item: false,
            loading_label: null,
            show_loading_label: false,
            error_message: null,
        }
    }

    componentDidMount() {
        this.setSetData(this.props.sets);
    }

    componentDidUpdate(prevProps: Readonly<SetsInventoryTabProps>, prevState: Readonly<SetsTableState>, snapshot?: any) {
        if (this.state.selected_set !== null && this.state.search_string.length === 0) {
            if (!isEqual(this.props.sets[this.state.selected_set].items, this.state.data)) {
                this.setState({
                    data: this.props.sets[this.state.selected_set].items
                });
            }
        }
    }

    setSetData(sets: {[key: string]: { equippable: boolean; items: InventoryDetails[] | [], equipped: boolean }}) {
        const setKeys = Object.keys(sets);

        // @ts-ignore
        const data = sets[setKeys[0]].items;

        let setIndex = this.state.selected_set_index === null ? 0 : this.state.selected_set_index;
        let selectedSet = '';

        if (this.state.selected_set === null) {
            selectedSet = setKeys[0];
            setIndex    = 0;

            for (let i = 0; i < setKeys.length; i++) {
                if (sets[setKeys[i]].equipped) {
                    setIndex    = setKeys.findIndex((setKey) => setKey === setKeys[i]);
                    selectedSet = setKeys[setIndex];
                }
            }
        } else {
            selectedSet = setKeys[setIndex];
        }

        this.setState({
            data: data,
            drop_down_labels: setKeys,
            selected_set: selectedSet,
            selected_set_index: setIndex,
        })
    }

    actions(row: InventoryDetails): JSX.Element {
        return <DangerButton button_label={'Remove'} on_click={() => this.removeFromSet(row.slot_id)} disabled={this.buttonsDisabled()} />
    }

    emptySet() {
        // @ts-ignore
        const setId = this.props.sets[this.state.selected_set].set_id

        this.setState({
            loading: true,
            error_message: null,
            success_message: null,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory-set/' + setId + '/remove-all').doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: result.data.message,
                }, () => {
                    this.props.update_inventory(result.data.inventory);
                });
            }, (error: AxiosError) => {
                this.setState({loading: false});

                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                    })
                }
            });
        })
    }

    equipSet() {
        let setId: any = this.props.savable_sets.filter((set) => {
            return set.name === this.state.selected_set;
        });

        if (setId.length > 0) {
            setId = setId[0].id;
        }

        this.setState({
            loading: true,
            show_loading_label: true,
            loading_label: 'Equipping set and recalculating your stats (this can take a few seconds) ...',
            error_message: null,
            success_message: null,
        }, () => {
            this.props.disable_tabs();

            (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory-set/equip/' + setId).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: result.data.message,
                }, () => {
                    this.props.update_inventory(result.data.inventory);

                    this.props.disable_tabs();
                });
            }, (error: AxiosError) => {
                this.setState({loading: false});

                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                    })
                }
            });
        });
    }

    removeFromSet(id: number) {
        if (this.state.selected_set !== null) {
            const setId = this.props.sets[this.state.selected_set].set_id;

            this.setState({
                loading: true
            }, () => {
                (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory-set/remove').setParameters({
                    inventory_set_id: setId,
                    slot_id: id
                }).doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        success_message: result.data.message,
                        search_string: '',
                    }, () => {
                        this.props.update_inventory(result.data.inventory);
                    });
                }, (error: AxiosError) => {
                    this.setState({loading: false});

                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        this.setState({
                            error_message: response.data.message,
                        })
                    }
                })
            });
        }
    }

    renameSet(name: string) {
        const setNames: string[] = Object.keys(this.props.sets);

        const foundName: string[] = setNames.filter((name: string) => {
            return name === this.state.selected_set;
        });

        const setId = this.props.sets[foundName[0]].set_id;

        this.setState({
            loading: true,
            error_message: null,
            success_message: null,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory-set/rename-set').setParameters({
                set_id: setId,
                set_name: name
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: result.data.message,
                }, () => {
                    this.setSetData(result.data.inventory.sets);

                    this.props.update_inventory(result.data.inventory);
                });
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        loading: false,
                        error_message: response.data.message,
                    });
                }
            })
        })
    }

    switchTable(set: string) {

        // @ts-ignore
        const data = this.props.sets[set].items;

        const keys = Object.keys(this.props.sets);

        const index = keys.indexOf(set, 0);

        this.setState({
            data: data,
            selected_set: set,
            selected_set_index: index !== -1 ? index : 0
        });
    }

    buildMenuItems() {
        return this.state.drop_down_labels.map((label: string) => {
            return {
                name: label,
                icon_class: clsx('ra ra-crossed-swords', {'text-yellow-600': this.cannotEquipSet(label)}),
                on_click: () => this.switchTable(label)
            }
        });
    }

    manageRenameSet() {
        this.setState({
            show_rename_set: !this.state.show_rename_set
        })
    }

    buildActionsDropDown() {
        const actions = [];

        actions.push({
            name: 'Rename set',
            icon_class: 'fas fa-edit',
            on_click: () => this.manageRenameSet()
        })

        if (this.state.selected_set !== null) {
            if (this.state.selected_set !== this.props.set_name_equipped && this.props.sets[this.state.selected_set].items.length > 0) {
                actions.push({
                    name: 'Empty set',
                    icon_class: 'fas fa-eraser',
                    on_click: () => this.emptySet()
                });

                if (!this.cannotEquipSet() && !this.props.is_automation_running) {
                    actions.push({
                        name: 'Equip set',
                        icon_class: 'ra ra-muscle-fat',
                        on_click: () => this.equipSet()
                    });
                }
            }
        }

        return actions;
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        // @ts-ignore
        const data = this.props.sets[this.state.selected_set].items.filter((item: InventoryDetails) => {
            return item.item_name.includes(value) || item.type.includes(value)
        });

        this.setState({
            data: data,
            search_string: value,
        });
    }

    buttonsDisabled() {

        if (this.state.selected_set !== null) {
            return this.props.sets[this.state.selected_set].equipped || this.props.is_dead || this.props.is_automation_running || this.state.loading
        }

        return true;
    }

    cannotEquipSet(setName?: string) {
        if (this.state.selected_set !== null) {

            if (typeof setName !== 'undefined') {
                return !this.props.sets[setName].equippable
            }

            return !this.props.sets[this.state.selected_set].equippable
        }


        return false;
    }

    clearSuccessMessage() {
        this.setState({
            success_message: null,
        });
    }

    clearErrorMessage() {
        this.setState({
            error_message: null,
        });
    }

    buildSetTitle() {
        if (this.state.selected_set !== null) {
            return 'Viewing: '+this.state.selected_set + '.';
        }

        return null
    }

    viewItem(item?: InventoryDetails | UsableItemsDetails) {
        this.setState({
            item_id: typeof item !== 'undefined' ? item.item_id : null,
            view_item: !this.state.view_item
        })
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.success_message !== null ?
                        <SuccessAlert close_alert={this.clearSuccessMessage.bind(this)} additional_css={'mt-4 mb-4'}>
                            {this.state.success_message}
                        </SuccessAlert>
                    : null
                }
                {
                    this.state.error_message !== null ?
                        <DangerAlert close_alert={this.clearErrorMessage.bind(this)} additional_css={'mt-4 mb-4'}>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }
                {
                    this.cannotEquipSet() ?
                        <WarningAlert additional_css={'mb-4'}>
                            Cannot equip set because it violates the <a href={'/information/equipment-sets'} target='_blank'>set <i className="fas fa-external-link-alt"></i></a> rules.
                            You can still treat this set like a stash tab.
                        </WarningAlert>
                    : null
                }
                {
                    this.buildSetTitle() !== null ?
                        <div>
                            <h4 className='text-orange-500 dark:text-orange-400'>{this.buildSetTitle()}</h4>
                        </div>
                    : null
                }
                <div className='flex flex-row flex-wrap items-center'>
                    <div>
                        <DropDown menu_items={this.buildMenuItems()} button_title={'Sets'} selected_name={this.state.selected_set} secondary_selected={this.props.set_name_equipped} disabled={this.props.is_dead || this.state.loading}  />
                    </div>
                    <div className='ml-2'>
                        <DropDown menu_items={this.buildActionsDropDown()} button_title={'Actions'} disabled={this.props.is_dead || this.state.loading}  />
                    </div>
                    <div className='sm:ml-4 md:ml-0 my-4 md:my-0 md:absolute md:right-[10px]'>
                        <input type='text' name='search' className='form-control' onChange={this.search.bind(this)} placeholder={'Search'} value={this.state.search_string} />
                    </div>
                </div>

                {
                    this.state.loading ?
                        <LoadingProgressBar show_label={this.state.show_loading_label} label={this.state.loading_label} />
                    : null
                }


                {
                    this.state.show_rename_set && this.state.selected_set !== null ?
                        <RenameSetModal is_open={this.state.show_rename_set} manage_modal={this.manageRenameSet.bind(this)} title={'Rename Set'} current_set_name={this.state.selected_set} rename_set={this.renameSet.bind(this)}/>
                    : null
                }

                {
                    this.state.view_item && this.state.item_id !== null ?
                        <InventoryUseDetails character_id={this.props.character_id} item_id={this.state.item_id} is_open={this.state.view_item} manage_modal={this.viewItem.bind(this)} />
                    : null
                }

                <div className={'max-w-[290px] sm:max-w-[100%] overflow-y-hidden'}>
                    <Table data={this.state.data} columns={BuildInventoryTableColumns(this, this.viewItem.bind(this))} dark_table={this.props.dark_tables}/>
                </div>
            </Fragment>
        );
    }
}
