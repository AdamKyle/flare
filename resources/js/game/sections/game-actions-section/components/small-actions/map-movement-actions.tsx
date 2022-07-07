import React, {Fragment} from "react";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import MapStateManager from "../../../../lib/game/map/state/map-state-manager";
import {getPortLocation} from "../../../../lib/game/map/location-helpers";
import PrimaryOutlineButton from "../../../../components/ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../../../components/ui/buttons/success-outline-button";
import OrangeButton from "../../../../components/ui/buttons/orange-button";
import PlayerKingdomsDetails from "../../../../lib/game/types/map/player-kingdoms-details";
import LocationDetails from "../../../../lib/game/map/types/location-details";
import KingdomDetails from "../../../../lib/game/map/types/kingdom-details";
import TeleportModal from "../../../components/actions/modals/teleport-modal";
import MovePlayer from "../../../../lib/game/map/ajax/move-player";
import ViewLocationDetailsModal from "../../../components/actions/modals/view-location-details-modal";
import TraverseModal from "../../../components/actions/modals/traverse-modal";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import PurpleButton from "../../../../components/ui/buttons/purple-button";
import Conjuration from "../../../components/actions/modals/conjuration";
import NpcKingdomsDetails from "../../../../lib/game/types/map/npc-kingdoms-details";
import SuccessButton from "../../../../components/ui/buttons/success-button";
import SettleKingdomModal from "../../../components/actions/modals/settle-kingdom-modal";

export default class MapMovementActions extends React.Component<any, any> {

