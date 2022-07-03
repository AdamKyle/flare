import React, {Fragment} from "react";
import BasicCard from "../../../components/ui/cards/basic-card";
import UnitInformationProps from "../../../lib/game/kingdoms/types/unit-information-props";
import {formatNumber} from "../../../lib/game/format-number";
import Select from "react-select";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import {parseInt} from "lodash";
import TimeHelpModal from "../modals/time-help-modal";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

export default class UnitInformation extends React.Component<UnitInformationProps, any> {
     constructor(props: UnitInformationProps) {
         super(props);

         this.state = {
             upgrade_section: null,
             success_message: null,
             error_message: null,
             amount_to_recruit: '',
             loading: false,
             show_time_help: false,
             cost_in_gold: 0,
             time_needed: 0,
         }
     }

     calculateCostsForUnit(baseCost: number, amount: number, is_iron: boolean, is_population: boolean) {

         if (typeof this.props.unit_cost_reduction === 'undefined') {
             console.error('unit_cost_reduction is undefined (prop)');

             return 'ERROR';
         }

         let cost = baseCost * amount;

         if (is_iron) {
             cost = cost - cost - this.props.kingdom_iron_cost_reduction;
         }

         if (is_population) {
             cost = (cost - cost * this.props.kingdom_population_cost_reduction);
         }

         return (cost - cost * this.props.unit_cost_reduction).toFixed(0);
     }

     calculateTimeRequired(baseTime: number, amount: number) {
         const time = baseTime * amount;

         return (time - time * this.props.kingdom_building_time_reduction).toFixed(0);
     }

    setGoldAmount(e: React.ChangeEvent<HTMLInputElement>) {

         if (typeof this.props.unit_cost_reduction === 'undefined') {
             this.setState({
                 error_message: 'Cannot determine cost. Unit Cost Reduction Is Undefined.'
             });

             return;
         }

         const value = e.target.value;

         const amount = this.getAmountToRecruit(value);

         if (amount === 0) {
             return;
         }

         const goldCost = this.props.unit.cost_per_unit * amount;
         const timeNeeded = this.props.unit.time_to_recruit * amount;

         this.setState({
             amount_to_recruit: amount,
             cost_in_gold: (goldCost - goldCost * this.props.unit_cost_reduction),
             time_needed: (timeNeeded - timeNeeded * this.props.kingdom_building_time_reduction),
             paying_with_gold: true,
         })
    }

    setResourceAmount(e: React.ChangeEvent<HTMLInputElement>) {

        if (typeof this.props.unit_cost_reduction === 'undefined') {
            this.setState({
                error_message: 'Cannot determine cost. Unit Cost Reduction Is Undefined.'
            });

            return;
        }

        const value = e.target.value;

        const amount = this.getAmountToRecruit(value);

        if (amount === 0) {
            return;
        }

        const timeNeeded = this.props.unit.time_to_recruit * amount;

        this.setState({
            amount_to_recruit: amount,
            time_needed: (timeNeeded - timeNeeded * this.props.kingdom_building_time_reduction),
        })
    }

    getAmountToRecruit(numberToRecruit: string) {
        let amount = parseInt(numberToRecruit) || 0;

        if (amount === 0) {
            return 0;
        }

        amount = Math.abs(amount);

        const currentMax = this.props.unit.max_amount;

        if (amount > currentMax) {
            amount = currentMax;
        }

        return amount;
    }

    getAmount() {
        return parseInt(this.state.amount_to_recruit) || 1;
    }

     renderSelectedSection() {
         switch(this.state.upgrade_section) {
             case 'gold':
                 return this.renderGoldSection();
             case 'resources':
                 return this.renderResourceSection();
             default:
                 return null;
         }
    }

    showSelectedForm(data: any) {
         this.setState({
             upgrade_section: data.value
         });
    }

    removeSelection() {
        this.setState({
            upgrade_section: null,
            success_message: null,
            error_message: null,
            amount_to_recruit: '',
            loading: false,
            cost_in_gold: 0,
            time_needed: 0,
        })
    }

    manageHelpDialogue() {
         this.setState({
             show_time_help: !this.state.show_time_help,
         })
    }

