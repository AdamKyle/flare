import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";

export default class QuestDetailsModal extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.state = {
            quest_details: null,
            loading: true,
        }

        this.tabs = [{
            name: 'Npc Details',
            key: 'npc-details',
        }, {
            name: 'Required To Complete',
            key: 'required-to-complete',
        }, {
            name: 'Quest Reward',
            key: 'quest-reward',
        }]
    }

    componentDidMount() {

        if (this.props.quest_id === null) {
            return;
        }

        (new Ajax()).setRoute('quest/' + this.props.quest_id + '/' + this.props.character_id).doAjaxCall('get',(result: AxiosResponse) => {
            this.setState({
                quest_details: result.data,
                loading: false,
            })
        }, (error: AxiosError) => {

        });
    }

    buildTitle() {
        if (this.state.quest_details === null) {
            return 'Fetching details ...';
        }

        return this.state.quest_details.name;
    }

    getNPCCommands(npc: any) {
        return npc.commands.map((command: any) => command.command).join(', ');
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open} handle_close={this.props.handle_close} secondary_actions={null} title={this.buildTitle()}>
                {
                    this.state.loading ?
                        <div className={'h-24 mt-10 relative'}>
                            <ComponentLoading />
                        </div>
                        :
                        <Fragment>
                            <Tabs tabs={this.tabs} full_width={true}>
                                <TabPanel key={'npc-details'}>
                                    <dl>
                                        <dt>Name</dt>
                                        <dd>{this.state.quest_details.npc.name}</dd>
                                        <dt className="text-green-700"><i className="fas fa-comment"></i> How to message</dt>
                                        <dd className='flex items-center text-green-700'>
                                            <span><code>{this.state.quest_details.npc.text_command_to_message} {this.getNPCCommands(this.state.quest_details.npc)}</code></span>
                                            <div>
                                                <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'}>
                                                    <h3 className='text-gray-700 dark:text-gray-200'>How to message</h3>
                                                    <p className='my-2 text-gray-700 dark:text-gray-200'>
                                                        All you have to do is copy and paste the command you see and paste it in the chat box, make sure there are no spaces before the
                                                        command. Quest NPC's require you to be at there location, failure to be will tell you when you message them.
                                                    </p>
                                                </PopOverContainer>
                                            </div>
                                        </dd>
                                        <dt>Coordinates (X/Y)</dt>
                                        <dd>{this.state.quest_details.npc.x_position} / {this.state.quest_details.npc.y_position}</dd>
                                        <dt>On Plane</dt>
                                        <dd>{this.state.quest_details.npc.game_map.name}</dd>
                                        <dt>Must be at same location?</dt>
                                        <dd>{this.state.quest_details.npc.must_be_at_same_location ? 'Yes' : 'No'}</dd>
                                    </dl>
                                </TabPanel>
                                <TabPanel key={'required-to-complete'}>
                                    Required
                                </TabPanel>
                                <TabPanel key={'quest-reward'}>
                                    Reward
                                </TabPanel>
                            </Tabs>
                        </Fragment>
                }
            </Dialogue>
        )
    }
}
