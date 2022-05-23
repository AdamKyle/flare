import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {LocationModalPros} from "../../../../lib/game/types/map/location-pins/modals/location-modal-pros";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import {formatNumber} from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import LocationModalState from "../../../../lib/game/types/map/location-pins/modals/location-modal-state";

export default class LocationModal extends React.Component<LocationModalPros, LocationModalState> {

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
        if (typeof this.props.character_position !== 'undefined' && typeof this.props.currencies !== 'undefined') {
            this.setState(fetchCost(
                this.props.location.x, this.props.location.y, this.props.character_position, this.props.currencies
            ));
        }
    }

    handleTeleport() {
        if (typeof this.props.teleport_player !== 'undefined') {
            this.props.teleport_player({
                x: this.state.x,
                y: this.state.y,
                cost: this.state.cost,
                timeout: this.state.time_out
            });
        }

        this.props.handle_close();
    }

    teleportDisabled(): boolean {
        return this.state.cost === 0 || !this.state.can_afford || !this.props.can_move;
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.props.title + ' (X/Y): ' + this.props.location.x + '/' + this.props.location.y}
                      secondary_actions={ this.props.hide_secondary_button ? null : {
                          secondary_button_disabled: this.teleportDisabled(),
                          secondary_button_label: 'Teleport',
                          handle_action: this.handleTeleport.bind(this),
                      }}
            >
                <p className='my-3'>{this.props.location.description}</p>
                {
                    this.props.location.increase_enemy_percentage_by !== null &&
                    this.props.location.increase_enemy_percentage_by !== null ?
                        <Fragment>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div className='flex items-center mb-4'>
                                <h4>Special Location Details</h4>
                                <div>
                                <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[150px] md:left-0'}>
                                    <h3>Special Locations</h3>
                                    <p className='my-2'>
                                        This is a special location which contains the same monsters you have been fighting but they are much stronger here.
                                        Players will want to have <a href={"/information/voidance"} target='_blank'>Devouring Darkness and Light <i className="fas fa-external-link-alt"></i></a> <a href={"/information/quest-items"} target='_blank'>Quest items <i className="fas fa-external-link-alt"></i> </a>
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
                        </Fragment>
                    : null
                }

                {
                    this.state.cost > 0 ?
                        <dl>
                            <dt>Cost to teleport (gold):</dt>
                            <dd className={clsx(
                                {'text-gray-700': this.state.cost === 0},
                                {'text-green-600': this.state.can_afford && this.state.cost > 0},
                                {'text-red-600': !this.state.can_afford && this.state.cost > 0}
                            )}>{formatNumber(this.state.cost)}</dd>
                            <dt>Can afford to teleport:</dt>
                            <dd>{this.state.can_afford ? 'Yes' : 'No'}</dd>
                            <dt>Distance (miles):</dt>
                            <dd>{this.state.distance}</dd>
                            <dt>Timeout (minutes):</dt>
                            <dd>{this.state.time_out}</dd>
                        </dl>
                    :
                        <WarningAlert>
                            You are too close to the location to be able to teleport.
                        </WarningAlert>
                }
            </Dialogue>
        )
    }
}
