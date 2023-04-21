import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ReincarnateCheckModelProps from "../../../../lib/game/character-sheet/types/modal/reincarnate-check-model-props";

export default class ReincarnationCheckModal extends React.Component<ReincarnateCheckModelProps, any> {


    constructor(props: ReincarnateCheckModelProps) {
        super(props);
    }

    render() {
        return (
            <Dialogue is_open={true}
                      handle_close={this.props.manage_modal}
                      title={'Are you sure?'}
                      secondary_actions={{
                          secondary_button_disabled: false,
                          secondary_button_label: 'Yes I am sure',
                          handle_action: this.props.handle_reincarnate,
                      }}
            >
                <div className='my-4'>
                    <p className='mb-4'>
                        <strong>This action cannot be undone!</strong>
                    </p>
                    <p className='mb-4'>
                        Are you sure you want to reincarnate? Here's what will happen:
                    </p>
                    <p className='mb-4'>
                        Your level will be reset to one, you will <strong>lose nothing else</strong>. 20% of your current raw stats
                        will be applied to your level 1 base raw stats. You will then level back up to max level and reincarnate again to get even stronger.
                    </p>
                    <p className='mb-4'>
                        You can reincarnate at anytime, for the cost of 50,000 Copper Coins. Each time you do we add 5%, which stacks with how many times you have reincarnated,
                        to your XP required to level up, which over time can make it take longer and longer to level up, but your character gets even stronger.
                    </p>
                </div>
            </Dialogue>
        );
    }
}
