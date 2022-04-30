import React, {Fragment} from "react";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
import KingdomPassiveTree from "./skill-tree/kingdom-passive-tree";
import TimerProgressBar from "../../../../../components/ui/progress-bars/timer-progress-bar";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";

export default class KingdomPassives extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            kingdom_passives: [],
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/kingdom-passives/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                kingdom_passives: result.data.kingdom_passives,
            })
        }, (error: AxiosError) => {

        })
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.loading ?
                        <div className={'relative p-10'}>
                            <ComponentLoading />
                        </div>

                    :
                        <div>
                            <div className='mb-4'>
                                <InfoAlert>
                                    Click The skill name for additional actions. The timer will show below the tree when a skill is in progress.
                                </InfoAlert>
                            </div>
                            <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <KingdomPassiveTree passives={this.state.kingdom_passives[0]} />

                            <div className='relative top-[24px]'>
                                <TimerProgressBar time_out_label={'Skill In Training: Goblin Coin Bank'} time_remaining={600} />
                            </div>
                        </div>
                }
            </Fragment>
        )
    }

}
