import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import TeleportModalProps from "../../../../lib/game/types/map/modals/teleport-modal-props";
import Select from "react-select";
import clsx from "clsx";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";


export default class TeleportModal extends React.Component<TeleportModalProps, any> {

    constructor(props: TeleportModalProps) {
        super(props);

        this.state = {
            x_position: 0,
            y_position: 0,
            character_position: {
                x: this.props.character_position.x, y: this.props.character_position.y
            },
            cost: 0,
            can_afford: true,
            distance: 0,
            time_out: 0,
        }
    }

    setXPosition(data: any) {
        this.setState({
            x_position: data.value,
        }, () => {
            this.fetchCost();
        });
    }

    setYPosition(data: any) {
        this.setState({
            y_position: data.value,
            cost: this.calculateDistance() * 1000,
        }, () => {
            this.fetchCost();
        });
    }

    fetchCost() {
        const distance = this.calculateDistance();
        const time     = Math.round(distance / 60);
        const cost     = time * 1000;
        let canAfford  = true;

        if (this.props.currencies == null) {
            canAfford = false;
        } else {
            if (cost > this.props.currencies.gold) {
                canAfford = false;
            }
        }

        this.setState({
            can_afford: canAfford,
            distance: distance,
            cost: cost,
            time_out: time,
        });
    }

    calculateDistance(): number {
        if (this.state.x_position === 0 && this.state.y_position === 0) {
            return 0;
        }

        const distanceX = Math.pow((this.state.x_position - this.state.character_position.x), 2);
        const distanceY = Math.pow((this.state.y_position - this.state.character_position.y), 2);

        let distance = distanceX + distanceY;
        distance = Math.sqrt(distance);

        if (isNaN(distance)) {
            return 0;
        }

        return Math.round(distance);
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

    convertToSelectable(data: number[]): {value: number, label: string}[] {
        return data.map((d) => {
            return {value: d, label: d.toString()}
        });
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open} handle_close={this.props.handle_close} title={this.props.title}>
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
                            />
                        </div>
                    </div>

                    <div className='flex items-center'>
                        <label className='w-[20px]'>Y</label>
                        <div className='w-2/3'>
                            <Select
                                onChange={this.setXPosition.bind(this)}
                                options={this.buildCoordinates('y')}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{ menuPortal: (base) => ({ ...base, zIndex: 9999, color: '#000000' }) }}
                                menuPortalTarget={document.body}
                            />
                        </div>
                    </div>
                </div>
                <div className='mt-3'>
                    <strong>Current Position (X/Y)</strong>: {this.props.character_position.x}/{this.props.character_position.y}
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Cost in Gold:</dt>
                    <dd className={clsx(
                        {'text-green-600' : this.state.can_afford},
                        {'text-red-600': !this.state.ca_afford}
                    )}>{this.state.cost}</dd>
                    <dt>Distance:</dt>
                    <dd>{this.state.distance}</dd>
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
                                    in order to level the skill over time, bly clicking train on Quick Feet and then selecting the amount of XP to sacrifice between 10-100%.
                                </p>
                            </PopOverContainer>
                        </div>
                    </dd>
                </dl>
            </Dialogue>
        )
    }
}
