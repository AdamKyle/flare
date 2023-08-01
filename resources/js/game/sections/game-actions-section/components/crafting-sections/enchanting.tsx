import React, {Fragment} from "react";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import {craftingGetEndPoints, craftingPostEndPoints} from "../../../../lib/game/actions/crafting-type-url";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import Select from "react-select";
import {formatNumber} from "../../../../lib/game/format-number";
import {isEqual} from "lodash";
import {generateServerMessage} from "../../../../lib/ajax/generate-server-message";
import DangerLinkButton from "../../../../components/ui/buttons/danger-link-button";
import {
    EnchantingState,
    Enchantment,
    ItemToEnchant
} from "../../../../lib/game/actions/crafting-sections/enchanting-state";
import EnchantingProps from "../../../../lib/game/actions/crafting-sections/enchanting-props";
import CraftingXp from "../crafting-xp";

export default class Enchanting extends React.Component<EnchantingProps, EnchantingState> {

    constructor(props: EnchantingProps) {
        super(props);

        this.state = {
            loading: true,
            selected_item: null,
            selected_prefix: null,
            selected_suffix: null,
            enchantable_items: [],
            enchantments: [],
            skill_xp: {
                current_xp: 0,
                next_level_xp: 0,
                skill_name: 'Unknown',
                level: 1,
            }
        }
    }

