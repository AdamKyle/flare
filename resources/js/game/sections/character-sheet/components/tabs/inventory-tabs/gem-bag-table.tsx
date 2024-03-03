import React, {Fragment} from "react";
import GemBagDetails from "../../../../../lib/game/character-sheet/types/inventory/gem-bag-details";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import Table from "../../../../../components/ui/data-tables/table";
import {
    buildGemColumns,
} from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../../lib/ajax/ajax";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import CharacterGem from "../../modals/character-gem";
import ActionDialogue from "../../../../../components/ui/dialogue/action-dialogue";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";

export class GemBagTable extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            viewGem: false,
            slot_id: 0,
            gem_slots: [],
            view_sell_all: false,
            view_sell: false,
            gem_to_sell: {},
            is_selling: false,
            success_message: null,
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('character/'+this.props.character_id+'/gem-bag').doAjaxCall('get', (
            result: AxiosResponse
        ) => {
            this.setState({
                gem_slots: result.data.gem_slots,
            })
        }, (error: AxiosError) => {
            console.error(error);
        })
    }

    showSellConfirmation(gemSlotId: number) {
        this.setState({
            gem_to_sell: this.state.gem_slots.find((gemSlot: any) => { return gemSlot.id === gemSlotId }),
            slot_id: gemSlotId,
            view_sell: true,
        })
    }

    showSellAllConfirmation() {
        this.setState({
            view_sell_all: true,
        });
    }

    sellGem() {
        this.setState({
            is_selling: true,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/sell-gem/' + this.state.slot_id)
                        .doAjaxCall('post', (result: AxiosResponse) => {
                            this.setState({
                                gem_to_sell: {},
                                slot_id: 0,
                                view_sell: false,
                                gem_slots: result.data.gems,
                                success_message: result.data.message,
                                is_selling: false,
                            });

                        }, (error: AxiosError) => {
                            this.setState({
                                gem_to_sell: {},
                                slot_id: 0,
                                view_sell: false,
                                is_selling: false,
                            });

                            console.error(error);
                        });
        });
    }

    sellAllGems() {
        this.setState({
            is_selling: true,
        }, () => {
            (new Ajax()).setRoute('character/'+this.props.character_id+'/sell-all-gems')
                .doAjaxCall('post', (result: AxiosResponse) => {
                    this.setState({
                        view_sell_all: false,
                        gem_slots: result.data.gems,
                        success_message: result.data.message,
                        is_selling: false,
                    });

                }, (error: AxiosError) => {
                    this.setState({
                        view_sell_all: false,
                        is_selling: false,
                    });

                    console.error(error);
                });
        });
    }

    viewItem(gemSlot: GemBagDetails) {
        this.setState({
            view_gem: true,
            slot_id: gemSlot.id
        });
    }

    closeViewGem() {
        this.setState({
            view_gem: false,
            slot_id: 0,
        });
    }

    gemActions(data: GemBagDetails): JSX.Element {
        return <PrimaryButton button_label={'Sell'} on_click={() => this.showSellConfirmation(data.id)} />;
    }

    buildGemDialogueTitle(gemSlotId: number): JSX.Element {
        let gemSlot = this.state.gem_slots.filter((gemSlot: GemBagDetails) => {
            return gemSlot.id === gemSlotId;
        });

        if (gemSlot.length > 0) {
            gemSlot = gemSlot[0];
        }

        return <span className={'text-lime-600 dark:text-lime-500'}>{gemSlot.name}</span>;
    }

    render() {
        return (
            <Fragment>
                <InfoAlert additional_css={'mt-4 mb-4'}>
                    Click the item name to get additional actions.
                </InfoAlert>
                <PrimaryButton button_label={'Sell all Gems'} on_click={this.showSellAllConfirmation.bind(this)} additional_css={'my-3'} />
                {
                    this.state.success_message !== null ?
                        <SuccessAlert additional_css='my-4'>
                            {this.state.success_message}
                        </SuccessAlert>
                    : null
                }
                <div className={'max-w-[390px] md:max-w-full overflow-x-hidden'}>
                    <Table data={this.state.gem_slots} columns={buildGemColumns(this, this.viewItem.bind(this))} dark_table={this.props.dark_table}/>
                </div>

                {
                    this.state.view_gem ?
                        <CharacterGem slot_id={this.state.slot_id}
                                      is_open={this.state.view_gem}
                                      title={this.buildGemDialogueTitle(this.state.slot_id)}
                                      character_id={this.props.character_id}
                                      manage_modal={this.closeViewGem.bind(this)}

                        />
                    : null
                }

                {
                    this.state.view_sell ?
                        <ActionDialogue is_open={this.state.view_sell}
                                        manage_modal={() => {
                                            this.setState({
                                                slot_id: 0,
                                                view_sell: false,
                                                gem_to_sell: {},
                                            });
                                        }}
                                        title={<span>Selling: <span className='text-lime-600 dark:text-lime-500'> {this.state.gem_to_sell.name} </span> (Tier: {this.state.gem_to_sell.tier})</span>}
                                        loading={this.state.is_selling}
                                        do_action={this.sellGem.bind(this)}
                        >
                            <p className='my-4'>
                                <strong>Are you sure?</strong> By selling this gem, you get 15% of the currencies required to make a gem of their
                                tier back.
                            </p>
                            <p className='my-4'>
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </ActionDialogue>
                    : null
                }

                {
                    this.state.view_sell_all ?
                        <ActionDialogue is_open={this.state.view_sell_all}
                                        manage_modal={() => {
                                            this.setState({
                                                view_sell_all: false,
                                            });
                                        }}
                                        title={'Sell All Gems'}
                                        loading={this.state.is_selling}
                                        do_action={this.sellAllGems.bind(this)}
                        >
                            <p className='my-4'>
                                <strong>Are you sure?</strong> By selling all gems, you get 15% of the currencies required to make a gem of their
                                tier back.
                            </p>
                            <p className='my-4'>
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </ActionDialogue>
                    : null
                }
            </Fragment>
        );
    }

}
