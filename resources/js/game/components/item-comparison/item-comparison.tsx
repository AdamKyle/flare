import React, {ReactNode} from "react";
import ItemComparisonProps from "./types/item-comparison-props";
import Comparison from "./comparison";
import ItemNameColorationText from "../items/item-name/item-name-coloration-text";
import PrimaryOutlineButton from "../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../ui/buttons/success-outline-button";
import ComparisonDetails from "./deffinitions/comparison-details";
import ExpandedComparison from "./expanded-comparison";
import {ItemType} from "../items/enums/item-type";
import WarningAlert from "../ui/alerts/simple-alerts/warning-alert";
import { startCase } from "lodash";
import {formatNumber} from "../../lib/game/format-number";
import clsx from "clsx";

const twoHandedWeapons = [
    ItemType.STAVE,
    ItemType.BOW,
    ItemType.HAMMER
];

export default class ItemComparison extends React.Component<ItemComparisonProps, any> {

    constructor(props: ItemComparisonProps) {
        super(props);

        this.state = {
            loading: false,
            success_message: null,
            error_message: null,
            expanded_comparison_details: null,
        }
    }

    componentDidUpdate(prevProps: Readonly<ItemComparisonProps>) {

        if (prevProps.is_showing_expanded_comparison && !this.props.is_showing_expanded_comparison) {
            this.setState({
                expanded_comparison_details: null
            })
        }
    }

    renderEquipButtons(isInline: boolean, comparisonItemType?: ItemType): ReactNode {
        const singleHandedItems = [
            ItemType.FAN,
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.GUN,
            ItemType.SCRATCH_AWL,
            ItemType.SHIELD,
        ]

        if (comparisonItemType) {
            if (twoHandedWeapons.includes(comparisonItemType)) {
                return;
            }
        }

        const itemType = this.props.comparison_info.itemToEquip.type as ItemType;

        if (singleHandedItems.includes(itemType)) {
            return (
                <div className='flex justify-center'>
                    <PrimaryOutlineButton button_label={'Left Hand'} on_click={() => this.props.handle_replace_action('left-hand')} />
                    <PrimaryOutlineButton button_label={'Right Hand'} on_click={() => this.props.handle_replace_action('right-hand')} additional_css={'ml-4'} />
                </div>
            )
        }

        if (twoHandedWeapons.includes(itemType)) {
            return (
                 <>
                     {
                         isInline ?
                             <WarningAlert additional_css='my-4'>
                                 This is a two handed weapon, it will replace both hands when equipped.
                             </WarningAlert>
                         : null
                     }

                    <div className='flex justify-center'>
                        <PrimaryOutlineButton button_label={'Left Hand'} on_click={() => this.props.handle_replace_action('left-hand')} />
                        <PrimaryOutlineButton button_label={'Right Hand'} on_click={() => this.props.handle_replace_action('right-hand')} additional_css={'ml-4'} />
                    </div>
                 </>
            )
        }

        if (ItemType.RING === itemType) {
            return (
                <div className='flex justify-center'>
                    <PrimaryOutlineButton button_label={'Ring One'} on_click={() => this.props.handle_replace_action('ring-one')} />
                    <PrimaryOutlineButton button_label={'Ring Two'} on_click={() => this.props.handle_replace_action('ring-two')} additional_css={'ml-4'} />
                </div>
            )
        }

        if ([ItemType.SPELL_DAMAGE, ItemType.SPELL_HEALING].includes(itemType)) {
            return (
                <div className='flex justify-center'>
                    <PrimaryOutlineButton button_label={'Spell One'} on_click={() => this.props.handle_replace_action('spell-one')} />
                    <PrimaryOutlineButton button_label={'Spell Two'} on_click={() => this.props.handle_replace_action('spell-two')} additional_css={'ml-4'} />
                </div>
            )
        }

        return (
            <div className='flex justify-center'>
                <PrimaryOutlineButton button_label={'Equip'} on_click={() => this.props.handle_replace_action(this.props.comparison_info.itemToEquip.type)} />
                <div className='ml-4 mt-2'>
                    This item has a default position of <strong>{startCase(this.props.comparison_info.itemToEquip.default_position)}</strong> selected for you.
                </div>
            </div>
        );
    }

    showExpandedComparison(comparison: ComparisonDetails) {
        this.setState({
            expanded_comparison_details: comparison
        }, () => {
            this.props.manage_show_expanded_comparison();
        });
    }

    renderExpandedComparison() {
        return <ExpandedComparison comparison_details={this.state.expanded_comparison_details}  />
    }

