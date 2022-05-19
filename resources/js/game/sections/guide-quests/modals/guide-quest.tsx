import React, {Fragment} from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../lib/ajax/ajax";
import BasicCard from "../../../components/ui/cards/basic-card";
import CharacterTopSection from "../../character-top-section/character-top-section";
import SmallerActions from "../../game-actions-section/smaller-actions";
import Actions from "../../game-actions-section/actions";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";

export default class GuideQuest extends React.Component<any, any> {
    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'story',
            name: 'Story'
        }, {
            key: 'instructions',
            name: 'Instructions',
        }];

        this.state = {
            loading: true,
            error_message: null,
            quest_data: null,
            can_hand_in: false,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/guide-quest/' + this.props.user_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                quest_data: result.data.quest,
                can_hand_in: result.data.can_hand_in
            });
        }, (error: AxiosError) => {});
    }

    buildTitle() {
        if (this.state.loading) {
            return 'One moment ...';
        }

        return this.state.quest_data.name;
    }

    handInQuest() {
        console.log('hand in ...');
    }

    render() {
        return(
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.buildTitle()}
                      secondary_actions={{
                          secondary_button_label: 'Hand in',
                          secondary_button_disabled: !this.state.can_hand_in,
                          handle_action: this.handInQuest.bind(this)
                      }}
                      large_modal={false}
                      primary_button_disabled={this.state.action_loading}
            >
                {
                    this.state.loading && this.state.quest_data === null ?
                        <div className='p-5 mb-2'><ComponentLoading/></div>
                    :
                        <Fragment>
                            <div className={''}>
                                <h3 className='mb-2'>Required to complete</h3>
                                <ul className='list-disc ml-[18px]'>
                                    { this.state.quest_data.required_level !== null ? <li className={'text-orange-600 dark:text-orange-400'}>Level your character to: {this.state.quest_data.required_level}</li> : null }
                                    { this.state.quest_data.quest_name !== null ? <li className={'text-orange-600 dark:text-orange-400'}>Complete the quest: {this.state.quest_data.quest_name}</li> : null }
                                    { this.state.quest_data.skill_name !== null ? <li className={'text-orange-600 dark:text-orange-400'}>Get Skill: {this.state.quest_data.skill_name} to level: {this.state.quest_data.required_skill_level}</li> : null }
                                    { this.state.quest_data.faction_name !== null ? <li className={'text-orange-600 dark:text-orange-400'}>Get Faction: {this.state.quest_data.faction_name} to level: {this.state.quest_data.required_faction_level}</li> : null }
                                    { this.state.quest_data.game_map_name !== null ? <li className={'text-orange-600 dark:text-orange-400'}>Get Access to: {this.state.quest_data.game_map_name}</li> : null }
                                </ul>
                            </div>

                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

                            <Tabs tabs={this.tabs}>
                                <TabPanel key={'story'}>
                                    <div className={'border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-scroll mb-4'}>
                                        <div dangerouslySetInnerHTML={{__html: this.state.quest_data.intro_text }} />
                                    </div>
                                </TabPanel>
                                <TabPanel key={'instructions'}>
                                    <div className={'border-1 rounded-sm p-3 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-scroll mb-4'}>
                                        <div dangerouslySetInnerHTML={{__html: this.state.quest_data.instructions }} />
                                    </div>
                                </TabPanel>
                            </Tabs>
                            <p className={'mt-4 mb-4'}>The Hand in button will become available when you meet the requirements.</p>
                        </Fragment>
                }
            </Dialogue>
        )
    }
}
