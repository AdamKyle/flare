import React, {Fragment} from "react";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import {formatNumber} from "../../../lib/game/format-number";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import {parseInt} from "lodash";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

export default class RecruitWithGold extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            success_message: null,
            error_message: null,
            amount_to_recruit: '',
            loading: false,
            show_time_help: false,
            cost_in_gold: 0,
            time_needed: 0,
        }
    }

    recruitUnits() {
        this.setState({
            error_message: null,
            success_message: null,
            loading: true,
        }, () => {

            (new Ajax()).setRoute('kingdoms/'+this.props.kingdom_id+'/recruit-units/' + this.props.unit.id).setParameters({
                amount: this.state.amount_to_recruit === '' ? 1 : this.state.amount_to_recruit,
                recruitment_type: 'gold',
            }).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({
                    loading: false,
                    success_message: response.data.message,
                    amount_to_recruit: '',
                    show_time_help: false,
                    cost_in_gold: 0,
                    time_needed: 0,
                });
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        loading: false,
                        error_message: response.data.message
                    });
                }
            });
        });
    }

    setGoldAmount(e: React.ChangeEvent<HTMLInputElement>) {

        if (typeof this.props.unit_cost_reduction === 'undefined') {
            this.setState({
                error_message: 'Cannot determine cost. Unit Cost Reduction Is Undefined.'
            });

            return;
        }

        const value = parseInt(e.target.value, 10) || 0;

        if (value === 0) {
            return this.setState({
                amount_to_recruit: '',
                time_needed: 0,
                paying_with_gold: 0,
                cost_in_gold: 0,
            });
        }

        let amount = this.getAmountToRecruit(value);

        if (amount === 0) {
            return this.setState({
                amount_to_recruit: '',
                time_needed: 0,
                paying_with_gold: 0,
                cost_in_gold: 0,
            });
        }

        const timeNeeded = this.props.unit.time_to_recruit * amount;

        this.setState({
            amount_to_recruit: amount - amount * this.props.unit_cost_reduction,
            time_needed: (timeNeeded - timeNeeded * this.props.kingdom_building_time_reduction),
            paying_with_gold: true,
        }, () => {
            amount = amount - amount * this.props.unit_cost_reduction;

            this.setState({
                cost_in_gold: this.props.unit.cost_per_unit * amount,
            });
        })
    }

    getAmountToRecruit(numberToRecruit: number) {
        if (numberToRecruit === 0) {
            return 0;
        }

        numberToRecruit = Math.abs(numberToRecruit);

        const currentMax = this.props.unit.max_amount;

        if (numberToRecruit > currentMax) {
            numberToRecruit = currentMax;
        }

        return numberToRecruit;
    }

    getPopulationCost() {
        const amount = parseInt(this.state.amount_to_recruit, 10) || 0;

        if (this.state.amount_to_recruit > 0 && amount === 0) {
            return 1;
        }

        return amount;
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.success_message !== null ?
                        <SuccessAlert additional_css={'mb-5'}>
                            {this.state.success_message}
                        </SuccessAlert>
                        : null
                }
                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css={'mb-5'}>
                            {this.state.error_message}
                        </DangerAlert>
                        : null
                }
                <div className='flex items-center mb-5'>
                    <label className='w-[50px] mr-4'>Amount</label>
                    <div className='w-2/3'>
                        <input type='text' onChange={this.setGoldAmount.bind(this)} className='form-control' disabled={this.state.loading} />
                    </div>
                </div>
                <dl className='mt-4 mb-4'>
                    <dt>Gold On Hand</dt>
                    <dd>{formatNumber(this.props.character_gold)}</dd>
                    <dt>Gold Cost</dt>
                    <dd>{formatNumber(this.state.cost_in_gold)}</dd>
                    <dt>Population Cost</dt>
                    <dd>{formatNumber(this.getPopulationCost())}</dd>
                    <dt>
                        Time Needed (Seconds)
                    </dt>
                    <dd className='flex items-center'>
                        <span>{formatNumber(this.state.time_needed)}</span>
                        <div>
                            <div className='ml-2'>
                                <button type={"button"} onClick={() => this.props.manage_help_dialogue(this.state.time_needed)} className='text-blue-500 dark:text-blue-300'>
                                    <i className={'fas fa-info-circle'}></i> Help
                                </button>
                            </div>
                        </div>
                    </dd>
                </dl>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                        : null
                }
                <PrimaryButton button_label={'Purchase Units'} additional_css={'mr-2'} on_click={this.recruitUnits.bind(this)} disabled={this.state.amount_to_recruit <= 0 || this.state.loading || this.state.cost_in_gold > this.props.character_gold}/>
                <DangerButton button_label={'Cancel'} on_click={this.props.remove_selection} disabled={this.state.loading}/>
            </Fragment>
        )
    }
}
