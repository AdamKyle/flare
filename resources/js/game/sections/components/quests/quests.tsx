import React, {Fragment} from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import QuestsProps from "../../../lib/game/types/map/quests/quests-props";
import QuestState from "../../../lib/game/types/map/quests/quest-state";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import QuestTree from "./components/quest-tree";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import DropDown from "../../../components/ui/drop-down/drop-down";


export default class Quests extends React.Component<QuestsProps, QuestState> {

    constructor(props: any) {
        super(props);

        this.state = {
            quests: [],
            completed_quests: [],
            current_plane: '',
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('quests/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                quests: result.data.quests,
                completed_quests: result.data.completed_quests,
                current_plane: result.data.player_plane,
                loading: false,
            });
        }, (error: AxiosError) => {

        });
    }


    setPlaneForQuests(plane: string) {
        this.setState({
            current_plane: plane
        });
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.loading ?
                        <div className={'h-24 mt-10 relative'}>
                            <ComponentLoading />
                        </div>
                    :
                        <Fragment>
                            <InfoAlert>
                                <p className='my-2'>Each plane has it's own sets of quests. Some have long quest chains that unlock various features and upgrades for your gear.</p>
                                <p className='my-2'>Players who do not do quests, will not get very far in Tlessa as these quests unlock features of the game and allow you to further progress. All quests tell you explicitly how to complete them.</p>
                                <p className='my-2'>The gathering of the required currencies, items and so on will take you a while, but once you have done all the quests, your character will be
                                    much stronger, better equipped you will have a better understanding of the various systems in Tlessa and how they come together.</p>
                                <p className='my-2'>Finally, there are two types of quests Tlessa: Quest Chains, what you see below, and One Off Quests (next tab over). Quest chains unlock the bulk of the features while one
                                    offs are good for upgrading early game quest items.</p>
                            </InfoAlert>
                            <DropDown menu_items={[
                                {
                                    name: 'Surface',
                                    on_click: this.setPlaneForQuests.bind(this),
                                    icon_class: 'ra ra-footprint'
                                },
                                {
                                    name: 'Labyrinth',
                                    on_click: this.setPlaneForQuests.bind(this),
                                    icon_class: 'ra ra-footprint'
                                },
                                {
                                    name: 'Hell',
                                    on_click: this.setPlaneForQuests.bind(this),
                                    icon_class: 'ra ra-footprint'
                                },
                            ]} button_title={'Planes'} />
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div className='overflow-x-auto max-w-[400px] sm:max-w-[600px] md:max-w-[100%]'>
                                <QuestTree quests={this.state.quests} completed_quests={this.state.completed_quests} character_id={this.props.character_id} plane={this.state.current_plane} />
                            </div>
                        </Fragment>
                }
            </Fragment>
        );
    }
}