    recruitUnits() {
        this.setState({
            error_message: null,
            success_message: null,
            loading: true,
        }, () => {

            (new Ajax()).setRoute('kingdoms/'+this.props.kingdom_id+'/recruit-units/' + this.props.unit.id).setParameters({
                amount: this.state.amount_to_recruit === '' ? 1 : this.state.amount_to_recruit,
                recruitment_type: this.state.upgrade_section,
            }).doAjaxCall('post', (response: AxiosResponse) => {
                this.setState({loading: false, success_message: response.data.message});
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

    renderGoldSection() {
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
                         <input type='text' value={this.state.amount_to_recruit} onChange={this.setGoldAmount.bind(this)} className='form-control' disabled={this.state.loading} />
                     </div>
                 </div>
                 <dl className='mt-4 mb-4'>
                     <dt>Gold Cost</dt>
                     <dd>{formatNumber(this.state.cost_in_gold)}</dd>
                     <dt>
                         Time Needed (Seconds)
                     </dt>
                     <dd className='flex items-center'>
                         <span>{formatNumber(this.state.time_needed)}</span>
                         <div>
                             <div className='ml-2'>
                                 <button type={"button"} onClick={() => this.manageHelpDialogue()} className='text-blue-500 dark:text-blue-300'>
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
                 <PrimaryButton button_label={'Purchase Units'} additional_css={'mr-2'} on_click={this.recruitUnits.bind(this)} disabled={this.state.amount_to_recruit <= 0 || this.state.loading}/>
                 <DangerButton button_label={'Cancel'} on_click={this.removeSelection.bind(this)} disabled={this.state.loading}/>
             </Fragment>
         )
    }

    renderResourceSection() {
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
                        <input type='text' value={this.state.amount_to_recruit} onChange={this.setResourceAmount.bind(this)} className='form-control' disabled={this.state.loading} />
                    </div>
                </div>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                        : null
                }
                <PrimaryButton button_label={'Recruit Units'} additional_css={'mr-2'} on_click={this.recruitUnits.bind(this)} disabled={this.state.amount_to_recruit <= 0 || this.state.loading}/>
                <DangerButton button_label={'Cancel'} on_click={this.removeSelection.bind(this)} disabled={this.state.loading}/>
            </Fragment>
        )
    }

     render() {
         return (
             <Fragment>
             <BasicCard>
                 <div className='text-right cursor-pointer text-red-500'>
                     <button onClick={() => this.props.close()}><i className="fas fa-minus-circle"></i></button>
                 </div>
                 <div className={'grid md:grid-cols-2 gap-4 mb-4 mt-4'}>
                     <div>
                         <h3>Basic Info</h3>
                         <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                         <dl>
                             <dt>Name</dt>
                             <dd>{this.props.unit.name}</dd>
                             <dt>Attack</dt>
                             <dd>{this.props.unit.attack}</dd>
                             <dt>Defence</dt>
                             <dd>{this.props.unit.defence}</dd>
                             <dt>Heal % (For on unit. Stacks.)</dt>
                             <dd>{this.props.unit.heal_percentage !== null ? (this.props.unit.heal_percentage * 100).toFixed(0) : 0}%</dd>
                             <dt>Good for attacking?</dt>
                             <dd>{this.props.unit.attacker ? 'Yes' : 'No'}</dd>
                             <dt>Good for defending?</dt>
                             <dd>{this.props.unit.defender ? 'Yes' : 'No'}</dd>
                             <dt>Travel Time</dt>
                             <dd>{this.props.unit.travel_time} Minutes per 1 square<sup>*</sup></dd>
                         </dl>
                         <p className='text-sm mt-5'><sup>*</sup> 1 Square = 16 Miles = 1 Player Directional Movement Action.</p>
                     </div>
                     <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                     <div>
                         <h3>Upgrade Costs (For 1 Unit)</h3>
                         <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                         <dl className='mb-5'>
                             <dt>Stone Cost:</dt>
                             <dd>{formatNumber(this.calculateCostsForUnit(this.props.unit.stone_cost, this.getAmount(), false, false))}</dd>
                             <dt>Clay Cost:</dt>
                             <dd>{formatNumber(this.calculateCostsForUnit(this.props.unit.clay_cost, this.getAmount(), false, false))}</dd>
                             <dt>Wood Cost:</dt>
                             <dd>{formatNumber(this.calculateCostsForUnit(this.props.unit.wood_cost, this.getAmount(), false, false))}</dd>
                             <dt>Iron Cost:</dt>
                             <dd>{formatNumber(this.calculateCostsForUnit(this.props.unit.iron_cost, this.getAmount(), true, false))}</dd>
                             <dt>Population Cost:</dt>
                             <dd>{formatNumber(this.calculateCostsForUnit(this.props.unit.required_population, this.getAmount(), false, true))}</dd>
                             <dt>Time Required (Seconds):</dt>
                             {
                                 this.state.upgrade_section === 'resources' ?
                                     <dd className='flex items-center'>
                                         <span>{formatNumber(this.state.time_needed)}</span>
                                         <div>
                                             <div className='ml-2'>
                                                 <button type={"button"} onClick={() => this.manageHelpDialogue()} className='text-blue-500 dark:text-blue-300'>
                                                     <i className={'fas fa-info-circle'}></i> Help
                                                 </button>
                                             </div>
                                         </div>
                                     </dd>
                                 :
                                     <dl>{formatNumber(this.calculateTimeRequired(this.props.unit.time_to_recruit, this.getAmount()))}</dl>
                             }
                         </dl>
                         {
                             this.props.is_in_queue ?
                                 <p className='mb-5 mt-5'>You must wait for the units recruitment to end.</p>
                             :
                                 this.props.kingdom_current_population === 0 ?
                                     <p className='mb-5 mt-5'>You have no population to recruit units with.</p>
                                 :
                                     this.state.upgrade_section !== null ?
                                         this.renderSelectedSection()
                                     :
                                         <Select
                                             onChange={this.showSelectedForm.bind(this)}
                                             options={[
                                                 {
                                                     label: 'Recruit with gold',
                                                     value: 'gold',
                                                 },
                                                 {
                                                     label: 'Recruit with resources',
                                                     value: 'resources',
                                                 }
                                             ]}
                                             menuPosition={'absolute'}
                                             menuPlacement={'bottom'}
                                             styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                             menuPortalTarget={document.body}
                                             value={[
                                                 {label: 'Please Select Recruit Path', value: ''}
                                             ]}
                                         />
                         }
                     </div>
                 </div>
             </BasicCard>
                 {
                     this.state.show_time_help ?
                         <TimeHelpModal
                             is_in_minutes={false}
                             is_in_seconds={true}
                             manage_modal={this.manageHelpDialogue.bind(this)}
                             time={this.calculateTimeRequired(this.props.unit.time_to_recruit, this.state.amount_to_recruit === '' ? 1 : this.state.amount_to_recruit)}
                         />
                     : null
                 }
             </Fragment>
         )
     }
}
