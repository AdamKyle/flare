import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";


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
                    {
                        this.props.pledging ?
                            <div className='my-4'>
                                <LoadingProgressBar />
                            </div>
                        : null
                    }
                    <div className='max-h-[450px] overflow-y-scroll md:max-h-auto md:overflow-y-visible'>
                        <p className='mb-4'>
                           Pledging to a Faction allows a player to complete tasks to gain fame with the Npcs
                           of that plane. As you complete tasks you will be rewarded with Currencies (Gold, Gold Dust and Shards), a <a href='/information/random-enchants' target='_blank'>Medium Unique Items <i
                            className="fas fa-external-link-alt"></i></a> and XP all (With exception of the item) equal to the current NPC Fame Level.
                        </p>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <p className='mb-4'>
                            Upon pledging, players will see a new tab on the Game tab named "Faction Loyalty". This tab will appear
                            in your action section regardless of mobile or desktop.
                            This tab will show you all the requirements for each NPC of that plane. There are two types of tasks: Bounties
                            and Crafting. Crafting can be done any where and just requires you to click the "Craft for NPC" while crafting the item the NPC wants.
                            Bounties are kill X creatures and must be done <strong>manually</strong>.
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
                </div>
            </Dialogue>
        );
    }
}
