import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import {AdditionalInfoProps} from "./types/additional-info-props";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";

export default class ResistanceInfoSection extends React.Component<AdditionalInfoProps, any> {


    constructor(props: AdditionalInfoProps) {
        super(props);

        this.state = {
            is_loading: true,
            error_message: '',
            resistance_info: [],
        }
    }

    componentDidMount(): void {

        if (this.props.character === null) {
            return;
        }

        (new Ajax).setRoute('character-sheet/' + this.props.character.id + '/resistance-info').doAjaxCall('get', (response: AxiosResponse) => {
            this.setState({
                is_loading: false,
                resistance_info: response.data.resistance_info,
            })
        }, (error: AxiosError) => {
            this.setState({is_loading: false});

            if (typeof error.response !== 'undefined') {
                this.setState({
                    error_message: error.response.data.mmessage,
                });
            }
        });
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        if (this.state.error_message !== null) {
            return (
                <DangerAlert additional_css={'my-4'}>
                    {this.state.error_message}
                </DangerAlert>
            )
        }

        if (this.state.is_loading) {
            return <LoadingProgressBar />
        }

        return (
            <div>
                <dl>
                    <dt>Spell Evasions</dt>
                    <dd>{(this.state.resistance_info.spell_evasion * 100).toFixed(2)}%</dd>
                    <dt>Affix Damage Reduction</dt>
                    <dd>{(this.state.resistance_info.affix_damage_reduction * 100).toFixed(2)}%</dd>
                </dl>
            </div>
        );
    }
}
