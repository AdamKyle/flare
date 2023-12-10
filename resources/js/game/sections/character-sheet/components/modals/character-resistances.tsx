import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoProps} from "../types/additional-info-props";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../../components/ui/loading/component-loading";

export default class CharacterResistances extends React.Component<AdditionalInfoProps, any> {


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
                                Resistances come from a variety of places. Click Additional Info to see other resistances such as Devouring light,
                                Ambush and counter.
                            </p>
                            <p className='mb-4'>
                                Rings will increase these values.
                            </p>
                            <dl>
                                <dt>Spell Evasions</dt>
                                <dd>{(this.state.resistance_info.spell_evasion * 100).toFixed(2)}%</dd>
                                <dt>Affix Damage Reduction</dt>
                                <dd>{(this.state.resistance_info.affix_damage_reduction * 100).toFixed(2)}%</dd>
                            </dl>
                        </div>
                }
            </Dialogue>
        );
    }
}
