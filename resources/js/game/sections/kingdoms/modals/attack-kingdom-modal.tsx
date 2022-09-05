import React, {Fragment} from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import SelectedUnitsToCallType from "../../../lib/game/kingdoms/types/selected-units-to-call-type";
import AttackKingdomModalState from "../../../lib/game/kingdoms/types/modals/attack-kingdom-modal-state";
import UnitMovement from "./partials/unit-movement";
import Select from "react-select";
import KingdomDamageSlotItems from "../../../lib/game/kingdoms/kingdom-damage-slot-items";
import {parseInt} from "lodash";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import KingdomHelpModal from "../../components/kingdoms/modals/kingdom-help-modal";

export default class AttackKingdomModal extends React.Component<any, AttackKingdomModalState> {

    private tabs: {key: string, name: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'use-items',
            name: 'Use items'
        }, {
            key: 'send-units',
            name: 'Send Units',
        }]

        this.state = {
            loading: false,
            fetching_data: true,
            items_to_use: [],
            kingdoms: [],
            error_message: '',
            success_message: '',
            selected_kingdoms: [],
            selected_units: [],
            selected_items: [],
            total_damage: 0,
            total_reduction: 0,
            show_help_modal: false,
            help_type: '',
        }
    }

    componentDidMount() {
        (new Ajax).setRoute('fetch-attacking-data/' + this.props.kingdom_to_attack_id + '/' + this.props.character_id)
                  .doAjaxCall('get', (result: AxiosResponse) => {
                      this.setState({
                          items_to_use: result.data.items_to_use,
                          kingdoms: result.data.kingdoms,
                          fetching_data: false,
                      })
                  }, (error: AxiosError) => {
                      console.error(error);
                  });
    }

    setAmountToMove(selectedUnits: SelectedUnitsToCallType[]|[]) {
        this.setState({
            selected_units: selectedUnits
        });
    }

    setKingdoms(kingdomsSelected: number[]|[]) {

        this.setState({
            selected_kingdoms: kingdomsSelected,
        })
    }

    attackKingdom() {
        console.log(this.state.selected_units);
    }

    manageShowHelpDialogue(type: string) {
        this.setState({
            show_help_modal: !this.state.show_help_modal,
            help_type: type,
        });
    }

    useItemsOnKingdom() {
        this.setState({
            loading: true,
            success_message: '',
            error_message: '',
        }, () => {
            (new Ajax).setRoute('drop-items-on-kingdom/' + this.props.kingdom_to_attack_id + '/' + this.props.character_id)
                .setParameters({
                    slots: this.state.selected_items
                }).doAjaxCall('post', (result: AxiosResponse) => {

                    this.setState({
                        loading: false,
                        success_message: result.data.message,
                    });

                }, (error: AxiosError) => {
                    this.setState({loading: false});

                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        this.setState({
                            success_message: response.data.message,
                        });
                    }

                    console.error(error);
                });
        });
    }

    buildItemsSelection() {
        return this.state.items_to_use.map((slot: KingdomDamageSlotItems) => {
            return {
                label: slot.item.affix_name,
                value: slot.id.toString(),
            }
        });
    }

    setItemsToUse(data: any) {

        const selectedItems: any = [];
        let damage: number = 0;

        data.forEach((selected: {label: string, value: string}) => {
            if (selected.value !== 'Please select one or more items') {

                const id = parseInt(selected.value, 10) || 0;

                if (id !== 0) {

                    const foundItem = this.state.items_to_use.filter((slot: KingdomDamageSlotItems) => {
                        return slot.id === id;
                    });

                    if (foundItem.length > 0) {
                        damage += foundItem[0].item.kingdom_damage;

                        selectedItems.push(id)
                    }
                }
            }
        });

        let reduction = 0;

        if (this.props.kingdom_defence > 1) {
            const defence = this.props.kingdom_defence - 1;

            reduction = defence / 0.05;

            if (reduction < 0.05) {
                reduction = 0.05;
            }

            damage -= damage * 0.05;
        }

        this.setState({
            selected_items: selectedItems,
            total_damage: damage,
            total_reduction: reduction,
        });
    }

    getSelectedItems() {
        const selectedItems = this.state.items_to_use.filter((item: KingdomDamageSlotItems) => {
            // @ts-ignore
            if (this.state.selected_items.includes(item.id)) {
                return item;
            }
        });

        if (selectedItems.length > 0) {
            return selectedItems.map((slot: KingdomDamageSlotItems) => {
                return {
                    label: slot.item.affix_name,
                    value: slot.id.toString(),
                }
            });
        }

        return [{
            label: 'Please select one or more items',
            value: 'Please select one or more items'
        }];
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Attack Kingdom'}
                      primary_button_disabled={this.state.loading}
                      secondary_actions={{
                          secondary_button_disabled: this.state.selected_units.length === 0 || this.state.loading,
                          secondary_button_label: 'Send Units',
                          handle_action: this.attackKingdom.bind(this)
                      }}
                      tertiary_actions={{
                          tertiary_button_disabled: this.state.selected_items.length === 0 || this.state.loading,
                          tertiary_button_label: 'Use items',
                          handle_action: this.useItemsOnKingdom.bind(this),
                      }}

            >
                {
                    this.state.fetching_data ?
                        <div className='py-4'>
                            <ComponentLoading />
                        </div>
                    :
                        <Fragment>
                            {
                                this.state.success_message !== '' ?
                                    <SuccessAlert>
                                        {this.state.success_message}
                                    </SuccessAlert>
                                : null
                            }

                            {
                                this.state.error_message !== '' ?
                                    <DangerAlert>
                                        {this.state.error_message}
                                    </DangerAlert>
                                    : null
                            }
                            <Tabs tabs={this.tabs} disabled={this.state.loading}>
                                <TabPanel key={'use-items'}>
                                    <Fragment>
                                        <Select
                                            onChange={this.setItemsToUse.bind(this)}
                                            isMulti
                                            options={this.buildItemsSelection()}
                                            menuPosition={'absolute'}
                                            menuPlacement={'bottom'}
                                            styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                            menuPortalTarget={document.body}
                                            value={this.getSelectedItems()}
                                        />
                                        <div className='my-4'>
                                            <dl>
                                                <dt>Total Damage</dt>
                                                <dd>
                                                    <div className='flex items-center mb-4'>
                                                        {(this.state.total_damage * 100).toFixed(2)}%
                                                        <div>
                                                            <div className='ml-2'>
                                                                <button type={"button"} onClick={() => this.manageShowHelpDialogue('total_damage')} className='text-blue-500 dark:text-blue-300'>
                                                                    <i className={'fas fa-info-circle'}></i> Help
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </dd>
                                                <dt>Building Damage</dt>
                                                <dd>
                                                    <div className='flex items-center mb-4'>
                                                        {((this.state.total_damage / 2) * 100).toFixed(2)}%
                                                        <div>
                                                            <div className='ml-2'>
                                                                <button type={"button"} onClick={() => this.manageShowHelpDialogue('building_damage')} className='text-blue-500 dark:text-blue-300'>
                                                                    <i className={'fas fa-info-circle'}></i> Help
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </dd>
                                                <dt>Unit Damage</dt>
                                                <dd>
                                                    <div className='flex items-center mb-4'>
                                                        {((this.state.total_damage / 2) * 100).toFixed(2)}%
                                                        <div>
                                                            <div className='ml-2'>
                                                                <button type={"button"} onClick={() => this.manageShowHelpDialogue('unit_damage')} className='text-blue-500 dark:text-blue-300'>
                                                                    <i className={'fas fa-info-circle'}></i> Help
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </dd>
                                                <dt>Total Reduction</dt>
                                                <dd>
                                                    <div className='flex items-center mb-4'>
                                                        {(this.state.total_reduction * 100).toFixed(2)} %
                                                        <div>
                                                            <div className='ml-2'>
                                                                <button type={"button"} onClick={() => this.manageShowHelpDialogue('total_reduction')} className='text-blue-500 dark:text-blue-300'>
                                                                    <i className={'fas fa-info-circle'}></i> Help
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </Fragment>
                                </TabPanel>

                                <TabPanel key={'send-units'}>
                                    <UnitMovement update_units_selected={this.setAmountToMove.bind(this)}
                                                  kingdoms={this.state.kingdoms}
                                                  update_kingdoms_selected={this.setKingdoms.bind(this)}
                                    />
                                </TabPanel>
                            </Tabs>

                            {
                                this.state.loading ?
                                    <LoadingProgressBar />
                                : null
                            }

                            {
                                this.state.show_help_modal ?
                                    <KingdomHelpModal manage_modal={this.manageShowHelpDialogue.bind(this)} type={this.state.help_type}/>
                                : null
                            }
                        </Fragment>
                }

            </Dialogue>
        );
    }
}
