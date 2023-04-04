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

export class GemBagTable extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            viewGem: false,
            slot_id: 0,
            gem_slots: [],
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

    sellGem(gemSlotId: number) {

    }

    sellAllGems() {

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
        return <PrimaryButton button_label={'Sell'} on_click={() => this.sellGem(data.id)} />;
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
                <PrimaryButton button_label={'Sell all Gems'} on_click={this.sellAllGems.bind(this)} additional_css={'my-3'} />
                <div className={'max-w-[290px] sm:max-w-[100%] overflow-x-hidden'}>
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
            </Fragment>
        );
    }

}