    componentDidMount() {
        const url = craftingGetEndPoints('enchant', this.props.character_id);

        (new Ajax()).setRoute(url).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                enchantable_items: result.data.affixes.character_inventory,
                enchantments: result.data.affixes.affixes,
                skill_xp: result.data.skill_xp,
            });
        });
    }

    clearCrafting() {
        this.props.remove_crafting();
    }

    enchant() {
        this.setState({
            loading: true,
        }, () => {
            const url = craftingPostEndPoints('enchant', this.props.character_id);

            (new Ajax()).setRoute(url).setParameters({
                slot_id: this.state.selected_item,
                affix_ids: [this.state.selected_prefix, this.state.selected_suffix]
            }).doAjaxCall('post', (result: AxiosResponse) => {
                const oldEnchantments = JSON.parse(JSON.stringify(this.state.enchantments));

                this.setState({
                    loading: false,
                    enchantable_items: result.data.affixes.character_inventory,
                    enchantments: result.data.affixes.affixes,
                    skill_xp: result.data.skill_xp,
                }, () => {

                    if (!isEqual(oldEnchantments, result.data.affixes)) {
                        generateServerMessage('new_items', 'You have new enchantments. Check the list(s)!');
                    }

                    if (result.data.character_inventory.length > 0) {
                        this.setState({
                            selected_item: result.data.character_inventory[0].slot_id
                        });
                    }
                });
            }, (error: AxiosError) => {

            })
        })
    }

    setSelectedItem(data: any) {
        this.setState({
            selected_item: parseInt(data.value)
        })
    }

    setPrefix(data: any) {
        this.setState({
            selected_prefix: parseInt(data.value)
        })
    }

    setSuffix(data: any) {
        this.setState({
            selected_suffix: parseInt(data.value)
        })
    }

    renderItemsToEnchantSelection() {
        return this.state.enchantable_items.map((item: any) => {
            return {
                label: item.item_name,
                value: item.slot_id,
            }
        });
    }

    resetPrefixes() {
        this.setState({
            selected_prefix: null,
        })
    }

    resetSuffixes() {
        this.setState({
            selected_suffix: null,
        })
    }

    renderEnchantmentOptions(type: 'prefix' | 'suffix') {
        const enchantments = this.state.enchantments.filter((enchantment: any) => {
            return enchantment.type === type;
        });

        return enchantments.map((enchantment: any) => {
            return {
                label: enchantment.name + ' Cost: ' + formatNumber(enchantment.cost),
                value: enchantment.id,
            }
        });
    }

    selectedItemToEnchant() {
        if (this.state.selected_item !== null) {

            // @ts-ignore
            const foundItem: ItemToEnchant[] = this.state.enchantable_items.filter((item: any) => {
                return item.slot_id === this.state.selected_item;
            });

            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item_name,
                    value: this.state.selected_item
                }
            }
        }

        return {
            label: 'Please select item.',
            value: 0,
        }
    }

    selectedEnchantment(type: 'prefix' | 'suffix') {
        // @ts-ignore
        const selectedType: number | null = this.state['selected_' + type];

        if (selectedType !== null) {

            // @ts-ignore
            const foundEnchantment: Enchantment[] = this.state.enchantments.filter((item: any) => {
                return item.id === selectedType;
            });

            if (foundEnchantment.length > 0) {
                return {
                    label: foundEnchantment[0].name + ' Cost: ' + formatNumber(foundEnchantment[0].cost),
                    value: selectedType
                }
            }
        }

        return {
            label: 'Please select '+type+' enchantment.',
            value: 0,
        }
    }

    cannotCraft() {
        return this.state.loading || this.state.selected_item === null || this.props.cannot_craft || (this.state.selected_prefix === null && this.state.selected_suffix === null);
    }

    render() {
        return (
            <Fragment>
                <div className='mt-2 grid lg:grid-cols-3 gap-2 lg:ml-[120px]'>
                    <div className='col-start-1 col-span-2'>
                        <Select
                            onChange={this.setSelectedItem.bind(this)}
                            options={this.renderItemsToEnchantSelection()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.selectedItemToEnchant()}
                        />
                    </div>
                    <div className='col-start-1 col-span-2'>
                        <div className='lg:hidden grid grid-cols-3'>
                            <div className='col-start-1 col-span-2'>
                                <Select
                                    onChange={this.setPrefix.bind(this)}
                                    options={this.renderEnchantmentOptions('prefix')}
                                    menuPosition={'absolute'}
                                    menuPlacement={'bottom'}
                                    styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                    menuPortalTarget={document.body}
                                    value={this.selectedEnchantment('prefix')}
                                />
                            </div>
                            <div className='cols-start-3 cols-end-3 mt-2 ml-4'>
                                <DangerLinkButton button_label={'Clear'} on_click={this.resetPrefixes.bind(this)} />
                            </div>
                        </div>
                        <div className='hidden lg:block'>
                            <Select
                                onChange={this.setPrefix.bind(this)}
                                options={this.renderEnchantmentOptions('prefix')}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.selectedEnchantment('prefix')}
                            />
                        </div>
                    </div>
                    <div className='hidden lg:block cols-start-3 cols-end-3 mt-2'>
                        <DangerLinkButton button_label={'Clear'} on_click={this.resetPrefixes.bind(this)} />
                    </div>
                    <div className='col-start-1 col-span-2'>
                        <div className='lg:hidden grid grid-cols-3'>
                            <div className='col-start-1 col-span-2'>
                                <Select
                                    onChange={this.setSuffix.bind(this)}
                                    options={this.renderEnchantmentOptions('suffix')}
                                    menuPosition={'absolute'}
                                    menuPlacement={'bottom'}
                                    styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                    menuPortalTarget={document.body}
                                    value={this.selectedEnchantment('suffix')}
                                />
                            </div>
                            <div className='cols-start-3 cols-end-3 mt-2 ml-4'>
                                <DangerLinkButton button_label={'Clear'} on_click={this.resetSuffixes.bind(this)} />
                            </div>
                        </div>
                        <div className='hidden lg:block'>
                            <Select
                                onChange={this.setSuffix.bind(this)}
                                options={this.renderEnchantmentOptions('suffix')}
                                menuPosition={'absolute'}
                                menuPlacement={'bottom'}
                                styles={{menuPortal: (base: any) => ({...base, zIndex: 9999, color: '#000000'})}}
                                menuPortalTarget={document.body}
                                value={this.selectedEnchantment('suffix')}
                            />
                        </div>
                    </div>
                    <div className='hidden lg:block cols-start-3 cols-end-3 mt-2'>
                        <DangerLinkButton button_label={'Clear'} on_click={this.resetSuffixes.bind(this)} />
                    </div>
                </div>
                <div className='m-auto w-1/2 relative left-[-30px] lg:left-[-60px]'>
                    {
                        this.state.loading ?
                            <LoadingProgressBar />
                        : null
                    }

                    {
                        this.state.enchantments.length > 0 ?
                            <CraftingXp skill_xp={this.state.skill_xp} />
                        : null
                    }
                </div>
                <div className={'text-center md:ml-[-100px] mt-3 mb-3'}>
                    <PrimaryButton button_label={'Enchant'} on_click={this.enchant.bind(this)} disabled={this.cannotCraft()} />
                    <DangerButton button_label={'Remove'}
                                  on_click={this.clearCrafting.bind(this)}
                                  additional_css={'ml-2'}
                                  disabled={this.state.loading || this.props.cannot_craft} />
                    <a href='/information/enchanting' target='_blank' className='ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a>
                </div>
            </Fragment>
        )
    }
}
