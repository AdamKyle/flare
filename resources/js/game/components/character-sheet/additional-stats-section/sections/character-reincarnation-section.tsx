import React from "react";
import {AdditionalInfoProps} from "../../../../sections/character-sheet/components/types/additional-info-props";
import {formatNumber} from "../../../../lib/game/format-number";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";

export default class CharacterReincarnationSection extends React.Component<AdditionalInfoProps, any> {


    constructor(props: AdditionalInfoProps) {
        super(props);

        this.state = {
            is_loading: true,
            reincarnation_details: [],
            error_message: '',
        }
    }

    componentDidMount(): void {

        if (this.props.character === null) {
            return;
        }

        (new Ajax).setRoute('character-sheet/'+this.props.character.id+'/reincarnation-info').doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                is_loading: false,
                reincarnation_details: response.data.reincarnation_details,
            })
        }, (error: AxiosError) => {
            this.setState({
                is_loading: false,
            })

            if (typeof error.response !== 'undefined') {
                this.setState({
                    erro_message: error.response.data.message,
                })
            }
        });
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        return (
            <>
                {
                    this.state.is_loading ?
                        <LoadingProgressBar />
                    :
                        <div>
                            <dl>
                                <dt>Reincarnated Times</dt>
                                <dd>{this.state.reincarnation_details.reincarnated_times !== null ? formatNumber(this.state.reincarnation_details.reincarnated_times) : 0}</dd>
                                <dt>Reincarnation Stat Bonus (pts.)</dt>
                                <dd>{this.state.reincarnation_details.reincarnated_stat_increase !== null ? formatNumber(this.state.reincarnation_details.reincarnated_stat_increase) : 0}</dd>
                                <dt>Base Stat Mod</dt>
                                <dd>{(this.state.reincarnation_details.base_stat_mod * 100).toFixed(2)}%</dd>
                                <dt>Base Damage Stat Mod</dt>
                                <dd>{(this.state.reincarnation_details.base_damage_stat_mod * 100).toFixed(2)}%</dd>
                                <dt>XP Penalty</dt>
                                <dd>{this.state.reincarnation_details.xp_penalty !== null ? (this.state.reincarnation_details.xp_penalty * 100).toFixed(0) : 0}%</dd>
                            </dl>
                        </div>
                }
            </>
        );
    }
}
