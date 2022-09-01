import React, {Fragment} from "react";
import Select from "react-select";
import {AxiosError, AxiosResponse} from "axios";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import CallForReinforcementsProps from "../../../lib/game/kingdoms/types/modals/call-for-reinforcements-props";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import CallForReinforcementsState from "../../../lib/game/kingdoms/types/modals/call-for-reinforcements-state";
import KingdomReinforcementType from "../../../lib/game/kingdoms/types/kingdom-reinforcement-type";
import UnitReinforcementType from "../../../lib/game/kingdoms/types/unit-reinforcement-type";
import SelectedUnitsToCallType from "../../../lib/game/kingdoms/types/selected-units-to-call-type";
import { formatNumber } from "../../../lib/game/format-number";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";


export default class CallForReinforcements extends React.Component<CallForReinforcementsProps, CallForReinforcementsState> {

    constructor(props: CallForReinforcementsProps) {
        super(props);

        this.state = {
            loading: true,
            processing_unit_request: false,
            kingdoms: [],
            error_message: '',
            success_message: '',
            selected_kingdoms: [],
            selected_units: [],
        }
    }

    componentDidMount() {
        (new Ajax).setRoute('kingdoms/units/'+this.props.character_id+'/'+this.props.kingdom_id+'/call-reinforcements')
                  .doAjaxCall('get', (result: AxiosResponse) => {
                      this.setState({
                          loading: false,
                          kingdoms: result.data
                      })
                  }, (error: AxiosError) => {
                      this.setState({loading: false});

                      console.error(error);
                  })
    }

