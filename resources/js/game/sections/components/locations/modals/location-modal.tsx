import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {LocationModalPros} from "../../../../lib/game/types/map/location-pins/modals/location-modal-pros";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import {formatNumber} from "../../../../lib/game/format-number";
import clsx from "clsx";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import LocationModalState from "../../../../lib/game/types/map/location-pins/modals/location-modal-state";
import SpecialLocationHelpModal from "./special-location-help-modal";
import LocationDetails from "./location-details";

export default class LocationModal extends React.Component<LocationModalPros, LocationModalState> {

    constructor(props: LocationModalPros) {
        super(props);

        this.state = {
            can_afford: false,
            open_help_dialogue: false,
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
        return this.state.cost === 0 || !this.state.can_afford || !this.props.can_move || this.props.is_automation_running || this.props.is_dead;
    }

    manageHelpDialogue() {
        this.setState({
            open_help_dialogue: !this.state.open_help_dialogue
        })
    }

    purgatorySmithsHouse() {
        if (this.props.location.type_name === "Purgatory Smiths House") {
            return (
                <Fragment>
                    <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                    <h5 className='text-orange-500 dark:text-orange-400'>Purgatory Smiths House</h5>
                    <p className='my-4'>
                        The basement of this house holds tunnels deep into the earth where the shadows become one with the darkness.
                        Players in this location cannot explore, instead they will find their attack section has been updated to allow them
                        to select a <a href='/information/ranked-fights' target="_blank">Rank <i className="fas fa-external-link-alt"></i></a> and
                        a monster, from here the fights take place on the server - All creatures have stats ranging from 10 million in rank 1
                        to 1 billion in rank 10.
                    </p>
                    <p className='mb-4'>
                        Ranked fights are tracked and reset every month. Players can see who is at the top of the rank fights
                        by opening the side menu and selecting Tops. From here you can see who is #1 in each of the ranks. Being #1 means killing the
                        last creature in the list for that rank.
                    </p>
                    <p>
                        <strong>Players in this location will find their stats are the same as on surface, all reductions are removed.</strong>
                    </p>
                    <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                </Fragment>
            )
        }

        return null;
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
                <LocationDetails location={this.props.location} />

                {this.purgatorySmithsHouse()}

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
                            <dt>Required Item To Enter:</dt>
                            <dd>
                                {
                                    this.props.location.required_quest_item_id !== null ?
                                        <a href={"/information/item/" + this.props.location.required_quest_item_id} target='_blank'>
                                            {this.props.location.required_quest_item_name} <i
                                            className="fas fa-external-link-alt"></i>
                                        </a>
                                    :
                                        'Requires No Item'
                                }
                            </dd>
                        </dl>
                    :
                        <WarningAlert>
                            You are too close to the location to be able to teleport.
                        </WarningAlert>
                }

                {
                    this.state.open_help_dialogue ?
                        <SpecialLocationHelpModal manage_modal={this.manageHelpDialogue.bind(this)} />
                    : null
                }
            </Dialogue>
        )
    }
}
