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
                    <ItemNameColorationText item={equippedItem.item_details} custom_width={false} /> <span className='text-green-700 dark:text-green-500'>(+{(equippedItem.item_base_stat * 100).toFixed(2)}%)</span>
                    {
                        equippedItem.attached_affixes.length > 0 ?
                            <ul className='ps-5 mt-2 space-y-1 list-disc list-inside'>
                                {this.renderAttachedAffixes(equippedItem.attached_affixes)}
                            </ul>
                        : null
                    }
                    {
                        equippedItem.item_holy_stacks_applied > 0 ?
                            <ul className='ps-5 mt-2 space-y-1 list-disc list-inside'>
                                <li className='text-slate-700 dark:text-slate-300'>
                                    You have applied: {equippedItem.item_holy_stacks_applied} of: {equippedItem.item_holy_stacks_applied} Holy Stacks, which gives you a bonus of: {(equippedItem.total_stat_increase * 100).toFixed(2)}% to {this.titelizeType()}
                                </li>
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
                    <ItemNameColorationText item={boonIncreaseAllStats.item_details} custom_width={false} /> <span className='text-green-700 dark:text-green-500'>(+{(boonIncreaseAllStats.increase_amount * 100).toFixed(2)}%)</span>
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
                    <ItemNameColorationText item={boonIncreaseAllStats.item_details} custom_width={false} /> <span className='text-green-700 dark:text-green-500'>(+{(boonIncreaseAllStats.increase_amount * 100).toFixed(2)}%)</span>
                </li>
            )
        })
    }

    renderAncestralItemSkill() {
        if (this.state.details === null) {
            return;
        }

        return this.state.details.ancestral_item_skill_data.map((ancestralItemSkill: any) => {
            return (
                <li>
                    <span className='text-orange-600 dark:text-orange-300'>{ancestralItemSkill.name}</span> <span className='text-green-700 dark:text-green-500'>(+{(ancestralItemSkill.increase_amount * 100).toFixed(2)}%)</span>
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
                    <span className='text-sky-600 dark:text-sky-500'>{classSpecialty.name}</span> <span className='text-green-700 dark:text-green-500'>(+{(classSpecialty.amount * 100).toFixed(2)}%)</span>
                </li>
            )
        })
    }

    renderAttachedAffixes(attachedAffixes: any[]) {
        return attachedAffixes.map((attachedAffix: any) => {
            return (
                <li>
                    <span className='text-slate-700 dark:text-slate-400'>{attachedAffix.affix_name}</span> <span className='text-green-700 dark:text-green-500'>(+{(attachedAffix[this.props.type + '_mod'] * 100).toFixed(2)}%);</span>
                </li>
            );
        })
    }

    render() {

        if (this.state.loading || this.state.details === null) {
            return <LoadingProgressBar />
        }

        return (
            <div>
                <div className='flex justify-between'>
                    <div className="flex items-center">
                        <h3 className="mr-2">{startCase(this.props.type.replace('-', ' '))}</h3>
                        <span className="text-gray-700 dark:text-gray-400">
                            (Raw: {this.state.details.base_value})
                        </span>
                    </div>
                    <DangerButton button_label={'Close'} on_click={this.props.close_section}/>
                </div>

                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>

                <div className='grid md:grid-cols-2 gap-2'>

                    <div>
                        <h4>Equipped Modifiers</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        {
                            this.state.details.items_equipped.length > 0 ?
                                <ol className="space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400">
                                    {this.renderItemListEffects()}
                                </ol>
                            :
                                <p>
                                    You have nothing equipped.
                                </p>
                        }
                    </div>

                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2 block md:hidden'></div>
                    <div>
                        <h4>Boons that increases all stats</h4>
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
                        <h4>Boons that increase: {this.titelizeType()}</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        {
                            this.state.details.boon_details !== null ?

                                this.state.details.boon_details.hasOwnProperty('increases_single_stat') ?
                                    <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                        {this.renderBoonIncreaseSpecificStatEffects()}
                                    </ul>
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
                                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                    {this.renderClassSpecialtiesStatIncrease()}
                                </ul>
                            :
                                <p>
                                    There are no class specials equipped that effect this stat.
                                </p>
                        }
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4'></div>
                        <h4> Ancestral Item Skills That Raise: {this.titelizeType()}</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        {
                            this.state.details.ancestral_item_skill_data !== null ?
                                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                    {this.renderAncestralItemSkill()}
                                </ul>
                            :
                                <p>
                                   There are no Ancestral Item Skills that effect this stat.
                                </p>
                        }
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4'></div>
                        <h4> Map Reduction To: {this.titelizeType()}</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                        {
                            this.state.details.map_reduction !== null ?
                                <ul className="space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400">
                                    <li>
                                        <span
                                            className='text-slate-700 dark:text-slate-400'>{this.state.details.map_reduction.map_name}</span>{" "}
                                        <span
                                            className='text-red-700 darmk:text-red-500'>(-{(this.state.details.map_reduction.reduction_amount * 100).toFixed(2)}%)</span>
                                    </li>
                                </ul>
                            :
                                <p>
                                    There are no map reductions applied to this stat.
                                </p>
                        }
                    </div>
                </div>


            </div>
        )
    }
}
