import React, {Fragment} from "react";
import {craftingGetEndPoints, craftingPostEndPoints} from "../../../../lib/game/actions/crafting-type-url";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import Select from "react-select";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";

export default class WorkBench extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            selected_item: null,
            selected_alchemy_item: null,
            selected_item_name: null,
            inventory_items: [],
            alchemy_items: [],
        }
    }

    componentDidMount() {
        const url = craftingGetEndPoints('workbench', this.props.character_id);

        (new Ajax()).setRoute(url).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                inventory_items: result.data.items,
                alchemy_items: result.data.alchemy_items,
            });
        }, (error: AxiosError) => {

        });
    }

    applyHolyOil() {
        const url = craftingPostEndPoints('workbench', this.props.character_id);

        this.setState({
            loading: true

        },() => {
            (new Ajax()).setRoute(url).setParameters({
                item_id: this.state.selected_item,
                alchemy_item_id: this.state.selected_alchemy_item,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    inventory_items: result.data.items,
                    alchemy_items: result.data.alchemy_items,
                }, () => {
                    const foundItem = this.state.inventory_items.filter((slot: any) => {
                        return slot.item.name === this.state.selected_item_name;
                    });

                    if (foundItem.length > 0) {
                        this.setState({
                            selected_item: foundItem[0].item.id,
                        })
                    }
                });
            }, (error: AxiosError) => {

            })
        });
    }

    setItem(data: any) {
        this.setState({
            selected_item: parseInt(data.value)
        }, () => {
            const foundItem = this.state.inventory_items.filter((slot: any) => {
                return slot.item.id === parseInt(data.value);
            });

            if (foundItem.length > 0) {
                this.setState({
                    selected_item_name: foundItem[0].item.affix_name
                });
            }
        });
    }

    setAlchemyItem(data: any) {
        this.setState({
            selected_alchemy_item: parseInt(data.value)
        });
    }

    buildItems() {
        return this.state.inventory_items.map((slot: any) => {
            return {
                label: slot.item.affix_name,
                value: slot.item.id
            }
        });
    }

    selectedItem() {
        if (this.state.selected_item !== null) {
            const foundItem = this.state.inventory_items.filter((slot: any) => {
                return slot.item.id === this.state.selected_item;
            });

            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item.affix_name,
                    value: this.state.selected_item,
                }
            }
        }

        return {label: 'Please Select an Item', value: 0}
    }

    buildAlchemicalItems() {
        return this.state.alchemy_items.map((slot: any) => {
            return {
                label: slot.item.name,
                value: slot.item.id
            }
        });
    }

    selectedAlchemyItem() {
        if (this.state.selected_alchemy_item !== null) {
            const foundItem = this.state.alchemy_items.filter((slot: any) => {
                return slot.item.id === this.state.selected_alchemy_item;
            });

            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item.name,
                    value: this.state.selected_alchemy_item,
                }
            }
        }

        return {label: 'Please Selected an Alchemical Item', value: 0}
    }

    clearCrafting() {
        this.props.remove_crafting();
    }

    isApplyButtonDisabled() {
        return this.state.loading || this.state.selected_item === null || this.state.selected_alchemical_item === null || this.props.cannot_craft
    }

    render() {
        return (
            <Fragment>
                <div className='mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]'>
                    <div className='col-start-1 col-span-2'>
                        <Select
                            onChange={this.setItem.bind(this)}
                            options={this.buildItems()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.selectedItem()}
                        />

                    </div>
                    <div className='col-start-1 col-span-2'>
                        <Select
                            onChange={this.setAlchemyItem.bind(this)}
                            options={this.buildAlchemicalItems()}
                            menuPosition={'absolute'}
                            menuPlacement={'bottom'}
                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                            menuPortalTarget={document.body}
                            value={this.selectedAlchemyItem()}
                        />
                    </div>
                </div>

                <div className='m-auto w-1/2 md:relative left-[-20px]'>
                    {
                        this.state.loading ?
                            <LoadingProgressBar />
                            : null
                    }
                </div>

                <div className={'text-center md:ml-[-100px] mt-3 mb-3'}>
                    <PrimaryButton button_label={'Apply Oil'} on_click={this.applyHolyOil.bind(this)} disabled={this.isApplyButtonDisabled()} />
                    <DangerButton button_label={'Remove'}
                                  on_click={this.clearCrafting.bind(this)}
                                  additional_css={'ml-2'}
                                  disabled={this.state.loading || this.props.cannot_craft} />
                    <a href='/information/holy-items' target='_blank' className='ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a>
                </div>
            </Fragment>
        );
    }
}