    private traverseUpdate: any;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            selected_map_option: null,
            map_id: 0,
            character_position: {
                x: 0, y: 0
            },
            locations: null,
            port_location: null,
            player_kingdoms: null,
            enemy_kingdoms: null,
            npc_kingdoms: null,
            coordinates: null,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            npc_kingdom_id: null,
            open_set_sail_modal: false,
            show_traverse: false,
            open_teleport_modal: false,
            location: null,
            show_location_details: false,
            show_conjuration: false,
            time_left: null,
        }

        // @ts-ignore
        this.traverseUpdate = Echo.private('update-map-plane-' + this.props.character.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('map/' + this.props.character.id)
            .doAjaxCall('get', (result: AxiosResponse) => {
                this.setStateFromData(result.data, this.setLocationData.bind(this));
            }, (err: AxiosError) => {

            });

        this.traverseUpdate.listen('Game.Maps.Events.UpdateMapBroadcast', (event: any) => {
            this.setStateFromData(event.mapDetails, this.setLocationData.bind(this));
        });
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<any>, snapshot?: any) {
        this.setLocationData()

        if (this.state.time_left !== null && this.state.time_left !== 0) {
            const timeLeft = this.state.time_left;

            this.setState({
                time_left: null
            }, () => {
                this.props.update_map_timer(timeLeft);
            });
        }
    }

    canSeeViewLocationDetailsButton() {
        return this.state.location !== null || this.state.player_kingdom_id !== null || this.state.enemy_kingdom_id !== null || this.state.npc_kingdom_id !== null;
    }

    canSettleHere() {
        const locations = this.state.locations.filter((location: LocationDetails) => {
            return location.x === this.state.character_position.x && location.y === this.state.character_position.y;
        });

        const playerKingdom = this.state.player_kingdoms.filter((playerKingdom: PlayerKingdomsDetails) => {
            return playerKingdom.x_position === this.state.character_position.x && playerKingdom.y_position === this.state.character_position.y;
        });

        const enemyKingdoms = this.state.enemy_kingdoms.filter((enemyKingdom: PlayerKingdomsDetails) => {
            return enemyKingdom.x_position === this.state.character_position.x && enemyKingdom.y_position === this.state.character_position.y;
        });

        const npcKingdoms = this.state.npc_kingdoms.filter((npcKingdom: NpcKingdomsDetails) => {
            return npcKingdom.x_position === this.state.character_position.x && npcKingdom.y_position === this.state.character_position.y;
        });

        return (locations.length === 0 && playerKingdom.length === 0 && enemyKingdoms.length === 0 && npcKingdoms.length === 0);
    }

    setLocationData() {
        if (this.state.locations !== null && (this.state.location === null && this.state.player_kingdom_id === null && this.state.enemy_kingdom_id === null && this.state.npc_kingdom_id === null)) {
            this.updateViewLocationData()
        } else if (this.state.locations === null && this.state.location !== null) {
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

        if (this.state.player_kingdoms === null) {
            return this.setState({player_kingdom_id: null});
        }

        const kingdom = this.state.player_kingdoms.filter((kingdom: PlayerKingdomsDetails) => kingdom.x_position === this.state.character_position.x && kingdom.y_position === this.state.character_position.y);

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

        const foundLocation      = this.state.locations.filter((location: LocationDetails) => location.x === this.state.character_position.x && location.y === this.state.character_position.y);

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

        if (this.state.enemy_kingdoms === null) {
            return this.setState({ enemy_kingdom_id: 0 });
        }

        const foundEnemyKingdom      = this.state.enemy_kingdoms.filter((kingdom: KingdomDetails) => kingdom.x_position === this.state.character_position.x && kingdom.y_position === this.state.character_position.y);

        if (foundEnemyKingdom.length > 0) {
            if (foundEnemyKingdom[0].id !== this.state.enemy_kingdom_id) {
                return this.setState({ enemy_kingdom_id: null });
            }
        } else {
            return this.setState({ enemy_kingdom_id: null });
        }
    }

    updateViewLocationData() {
        if (this.state.locations == null || this.state.player_kingdoms === null || this.state.enemy_kingdoms === null || this.state.npc_kingdoms === null) {
            return;
        }

        const foundLocation      = this.state.locations.filter((location: LocationDetails) => location.x === this.state.character_position.x && location.y === this.state.character_position.y);
        const foundPlayerKingdom = this.state.player_kingdoms.filter((kingdom: KingdomDetails) => kingdom.x_position === this.state.character_position.x && kingdom.y_position === this.state.character_position.y);
        const foundEnemyKingdom  = this.state.enemy_kingdoms.filter((kingdom: KingdomDetails) => kingdom.x_position === this.state.character_position.x && kingdom.y_position === this.state.character_position.y);
        const foundNpcKingdom    = this.state.npc_kingdoms.filter((kingdom: KingdomDetails) => kingdom.x_position === this.state.character_position.x && kingdom.y_position === this.state.character_position.y);

        let state = {
            location: null,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            npc_kingdom_id: null
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

        if (foundNpcKingdom.length > 0) {
            // @ts-ignore
            state.npc_kingdom_id = foundNpcKingdom[0].id;
        }

        if (state.location === null && state.player_kingdom_id === null && state.enemy_kingdom_id === null && state.npc_kingdom_id === null) {
            return;
        }

        this.setState(state);
    }

    setStateFromData(data: any, callback: () => void) {
        let state = {...MapStateManager.setMapMovementActionsState(data), ...{loading: false, map_id: data.character_map.game_map.id}};

        state.port_location = getPortLocation(state);

        if (state.time_left !== 0) {
            state.can_player_move = false;
        }

        // @ts-ignore
        this.setState(state, () => {
            callback();
        });
    }

    manageTeleport() {
        this.setState({
            open_teleport_modal: !this.state.open_teleport_modal
        })
    }

    manageTraverse() {
        this.setState({
            show_traverse: !this.state.show_traverse
        })
    }

    manageViewLocation() {
        this.setState({
            show_location_details: !this.state.show_location_details
        })
    }

    closeViewLocation() {
        this.setState({
            show_location_details: false
        })
    }

    conjure() {
        this.setState({
            show_conjuration: true,
        });
    }

    closeConjure() {
        this.setState({
            show_conjuration: false,
        });
    }

    teleportPlayer(data: {x: number, y: number, cost: number, timeout: number}) {
        (new MovePlayer(this)).teleportPlayer(data, this.props.character.id, this.props.view_port);
    }

    manageSettleModal() {
        this.setState({
            open_settle_kingdom_modal: !this.state.open_settle_kingdom_modal,
        });
    }

    render() {
        return  (
            <Fragment>
                {
                    this.state.loading ?
                        <div className='p-5 mb-2'>
                            <ComponentLoading/>
                        </div>
                        :
                        <Fragment>
                            {
                                this.props.character.is_dead ?
                                    <p className='mt-5 text-red-600 dark:text-red-400'>You are dead. Dead people cannot
                                        do things. Click revive to live again!</p>
                                    : null
                            }

                            {
                                this.props.character.is_automation_running ?
                                    <div className='my-2'>
                                        <WarningAlert>
                                            Automation is running, You cannot teleport. <a href='/information/automation' target='_blank'>See Automation Help <i
                                            className="fas fa-external-link-alt"></i></a> for more details.
                                        </WarningAlert>
                                    </div>
                                    : null
                            }

                            <div className='grid gap-3'>
                                {
                                    this.canSeeViewLocationDetailsButton() ?
                                        <OrangeButton button_label={'View Location'}
                                                      on_click={this.manageViewLocation.bind(this)} />
                                    : null
                                }

                                {
                                    this.canSettleHere() ?
                                        <SuccessButton additional_css={'text-center ml-2'} button_label={'Settle'} on_click={this.manageSettleModal.bind(this)} disabled={this.state.is_movement_disabled || this.props.is_dead || this.props.is_automation_running}/>
                                    : null
                                }

                                <PurpleButton button_label={'Conjure Celestial'}
                                              on_click={() => this.conjure()}
                                              disabled={this.state.is_movement_disabled || this.props.is_dead || this.props.is_automation_running}
                                />

                                <PrimaryOutlineButton button_label={'Teleport'}
                                                      on_click={this.manageTeleport.bind(this)}
                                                      disabled={!this.props.character.can_move || this.props.is_automation_running || this.props.character.is_dead} />
                                <SuccessOutlineButton button_label={'Traverse'}
                                                      on_click={this.manageTraverse.bind(this)}
                                                      disabled={!this.props.character.can_move || this.props.character.is_dead} />
                            </div>
                        </Fragment>
                }


                {
                    this.state.open_teleport_modal ?
                        <TeleportModal is_open={this.state.open_teleport_modal}
                                       teleport_player={this.teleportPlayer.bind(this)}
                                       handle_close={this.manageTeleport.bind(this)}
                                       handle_action={this.teleportPlayer.bind(this)}
                                       title={'Teleport'} coordinates={this.state.coordinates}
                                       character_position={this.state.character_position}
                                       currencies={this.props.currencies}
                                       locations={this.state.locations}
                                       player_kingdoms={this.state.player_kingdoms}
                                       enemy_kingdoms={this.state.enemy_kingdoms}
                                       npc_kingdoms={this.state.npc_kingdoms}
                        />
                    : null
                }

                {
                    this.state.show_location_details ?
                        <ViewLocationDetailsModal location={this.state.location}
                                                  close_modal={this.closeViewLocation.bind(this)}
                                                  character_id={this.props.character.id}
                                                  kingdom_id={this.state.player_kingdom_id}
                                                  enemy_kingdom_id={this.state.enemy_kingdom_id}
                                                  npc_kingdom_id={this.state.npc_kingdom_id}
                                                  is_small_screen={true}
                                                  can_move={this.props.character.can_move}
                                                  is_automation_running={this.props.is_automation_running}
                                                  is_dead={this.props.character.is_dead}
                        />
                    : null
                }

                {
                    this.state.show_traverse ?
                        <TraverseModal
                            is_open={this.state.show_traverse}
                            handle_close={this.manageTraverse.bind(this)}
                            character_id={this.props.character.id}
                            map_id={this.state.map_id}
                        />
                    : null
                }

                {
                    this.state.show_conjuration ?
                        <Conjuration is_open={this.state.show_conjuration}
                                     handle_close={this.closeConjure.bind(this)}
                                     title={'Conjuration'}
                                     currencie={this.props.currencies}
                                     character_id={this.props.character.id}
                        />
                    : null
                }

                {
                    this.state.open_settle_kingdom_modal ?
                        <SettleKingdomModal
                            is_open={this.state.open_settle_kingdom_modal}
                            handle_close={this.manageSettleModal.bind(this)}
                            character_id={this.props.character.id}
                            map_id={this.state.map_id}
                        />
                        : null
                }
            </Fragment>
        )
    }
}
