import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import {formatNumber} from "../../../../lib/game/format-number";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../../components/ui/loading/component-loading";

export default class CharacterReincarnationModal extends React.Component<AdditionalInfoModalProps, any> {


    constructor(props: AdditionalInfoModalProps) {
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
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
            >
                {
                    this.state.is_loading ?
                        <ComponentLoading />
                    :
                        <div>
                            <p className='mb-4'>
                                Reincarnation is a way to slowly grow your character over time. You can read more about it <a href='/information/reincarnation' target="_blank">
                                    here <i
                                    className="fas fa-external-link-alt"></i>
                                </a>.
                            </p>
                            <p className='mb-4'>
                                The gist is simple, reincarnate your character and start back at level one but keep 20% of your raw stats at the time
                                or reincarnation.
                            </p>
                            <p className='mb-4'>
                                You will only increase your Base Stat and Damage Mod when your character base stats reach 999,999 and you
                                cannot reincarnate anymore but can technically continue leveling Doing so, leveling, will increase these
                                additional modifiers which are then applied to your modified stats. This gives incentive to
                                keep leveling even after maxing reincarnation.
                            </p>
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
            </Dialogue>
        );
    }
}
