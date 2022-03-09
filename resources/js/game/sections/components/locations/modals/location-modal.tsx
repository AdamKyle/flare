import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {LocationModalPros} from "../../../../lib/game/types/map/location-pins/modals/location-modal-pros";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import {formatNumber} from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";

export default class LocationModal extends React.Component<LocationModalPros, any> {

    constructor(props: LocationModalPros) {
        super(props);

        this.state = {
            can_afford: false,
            distance: 0,
            cost: 0,
            time_out: 0,
            x: this.props.location.x,
            y: this.props.location.y,
        }
    }

    componentDidMount() {
        this.setState(fetchCost(
            this.props.location.x, this.props.location.y, this.props.character_position, this.props.currencies
        ));
    }

    renderAdventures() {
        if (this.props.location.adventures !== null) {
            return this.props.location.adventures.map((adventure) => {
                return <li>
                    <a href={'/information/adventure/' + adventure.id} target='_blank'>
                        {adventure.name} <i className="fas fa-external-link-alt"></i>
                    </a>
                </li>
            });
        }

        return [];
    }

    handleTeleport() {
        this.props.teleport_player({
            x: this.state.x,
            y: this.state.y,
            cost: this.state.cost,
            timeout: this.state.time_out
        });

        this.props.handle_close();
    }

    teleportDisabled(): boolean {
        return this.state.cost === 0;
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.props.title + ' (X/Y): ' + this.props.location.x + '/' + this.props.location.y}
                      secondary_actions={{
                          secondary_button_disabled: this.teleportDisabled(),
                          secondary_button_label: 'Teleport',
                          handle_action: this.handleTeleport.bind(this),
                      }}
            >
                <p className='my-3'>{this.props.location.description}</p>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                {
                    this.props.location.increase_enemy_percentage_by !== null &&
                    this.props.location.increase_enemy_percentage_by !== null ?
                        <Fragment>
                            <div className='flex items-center mb-4'>
                                <h4>Special Location Details</h4>
                                <div>
                                <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                    <h3>Special Locations</h3>
                                    <p className='my-2'>
                                        This is a special location which contains the same monsters you have been fighting but they are much stronger here.
                                        Players will want to have <a href={"/information/voidance"} target='_blank'>Devouring Darkness and Light<i className="fas fa-external-link-alt"></i></a> <a href={"/information/quest-items"} target='_blank'>Quest items <i className="fas fa-external-link-alt"></i> </a>
                                        Which you can get from completing various: <a href={"/information/quests"} target='_blank'>Quests <i className="fas fa-external-link-alt"></i></a> with in the game.
                                    </p>
                                    <p>
                                        These places offer specific quest items that drop at a 1/1,000,000 chance with your looting skill bonus capped at 45%. You can read more about
                                        special locations and see their drops by reading: <a href={"/information/special-locations"} target='_blank'>Special Locations <i className="fas fa-external-link-alt"></i></a>.
                                    </p>
                                </PopOverContainer>
                                </div>
                            </div>
                            <dl>
                                <dt>Increase Core Stats By: </dt>
                                <dd>{formatNumber(this.props.location.increases_enemy_stats_by)}</dd>
                                <dt>Increase Percentage Based Values By: </dt>
                                <dd>{(this.props.location.increase_enemy_percentage_by * 100).toFixed(0)}%</dd>
                            </dl>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        </Fragment>
                    : null
                }

                {
                    this.props.location.adventures !== null ?
                        <Fragment>
                            <div className='grid gap-2 md:grid-cols-2'>
                                {
                                    this.props.location.adventures.length > 0 ?
                                        <Fragment>
                                            <div>
                                                <div className='flex items-center mb-4'>
                                                    <h4>Adventures</h4>
                                                    <div>
                                                        <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                                            <h3>Adventures</h3>
                                                            <p className='my-2'>
                                                                Looks likes this locations has <a href={"/information/adventure"} target='_blank'>adventures <i className="fas fa-external-link-alt"></i></a> that
                                                                you can complete. If you click on one, you can see it's details. This is very fun! Adventures can be done to gain XP, Skill XP and
                                                                <a href={"/information/factions"} target='_blank'> faction points <i className="fas fa-external-link-alt"></i></a> which give you <a href={"/information/random-enchants"} target='_blank'>
                                                                Uniques <i className="fas fa-external-link-alt"></i></a>.
                                                            </p>
                                                        </PopOverContainer>
                                                    </div>
                                                </div>
                                                <ul>
                                                    {this.renderAdventures()}
                                                </ul>
                                            </div>
                                            <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                                        </Fragment>
                                    : null
                                }

                                <div className={clsx({
                                    'col-start-1 col-span-2': this.props.location.adventures.length === 0
                                })}>
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

                                    {
                                        this.state.cost > 0 ?
                                            <dl>
                                                <dt>Cost to teleport (gold):</dt>
                                                <dd className={clsx(
                                                    {'text-gray-700': this.state.cost === 0},
                                                    {'text-green-600' : this.state.can_afford && this.state.cost > 0},
                                                    {'text-red-600': !this.state.ca_afford && this.state.cost > 0}
                                                )}>{formatNumber(this.state.cost)}</dd>
                                                <dt>Can afford to teleport:</dt>
                                                <dd>{this.state.can_afford ? 'Yes' : 'No'}</dd>
                                                <dt>Distance (miles):</dt>
                                                <dd>{this.state.distance}</dd>
                                                <dt>Timeout (minutes):</dt>
                                                <dd>{this.state.time_out}</dd>
                                            </dl>
                                            : <WarningAlert>
                                                You are too close to the location to be able to teleport.
                                            </WarningAlert>
                                    }

                                </div>

                            </div>
                        </Fragment>
                    : null
                }
            </Dialogue>
        )
    }
}
