import React from "react";
import DangerButton from "../../../../../ui/buttons/danger-button";
import {startCase} from "lodash";
import Ajax from "../../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../../../ui/progress-bars/loading-progress-bar";

export default class HealthBreakDown extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            error_message: null,
            is_loading: true,
            details: null,
        }
    }

    componentDidMount(): void {

        this.setState({
            error_message: '',
        }, () => {
            if (this.props.character === null) {
                return;
            }

            (new Ajax).setRoute('character-sheet/'+this.props.character_id+'/specific-attribute-break-down').setParameters({
                type: this.props.type,
                is_voided: this.props.is_voided ? 1 : 0,
            }).doAjaxCall('get', (response: AxiosResponse) => {
                this.setState({
                    is_loading: false,
                    details: response.data.break_down,
                })
            }, (error: AxiosError) => {
                this.setState({is_loading: false});

                if (typeof error.response !== 'undefined') {
                    this.setState({
                        error_message: error.response.data.mmessage,
                    });
                }
            });
        });
    }

    titelizeType(): string {
        return startCase(this.props.type.replace('-', ' '));
    }

    renderClassSpecialtiesStatIncrease() {
        if (this.state.details === null) {
            return;
        }

        if (this.state.details.class_specialties === null) {
            return null;
        }

        return this.state.details.class_specialties.map((classSpecialty: any) => {
            return (
                <li>
                    <span className='text-sky-600 dark:text-sky-500'>{classSpecialty.name}</span> <span className='text-green-700 darmk:text-green-500'>(+{(classSpecialty.amount * 100).toFixed(2)}%)</span>
                </li>
            )
        })
    }

    render() {

        if (this.state.loading || this.state.details === null) {
            return <LoadingProgressBar />
        }

        return (
            <div>
                <div className='flex justify-between'>
                    <h3 className="mr-2">{(this.props.is_voided ? 'Voided ' : '')  + startCase(this.props.type.replace('-', ' '))}</h3>
                    <DangerButton button_label={'Close'} on_click={this.props.close_section}/>
                </div>

                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>

                {
                    this.props.is_voided ?
                        <p className='my-4'>
                            Your modded dur, when voided, is based off a few other aspects such as equipment with out
                            affixes,
                            class specialties and other minor factors.
                        </p>
                    : null
                }

                <div className={'grid md:grid-cols-2 gap-2'}>
                    <div>
                        <h4>Stat Modifiers</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        <ul className="space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                            <span className='text-slate-700 dark:text-slate-400'>Durability (Modded Dur)</span> <span
                            className='text-green-700 darmk:text-green-500'>(+{this.state.details.stat_amount})</span>
                        </ul>
                    </div>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2 block md:hidden'></div>
                    <div>
                        <h4> Equipped Class Specials That Raise: {this.titelizeType()}</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        {
                            this.state.details.class_specialties !== null ?
                                <ol className="space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                                    {this.renderClassSpecialtiesStatIncrease()}
                                </ol>
                            :
                                <p>
                                    You have nothing equipped.
                                </p>
                        }
                    </div>
                </div>
            </div>
        )
    }
}
