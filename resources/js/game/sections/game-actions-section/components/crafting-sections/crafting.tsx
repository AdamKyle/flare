import React, {Fragment} from "react";
import {craftingGetEndPoints, craftingPostEndPoints} from "../../../../lib/game/actions/crafting-type-url";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import {formatNumber} from "../../../../lib/game/format-number";
import Select from "react-select";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import {getCraftingType} from "../../../../lib/game/actions/crafting-types";
import {isEqual} from "lodash";
import {generateServerMessage} from "../../../../lib/ajax/generate-server-message";
import CraftingXp from "../crafting-xp";

export default class Crafting extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_item: null,
            selected_type: null,
            loading: false,
            craftable_items: [],
            skill_xp: {
                curent_xp: 0,
                next_level_xp: 0,
                skill_name: 'Unknown',
                level: 1,
            }
        }
    }

    setItemToCraft(data: any) {
        const foundItem = this.state.craftable_items.filter((item: any) => {
            return item.id === parseInt(data.value)
        });

        if (foundItem.length > 0) {
            this.setState({
                selected_item: foundItem[0]
            });
        }
    }

    setTypeToCraft(data: any) {
        this.setState({
            selected_type: data.value,
            loading: true,
        }, () => {
            if (this.state.selected_type !== null && this.state.selected_type !== '') {
                const url = craftingGetEndPoints('craft', this.props.character_id);

                (new Ajax()).setRoute(url).setParameters({
                    crafting_type: this.state.selected_type
                }).doAjaxCall('get', (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        craftable_items: result.data.items,
                        skill_xp: result.data.xp
                    });
                }, (error: AxiosError) => {

                });
            }
        })
    }

    changeType() {
        this.setState({
            selected_type: null,
            selected_item: null,
            craftable_items: [],
        });
    }

    buildItems() {
        return this.state.craftable_items.map((item: any) => {
            return {
                label: item.name + ' Gold Cost: ' + formatNumber(item.cost),
                value: item.id,
            }
        })
    }

    defaultItem() {

        if (this.state.selected_item !== null) {
            return {
                label: this.state.selected_item.name + ' Gold Cost: ' + formatNumber(this.state.selected_item.cost),
                value: this.state.selected_item.id,
            }
        }

        return {label: 'Please select item to craft', value: 0};
    }

    craft() {
        this.setState({
            loading: true,
        }, () => {
            const url = craftingPostEndPoints('craft', this.props.character_id);

            (new Ajax()).setRoute(url).setParameters({
                item_to_craft: this.state.selected_item.id,
                type: getCraftingType(this.state.selected_item.type),
            }).doAjaxCall('post', (result: AxiosResponse) => {
                const oldCraftableItems = JSON.parse(JSON.stringify(this.state.craftable_items));

                this.setState({
                   loading: false,
                   craftable_items: result.data.items,
                   skill_xp: result.data.xp
                }, () => {
                   if (!isEqual(oldCraftableItems, result.data.items)) {
                       generateServerMessage('new_items', 'You have new items to craft. Check the list!');
                   }
                });
            }, (error: AxiosError) => {

            });
        })
    }

    clearCrafting() {
        this.props.remove_crafting();
    }

    defaultCraftingType() {
        return {label: 'Please select type to craft', value: ''};
    }

    renderCraftingTypeSelection() {
        return [
            {
                label: 'Weapons',
                value: 'weapon',
            },
            {
                label: 'Staves',
                value: 'stave',
            },
            {
                label: 'Hammers',
                value: 'hammer',
            },
            {
                label: 'Bows',
                value: 'bow',
            },
            {
                label: 'Armour',
                value: 'armour',
            },
            {
                label: 'Rings',
                value: 'ring',
            },
            {
                label: 'Spells',
                value: 'spell',
            },
        ]
    }

    render() {
        return (
            <Fragment>
                <div className='mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]'>
                    <div className='lg:cols-start-1 lg:col-span-2'>
                        {
                            this.state.selected_type === null ?
                                <Fragment>
                                    <Select
                                        onChange={this.setTypeToCraft.bind(this)}
                                        options={this.renderCraftingTypeSelection()}
                                        menuPosition={'absolute'}
                                        menuPlacement={'bottom'}
                                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                        menuPortalTarget={document.body}
                                        value={this.defaultCraftingType()}
                                    />
                                    <p className='mt-3 text-sm'>
                                        When it comes to weapons there are general "weapons" that any one can use, then there are specialty weapons: Hammers, Staves and Bows.
                                        For Weapon Crafting, you can craft ANY of these types to gain levels.
                                    </p>
                                </Fragment>
                            :
                                <Select
                                    onChange={this.setItemToCraft.bind(this)}
                                    options={this.buildItems()}
                                    menuPosition={'absolute'}
                                    menuPlacement={'bottom'}
                                    styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                    menuPortalTarget={document.body}
                                    value={this.defaultItem()}
                                />
                        }


                        {
                            this.state.loading ?
                                <LoadingProgressBar />
                                : null
                        }

                        {
                            this.state.craftable_items.length > 0 ?
                                <CraftingXp skill_xp={this.state.skill_xp} />
                            : null
                        }
                    </div>
                </div>
                {
                    this.props.is_small ? 
                        <div className={'mt-3 mb-3 grid text-center'}>
                            <PrimaryButton additional_css="mb-2" button_label={'Craft'} on_click={this.craft.bind(this)} disabled={this.state.loading || this.state.selected_item === null || this.props.cannot_craft} />
                            <PrimaryButton button_label={'Change Type'} on_click={this.changeType.bind(this)} disabled={this.state.loading || this.state.selected_type == null || this.props.cannot_craft} additional_css={'lg:ml-2 mb-2'} />
                            <DangerButton button_label={'Close'}
                                        on_click={this.clearCrafting.bind(this)}
                                        additional_css={'lg:ml-2'}
                                        disabled={this.state.loading || this.props.cannot_craft} />
                            <a href='/information/crafting' target='_blank' className='relative top-[20px] md:top-[0px] ml-2'>Help <i
                                className="fas fa-external-link-alt"></i></a>
                        </div>
                    :
                        <div className={'text-center lg:ml-[-100px] mt-3 mb-3'}>
                            <PrimaryButton additional_css="mb-2" button_label={'Craft'} on_click={this.craft.bind(this)} disabled={this.state.loading || this.state.selected_item === null || this.props.cannot_craft} />
                            <PrimaryButton button_label={'Change Type'} on_click={this.changeType.bind(this)} disabled={this.state.loading || this.state.selected_type == null || this.props.cannot_craft} additional_css={'lg:ml-2 mb-2'} />
                            <DangerButton button_label={'Close'}
                                        on_click={this.clearCrafting.bind(this)}
                                        additional_css={'ml-2'}
                                        disabled={this.state.loading || this.props.cannot_craft} />
                            <a href='/information/crafting' target='_blank' className='relative top-[20px] md:top-[0px] ml-2'>Help <i
                                className="fas fa-external-link-alt"></i></a>
                        </div>
                }
            </Fragment>
        )
    }

}
