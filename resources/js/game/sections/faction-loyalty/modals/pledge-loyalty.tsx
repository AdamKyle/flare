import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";


export default class PledgeLoyalty extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Dialogue is_open={true}
                      handle_close={this.props.manage_modal}
                      title={'Pledge Loyalty To: ' + this.props.faction.map_name}
                      secondary_actions={{
                          secondary_button_disabled: false,
                          secondary_button_label: 'I pledge my allegiance',
                          handle_action: this.props.handle_pledge,
                      }}
            >
                <div className='my-4'>
                    <p className='mb-4'>
                        <strong>Would you like to pledge your loyalty to Surface?</strong>
                    </p>
                    <p className='mb-4'>
                        You can switch this at any time. Doing so allows you to gain rewards for each NPC
                        you assist with their tasks, which in turn increases the loyalty you have with that NPC.
                    </p>
                    <p className='mb-4'>
                        As you level your loyalty with an NPC you will not only gain currency, xp and a Medium Unique item, you
                        will also gain Bonus defence towards defence of items being dropped on your kingdoms.
                    </p>
                    <p className='mb-4'>
                        Players can switch their loyalty at any time. The bonus kingdom defence only applies when you
                        have pledge dto that loyalty and onlt you kingdoms of that plane.
                    </p>
                </div>
            </Dialogue>
        );
    }
}
