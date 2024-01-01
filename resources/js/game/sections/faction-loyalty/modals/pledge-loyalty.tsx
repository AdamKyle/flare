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
                       Pledging to a Faction allows a player to complete tasks to gain fame with the NPs
                       of that plane. As Players complete tasks they will be rewarded with Currencies, <a href='/information/random-enchants' target='_blank'>Medium Unique Items <i
                        className="fas fa-external-link-alt"></i></a>
                       and XP.
                    </p>
                    <p className='mb-4'>
                        A bonus to completing Fame tasks for NPC's is that your kingdoms on that plane will gain a % of defence based on how
                        many NPC's you have helped and their Fame Level.
                    </p>
                    <p className='mb-4'>
                        Upon pledging, players will see a new tab on the Game tab named "Faction Loyalty".
                        This tab will show you all the requirements for each NPC of that plane. There are two types of tasks: Bounties
                        and Crafting. Crafting can be done any where and just requires you to click the "Craft for NPC" while Bounties are kill X
                        creatures and must be done <strong>manually</strong>.
                    </p>
                    <p className='mb-4'>
                        Each NPC will have a button called Assist. Players can assist only one NPC at a time with their tasks and must be
                        assisting for their bounties and crafting to count. The total amount of tasks to do is the total amount of
                        Fame needed to level the NPC. These tasks will switch each level.
                    </p>
                    <p className='mb-4 italic'>
                        It is suggested you <a href='/information/automation' target='_blank'>read more about Faction Loyalties <i
                        className="fas fa-external-link-alt"></i></a> for your own curiosity child.
                    </p>
                </div>
            </Dialogue>
        );
    }
}
