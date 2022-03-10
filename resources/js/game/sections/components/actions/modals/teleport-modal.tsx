import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import TeleportModalProps from "../../../../lib/game/types/map/modals/teleport-modal-props";
import Select from "react-select";
import clsx from "clsx";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import {formatNumber} from "../../../../lib/game/format-number";


export default class TeleportModal extends React.Component<TeleportModalProps, any> {

    constructor(props: TeleportModalProps) {
        super(props);

        this.state = {
            x_position: this.props.character_position.x,
            y_position: this.props.character_position.y,
            character_position: {
                x: this.props.character_position.x, y: this.props.character_position.y
            },
            cost: 0,
            can_afford: false,
            distance: 0,
            time_out: 0,
            current_location: null,
            current_player_kingdom: null,
        }
    }

    componentDidMount() {
        if (this.props.locations !== null) {
            const foundLocation = this.props.locations.filter((location) => location.x === this.props.character_position.x && location.y === this.props.character_position.y);

            if (foundLocation.length > 0) {
                this.setState({
                    current_location: foundLocation[0],
                });
            }
        }

        if (this.props.player_kingdoms !== null) {
            const foundKingdom = this.props.player_kingdoms.filter((kingdom) => kingdom.x_position === this.props.character_position.x && kingdom.y_position === this.props.character_position.y);

            if (foundKingdom.length > 0) {
                this.setState({
                    current_player_kingdom: foundKingdom[0],
                });
            }
        }
    }

    componentDidUpdate() {
        if (this.props.locations === null) {
            return;
        }

        if (this.state.current_location === null) {
            const foundLocation = this.props.locations.filter((location) => location.x === this.props.character_position.x && location.y === this.props.character_position.y);

            if (foundLocation.length > 0) {
                this.setState({
                    current_location: foundLocation[0],
                });
            }
        }

        if (this.state.current_player_kingdom === null && this.props.player_kingdoms !== null) {
            const foundKingdom = this.props.player_kingdoms.filter((kingdom) => kingdom.x_position === this.props.character_position.x && kingdom.y_position === this.props.character_position.y);

            if (foundKingdom.length > 0) {
                this.setState({
                    current_player_kingdom: foundKingdom[0],
                });
            }
        }
    }

    getDefaultLocationValue() {
        if (this.state.current_location !== null) {
            return {label: this.state.current_location.name + ' (X/Y): ' + this.state.current_location.x + '/' + this.state.current_location.y, value: this.state.current_location.id}
        }

        return  {value: 0, label: ''};
    }

    getDefaultPlayerKingdomValue() {
        if (this.state.current_player_kingdom !== null) {
            return {label: this.state.current_player_kingdom.name + ' (X/Y): ' + this.state.current_player_kingdom.x_position + '/' + this.state.current_player_kingdom.y_position, value: this.state.current_player_kingdom.id}
        }

        return  {value: 0, label: ''};
    }

    setXPosition(data: any) {
        this.setState({
            x_position: data.value,
            current_location: null,
            current_player_kingdom: null,
        }, () => {
            let state = fetchCost(this.state.x_position, this.state.y_position, this.state.character_position, this.props.currencies);

            this.setState(state);
        });
    }

    setLocationData(data: any) {
        if (this.props.locations !== null) {
            const foundLocation = this.props.locations.filter((location) => location.id === data.value);

            if (foundLocation.length > 0) {
                this.setState({
                    x_position: foundLocation[0].x,
                    y_position: foundLocation[0].y,
                    current_location: foundLocation[0],
                    current_player_kingdom: null
                }, () => {
                    this.setState(fetchCost(this.state.x_position, this.state.y_position, this.state.character_position, this.props.currencies));
                });
            }
        }
    }

    setMyKingdomData(data: any) {
        if (this.props.player_kingdoms !== null) {
            const foundKingdom = this.props.player_kingdoms.filter((location) => location.id === data.value);

            if (foundKingdom.length > 0) {
                this.setState({
                    x_position: foundKingdom[0].x_position,
                    y_position: foundKingdom[0].y_position,
                    current_player_kingdom: foundKingdom[0],
                    current_location: null,
                }, () => {
                    this.setState(fetchCost(this.state.x_position, this.state.y_position, this.state.character_position, this.props.currencies));
                });
            }
        }
    }

    setYPosition(data: any) {
        this.setState({
            y_position: data.value,
            current_location: null,
            current_player_kingdom: null,
        }, () => {
            this.setState(fetchCost(this.state.x_position,  data.value, this.state.character_position, this.props.currencies));
        });
    }



    buildCoordinates(type: string): {value: number, label: string}[]|[] {
        if (this.props.coordinates !== null) {
            switch(type) {
                case 'x':
                    return this.convertToSelectable(this.props.coordinates.x);
                case 'y':
                    return this.convertToSelectable(this.props.coordinates.y);
                default:
                    return [];
            }
        }

        return [];
    }

