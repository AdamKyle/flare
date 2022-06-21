import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import Select from "react-select";
import clsx from "clsx";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import {fetchCost} from "../../../../lib/game/map/teleportion-costs";
import {formatNumber} from "../../../../lib/game/format-number";
import SetSailModalProps from "../../../../lib/game/types/map/modals/set-sail-modal-props";
import SetSailModalState from "../../../../lib/game/types/map/modals/set-sail-modal-state";
import {viewPortWatcher} from "../../../../lib/view-port-watcher";
import TeleportHelpModal from "./teleport-help-modal";


export default class TeleportModal extends React.Component<SetSailModalProps, SetSailModalState> {

    constructor(props: SetSailModalProps) {
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
            current_port: null,
            current_location: null,
            current_player_kingdom: null,
            current_enemy_kingdom: null,
            view_port: null,
            show_help: false,
        }
    }

    componentDidMount() {
        viewPortWatcher(this);

        if (this.props.ports !== null) {
            const foundLocation = this.props.ports.filter((port) => port.x === this.props.character_position.x && port.y === this.props.character_position.y);

            if (foundLocation.length > 0) {
                this.setState({
                    current_port: foundLocation[0],
                });
            }
        }
    }

    componentDidUpdate() {
        if (this.props.ports === null) {
            return;
        }

        if (this.state.current_port === null) {
            const foundLocation = this.props.ports.filter((port) => port.x === this.props.character_position.x && port.y === this.props.character_position.y);

            if (foundLocation.length > 0) {
                this.setState({
                    current_port: foundLocation[0],
                });
            }
        }

        if (this.state.view_port !== null) {
            if (this.state.view_port < 1600) {
                this.props.handle_close();
            }
        }
    }

    getDefaultPortValue() {
        if (this.state.current_port !== null) {
            return {label: this.state.current_port.name + ' (X/Y): ' + this.state.current_port.x + '/' + this.state.current_port.y, value: this.state.current_port.id}
        }

        return  {value: 0, label: ''};
    }

    setPortData(data: any) {
        if (this.props.ports !== null) {
            const foundLocation = this.props.ports.filter((ports) => ports.id === data.value);

            if (foundLocation.length > 0) {
                this.setState({
                    x_position: foundLocation[0].x,
                    y_position: foundLocation[0].y,
                    current_location: foundLocation[0],
                    current_player_kingdom: null,
                    current_enemy_kingdom: null
                }, () => {
                    this.setState(fetchCost(this.state.x_position, this.state.y_position, this.state.character_position, this.props.currencies));
                });
            }
        }
    }

    buildSetSailOptions(): {value: number, label: string}[]|[] {
        if (this.props.ports !== null) {
            return this.props.ports.map((port) => {
                return {label: port.name + ' (X/Y): ' + port.x + '/' + port.y, value: port.id}
            });
        }

        return [];
    }

    setSail() {
        this.props.set_sail({
            x: this.state.x_position,
            y: this.state.y_position,
            cost: this.state.cost,
            timeout: this.state.time_out
        });

         this.props.handle_close();
    }

    manageHelpDialogue() {
        this.setState({
            show_help: !this.state.show_help
        })
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.props.title}
                      secondary_actions={{
                          handle_action: this.setSail.bind(this),
                          secondary_button_disabled: !this.state.can_afford,
                          secondary_button_label: 'Set Sail',
                      }}
            >
            <div className='flex items-center'>
                <label className='w-[50px]'>Ports</label>
                <div className='w-2/3'>
                    <Select
                        onChange={this.setPortData.bind(this)}
                        options={this.buildSetSailOptions()}
                        menuPosition={'absolute'}
                        menuPlacement={'bottom'}
                        styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999, color: '#000000' }) }}
                        menuPortalTarget={document.body}
                        value={this.getDefaultPortValue()}
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
