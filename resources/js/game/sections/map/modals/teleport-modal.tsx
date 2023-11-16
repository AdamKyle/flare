import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import TeleportModalProps from "../types/map/modals/teleport-modal-props";
import Select from "react-select";
import clsx from "clsx";
import {formatNumber} from "../../../lib/game/format-number";
import {viewPortWatcher} from "../../../lib/view-port-watcher";
import TeleportHelpModal from "./teleport-help-modal";
import ManageTeleportModalState from "../lib/state/manage-teleport-modal-state";
import TeleportModalState from "../types/map/modals/teleport-modal-state";
import TeleportComponent from "../types/teleport-component";

export default class TeleportModal extends React.Component<TeleportModalProps, TeleportModalState> {

    private teleportComponent: TeleportComponent;

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
            current_enemy_kingdom: null,
            current_npc_kingdom: null,
            view_port: null,
            show_help: false,
        }

        this.teleportComponent = new TeleportComponent(this);
    }

    componentDidMount() {
        viewPortWatcher(this);

        (new ManageTeleportModalState(this)).updateTeleportModalState();
    }

    componentDidUpdate() {
        if (this.props.locations === null) {
            return;
        }

        (new ManageTeleportModalState(this)).updateTeleportModalState();
    }

    manageHelpDialogue() {
        this.setState({
            show_help: !this.state.show_help
        })
    }

    setXPosition(data: any) {
        this.teleportComponent.setSelectedXPosition(data);
    }

    setYPosition(data: any) {
        this.teleportComponent.setSelectedYPosition(data);
    }

    setLocationData(data: any) {
        this.teleportComponent.setSelectedLocationData(data);
    }

    setMyKingdomData(data: any) {
        this.teleportComponent.setSelectedMyKingdomData(data);
    }

    setEnemyKingdomData(data: any) {
        this.teleportComponent.setSelectedEnemyKingdomData(data);
    }

    setNPCKingdomData(data: any) {
        this.teleportComponent.setSelectedNPCKingdomData(data);
    }

    convertToSelectable(data: number[]): any {
        return this.teleportComponent.buildCoordinatesOptions(data);
    }

    showMyKingdomSelect() {
        if (this.props.player_kingdoms === null) {
            return false
        }

        return this.props.player_kingdoms.length > 0;
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

        if (this.props.coordinates === null) {
            return null;
        }

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
                                options={this.convertToSelectable(this.props.coordinates.x)}
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
                                options={this.convertToSelectable(this.props.coordinates.y)}
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
                    <div className={clsx('flex items-center', {
                        'col-start-1 col-span-2': !this.showMyKingdomSelect()
                    })}>
                        <label className='w-[100px]'>Locations:</label>
                        <div className='w-2/3'>
                            <Select
                                onChange={this.setLocationData.bind(this)}
                                options={this.teleportComponent.buildLocationOptions()}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.teleportComponent.getDefaultLocationValue()}
                            />
                        </div>
                    </div>
                    {
                        this.showMyKingdomSelect() ?
                            <div className='flex items-center'>
                                <label className='w-[100px]'>My Kingdoms:</label>
                                <div className='w-2/3'>
                                    <Select
                                        onChange={this.setMyKingdomData.bind(this)}
                                        options={this.teleportComponent.buildMyKingdomsOptions()}
                                        menuPosition={'absolute'}
                                        menuPlacement={'bottom'}
                                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                        menuPortalTarget={document.body}
                                        value={this.teleportComponent.getDefaultPlayerKingdomValue()}
                                    />
                                </div>
                            </div>
                        : null
                    }
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className='flex items-center'>
                    <label className='w-[100px]'>Enemy Kingdoms</label>
                    <div className='w-2/3'>
                        <Select
                            onChange={this.setEnemyKingdomData.bind(this)}
                            options={this.teleportComponent.buildEnemyKingdomOptions()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999, color: '#000000' }) }}
                            menuPortalTarget={document.body}
                            value={this.teleportComponent.getDefaultEnemyKingdomValue()}
                        />
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div className='flex items-center'>
                    <label className='w-[100px]'>NPC Kingdoms</label>
                    <div className='w-2/3'>
                        <Select
                            onChange={this.setNPCKingdomData.bind(this)}
                            options={this.teleportComponent.buildNpcKingdomOptions()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999, color: '#000000' }) }}
                            menuPortalTarget={document.body}
                            value={this.teleportComponent.getDefaultNPCKingdomValue()}
                        />
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Cost in Gold:</dt>
                    <dd className={clsx(
                        {'text-gray-700': this.state.cost === 0},
                        {'text-green-600' : this.state.can_afford && this.state.cost > 0},
                        {'text-red-600': !this.state.can_afford && this.state.cost > 0}
                    )}>{formatNumber(this.state.cost)}</dd>
                    <dt>Can Afford:</dt>
                    <dd>{this.state.can_afford ? 'Yes' : 'No'}</dd>
                    <dt>Distance:</dt>
                    <dd>{this.state.distance} Miles</dd>
                    <dt>Timeout for:</dt>
                    <dd className='flex items-center'>
                        <span>{this.state.time_out} Minutes</span>
                        <div>
                            <div className='ml-2'>
                                <button type={"button"} onClick={() => this.manageHelpDialogue()} className='text-blue-500 dark:text-blue-300'>
                                    <i className={'fas fa-info-circle'}></i> Help
                                </button>
                            </div>
                        </div>
                    </dd>
                </dl>

                {
                    this.state.show_help ?
                        <TeleportHelpModal manage_modal={this.manageHelpDialogue.bind(this)} />
                    : null
                }
            </Dialogue>
        )
    }
}
