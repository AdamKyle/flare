import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import clsx from "clsx";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import CurrencyRequirement from "./components/currency-requirement";
import Reward from "./components/reward";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class QuestDetailsModal extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.state = {
            quest_details: null,
            loading: true,
            handing_in: false,
            success_message: null,
            error_message: null,
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

    handInQuest() {
        this.setState({
            handing_in: true,
        }, () => {
            (new Ajax()).setRoute('quest/'+this.props.quest_id+'/hand-in-quest/' + this.props.character_id).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    handing_in: false,
                    success_message: result.data.message,
                });

                const data = result.data;

                delete data.message;

                this.props.update_quests(data);

                this.props.handle_close();
            }, (error: AxiosError) => {

                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        handing_in: false,
                        error_message: response.data.message
                    });
                }
            })
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

    renderPlaneAccessRequirements(map: { map_required_item: any | null }) {
        if (map.map_required_item !== null) {
            return (
                <Fragment>
                    <dt>Required to access</dt>
                    <dd>{map.map_required_item.name}</dd>

                    {
                        map.map_required_item.required_quest !== null ?
                            <Fragment>
                                <dt>Which needs you to complete (Quest)</dt>
                                <dd>{map.map_required_item.required_quest.name}</dd>
                                <dt>By Speaking to</dt>
                                <dd>{map.map_required_item.required_quest.npc.real_name}</dd>
                                <dt>Who is at (X/Y)</dt>
                                <dd>{map.map_required_item.required_quest.npc.x_position}/{map.map_required_item.required_quest.npc.y_position}</dd>
                                <dt>On plane</dt>
                                <dd>{map.map_required_item.required_quest.npc.game_map.name}</dd>
                                {this.renderPlaneAccessRequirements(map.map_required_item.required_quest.npc.game_map)}
                            </Fragment>
                            : null
                    }

                    {
                        map.map_required_item.required_monster !== null ?
                            <Fragment>
                                <dt>Which requires you to fight (first)</dt>
                                <dd>{map.map_required_item.required_monster.name}</dd>
                                <dt>Who resides on plane</dt>
                                <dd>{map.map_required_item.required_monster.game_map.name}</dd>
                                {this.renderPlaneAccessRequirements(map.map_required_item.required_monster.game_map)}
                            </Fragment>
                            : null
                    }
                </Fragment>
            );
        }

        return null;
    }

    renderLocations(locations: any) {
        return locations.map((location: any) => {
            return  <Fragment>
                <dl>
                    <dt>By Going to</dt>
                    <dd>{location.name}</dd>
                    <dt>Which is at (X/Y)</dt>
                    <dd>{location.x}/{location.y}</dd>
                    <dt>On Plane</dt>
                    <dd>{location.map.name}</dd>
                    {this.renderPlaneAccessRequirements(location.map)}
                </dl>
            </Fragment>
        });
    }

    renderItem(item: any) {
        return (
            <Fragment>
                {
                    item.drop_location_id !== null ?
                        <InfoAlert>
                            <p>Some items, such as this one, only drop when you are at a special location. These locations
                                increase enemy strength making them more of a challenge.</p>
                            <p>These items have a 1/1,000,000 chance to drop. Your looting skill is capped at 45% here.</p>
                            <p>
                                <strong>These items will not drop if you are using Exploration. You must manually farm these quest items.</strong>
                            </p>
                        </InfoAlert>
                        : null
                }
                {
                    item.required_monster !== null ?
                        item.required_monster.is_celestial_entity ?
                            <InfoAlert>
                                <p>
                                    Some quests such as this one may have you fighting a Celestial entity. You can check the <a href="/information/npcs" target="_blank">help docs (NPC's)</a> to find out, based on which plane,
                                    which Summoning NPC you ned to speak to inorder to conjure the entity, there is only one per plane.
                                </p>
                                <p>
                                    Celestial Entities below Dungeons plane, will not be included in the weekly spawn.
                                </p>
                            </InfoAlert>
                            : null
                        : null
                }
                <dl>
                    {
                        item.required_monster !== null ?
                            <Fragment>
                                <dt>Obtained by killing</dt>
                                <dd>{item.required_monster.name} {item.required_monster.is_celestial_entity ? "(Celestial)" : "(Regular Monster)"}</dd>
                                <dt>Resides on plane</dt>
                                <dd>{item.required_monster.game_map.name}</dd>
                                {this.renderPlaneAccessRequirements(item.required_monster.game_map)}
                            </Fragment>
                            : null
                    }

                    {
                        item.required_quest !== null ?
                            <Fragment>
                                <dt>Obtained by completing</dt>
                                <dd>{item.required_quest.name}</dd>
                                <dt>Which belongs to (NPC)</dt>
                                <dd>{item.required_quest.npc.real_name}</dd>
                                <dt>Who is on the plane of</dt>
                                <dd>{item.required_quest.npc.game_map.name}</dd>
                                <dt>At coordinates (X/Y)</dt>
                                <dd>{item.required_quest.npc.x_position} / {item.required_quest.npc.y_position}</dd>
                                {this.renderPlaneAccessRequirements(item.required_quest.npc.game_map)}
                            </Fragment>
                            : null
                    }

                    {
                        item.drop_location_id !== null ?
                            <Fragment>
                                <dt>By Visiting (Fighting monsters for it to drop)</dt>
                                <dd>{item.drop_location.name}</dd>
                                <dt>At coordinates (X/Y)</dt>
                                <dd>{item.drop_location.x} / {item.drop_location.y}</dd>
                                <dt>Which is on the plane</dt>
                                <dd>{item.drop_location.map.name}</dd>
                                {this.renderPlaneAccessRequirements(item.drop_location.map)}
                            </Fragment>
                            : null
                    }
                </dl>
                {
                    item.locations.length > 0 ?
                        <Fragment>
                            <hr />
                            <h3 className="tw-font-light">Locations</h3>
                            <p>Locations that will give you the item, just for visiting.</p>
                            <hr />
                            {this.renderLocations(item.locations)}
                        </Fragment>
                        : null
                }
            </Fragment>
        )
    }

    fetchNpcPlaneAccess() {
        let npcPlaneAccess = null;

        if (!this.state.loading) {
            npcPlaneAccess = this.renderPlaneAccessRequirements(this.state.quest_details.npc.game_map);
        }

        return npcPlaneAccess
    }

    render() {
        const npcPLaneAccess = this.fetchNpcPlaneAccess();

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.handle_close}
                      secondary_actions={{
                          secondary_button_disabled: !this.props.is_parent_complete || this.props.is_quest_complete,
                          secondary_button_label: 'Hand in',
                          handle_action: this.handInQuest.bind(this),
                      }}
                      title={this.buildTitle()}
                      large_modal={true}
            >
                {
                    this.state.loading ?
                        <div className={'h-24 mt-10 relative'}>
                            <ComponentLoading />
                        </div>
                        :
                        <Fragment>
                            <Tabs tabs={this.tabs} full_width={true}>
                                <TabPanel key={'npc-details'}>
                                    <div className={clsx({'grid md:grid-cols-2 gap-2': npcPLaneAccess !== null})}>
                                        <div>
                                            <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                                            <strong>Basic Info</strong>
                                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                            <dl>
                                                <dt>Name</dt>
                                                <dd>{this.state.quest_details.npc.real_name}</dd>
                                                <dt>Coordinates (X/Y)</dt>
                                                <dd>{this.state.quest_details.npc.x_position} / {this.state.quest_details.npc.y_position}</dd>
                                                <dt>On Plane</dt>
                                                <dd>{this.state.quest_details.npc.game_map.name}</dd>
                                                <dt>Must be at same location?</dt>
                                                <dd>{this.state.quest_details.npc.must_be_at_same_location ? 'Yes' : 'No'}</dd>
                                            </dl>
                                            <div className={clsx('mt-4 mb-4 transition-all duration-200')}>
                                                {
                                                    this.props.is_quest_complete ?
                                                        <div dangerouslySetInnerHTML={{__html: this.state.quest_details.after_completion_description.replace(/\n/g, "<br/>")}}></div>
                                                   :
                                                        <div dangerouslySetInnerHTML={{__html: this.state.quest_details.before_completion_description.replace(/\n/g, "<br/>")}}></div>
                                                }

                                            </div>
                                        </div>
                                        {
                                            npcPLaneAccess !== null ?
                                                <div className={clsx({'md:pl-2': npcPLaneAccess !== null})}>
                                                    <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                                                    <strong>Npc Access Requirements</strong>
                                                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                                    <dl className={'md:ml-8'}>{npcPLaneAccess}</dl>
                                                </div>
                                            : null
                                        }

                                    </div>
                                </TabPanel>
                                <TabPanel key={'required-to-complete'}>
                                    <CurrencyRequirement quest={this.state.quest_details} item_requirements={this.renderItem.bind(this)}/>
                                </TabPanel>
                                <TabPanel key={'quest-reward'}>
                                    <Reward quest={this.state.quest_details} />
                                </TabPanel>
                            </Tabs>

                            {
                                this.state.success_message !== null ?
                                    <div className='mb-4 mt-4'>
                                        <SuccessAlert>
                                            {this.state.success_message}
                                        </SuccessAlert>
                                    </div>
                                    : null
                            }

                            {
                                this.state.error_message !== null ?
                                    <div className='mb-4 mt-4'>
                                        <DangerAlert>
                                            {this.state.error_message}
                                        </DangerAlert>
                                    </div>
                                    : null
                            }

                            {
                                this.state.handing_in ?
                                    <LoadingProgressBar />
                                : null
                            }
                        </Fragment>
                }
            </Dialogue>
        )
    }
}
