import React, {Fragment} from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import {craftingGetEndPoints} from "../../../lib/game/actions/crafting-type-url";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import clsx from "clsx";

export default class CraftingSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_item: null,
            selected_type: null,
            loading: false,
            craftable_items: [],
        }
    }

    componentDidMount() {
        if (this.props.type !== 'craft') {
            const url = craftingGetEndPoints(this.props.type, this.props.character_id);

            (new Ajax()).setRoute(url).doAjaxCall('get', (result: AxiosResponse) => {
                console.log(result.data);
            }, (error: AxiosError) => {

            })
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
                const url = craftingGetEndPoints(this.props.type, this.props.character_id);

                (new Ajax()).setRoute(url).setParameters({
                    params: {crafting_type: this.state.selected_type}
                }).doAjaxCall('get', (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        craftable_items: result.data.items,
                    });
                }, (error: AxiosError) => {

                });
            }
        })
    }

    changeType() {
        this.setState({
            selected_type: null,
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
            {
                label: 'Artifacts',
                value: 'artifact',
            }
        ]
    }

    render() {
        return (
            <Fragment>
                <div className='mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]'>
                    <div className='cols-start-1 col-span-2'>
                        {
                            this.props.type === 'craft' && this.state.selected_type === null ?
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

                    </div>
                    <div className={clsx({'cols-start-3': this.props.type !== 'craft'}, {'hidden': this.props.type === 'craft'})}>
                        <PrimaryButton button_label={'Craft'} on_click={this.craft.bind(this)} disabled={this.state.loading || this.state.selected_item === null} />

                        <DangerButton button_label={'Remove'}
                                      on_click={this.clearCrafting.bind(this)}
                                      additional_css={'ml-2'}
                                      disabled={this.state.loading} />
                    </div>
                </div>
                {
                    this.props.type === 'craft' && this.state.selected_type !== null ?
                        <div className={'text-center md:ml-[-100px] mt-3 mb-3'}>
                            <PrimaryButton button_label={'Craft'} on_click={this.craft.bind(this)} disabled={this.state.loading || this.state.selected_item === null} />
                            <PrimaryButton button_label={'Change Type'} on_click={this.changeType.bind(this)} disabled={this.state.loading} additional_css={'ml-2'} />
                            <DangerButton button_label={'Remove'}
                                          on_click={this.clearCrafting.bind(this)}
                                          additional_css={'ml-2'}
                                          disabled={this.state.loading} />
                        </div>
                    : null
                }
            </Fragment>
        )
    }

}
