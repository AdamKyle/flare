import React, {Fragment} from "react";
import MapActionsProps from "../../../../lib/game/types/map/map-actions-props";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import SuccessOutlineButton from "../../../../components/ui/buttons/success-outline-button";
import MapActionsState from "../../../../lib/game/types/map/map-actions-state";
import clsx from 'clsx';
import TeleportModal from "../modals/teleport-modal";
import OrangeButton from "../../../../components/ui/buttons/orange-button";
import ViewLocationDetailsModal from "../modals/view-location-details-modal";
import SetSailModal from "../modals/set-sail-modal";

export default class MapActions extends React.Component<MapActionsProps, MapActionsState> {

    constructor(props: MapActionsProps) {
        super(props);

        this.state = {
            is_movement_disabled: false,
            open_teleport_modal: false,
            location: null,
            show_location_details: false,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            open_set_sail_modal: false,
        }
    }

    componentDidMount() {
        if (this.props.locations !== null) {
            this.updateViewLocationData();
        }
    }

    componentDidUpdate(prevProps: Readonly<MapActionsProps>, prevState: Readonly<MapActionsState>, snapshot?: any) {
        if (this.props.can_player_move && this.state.is_movement_disabled) {
            this.setState({is_movement_disabled: false});
        }

        if (!this.props.can_player_move && !this.state.is_movement_disabled) {
            this.setState({is_movement_disabled: true});
        }

        if (this.props.locations !== null && (this.state.location === null && this.state.player_kingdom_id === null && this.state.enemy_kingdom_id === null)) {
            this.updateViewLocationData()
        } else if (this.props.locations === null && this.state.location !== null) {
            this.setState({
                location: null,
            });
        } else if (this.state.player_kingdom_id !== null) {
            this.handlePlayerKingdomChange();
        } else if (this.state.location !== null) {
            this.handleLocationChange();
        } else if (this.state.enemy_kingdom_id !== null) {
            this.handleEnemyKingdomChange();
        }
    }

    handlePlayerKingdomChange() {
        if (this.state.player_kingdom_id === null) {
            return;
        }

        if (this.props.player_kingdoms === null) {
            return this.setState({player_kingdom_id: null});
        }

        const kingdom = this.props.player_kingdoms.filter((kingdom) => kingdom.x_position === this.props.character_position.x && kingdom.y_position === this.props.character_position.y);

        if (kingdom.length > 0) {
            if (kingdom[0].id !== this.state.player_kingdom_id) {
                return this.setState({player_kingdom_id: null});
            }
        } else {
            return this.setState({player_kingdom_id: null});
        }
    }

    handleLocationChange() {
        if (this.state.location === null) {
            return;
        }

        if (this.props.locations === null) {
            return this.setState({ location: null });
        }

        const foundLocation      = this.props.locations.filter((location) => location.x === this.props.character_position.x && location.y === this.props.character_position.y);

        if (foundLocation.length > 0) {
            if (foundLocation[0].id !== this.state.location.id) {
                return this.setState({ location: null });
            }
        } else {
            return this.setState({ location: null });
        }
    }

    handleEnemyKingdomChange() {
        if (this.state.enemy_kingdom_id === 0) {
            return;
        }

        if (this.props.enemy_kingdoms === null) {
            return this.setState({ enemy_kingdom_id: 0 });
        }

        const foundEnemyKingdom      = this.props.enemy_kingdoms.filter((kingdom) => kingdom.x_position === this.props.character_position.x && kingdom.y_position === this.props.character_position.y);

        if (foundEnemyKingdom.length > 0) {
            if (foundEnemyKingdom[0].id !== this.state.enemy_kingdom_id) {
                return this.setState({ enemy_kingdom_id: null });
            }
        } else {
            return this.setState({ enemy_kingdom_id: null });
        }
    }

    updateViewLocationData() {

        if (this.props.locations == null || this.props.player_kingdoms === null || this.props.enemy_kingdoms === null) {
            return;
        }

        const foundLocation      = this.props.locations.filter((location) => location.x === this.props.character_position.x && location.y === this.props.character_position.y);
        const foundPlayerKingdom = this.props.player_kingdoms.filter((kingdom) => kingdom.x_position === this.props.character_position.x && kingdom.y_position === this.props.character_position.y);
        const foundEnemyKingdom  = this.props.enemy_kingdoms.filter((kingdom) => kingdom.x_position === this.props.character_position.x && kingdom.y_position === this.props.character_position.y);

        let state = {
            location: null,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
        }

        if (foundLocation.length > 0) {
            // @ts-ignore
            state.location = foundLocation[0];
        }

        if (foundPlayerKingdom.length > 0) {
            // @ts-ignore
            state.player_kingdom_id = foundPlayerKingdom[0].id;
        }

        if (foundEnemyKingdom.length > 0) {
            // @ts-ignore
            state.enemy_kingdom_id = foundEnemyKingdom[0].id;
        }

        if (state.location === null && state.player_kingdom_id === null && state.enemy_kingdom_id === null) {
            return;
        }

        this.setState(state);
    }

