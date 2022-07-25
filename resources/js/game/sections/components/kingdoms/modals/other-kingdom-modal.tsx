import React, {Fragment} from "react";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import KingdomModalState from "../../../../lib/game/types/map/kingdom-pins/modals/kingdom-modal-state";
import {formatNumber, percent} from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import OtherKingdomModalProps from "../../../../lib/game/types/map/kingdom-pins/modals/other-kingdom-modal-props";
import KingdomDetails from "../../../../lib/game/map/types/kingdom-details";
import KingdomHelpModal from "./kingdom-help-modal";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";

export default class OtherKingdomModal extends React.Component<OtherKingdomModalProps, KingdomModalState> {

    constructor(props: any) {
        super(props);

        this.state = {
            can_afford: false,
            distance: 0,
            cost: 0,
            time_out: 0,
            x: 0,
            y: 0,
            loading: true,
            kingdom_details: null,
            show_help: false,
            help_type: '',
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('kingdoms/other/'+this.props.kingdom_id).doAjaxCall('get', (result: AxiosResponse) => {
            const kingdomData = result.data;

            const costState = fetchCost(kingdomData.x_position, kingdomData.y_position, this.props.character_position, this.props.currencies);

            const state = {...costState, ...{loading: false, kingdom_details: kingdomData, x: kingdomData.x_position, y: kingdomData.y_position}};

            this.setState(state);

        }, (error: AxiosError) => {
            console.log(error);
        });
    }

    attackKingdom(kingdom?: KingdomDetails | null) {
        console.log(kingdom);
    }

    purchaseKingdom() {
        console.log(this.state.kingdom_details);
    }

    handleTeleport() {
        if (typeof this.props.teleport_player !== 'undefined') {
            this.props.teleport_player({
                x: this.state.x,
                y: this.state.y,
                cost: this.state.cost,
                timeout: this.state.time_out,
            });
        }

        this.props.handle_close();
    }

    teleportDisabled() {
        return this.state.cost === 0 || !this.state.can_afford || !this.props.can_move || this.props.is_dead;
    }

    buildTitle() {
        if (this.state.kingdom_details !== null) {
            const kingdomDetails = this.state.kingdom_details;
            let title            = '';

            if (kingdomDetails.npc_owned) {
                title = 'NPC Owned';
            } else if (this.props.is_enemy_kingdom) {
                title = 'Enemy';
            }

            return kingdomDetails.name + ' ['+ title +'] (X/Y): ' + kingdomDetails.x_position + '/' + kingdomDetails.y_position;
        }

        return 'Error: Could not build title.';
    }



    manageHelpDialogue(type: 'wall_defence' | 'treas_defence' | 'gb_defence' | 'passive_defence' | 'total_defence' | 'teleport_details') {
        this.setState({
            show_help: !this.state.show_help,
            help_type: type,
        });
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.state.loading ? 'One moment ...' : this.buildTitle()}
                      secondary_actions={ this.props.hide_secondary ? null : {
                          secondary_button_disabled: this.teleportDisabled(),
                          secondary_button_label: 'Teleport',
                          handle_action: this.handleTeleport.bind(this),
                      }}
                      tertiary_actions={{
                          tertiary_button_disabled: this.props.is_automation_running || this.props.is_dead,
                          tertiary_button_label: 'Attack Kingdom',
                          handle_action: () => this.attackKingdom(this.state.kingdom_details),
                      }}
            >
                {
                    this.state.loading ?
                        <div className={'h-40'}>
                            <ComponentLoading />
                        </div>
                    :
                        <Fragment>
                            <div>
                                <div className='lg:grid lg:grid-cols-1 lg:grid-cols-3'>
                                    <div className='lg:col-start-1 lg:col-span-2'>
                                        <dl>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Wall Defence
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('wall_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.walls_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Treas. Defence
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('treas_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.treasury_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    GB. Defence
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('gb_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.gold_bars_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Passive Defence
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('passive_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.passive_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Total Defence
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('total_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.defence_bonus)}%</dd>
                                        </dl>
                                    </div>
                                    <div className='lg:col-start-3 lg:col-end-3'>
                                        <dl>
                                            <dt>Treasure:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.treasury)}</dd>
                                            <dt>Gold Bars:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.gold_bars)}</dd>
                                        </dl>
                                        <PrimaryButton button_label={'Purchase NPC Kingdom'} on_click={this.purchaseKingdom.bind(this)} additional_css={'mt-4'} />
                                    </div>
                                </div>
                                {
                                    !this.props.hide_secondary ?
                                        <Fragment>
                                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                            <div>
                                                {
                                                    this.state.cost > 0 ?
                                                        <Fragment>
                                                            <div className='flex items-center mb-4'>
                                                                <h4>Teleport Details</h4>
                                                                <div>
                                                                    <div className='ml-2'>
                                                                        <button type={"button"} onClick={() => this.manageHelpDialogue('teleport_details')} className='text-blue-500 dark:text-blue-300'>
                                                                            <i className={'fas fa-info-circle'}></i> Help
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <dl>
                                                                <dt>Cost to teleport (gold):</dt>
                                                                <dd className={clsx(
                                                                    {'text-gray-700': this.state.cost === 0},
                                                                    {'text-green-600' : this.state.can_afford && this.state.cost > 0},
                                                                    {'text-red-600': !this.state.can_afford && this.state.cost > 0}
                                                                )}>{formatNumber(this.state.cost)}</dd>
                                                                <dt>Can afford to teleport:</dt>
                                                                <dd>{this.state.can_afford ? 'Yes' : 'No'}</dd>
                                                                <dt>Distance (miles):</dt>
                                                                <dd>{this.state.distance}</dd>
                                                                <dt>Timeout (minutes):</dt>
                                                                <dd>{this.state.time_out}</dd>
                                                            </dl>
                                                        </Fragment>
                                                    :
                                                        <WarningAlert>
                                                            You are too close to the location to be able to teleport.
                                                        </WarningAlert>
                                                }
                                            </div>
                                        </Fragment>
                                    : null
                                }
                            </div>

                            {
                                this.state.show_help ?
                                    <KingdomHelpModal manage_modal={this.manageHelpDialogue.bind(this)} type={this.state.help_type}/>
                                : null
                            }
                        </Fragment>
                }
            </Dialogue>
        );
    }
}
