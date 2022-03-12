import React, {Fragment} from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import QuestsProps from "../../../lib/game/types/map/quests/quests-props";
import QuestState from "../../../lib/game/types/map/quests/quest-state";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import QuestTree from "./components/quest-tree";


export default class Quests extends React.Component<QuestsProps, QuestState> {

    constructor(props: any) {
        super(props);

        this.state = {
            quests: [],
            completed_quests: [],
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('quests/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                quests: result.data.quests,
                completed_quests: result.data.completed_quests,
                loading: false,
            });
        }, (error: AxiosError) => {

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
                            <div className='overflow-x-auto max-w-[400px] sm:max-w-[600px] md:max-w-[100%]'>
                                <QuestTree quests={this.state.quests} character_id={this.props.character_id} />
                            </div>
                        </Fragment>
                }
            </Fragment>
        );
    }
}