    renderColumns() {
        return (
            <>
                <div className={'my-4'}>
                    Looking to purchase: <strong>{this.props.comparison_info.itemToEquip.affix_name}</strong>, below is your comparison data, if you
                    were to equip this item in the equipped items slot. This fabulous item will only cost you: {formatNumber(this.props.comparison_info.itemToEquip.cost)} gold!
                </div>
                <div className='grid md:grid-cols-2 gap-2'>
                    <div>
                        <h3 className={'mt-4'}>
                            <ItemNameColorationText item={this.props.comparison_info.details[0]} custom_width={true} />
                        </h3>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <Comparison comparison={this.props.comparison_info.details[0]} />
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <div className="flex items-center">
                            <div className='mr-2'>
                                <PrimaryOutlineButton button_label={'See Expanded Details'} on_click={() => this.showExpandedComparison(this.props.comparison_info.details[0])} />
                            </div>
                            <div>
                                <SuccessOutlineButton button_label={this.props.replace_button_text} on_click={() => this.props.handle_replace_action(this.props.comparison_info.details[0].position)} />
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 className={'mt-4'}>
                            <ItemNameColorationText item={this.props.comparison_info.details[1]} custom_width={true} />
                        </h3>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <Comparison comparison={this.props.comparison_info.details[1]} />
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                        <div className="flex items-center">
                            <div className='mr-2'>
                                <PrimaryOutlineButton button_label={'See Expanded Details'} on_click={() => this.showExpandedComparison(this.props.comparison_info.details[1])} />
                            </div>
                            <div>
                                <SuccessOutlineButton button_label={this.props.replace_button_text} on_click={() => this.props.handle_replace_action(this.props.comparison_info.details[1].position)} />
                            </div>
                        </div>
                    </div>
                </div>
            </>
        )
    }

    renderSingleComparison() {
        const nonArmourItems = [
            ItemType.FAN,
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.GUN,
            ItemType.SCRATCH_AWL,
            ItemType.SHIELD,
            ItemType.STAVE,
            ItemType.HAMMER,
            ItemType.BOW,
            ItemType.RING,
            ItemType.SPELL_HEALING,
            ItemType.SPELL_DAMAGE,
        ];

        return (
            <div className='mr-auto ml-auto w-3/5'>
                <div className={'my-4'}>
                    Looking to purchase: <strong>{this.props.comparison_info.itemToEquip.affix_name}</strong>, below is
                    your comparison data, if you
                    were to equip this item in the equipped items slot. This fabulous item will only cost
                    you: {formatNumber(this.props.comparison_info.itemToEquip.cost)} gold!
                </div>

                <h3>
                    <ItemNameColorationText item={this.props.comparison_info.details[0]} custom_width={true}/>
                </h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <Comparison comparison={this.props.comparison_info.details[0]}/>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>

                <div className="flex items-center">
                    <div className='mr-2'>
                        <PrimaryOutlineButton button_label={'See Expanded Details'}
                                              on_click={() => this.showExpandedComparison(this.props.comparison_info.details[0])}/>
                    </div>
                    <div>
                        <SuccessOutlineButton button_label={this.props.replace_button_text}
                                              on_click={() => this.props.handle_replace_action(this.props.comparison_info.details[0].position)}/>
                    </div>
                    {
                        nonArmourItems.includes(this.props.comparison_info.itemToEquip.type as ItemType) ?
                            <>
                                <div className={clsx('px-5', {
                                    'hidden': twoHandedWeapons.includes(this.props.comparison_info.details[0].type),
                                })}>Or (Select position)</div>

                                {this.renderEquipButtons(false, this.props.comparison_info.details[0].type)}
                            </>
                            : null
                    }
                </div>
            </div>
        )
    }

    renderComparison() {

        if (this.props.comparison_info.details.length === 0) {
            return <div className='w-full md:max-w-3/5 md:mr-auto md:ml-auto'>
                <p className='my-4 text-center'>
                    You don't have anything equipped in this slot. Why not buy and equip
                    the <strong>{this.props.comparison_info.itemToEquip.affix_name}</strong> for the low, low price of: {formatNumber(this.props.comparison_info.itemToEquip.cost)} gold?
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4 w-1/3 ml-auto mr-auto"></div>
                {this.renderEquipButtons(true)}
            </div>
        }

        if (this.props.comparison_info.details.length > 1) {
            return this.renderColumns();
        }

        return this.renderSingleComparison();
    }

    render() {
        return <>{this.state.expanded_comparison_details !== null ? this.renderExpandedComparison() : this.renderComparison()}</>
    }
}
