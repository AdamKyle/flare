import React, { Fragment } from "react";
import Select from "react-select";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../lib/ajax/ajax";
import {formatNumber} from "../../../../lib/game/format-number";
import {ceil} from "lodash";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class QueenOfHearts extends React.Component<any, any> {

    private queenOfHearts: any;

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
            move_options: {
                unique_id: null,
                item_to_move_to_id: null,
                affix_to_move: null,
            },
            preforming_action: false,
            character_uniques: [],
            character_non_uniques: [],
            reroll_cost: {
                gold_dust_dust: 0,
                shards: 0,
            },
            movement_cost: {
                gold_dust_dust: 0,
                shards: 0,
            },
            loading: true,
            error_message: null,
        }

        // @ts-ignore
        this.queenOfHearts = Echo.private('update-queen-of-hearts-panel-' + this.props.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory/uniques').doAjaxCall('get',
            (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    character_uniques: result.data.unique_slots,
                    character_non_uniques: result.data.non_unique_slots
                });
            }, (error: AxiosError) => {console.error(error);});

        // @ts-ignore
        this.queenOfHearts.listen('Game.Core.Events.UpdateQueenOfHeartsPanel', (event: any) => {
            this.setState({
                character_uniques: event.panelData.unique_slots,
                character_non_uniques: event.panelData.non_unique_slots
            });
        });
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
        this.setState({
            reroll_options: {...this.state.reroll_options, ...{reroll_option: data.value}},
        }, () => this.calculateCost());
    }

    setItemForReRoll(data: any) {
        this.setState({
            reroll_options: {...this.state.reroll_options, ...{item_selected: data.value}},
        });
    }

    setAttributeToReRoll(data: any) {
        this.setState({
            reroll_options: {...this.state.reroll_options, ...{attribute: data.value}},
        }, () => this.calculateCost());
    }

    setSelectedItemToMove(data: any) {
        this.setState({
            move_options: {...this.state.move_options, ...{unique_id: data.value}}
        });
    }

    setAffixTypeToMove(data: any) {
        this.setState({
            move_options: {...this.state.move_options, ...{affix_to_move: data.value}}
        }, () => {
            this.calculateMovementCost();
        });
    }

    setItemToMove(data: any) {
        this.setState({
            move_options: {...this.state.move_options, ...{item_to_move_to_id: data.value}}
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

    moveEnchantOptions() {
        let foundSelected = this.state.character_uniques.filter((unique: any) => {
            return unique.id === this.state.move_options.unique_id
        });

        if (foundSelected.length === 0) {
            return [];
        }

        foundSelected = foundSelected[0];
        const options = [];

        if (foundSelected.item.item_prefix !== null) {
            options.push({
                label: 'Prefix',
                value: 'prefix',
            });
        }

        if (foundSelected.item.item_suffix !== null) {
            options.push({
                label: 'Suffix',
                value: 'suffix',
            });
        }

        if (foundSelected.item.affix_count > 1) {
            options.push({
                label: 'Both',
                value: 'all-enchantments',
            });
        }

        return options;
    }

    getSelectedReRollOption() {
        const foundSelected = this.reRollOptions().filter((option: any) => {
            return option.value === this.state.reroll_options.reroll_option
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].label, value: foundSelected[0].value};
        }

        return {label: 'Please select what to re-roll', value: null};
    }

    getAffixToMove() {
        const foundSelected = this.moveEnchantOptions().filter((option: any) => {
            return option.value === this.state.move_options.affix_to_move
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].label, value: foundSelected[0].value};
        }

        return {label: 'Please select what to move', value: null};
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

    getSelectedUnique() {
        const foundSelected = this.state.character_uniques.filter((unique: any) => {
            return unique.id === this.state.move_options.unique_id
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].item.affix_name, value: foundSelected[0].id};
        }

        return {label: 'Please select unique', value: ''}
    }

    itemsToMoveTo() {
        const items = [...this.state.character_uniques, ...this.state.character_non_uniques];

        return items.map((item: any) => {
            return  {
                label: item.item.affix_name,
                value: item.id
            }
        }).filter((item: any) => {
            return item.value !== this.state.move_options.unique_id
        });
    }

    getSelectedItemToMove() {
        const items = [...this.state.character_uniques, ...this.state.character_non_uniques];

        const foundSelected = items.filter((unique: any) => {
            return unique.id === this.state.move_options.item_to_move_to_id
        });

        if (foundSelected.length > 0) {
            return {label: foundSelected[0].item.affix_name, value: foundSelected[0].id};
        }

        return {label: 'Please select item', value: ''}
    }

    uniquesToMove() {
        return this.state.character_uniques.map((unique: any) => {
            return {
                label: unique.item.affix_name,
                value: unique.id,
            }
        })
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
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    this.setState({
                        preforming_action: false,
                    });

                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                    });
                }
            })
        });
    }

    calculateCost() {
        let goldDustCost = 10000;
        let shards       = 100;

        if (this.state.reroll_options.reroll_option === 'all-enchantments') {
            goldDustCost *= 2;
            shards       *= 2;
        }

        if (this.state.reroll_options.attribute === 'everything') {
            goldDustCost += 500;
            shards       += 250;
        } else {
            goldDustCost += 100;
            shards       += 100;
        }

        this.setState({
            reroll_cost: {
                gold_dust_dust: goldDustCost,
                shards: shards,
            },
        });
    }

    calculateMovementCost() {
        let goldDust = 0;
        let shards   = 0;

        if (this.state.move_options.unique_id !== null && this.state.move_options.affix_to_move !== null) {
            let foundSelected = this.state.character_uniques.filter((unique: any) => {
                return unique.id === this.state.move_options.unique_id
            });

            if (foundSelected.length > 0) {
                foundSelected = foundSelected[0];
            }

            if (this.state.move_options.affix_to_move === 'all-enchantments') {

                if (foundSelected.item.item_prefix !== null) {
                    goldDust += foundSelected.item.item_prefix.cost;
                }

                if (foundSelected.item.item_prefix !== null) {
                    goldDust += foundSelected.item.item_suffix.cost;
                }
            } else {
                goldDust += foundSelected.item['item_' + this.state.move_options.affix_to_move].cost
            }
        }

        if (goldDust > 0) {
            goldDust = goldDust / 1000000
            shards   = parseInt(ceil(goldDust * .005).toFixed(0));
        }

        this.setState({
            movement_cost: {
                gold_dust_dust: goldDust,
                shards: shards,
            },
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
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.setState({
                        error_message: response.data.message,
                        preforming_action: false,
                    });
                }
            })
        });
    }

    moveAffixes() {
        this.setState({
            preforming_action: true,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/random-enchant/move').setParameters({
                selected_slot_id: this.state.move_options.unique_id,
                selected_secondary_slot_id: this.state.move_options.item_to_move_to_id,
                selected_affix: this.state.move_options.affix_to_move
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    preforming_action: false,
                    character_uniques: result.data.unique_slots,
                    character_non_uniques: result.data.non_unique_slots,
                    move_options: {
                        unique_id: null,
                        item_to_move_to_id: null,
                        affix_to_move: null
                    }
                });
            }, (error: AxiosError) => {
                if (typeof error.response !== 'undefined') {
                    const response = error.response;

                    this.clearAll();

                    this.setState({
                        error_message: response.data.message,
                        preforming_action: false,
                    });
                }
            })
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
            },
            move_options: {
                unique_id: null,
                item_to_move_to_id: null,
                affix_to_move: null,
            },
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
                            this.state.error_message !== null ?
                                <DangerAlert additional_css={'mb-4 mt-2'}>
                                    {this.state.error_message}
                                </DangerAlert>
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
                            this.state.initial_action === 'move-enchants' ?
                                <Fragment>
                                    <Select
                                        onChange={this.setSelectedItemToMove.bind(this)}
                                        options={this.uniquesToMove()}
                                        menuPosition={'absolute'}
                                        menuPlacement={'bottom'}
                                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                        menuPortalTarget={document.body}
                                        value={this.getSelectedUnique()}
                                    />

                                    {
                                        this.state.move_options.unique_id !== null && this.state.move_options.unique_id !== '' ?
                                            <div className='mt-2'>
                                                <Select
                                                    onChange={this.setAffixTypeToMove.bind(this)}
                                                    options={this.moveEnchantOptions()}
                                                    menuPosition={'absolute'}
                                                    menuPlacement={'bottom'}
                                                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                                    menuPortalTarget={document.body}
                                                    value={this.getAffixToMove()}
                                                />
                                            </div>
                                        : null
                                    }

                                    {
                                        this.state.move_options.affix_to_move !== null && this.state.move_options.affix_to_move !== '' ?
                                            <div className='mt-2'>
                                                <Select
                                                    onChange={this.setItemToMove.bind(this)}
                                                    options={this.itemsToMoveTo()}
                                                    menuPosition={'absolute'}
                                                    menuPlacement={'bottom'}
                                                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                                    menuPortalTarget={document.body}
                                                    value={this.getSelectedItemToMove()}
                                                />
                                            </div>
                                        : null
                                    }

                                    {
                                        this.state.movement_cost.gold_dust !== 0 && this.state.movement_cost.shards !== 0 ?
                                            <p className='mt-2 text-orange-600 dark:text-orange-500'>
                                                <strong>Gold Dust Cost</strong>: {formatNumber(this.state.movement_cost.gold_dust_dust)}, <strong>Shards Cost</strong>: {formatNumber(this.state.movement_cost.shards)}
                                            </p>
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

                <div className={'text-center lg:ml-[-100px] mt-3 mb-3'}>

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
                        this.state.initial_action === 'move-enchants' ?
                            <PrimaryButton button_label={'Move Enchants'} on_click={this.moveAffixes.bind(this)} disabled={!(this.state.move_options.unique_id !== null && this.state.move_options.item_to_move_to_id !== null && this.state.move_options.affix_to_move !== null) || this.state.preforming_action} />
                        : null
                    }

                    {
                        this.state.initial_action !== null ?
                            <PrimaryButton button_label={'Change Action'} on_click={this.clearAll.bind(this)} disabled={this.state.preforming_action} additional_css={'ml-2'}/>
                        : null
                    }

                    <DangerButton button_label={'Remove Queen'} on_click={this.props.remove_crafting} disabled={this.state.preforming_action} additional_css={'ml-2'}/>

                    <a href='/information/random-enchants' target='_blank' className='hidden lg:block ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a>
                </div>

                <div className='text-center mt-4 block lg:hidden'>
                    <a href='/information/random-enchants' target='_blank' className='block lg:hidden ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a>
                </div>
            </Fragment>
        )
    }
}
