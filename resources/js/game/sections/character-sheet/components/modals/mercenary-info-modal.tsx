import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import MercenaryInfoModalProps
    from "../../../../lib/game/character-sheet/types/mercenaries/modal/mercenary-info-modal-props";
import Select from "react-select";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import MercenaryXpBuffs from "../../../../lib/game/character-sheet/types/mercenaries/types/mercenary-xp-buffs";
import {formatNumber} from "../../../../lib/game/format-number";
import MercenaryInfoModalState
    from "../../../../lib/game/character-sheet/types/mercenaries/modal/mercenary-info-modal-state";

export default class MercenaryInfoModal extends React.Component<MercenaryInfoModalProps, MercenaryInfoModalState> {

    constructor(props: MercenaryInfoModalProps) {
        super(props);

        this.state = {
            selected_buff: null,
            selected_buff_xp: 0.0,
            selected_buff_cost: 0.0,
            loading: false,
            error_message: null,
            success_message: null,
        }
    }

    reincarnate() {

        if (this.props.mercenary === null) {
            return;
        }

        this.props.reincarnate(this.props.mercenary.id);
    }

    selectBuffToPurchase(data: any) {
        const value = data.value;

        const foundBuff = this.props.xp_buffs.filter((buff: MercenaryXpBuffs) => {
            return buff.value === value;
        });

        if (foundBuff.length > 0) {
            this.setState({
                selected_buff: value,
                selected_buff_xp: foundBuff[0].xp_amount,
                selected_buff_cost: foundBuff[0].cost,
            });
        }
    }

    buildOptions() {
        return this.props.xp_buffs.map((buff: MercenaryXpBuffs) => {
            return {
                label: buff.label + ' XP Increase Per Kill: ' + (buff.xp_amount * 100).toFixed(0) + '%',
                value: buff.value
            }
        })
    }

    selectedBuff() {
        if (this.state.selected_buff !== null) {
            const foundBuff = this.props.xp_buffs.filter((buff: MercenaryXpBuffs) => {
                return buff.value === this.state.selected_buff;
            });

            return {
                label: this.state.selected_buff + ' XP Increase Per Kill: ' + (foundBuff[0].xp_amount * 100).toFixed(0) + '%',
                value: this.state.selected_buff,
            }
        }

        return {
            label: 'Please select',
            value: '',
        }
    }

    purchaseBuff() {
        if (this.props.mercenary === null) {
            return;
        }

        if (this.state.selected_buff == null) {
            return;
        }

        this.props.purchase_buff(this.props.mercenary.id, this.state.selected_buff);
    }

    render() {

        if (this.props.mercenary === null) {
            return null;
        }

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      title={this.props.mercenary.name}
                      secondary_actions={{
                          secondary_button_disabled: !this.props.mercenary.can_reincarnate,
                          secondary_button_label: 'Re-incarnate',
                          handle_action: this.reincarnate.bind(this),
                      }}
            >
                <div className='max-h-[350px] overflow-y-auto lg:max-h-full lg:overflow-y-hidden'>
                    <p className='my-4'>
                        Mercenaries such as this one give bonuses to currency acquisition. Any action that would give the
                        currency the mercenary is named after will increase as you raise this mercenaries level.
                    </p>
                    <p className='mb-4'>
                        Once a mercenary reaches level 100, you can pay 500 shards to reincarnate them. This allows you to get the bonus
                        this mercenary applies to currency drops and actions that give currencies - of 1100%. Each time you reincarnate you will ned 5%
                        more - which stacks each time you do it - XP to level the mercenary. You can reincarnate 10 times.
                    </p>
                    <p className='mb-4'>
                        Mercenaries are leveled by you killing monsters. Its <strong>recommended</strong> you use exploration to do this.
                        Each Mercenary you own will gain 25XP per kill you make.
                    </p>
                    <p className='mb-4'>
                        <a href='/information/mercenary' target='_blank' className='ml-2 relative top-[5px]'>Learn more about mercenaries <i
                            className="fas fa-external-link-alt"></i></a>
                    </p>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div className='grid lg:grid-cols-2 gap-2 my-4'>
                        <div>
                            <h3 className='my-2'>Base Details</h3>
                            <dl>
                                <dt>Level: </dt>
                                <dd>{this.props.mercenary.level} / {this.props.mercenary.max_level}</dd>
                                <dt>XP: </dt>
                                <dd>{this.props.mercenary.current_xp} / {this.props.mercenary.xp_required}</dd>
                                <dt>XP Buff: </dt>
                                <dd>{this.props.mercenary.xp_buff !== null ? (this.props.mercenary.xp_buff * 100).toFixed(0) : 0} %</dd>
                                <dt>Bonus To Currency Reward: </dt>
                                <dd>{(this.props.mercenary.bonus * 100).toFixed(2)} %</dd>
                                <dt>Times Reincarnated: </dt>
                                <dd>{this.props.mercenary.times_reincarnated === null ? 0 : this.props.mercenary.times_reincarnated}</dd>
                            </dl>
                        </div>
                        <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <div>
                            <h3 className='my-2'>Purchase XP Buffs</h3>
                            <p className='mb-4'>
                                Purchase a XP buff for the mercenary. All buffs are permanent.
                            </p>
                            <Select
                                onChange={this.selectBuffToPurchase.bind(this)}
                                options={this.buildOptions()}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.selectedBuff()}
                            />
                            {
                                this.state.selected_buff !== null ?
                                    <dl className='my-4'>
                                        <dt>Selected Buff:</dt>
                                        <dd>{this.state.selected_buff}</dd>
                                        <dt>Selected Buff XP Value:</dt>
                                        <dd>{(this.state.selected_buff_xp * 100).toFixed(0)}%</dd>
                                        <dt>Selected Buff Cost:</dt>
                                        <dd>{formatNumber(this.state.selected_buff_cost)} Gold</dd>
                                    </dl>
                                : null
                            }
                            <PrimaryButton button_label={'Purchase buff'} on_click={this.purchaseBuff.bind(this)} additional_css={'mt-4'} />
                        </div>
                    </div>

                    <div className='my-4'>
                        {
                            this.props.reincarnating || this.props.buying_buff ?
                                <LoadingProgressBar />
                                : null
                        }

                        {
                            this.props.error_message !== null ?
                                <DangerAlert>
                                    {this.props.error_message}
                                </DangerAlert>
                                : null
                        }
                    </div>
                </div>
            </Dialogue>
        );
    }
}
