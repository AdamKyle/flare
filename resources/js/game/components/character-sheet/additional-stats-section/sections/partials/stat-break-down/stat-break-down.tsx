import React from "react";
import DangerButton from "../../../../../ui/buttons/danger-button";
import {startCase} from "lodash";
import Ajax from "../../../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../../../ui/progress-bars/loading-progress-bar";
import ItemNameColorationText from "../../../../../items/item-name/item-name-coloration-text";

export default class StatBreakDown extends React.Component<any, any> {

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

            (new Ajax).setRoute('character-sheet/'+this.props.character_id+'/stat-break-down').setParameters({
                stat_type: this.props.type,
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

    renderItemListEffects() {
        if (this.state.details === null) {
            return;
        }

        return this.state.details.items_equipped.map((equippedItem: any) => {
            return (
                <li>
                    <ItemNameColorationText item={equippedItem.item_details} custom_width={false} /> <span className='text-green-700 darmk:text-green-500'>(+{(equippedItem.item_base_stat * 100).toFixed(2)}%)</span>
                    {
                        equippedItem.attached_affixes.length > 0 ?
                            <ul className='ps-5 mt-2 space-y-1 list-disc list-inside'>
                                {this.renderAttachedAffixes(equippedItem.attached_affixes)}
                            </ul>
                        : null
                    }
                </li>
            )
        })
    }

    renderBoonIncreaseAllStatsEffects() {
        if (this.state.details === null) {
            return;
        }

        if (this.state.details.boon_details.increases_all_stats.length <= 0) {
            return;
        }

        return this.state.details.boon_details.increases_all_stats.map((boonIncreaseAllStats: any) => {
            return (
                <li>
                    <ItemNameColorationText item={boonIncreaseAllStats.item_details} custom_width={false} /> <span className='text-green-700 darmk:text-green-500'>(+{(boonIncreaseAllStats.increase_amount * 100).toFixed(2)}%)</span>
                </li>
            )
        })
    }

    renderBoonIncreaseSpecificStatEffects() {
        if (this.state.details === null) {
            return;
        }

        if (this.state.details.boon_details.increases_single_stat.length <= 0) {
            return null;
        }

        return this.state.details.boon_details.increases_single_stat.map((boonIncreaseAllStats: any) => {
            return (
                <li>
                    <ItemNameColorationText item={boonIncreaseAllStats.item_details} custom_width={false} /> <span className='text-green-700 darmk:text-green-500'>(+{(boonIncreaseAllStats.increase_amount * 100).toFixed(2)}%)</span>
                </li>
            )
        })
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

    renderAttachedAffixes(attachedAffixes: any[]) {
        return attachedAffixes.map((attachedAffix: any) => {
            return (
                <li>
                    <span className='text-slate-700 dark:text-slate-400'>{attachedAffix.affix_name}</span> <span className='text-green-700 darmk:text-green-500'>(+{(attachedAffix[this.props.type + '_mod'] * 100).toFixed(2)}%);</span>
                </li>
            );
        })
    }

    render() {

        if (this.state.loading || this.state.details === null) {
            return <LoadingProgressBar />
        }

        console.log(this.state.details);

        return (
            <div>
                <div className='flex justify-between'>
                    <h3>{
                        startCase(this.props.type.replace('-', ' '))
                    }</h3>
                    <DangerButton button_label={'Close'} on_click={this.props.close_section}/>
                </div>

                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>

                <div className='grid md:grid-cols-2 gap-2'>

                    <div>
                        <h4>Equipped Modifiers</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        <ol className="space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                            {
                                this.state.details.items_equipped.length > 0 ?
                                    this.renderItemListEffects()
                                :
                                    <p>
                                        You have nothing equipped.
                                    </p>
                            }
                        </ol>
                    </div>

                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2 block md:hidden'></div>
                    <div>
                        <h4>Increases All Stats Boon Modifiers</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>

                        {
                            this.state.details.boon_details !== null ?
                                <ol className="space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                                    {this.renderBoonIncreaseAllStatsEffects()}
                                </ol>
                                :
                                <p>
                                    There are no boons applied that effect this specific stat.
                                </p>
                        }
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4'></div>
                        <h4>Increases {this.titelizeType()}</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        {
                            this.state.details.boon_details !== null ?

                                this.state.details.boon_details.hasOwnProperty('increases_single_stat') ?
                                    this.renderBoonIncreaseSpecificStatEffects()
                                    :
                                    <p>
                                        There are no boons applied that effect this specific stat.
                                    </p>
                                :
                                <p>
                                    There are no boons applied that effect this specific stat.
                                </p>
                        }
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4'></div>
                        <h4> Equipped Class Specials That Raise: {this.titelizeType()}</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        {
                            this.state.details.class_specialties !== null ?
                                this.renderClassSpecialtiesStatIncrease()
                            :
                                <p>
                                    There are no class specials equipped that effect this stat.
                                </p>
                        }
                    </div>
                </div>


            </div>
        )
    }
}
