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
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import KingdomDetails from "../../../../lib/game/map/types/kingdom-details";

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
        return this.state.cost === 0 || !this.state.can_afford;
    }

    attackKingdomDisabled() {
        this.state.kingdom_details === null || this.state.loading
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
                                <div className={'grid md:grid-cols-3 md:gap-2'}>
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
                                <div className='grid md:grid-cols-3'>
                                    <div className='col-start-1 col-span-3 md:col-span-2'>
                                        <dl>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Wall Defence
                                                    <div>
                                                        <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                                            <h3>Wall Defence Bonus</h3>
                                                            <p className='my-2'>
                                                                This is calculated by your wall level divided wall max level to give the kingdom additional defence bonus towards attacking
                                                                units and items that are dropped on your kingdoms.
                                                            </p>
                                                        </PopOverContainer>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.walls_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Treas. Defence
                                                    <div>
                                                        <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                                            <h3>Treasury Defence Bonus</h3>
                                                            <p className='my-2'>
                                                                This is calculated by dividing the amount of treasure by the max you can store: 2 Billion Gold.
                                                                This defence bonus is then added to walls and the other bonuses below to give the kingdom an over all defence bonus.
                                                            </p>
                                                        </PopOverContainer>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.treasury_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    GB. Defence
                                                    <div>
                                                        <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                                            <h3>Gold Bars Defence Bonus</h3>
                                                            <p className='my-2'>
                                                                This is the amount of gold bars you have divided by the amount you can have which is 1000 at a cost of 2 billion each.
                                                                Gold bars can only be purchased in kingdoms where you have the <a href='/information/kingdom-passive-skills' target='_blank'>
                                                                Goblin Bank <i className="fas fa-external-link-alt"></i></a> building unlocked. This is then added to other defence bonuses you have.
                                                            </p>
                                                        </PopOverContainer>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.gold_bars_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Passive Defence
                                                    <div>
                                                        <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                                            <h3>Passive Defence Bonus</h3>
                                                            <p className='my-2'>
                                                                By training <a href='/information/kingdom-passive-skills' target='_blank'>passive skills<i className="fas fa-external-link-alt"></i></a> Your kingdom can unlock additional defence
                                                                bonus for yur kingdom. This is then applied to all other defence bonuses.
                                                            </p>
                                                        </PopOverContainer>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.passive_defence)}%</dd>
                                            <dt>
                                                <div className='flex items-center mb-4'>
                                                    Total Defence
                                                    <div>
                                                        <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                                            <h3>Total Defence Bonus</h3>
                                                            <p className='my-2'>
                                                                This is the total and combined defence your kingdom has. If another player sends cannons towards you, your defence will be capped at 45% regardless if
                                                                it is higher then that. Defence bonus mostly protects against users using items on your kingdom that can do up to 100% in damage for a single one.
                                                            </p>
                                                        </PopOverContainer>
                                                    </div>
                                                </div>
                                            </dt>
                                            <dd>{percent(this.state.kingdom_details?.defence_bonus)}%</dd>
                                        </dl>
                                        <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                                    </div>
                                    <div className='md:col-start-3 md:col-end-3'>
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
                                                                    <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                                                        <h3>Teleportation</h3>
                                                                        <p className='my-2'>
                                                                            This location will let you teleport to it for a fee and a timeout in minutes. If you have trained the skill <a href='/information/skill-information' target='_blank'>
                                                                            Quick Feet <i className="fas fa-external-link-alt"></i></a> to a high enough level then the timer will reduce the time before you can move again by a % down to a maximum of 1 minute.
                                                                            If the teleport button is disabled, you cannot afford to travel.
                                                                        </p>
                                                                    </PopOverContainer>
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
                                                        : <WarningAlert>
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
            </Dialogue>
        );
    }
}
