import React, {Fragment} from "react";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import KingdomModalProps from "../../../../lib/game/types/map/kingdom-pins/modals/kingdom-modal-props";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import KingdomModalState from "../../../../lib/game/types/map/kingdom-pins/modals/kingdom-modal-state";
import {formatNumber, percent} from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import KingdomHelpModal from "./kingdom-help-modal";

export default class KingdomModal extends React.Component<KingdomModalProps, KingdomModalState> {

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
        (new Ajax()).setRoute('kingdom/'+this.props.kingdom_id+'/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            const kingdomData = result.data;

            const costState = fetchCost(kingdomData.x_position, kingdomData.y_position, this.props.character_position, this.props.currencies);

            const state = {...costState, ...{loading: false, kingdom_details: kingdomData, x: kingdomData.x_position, y: kingdomData.y_position}};

            this.setState(state);

        }, (error: AxiosError) => {

        });
    }

    teleportDisabled() {
        return this.state.cost === 0 || !this.state.can_afford || !this.props.can_move || this.props.is_automation_running || this.props.is_dead;
    }

    attackKingdomDisabled() {
        return this.state.kingdom_details === null || this.state.loading;
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

    manageHelpDialogue(type: 'wall_defence' | 'treas_defence' | 'gb_defence' | 'passive_defence' | 'total_defence' | 'teleport_details') {
        this.setState({
            show_help: !this.state.show_help,
            help_type: type,
        });
    }

    buildTitle() {
        if (this.state.kingdom_details !== null) {
            const kingdomDetails = this.state.kingdom_details;

            return kingdomDetails.name + ' (X/Y): ' + kingdomDetails.x_position + '/' + kingdomDetails.y_position;
        }

        return 'Error: Could not build title.';
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.state.loading ? 'One moment ...' : this.buildTitle()}
                      secondary_actions={{
                          secondary_button_disabled: this.teleportDisabled(),
                          secondary_button_label: 'Teleport',
                          handle_action: this.handleTeleport.bind(this),
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
                                <div className={'w-full lg:grid lg:grid-cols-3 lg:gap-2'}>
                                    <div>
                                        <dl>
                                            <dt>Wood:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.current_wood)} / {formatNumber(this.state.kingdom_details?.max_wood)}</dd>
                                            <dt>Clay:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.current_clay)} / {formatNumber(this.state.kingdom_details?.max_clay)}</dd>
                                        </dl>
                                    </div>
                                    <div>
                                        <dl>
                                            <dt>Stone:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.current_stone)} / {formatNumber(this.state.kingdom_details?.max_stone)}</dd>
                                            <dt>Iron:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.current_iron)} / {formatNumber(this.state.kingdom_details?.max_iron)}</dd>
                                        </dl>
                                    </div>
                                    <div>
                                        <dl>
                                            <dt>Pop.:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.current_population)} / {formatNumber(this.state.kingdom_details?.max_population)}</dd>
                                        </dl>
                                    </div>
                                </div>
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                <div className='lg:grid lg:grid-cols-2'>
                                    <div>
                                        <dl>
                                            <dt>Wall Defence</dt>
                                            <dd>
                                                <div className='flex items-center mb-4'>
                                                    {percent(this.state.kingdom_details?.walls_defence)}%
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('wall_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dd>
                                            <dt>Treas. Defence</dt>
                                            <dd>
                                                <div className='flex items-center mb-4'>
                                                    {percent(this.state.kingdom_details?.treasury_defence)}%
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('treas_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dd>
                                            <dt>GB. Defence</dt>
                                            <dd>
                                                <div className='flex items-center mb-4'>
                                                    {percent(this.state.kingdom_details?.gold_bars_defence)}%

                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('gb_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dd>
                                            <dt>Passive Defence</dt>
                                            <dd>
                                                <div className='flex items-center mb-4'>
                                                    {percent(this.state.kingdom_details?.passive_defence)}%
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('passive_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dd>
                                            <dt>Total Defence</dt>
                                            <dd>
                                                <div className='flex items-center mb-4'>
                                                    {percent(this.state.kingdom_details?.defence_bonus)}%
                                                    <div>
                                                        <div className='ml-2'>
                                                            <button type={"button"} onClick={() => this.manageHelpDialogue('total_defence')} className='text-blue-500 dark:text-blue-300'>
                                                                <i className={'fas fa-info-circle'}></i> Help
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </dd>
                                        </dl>
                                        <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                                    </div>
                                    <div>
                                        <dl>
                                            <dt>Treasure:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.treasury)}</dd>
                                            <dt>Gold Bars:</dt>
                                            <dd>{formatNumber(this.state.kingdom_details?.gold_bars)}</dd>
                                        </dl>
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
                        </Fragment>
                }

                {
                    this.state.show_help ?
                        <KingdomHelpModal manage_modal={this.manageHelpDialogue.bind(this)} type={this.state.help_type}/>
                        : null
                }
            </Dialogue>
        );
    }
}
