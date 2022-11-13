import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import MercenaryInfoModalProps
    from "../../../../lib/game/character-sheet/types/mercenaries/modal/mercenary-info-modal-props";

export default class MercenaryInfoModal extends React.Component<MercenaryInfoModalProps, any> {

    constructor(props: MercenaryInfoModalProps) {
        super(props);
    }

    reincarnate() {

        if (this.props.mercenary === null) {
            return;
        }

        this.props.reincarnate(this.props.mercenary.id);
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
                <dl>
                    <dt>Level: </dt>
                    <dd>{this.props.mercenary.level} / {this.props.mercenary.max_level}</dd>
                    <dt>XP: </dt>
                    <dd>{this.props.mercenary.current_xp} / {this.props.mercenary.xp_required}</dd>
                    <dt>Bonus To Currency Reward: </dt>
                    <dd>{(this.props.mercenary.bonus * 100).toFixed(2)} %</dd>
                    <dt>Times Reincarnated: </dt>
                    <dd>{this.props.mercenary.times_reincarnated === null ? 0 : this.props.mercenary.times_reincarnated}</dd>
                </dl>
                <div className='my-4'>
                    {
                        this.props.reincarnating ?
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
            </Dialogue>
        );
    }
}