    buildLocationOptions(): {value: number, label: string}[]|[] {
        if (this.props.locations !== null) {
            return this.props.locations.map((location) => {
                return {label: location.name + ' (X/Y): ' + location.x + '/' + location.y, value: location.id}
            });
        }

        return [];
    }

    buildMyKingdomsOptions(): {value: number, label: string}[]|[] {
        if (this.props.player_kingdoms !== null) {
            return this.props.player_kingdoms.map((kingdom) => {
                return {label: kingdom.name + ' (X/Y): ' + kingdom.x_position + '/' + kingdom.y_position, value: kingdom.id}
            });
        }

        return [];
    }

    convertToSelectable(data: number[]): {value: number, label: string}[] {
        return data.map((d) => {
            return {value: d, label: d.toString()}
        });
    }

    teleportPlayer() {
        this.props.teleport_player({
            x: this.state.x_position,
            y: this.state.y_position,
            cost: this.state.cost,
            timeout: this.state.time_out
        });

         this.props.handle_close();
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.props.title}
                      secondary_actions={{
                          handle_action: this.teleportPlayer.bind(this),
                          secondary_button_disabled: !this.state.can_afford,
                          secondary_button_label: 'Teleport',
                      }}
            >
                <div className='grid grid-cols-2'>
                    <div className='flex items-center'>
                        <label className='w-[20px]'>X</label>
                        <div className='w-2/3'>
                            <Select
                                onChange={this.setXPosition.bind(this)}
                                options={this.buildCoordinates('x')}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999, color: '#000000' }) }}
                                menuPortalTarget={document.body}
                                value={{label: this.state.x_position, value: this.state.x_position}}
                            />
                        </div>
                    </div>

                    <div className='flex items-center'>
                        <label className='w-[20px]'>Y</label>
                        <div className='w-2/3'>
                            <Select
                                onChange={this.setYPosition.bind(this)}
                                options={this.buildCoordinates('y')}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999, color: '#000000' }) }}
                                menuPortalTarget={document.body}
                                value={{label: this.state.y_position, value: this.state.y_position}}
                            />
                        </div>
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div className='grid gap-2 md:grid-cols-2'>
                        <div className='flex items-center'>
                            <label className='w-[100px]'>Locations:</label>
                            <div className='w-2/3'>
                                <Select
                                    onChange={this.setLocationData.bind(this)}
                                    options={this.buildLocationOptions()}
                                    menuPosition={'absolute'}
                                    menuPlacement={'bottom'}
                                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                    menuPortalTarget={document.body}
                                    value={this.getDefaultLocationValue()}
                                />
                            </div>
                        </div>
                        {
                            this.props.player_kingdoms !== null ?
                                <div className='flex items-center'>
                                    <label className='w-[100px]'>My Kingdoms:</label>
                                    <div className='w-2/3'>
                                        <Select
                                            onChange={this.setMyKingdomData.bind(this)}
                                            options={this.buildMyKingdomsOptions()}
                                            menuPosition={'absolute'}
                                            menuPlacement={'bottom'}
                                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                            menuPortalTarget={document.body}
                                            value={this.getDefaultPlayerKingdomValue()}
                                        />
                                    </div>
                                </div>
                            : null
                        }
                    </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Cost in Gold:</dt>
                    <dd className={clsx(
                        {'text-gray-700': this.state.cost === 0},
                        {'text-green-600' : this.state.can_afford && this.state.cost > 0},
                        {'text-red-600': !this.state.ca_afford && this.state.cost > 0}
                    )}>{formatNumber(this.state.cost)}</dd>
                    <dt>Distance:</dt>
                    <dd>{this.state.distance} Miles</dd>
                    <dt>Timeout for:</dt>
                    <dd className='flex items-center'>
                        <span>{this.state.time_out} Minutes</span>
                        <div>
                            <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                <h3>Regarding Skills</h3>
                                <p className='my-2'>
                                    When it comes to the teleport timeout, you have a skill called <a href='/information/skill-information' target='_blank'>Quick Feet <i
                                    className="fas fa-external-link-alt"></i></a>, which if raised over time, will
                                    reduce the movement time out of teleporting down from the current value to 1 minute, regardless of distance.
                                </p>
                                <p>
                                    You can find this on your character sheet, under Skills. You can sacrifice a % of your XP from monsters
                                    in order to level the skill over time, by clicking train on Quick Feet and then selecting the amount of XP to sacrifice between 10-100%.
                                </p>
                            </PopOverContainer>
                        </div>
                    </dd>
                </dl>
            </Dialogue>
        )
    }
}