    callUnits() {
        this.setState({
            processing_unit_request: true,
        }, () => {
            (new Ajax).setRoute('kingdom/move-reinforcements/'+this.props.character_id+'/'+this.props.kingdom_id)
                .setParameters({units_to_move: this.state.selected_units})
                .doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        processing_unit_request: false,
                    })
                }, (error: AxiosError) => {
                    this.setState({processing_unit_request: false});

                    console.error(error);
                });
        });

    }

    kingdomOptions() {
        return this.state.kingdoms.map((kingdom: KingdomReinforcementType) => {
            return {
                label: kingdom.kingdom_name,
                value: kingdom.kingdom_id.toString(),
            }
        });
    }

    setAmountToMove(kingdomId: number, unitId: number, unitAmount: number, e: React.ChangeEvent<HTMLInputElement>) {
        let unitsToCall = JSON.parse(JSON.stringify(this.state.selected_units));

        const index = unitsToCall.findIndex((unitToCall: SelectedUnitsToCallType) => {
            return unitToCall.kingdom_id === kingdomId && unitToCall.unit_id === unitId;
        });

        let amount: number = parseInt(e.target.value, 10) || 0;

        if (amount <= 0) {
             amount = 0;
        }

        if (amount > unitAmount) {
            amount = unitAmount;
        }

        if (index === -1) {
            if (amount === 0) {
                return;
            }

            unitsToCall.push({
                kingdom_id: kingdomId,
                unit_id:    unitId,
                amount:     amount > unitAmount ? unitAmount : amount,
            });
        } else {
            if (amount === 0) {
                unitsToCall.splice(index, 1);
            }

            unitsToCall[index].amount = amount > unitAmount ? unitAmount : amount;
        }


        this.setState({
            selected_units: unitsToCall
        });
    }


    getValueOfUnitsToCall(kingdomId: number, unitId: number): string|number {
        let unitsToCall = JSON.parse(JSON.stringify(this.state.selected_units));

        const index = unitsToCall.findIndex((unitToCall: SelectedUnitsToCallType) => {
            return unitToCall.kingdom_id === kingdomId && unitToCall.unit_id === unitId
        });

        if (index === -1) {
            return '';
        }

        return unitsToCall[index].amount
    }

    unitOptions() {
        const kingdomsWithUnits = this.state.kingdoms.filter((kingdom: KingdomReinforcementType) => {
            if (this.state.selected_kingdoms.includes(kingdom.kingdom_id)) {
                return kingdom
            }
        });

        const self = this;

        const units = kingdomsWithUnits.map((kingdom: KingdomReinforcementType, kingdomIndex: number) => {
            return kingdom.units.map((unit: UnitReinforcementType, index: number) => {
                return (
                    <div key={kingdom.kingdom_id + '-' + unit.id}>
                        {
                            index === 0 ?
                                <p className='my-2'>From Kingdom: {kingdom.kingdom_name} and will take: {self.getTimeToTravel(kingdom.time)} to get to this kingdom</p>
                            : null
                        }
                        <div className='flex items-center my-4'>
                            <label className='w-1/2'>{unit.name} Amount to move</label>
                            <div className='w-1/2'>
                                <input type='number'
                                       value={this.getValueOfUnitsToCall(kingdom.kingdom_id, unit.id)}
                                       onChange={(e: any) => this.setAmountToMove(kingdom.kingdom_id, unit.id, unit.amount, e)}
                                       className='form-control'
                                />
                                <span className='text-gray-500 dark:text-white text-xs'>Max amount to recruit: {formatNumber(unit.amount)}</span>
                            </div>
                        </div>
                        {
                            kingdom.units.length === index + 1 && kingdomsWithUnits.length > 1 && kingdomsWithUnits.length !== kingdomIndex + 1  ?
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            : null
                        }
                    </div>
                )
            });
        });

        return units;
    }

    getTimeToTravel(time: number): string {
        const hours = time / 60;

        if (hours >= 1) {
            return ' roughly ' + hours.toFixed(0) + ' hour(s) ';
        }

        return time + ' minute(s) ';
    }

    setKingdoms(data: any) {
        const validData = data.filter((data: any) => data.value !== 'Please select one or more kingdoms');

        let selectedKingdoms = JSON.parse(JSON.stringify(this.state.selected_kingdoms));

        selectedKingdoms = validData.map((value: any) => parseInt(value.value, 10) || 0);

        this.setState({
            selected_kingdoms: selectedKingdoms,
        })
    }

    getSelectedKingdomsValue() {
        const kingdoms = this.state.selected_kingdoms.map((kingdom: number) => {
            const index = this.state.kingdoms.findIndex((kingdomData: KingdomReinforcementType) => {
                return kingdomData.kingdom_id === kingdom;
            });

            if (index !== -1) {
                return {
                    label: this.state.kingdoms[index].kingdom_name,
                    value: this.state.kingdoms[index].kingdom_id.toString()
                }
            }
        });

        if (kingdoms.length > 0) {
            return kingdoms;
        }

        return [{label: 'Please select one or more kingdoms', value: 'Please select one or more kingdoms'}];
    }

    renderKingdomSelect() {
        return (
            <Fragment>
                <Select
                    onChange={this.setKingdoms.bind(this)}
                    isMulti
                    options={this.kingdomOptions()}
                    menuPosition={'absolute'}
                    menuPlacement={'bottom'}
                    styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                    menuPortalTarget={document.body}
                    value={this.getSelectedKingdomsValue()}
                />
            </Fragment>

        )
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={'Call for reinforcements'}
                      primary_button_disabled={this.state.loading}
                      secondary_actions={{
                          handle_action: this.callUnits.bind(this),
                          secondary_button_disabled: this.state.loading || (this.state.kingdoms.length === 0 && this.state.selected_units.length === 0),
                          secondary_button_label: 'Call Reinforcements',
                      }}
            >
                {
                    this.state.loading ?
                        <ComponentLoading />
                    : null
                }

                {
                    this.state.kingdoms.length > 0 ?
                        <Fragment>
                            {this.renderKingdomSelect()}

                            {
                                this.state.selected_kingdoms.length > 0 ?
                                    <div className='my-4 max-h-[350px] overflow-y-scroll'>
                                        {this.unitOptions()}
                                    </div>

                                : null
                            }

                            {
                                this.state.processing_unit_request ?
                                    <LoadingProgressBar />
                                : null
                            }
                        </Fragment>
                    :
                        <InfoAlert>
                            You have no units in other kingdoms to move units from or you have no other kingdoms.
                        </InfoAlert>
                }
            </Dialogue>
        )
    }
}