    move(direction: string) {
        this.setState({
            is_movement_disabled: true,
        }, () => {
            this.props.move_player(direction);
        })
    }

    manageTeleportModal() {
        this.setState({
            open_teleport_modal: !this.state.open_teleport_modal,
        });
    }

    manageSetSailModal() {
        this.setState({
            open_set_sail_modal: !this.state.open_set_sail_modal,
        });
    }

    adventure() {

    }

    setSail() {
        this.manageSetSailModal();
    }

    teleport() {
        this.manageTeleportModal()
    }

    traverse() {

    }

    viewLocation() {
        this.setState({
            show_location_details: true,
        });
    }

    closeViewLocation() {
        this.setState({
            show_location_details: false,
        });
    }

    renderAdventureButton() {
        if (this.props.location_with_adventures !== null) {
            if (this.props.location_with_adventures.adventures !== null) {
                if (this.props.location_with_adventures.adventures.length > 0) {
                    return <SuccessOutlineButton additional_css={clsx('text-center px-0', {
                        'col-start-2 col-end-2': this.props.port_location === null
                    })} button_label={'Adventure'} on_click={this.adventure.bind(this)} disabled={this.state.is_movement_disabled} />
                }
            }
        }
    }

    adventureButtonIsHidden() {
        if (this.props.location_with_adventures === null) {
            return true;
        }

        if (this.props.location_with_adventures.adventures === null) {
            return true;
        }

        if (this.props.location_with_adventures.adventures.length === 0) {
            return true;
        }

        return false;
    }

    renderViewDetailsButton() {
        if (this.state.location !== null || this.state.player_kingdom_id !== 0 || this.state.enemy_kingdom_id !== 0) {
            return <OrangeButton additional_css={'block lg:hidden'} button_label={'View Location Details'} on_click={() => this.viewLocation()} />;
        }
    }

    render() {
        return (
            <Fragment>
                <div className='grid xl:grid-cols-2'>
                    <span>X/Y: {this.props.character_position.x}/{this.props.character_position.y}</span>
                    <div className='xl:mr-[24px]'>
                        <div className={'grid grid-cols-3 gap-1'}>
                            {
                                this.renderAdventureButton()
                            }

                            {
                                this.props.port_location !== null ?
                                    <SuccessOutlineButton additional_css={clsx('text-center', {
                                        'col-start-2 col-end-2': this.adventureButtonIsHidden(),
                                    })} button_label={'Set Sail'} on_click={this.setSail.bind(this)} disabled={this.state.is_movement_disabled}/>
                                    : null
                            }

                            <SuccessOutlineButton additional_css={clsx('text-center', {'col-start-3 col-end-3': this.props.location_with_adventures === null && this.props.port_location === null }, {'col-start-3 col-end-3': this.props.location_with_adventures !== null || this.props.port_location !== null })}
                                                  button_label={'Teleport'}
                                                  on_click={this.teleport.bind(this)}
                                                  disabled={this.state.is_movement_disabled}
                            />
                        </div>
                    </div>
                </div>
                <div className='text-left mt-4 mb-3'>
                    Characters On Map: {this.props.players_on_map}
                </div>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                <div className='grid gap-2 lg:grid-cols-5 lg:gap-4'>
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'North'} on_click={() => this.move('north')} />
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'South'} on_click={() => this.move('south')} />
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'West'} on_click={() => this.move('west')} />
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'East'} on_click={() => this.move('east')} />
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'Traverse'} on_click={() => this.traverse()} />
                    {this.renderViewDetailsButton()}
                </div>

                {
                    this.state.open_teleport_modal ?
                        <TeleportModal is_open={this.state.open_teleport_modal}
                                       teleport_player={this.props.teleport_player}
                                       handle_close={this.manageTeleportModal.bind(this)}
                                       handle_action={this.props.teleport_player}
                                       title={'Teleport'} coordinates={this.props.coordinates}
                                       character_position={this.props.character_position}
                                       currencies={this.props.currencies}
                                       view_port={this.props.view_port}
                                       locations={this.props.locations}
                                       player_kingdoms={this.props.player_kingdoms}
                                       enemy_kingdoms={this.props.enemy_kingdoms}
                        />
                    : null
                }

                {
                    this.state.open_set_sail_modal ?
                        <SetSailModal  is_open={this.state.open_set_sail_modal}
                                       set_sail={this.props.set_sail}
                                       handle_close={this.manageSetSailModal.bind(this)}
                                       handle_action={this.props.set_sail}
                                       title={'Set Sail'}
                                       character_position={this.props.character_position}
                                       currencies={this.props.currencies}
                                       ports={this.props.ports}
                        />
                        : null
                }

                {
                     this.state.show_location_details ?
                         <ViewLocationDetailsModal location={this.state.location}
                                                   close_modal={this.closeViewLocation.bind(this)}
                                                   character_id={this.props.character_id}
                                                   kingdom_id={this.state.player_kingdom_id}
                                                   enemy_kingdom_id={this.state.enemy_kingdom_id}
                         />
                     : null
                }
            </Fragment>
        )
    }
}