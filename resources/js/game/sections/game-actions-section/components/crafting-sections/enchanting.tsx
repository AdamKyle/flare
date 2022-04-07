import React, {Fragment} from "react";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import {craftingGetEndPoints} from "../../../../lib/game/actions/crafting-type-url";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosResponse} from "axios";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";

export default class Enchanting extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            selected_item: null,
            selected_prefix: null,
            selected_suffix: null,
            enchantable_items: [],
            enchantments: [],
        }
    }

    componentDidMount() {
        const url = craftingGetEndPoints('enchant', this.props.character_id);

        (new Ajax()).setRoute(url).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                enchantable_items: result.data.affixes,
                enchantments: result.data.character_inventory,
            });
        });
    }

    clearCrafting() {
        this.props.remove_crafting();
    }

    craft() {

    }

    cannotCraft() {
        return this.state.loading || this.state.selected_item === null || this.props.cannot_craft || this.state.selected_prefix == null || this.state.selected_suffix === null;
    }

    render() {
        return (
            <Fragment>
                <div className='mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]'>
                    <div>
                        Items
                    </div>
                    <div>
                        Prefix
                    </div>
                    <div>
                        Suffix
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
                    <PrimaryButton button_label={'Craft'} on_click={this.craft.bind(this)} disabled={this.cannotCraft()} />
                    <DangerButton button_label={'Remove'}
                                  on_click={this.clearCrafting.bind(this)}
                                  additional_css={'ml-2'}
                                  disabled={this.state.loading || this.props.cannot_craft} />
                </div>
            </Fragment>
        )
    }
}
