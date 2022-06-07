import React, { Fragment } from "react";
import Select from "react-select";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../lib/ajax/ajax";
import {formatNumber} from "../../../../lib/game/format-number";

export default class QueenOfHearts extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {
            initial_action: null,
            buy_option: null,
            reroll_options: {
                item_selected: null,
                reroll_option: null,
                attribute: null,
            },
            preforming_action: false,
            character_uniques: [],
            character_non_uniques: [],
            reroll_cost: {
                gold_dust_dust: 0,
                shards: 0,
            },
            loading: true,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/uniques').doAjaxCall('get',
            (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    character_uniques: result.data.unique_slots,
                    character_non_uniques: result.data.non_unique_slots
                });
            }, (error: AxiosError) => {console.log(error)});
    }

    setInitialOption(data: any) {
        this.setState({
            initial_action: data.value,
        });
    }

    setBuyOption(data: any) {
        this.setState({
            buy_option: data.value,
        });
    }

    setReRollOption(data: any) {
        if (data.value === 'prefix' || data.value === 'suffix') {
            this.setState({
                reroll_options: {...this.state.reroll_options, ...{reroll_option: data.value}},
                reroll_cost: {gold_dust_dust: 10000, shards: 100}
            });
        } else {
            this.setState({
                reroll_options: {...this.state.reroll_options, ...{reroll_option: data.value}},
                reroll_cost: {gold_dust_dust: 20000, shards: 200}
            });
        }
    }

    setItemForReRoll(data: any) {
        this.setState({
            reroll_options: {...this.state.reroll_options, ...{item_selected: data.value}},
        });
    }

    setAttributeToReRoll(data: any) {
        const cost = JSON.parse(JSON.stringify(this.state.reroll_cost));

        if (data.value !== 'everything') {
            cost.gold_dust_cost += 100
            cost.shards += 100;
        } else {
            cost.gold_dust_cost += 500
            cost.shards += 500;
        }

        this.setState({
            reroll_options: {...this.state.reroll_options, ...{attribute: data.value}},
            reroll_cost: cost,
        });
    }

    initialOptions() {
        return [{
            label: 'Buy Item',
            value: 'buy-item',
        },{
            label: 'Re-Roll Item',
            value: 're-roll-item',
        },{
            label: 'Move Enchants',
            value: 'move-enchants',
        }]
    }

    buyItemOptions() {
        return [{
            label: '10 Billion (Gold Cost) - Basic',
            value: 'basic'
        }, {
            label: '50 Billion (Gold Cost) - Medium',
            value: 'medium'
        }, {
            label: '100 Billion (Gold Cost) - Legendary',
            value: 'legendary'
        }];
    }

    getSelectedBuyValue() {
        const foundSelected = this.buyItemOptions().filter((option: {label: string, value: string}) => {
            return option.value === this.state.buy_option
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].label, value: foundSelected[0].value};
        }

        return {label: 'Please select item to purchase', value: null};
    }

    reRollOptions() {
        return [{
            label: 'Prefix',
            value: 'prefix'
        }, {
            label: 'Suffix',
            value: 'suffix'
        }, {
            label: 'Both',
            value: 'all-enchantments'
        }];
    }

    getSelectedReRollOption() {
        const foundSelected = this.reRollOptions().filter((option: {label: string, value: string}) => {
            return option.value === this.state.reroll_options.reroll_option
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].label, value: foundSelected[0].value};
        }

        return {label: 'Please select what to re-roll', value: null};
    }

    itemsForReRoll() {
        return this.state.character_uniques.map((unique: any) => {
            return {
                label: unique.item.affix_name,
                value: unique.id,
            }
        });
    }

    getSelectedItem() {
        const foundSelected = this.state.character_uniques.filter((unique: any) => {
            return unique.id === this.state.reroll_options.item_selected
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].item.affix_name, value: foundSelected[0].id};
        }

        return {label: 'Please select item to re-roll', value: null};
    }

    getAttributesForReRoll() {
        return [{
            label: 'Base Details',
            value: 'base'
        }, {
            label: 'Core Stats',
            value: 'stats'
        }, {
            label: 'Skill Modifiers',
            value: 'skills'
        }, {
            label: 'Damage Modifiers',
            value: 'damage'
        }, {
            label: 'Resistances',
            value: 'resistances'
        }, {
            label: 'All of it',
            value: 'everything'
        }];
    }

    getSelectedAttributeOption() {
        const foundSelected = this.getAttributesForReRoll().filter((option: {label: string, value: string}) => {
            return option.value === this.state.reroll_options.attribute
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].label, value: foundSelected[0].value};
        }

        return {label: 'Please select what attributes to re-roll', value: null};
    }

    buyItem() {
        this.setState({
            preforming_action: true,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/random-enchant/purchase').setParameters({
                type: this.state.buy_option,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    preforming_action: false,
                });
            }, (error: AxiosError) => {console.log(error)})
        });
    }

    reRoll() {
        this.setState({
            preforming_action: true,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/random-enchant/reroll').setParameters({
                selected_slot_id: this.state.reroll_options.item_selected,
                selected_affix: this.state.reroll_options.reroll_option,
                selected_reroll_type: this.state.reroll_options.attribute,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    preforming_action: false,
                    character_uniques: result.data.unique_slots,
                    character_non_uniques: result.data.non_unique_slots
                });
            }, (error: AxiosError) => {console.log(error)})
        });
    }

    clearAll() {
        this.setState({
            buy_option: null,
            initial_action: null,
            reroll_options: {
                reroll_option: null,
                item_selected: null,
            },
            reroll_cost: {
                gold_dust: 0,
                shards: 0,
            }
        })
    }

    render() {

        return (
            <Fragment>
                <div className='mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]'>
                    <div className='cols-start-1 col-span-2'>
                        {
                            this.state.loading ?
                                <LoadingProgressBar />
                                : null
                        }

                        {
                            this.state.initial_action === null && !this.state.loading ?
                                <Select
                                    onChange={this.setInitialOption.bind(this)}
                                    options={this.initialOptions()}
                                    menuPosition={'absolute'}
                                    menuPlacement={'bottom'}
                                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                    menuPortalTarget={document.body}
                                    value={[{
                                        label: 'Please select option',
                                        value: '',
                                    }]}
                                />
                            : null
                        }

                        {
                            this.state.initial_action === 'buy-item' ?
                                <Fragment>
                                    <Select
                                        onChange={this.setBuyOption.bind(this)}
                                        options={this.buyItemOptions()}
                                        menuPosition={'absolute'}
                                        menuPlacement={'bottom'}
                                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                        menuPortalTarget={document.body}
                                        value={this.getSelectedBuyValue()}
                                    />
                                    <p className='mt-2'>These items can roll with one or two uniques and all aspects are randomly rolled.
                                    The more expensive, the better.</p>
                                </Fragment>
                            : null
                        }

                        {
                            this.state.initial_action === 're-roll-item' ?
                                <Fragment>
                                    <Select
                                        onChange={this.setItemForReRoll.bind(this)}
                                        options={this.itemsForReRoll()}
                                        menuPosition={'absolute'}
                                        menuPlacement={'bottom'}
                                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                        menuPortalTarget={document.body}
                                        value={this.getSelectedItem()}
                                    />
                                    {
                                        this.state.reroll_options.item_selected !== null ?
                                            <div className='mt-2'>
                                                <Select
                                                    onChange={this.setReRollOption.bind(this)}
                                                    options={this.reRollOptions()}
                                                    menuPosition={'absolute'}
                                                    menuPlacement={'bottom'}
                                                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                                    menuPortalTarget={document.body}
                                                    value={this.getSelectedReRollOption()}
                                                />
                                                <p className='mt-2'>Here you can re-roll one or both of the affixes attached to the item.</p>
                                            </div>
                                        : null
                                    }

                                    {
                                        this.state.reroll_options.item_selected !== null &&  this.state.reroll_options.reroll_option !== null?
                                            <div className='mt-2'>
                                                <Select
                                                    onChange={this.setAttributeToReRoll.bind(this)}
                                                    options={this.getAttributesForReRoll()}
                                                    menuPosition={'absolute'}
                                                    menuPlacement={'bottom'}
                                                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                                    menuPortalTarget={document.body}
                                                    value={this.getSelectedAttributeOption()}
                                                />
                                                <p className='mt-2'>Here you can re-roll specific aspects, or all aspects of the affix.</p>
                                                <p className='mt-2'>Cost is calculated based on Number of Affixes to re-roll plus 100 GD and shards for
                                                    the selected attribute <strong>or</strong> plus 500 GD and Shards for all attributes</p>
                                                {
                                                    this.state.reroll_options.reroll_option !== null ?
                                                        <p className='mt-2 text-orange-600 dark:text-orange-500'>
                                                            <strong>Gold Dust Cost</strong>: {formatNumber(this.state.reroll_cost.gold_dust_dust)}, <strong>Shards Cost</strong>: {formatNumber(this.state.reroll_cost.shards)}
                                                        </p>
                                                        : null
                                                }
                                            </div>
                                            : null
                                    }

                                </Fragment>
                                : null
                        }

                        {
                            this.state.preforming_action ?
                                <LoadingProgressBar />
                                : null
                        }
                    </div>
                </div>

                <div className={'hidden lg:block lg:text-center md:ml-[-100px] mt-3 mb-3'}>

                    {
                        this.state.initial_action === 'buy-item' ?
                            <PrimaryButton button_label={'Purchase'} on_click={this.buyItem.bind(this)} disabled={this.state.buy_option === null || this.state.preforming_action} />
                        : null
                    }

                    {
                        this.state.initial_action === 're-roll-item' ?
                            <PrimaryButton button_label={'Re roll'} on_click={this.reRoll.bind(this)} disabled={!(this.state.reroll_options.item_selected !== null && this.state.reroll_options.reroll_option !== null && this.state.reroll_options.attribute !== null) || this.state.preforming_action} />
                            : null
                    }

                    {
                        this.state.initial_action !== null ?
                            <PrimaryButton button_label={'Change Action'} on_click={this.clearAll.bind(this)} disabled={this.state.preforming_action} additional_css={'ml-2'}/>
                            : null
                    }

                    <DangerButton button_label={'Remove Queen'} on_click={this.props.remove_crafting} disabled={this.state.preforming_action} additional_css={'ml-2'}/>

                    <a href='/information/random-enchants' target='_blank' className='ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a>
                </div>
            </Fragment>
        )
    }
}
