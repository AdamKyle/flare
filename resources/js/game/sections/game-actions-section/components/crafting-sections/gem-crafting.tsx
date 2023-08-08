import React, {Fragment} from "react";
import Ajax from "../../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import Select from "react-select";
import {formatNumber} from "../../../../lib/game/format-number";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import CraftingXp from "../crafting-xp";

export default class GemCrafting extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            tiersForCrafting: [],
            selectedTier: 0,
            tierCost: {
                copper_coin_cost: 0,
                shards_cost: 0,
                gold_dust_cost: 0,
            },
            errorMessage: null,
            loading: true,
            isCrafting: false,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('gem-crafting/craftable-tiers/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                tiersForCrafting: result.data.tiers,
                skill_xp: result.data.skill_xp,
                loading: false,
            });
        }, (error: AxiosError) => {
            console.error(error);
        });
    }

    craft() {
        this.setState({
            isCrafting: true,
            errorMessage: null,
        }, () => {
            (new Ajax()).setRoute('gem-crafting/craft/' + this.props.character_id).setParameters({
                tier: this.state.selectedTier,
            }).doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    tiersForCrafting: result.data.tiers,
                    skill_xp: result.data.skill_xp,
                    isCrafting: false,
                });
            }, (error: AxiosError) => {
                this.setState({
                    isCrafting: false,
                }, () => {
                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        this.setState({
                            errorMessage: response.data.message,
                        });
                    }
                })
            });
        });
    }

    setTierToCraft(data: any) {

        if (data.value === 0) {
            this.setState({
                selectedTier: 0,
                tierCost: {
                    copper_coin_cost: 0,
                    shards_cost: 0,
                    gold_dust_cost: 0,
                }
            });

            return;
        }

        this.setState({
            selectedTier: data.value,
        }, () => {
            const tierData = this.state.tiersForCrafting[data.value - 1];

            const cost = {
                copper_coin_cost: tierData.cost.copper_coins,
                shards_cost: tierData.cost.shards,
                gold_dust_cost: tierData.cost.gold_dust,
            }

            this.setState({
                tierCost: cost,
            });
        });
    }

    craftingTiers() {
        const tierForSelection = this.state.tiersForCrafting.map((tier: any, index: number) => {
            return {
                label: 'Gem Tier ' + (index + 1),
                value: index + 1,
            }
        });

        tierForSelection.splice(0, 0, {
            label: 'Please select tier',
            value: 0,
        });

        return tierForSelection;
    }

    craftingTierSelected() {
        if (this.state.selectedTier === 0) {
            return {
                label: 'Please Select',
                value: 0,
            }
        }

        return {
            label: 'Gem Tier ' + this.state.selectedTier,
            value: this.state.selectedTier,
        }
    }

    render() {
        return (
            <Fragment>
                <div className='mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]'>
                    <div className='lg:cols-start-1 lg:col-span-2'>
                        {
                            this.state.loading ?
                                <LoadingProgressBar />
                            :
                                <Fragment>
                                    <Select
                                        onChange={this.setTierToCraft.bind(this)}
                                        options={this.craftingTiers()}
                                        menuPosition={'absolute'}
                                        menuPlacement={'bottom'}
                                        styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                        menuPortalTarget={document.body}
                                        value={this.craftingTierSelected()}
                                    />

                                    {
                                        this.state.selectedTier !== 0 ?
                                            <div className='mt-4 mb-2'>
                                                <dl>
                                                    <dt>Gold Dust Cost</dt>
                                                    <dl>{formatNumber(this.state.tierCost.gold_dust_cost)}</dl>
                                                    <dt>Shards Cost</dt>
                                                    <dl>{formatNumber(this.state.tierCost.shards_cost)}</dl>
                                                    <dt>Copper Coin Cost</dt>
                                                    <dl>{formatNumber(this.state.tierCost.copper_coin_cost)}</dl>
                                                </dl>
                                            </div>
                                            : null
                                    }

                                    {
                                        this.state.isCrafting ?
                                            <LoadingProgressBar />
                                        : null
                                    }

                                    {
                                        this.state.tiersForCrafting.length > 0 ?
                                            <CraftingXp skill_xp={this.state.skill_xp} />
                                        : null
                                    }
                                </Fragment>
                        }
                    </div>
                </div>
                {
                    this.state.errorMessage !== null ?
                        <DangerAlert>
                            {this.state.errorMessage}
                        </DangerAlert>
                    : null
                }
                <div className='text-center lg:ml-[-100px] mt-3 mb-3'>
                    <PrimaryButton button_label={'Craft'}
                                   on_click={this.craft.bind(this)}
                                   disabled={this.state.loading || this.state.selected_item === null || this.props.cannot_craft || this.state.isCrafting}
                    />
                    <DangerButton button_label={'Close'}
                                  on_click={this.props.remove_crafting}
                                  additional_css={'ml-2'}
                                  disabled={this.state.loading || this.props.cannot_craft || this.state.isCrafting} />
                    <a href='/information/gem-crafting' target='_blank' className='relative top-[20px] md:top-[0px] ml-2'>Help <i
                        className="fas fa-external-link-alt"></i></a>
                </div>
            </Fragment>

        )
    }
}
