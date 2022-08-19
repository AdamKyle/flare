import React, {Fragment} from "react";
import KingdomDetailsProps from "../../lib/game/kingdoms/types/kingdom-details-props";
import {formatNumber} from "../../lib/game/format-number";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";
import SkyOutlineButton from "../../components/ui/buttons/sky-outline-button";
import ChangeNameModal from "./modals/change-name-modal";
import BuyPopulationModal from "./modals/buy-population-modal";
import BuildingDetails from "../../lib/game/kingdoms/building-details";
import {buildBuildingsColumns} from "../../lib/game/kingdoms/build-buildings-columns";
import GoblinBankModal from "./modals/goblin-bank-modal";

export default class KingdomDetails extends React.Component<KingdomDetailsProps, any> {
    constructor(props: KingdomDetailsProps) {
        super(props);

        this.state = {
            show_change_name_modal: false,
            show_buy_pop_modal: false,
            show_goblin_bank: false,
        }
    }

    calculateTotalDefence(): number {
        const kingdom = this.props.kingdom;

        return kingdom.walls_defence + kingdom.treasury_defence +
               kingdom.gold_bars_defence + kingdom.passive_defence +
               kingdom.defence_bonus;
    }

    showChangeName() {
        this.setState({
            show_change_name_modal: !this.state.show_change_name_modal
        });
    }

    showBuyPop() {
        this.setState({
            show_buy_pop_modal: !this.state.show_buy_pop_modal
        });
    }

    showGoblinBank() {
        this.setState({
            show_goblin_bank: !this.state.show_goblin_bank
        });
    }

    canManageGoldBars(): boolean {
        let bankBuilding: BuildingDetails[]|BuildingDetails = this.props.kingdom.buildings.filter((building: BuildingDetails) => {
            return building.name === 'Goblin Coin Bank';
        });

        if (bankBuilding.length === 0) {
            return false;
        }

        bankBuilding = bankBuilding[0];

        return bankBuilding.is_locked;
    }

    render() {
        return (
            <Fragment>
                <div className='grid md:grid-cols-2 gap-4'>
                    <div>
                        <h3>Basics</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Name</dt>
                            <dd>{this.props.kingdom.name}</dd>
                            <dt>Morale</dt>
                            <dd>{
                                (this.props.kingdom.current_morale * 100).toFixed(2) + '/100 %'
                            }</dd>
                            <dt>Treasury</dt>
                            <dd>{
                                formatNumber(this.props.kingdom.treasury)
                            }</dd>
                            <dt>Gold Bars</dt>
                            <dd>{
                                formatNumber(this.props.kingdom.gold_bars)
                            }</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div>
                        <h3>Resources</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Stone</dt>
                            <dd>{formatNumber(this.props.kingdom.current_stone) + '/' + formatNumber(this.props.kingdom.max_stone)}</dd>
                            <dt>Clay</dt>
                            <dd>{formatNumber(this.props.kingdom.current_clay) + '/' + formatNumber(this.props.kingdom.max_clay)}</dd>
                            <dt>Wood</dt>
                            <dd>{formatNumber(this.props.kingdom.current_wood) + '/' + formatNumber(this.props.kingdom.max_wood)}</dd>
                            <dt>Iron</dt>
                            <dd>{formatNumber(this.props.kingdom.current_iron) + '/' + formatNumber(this.props.kingdom.max_iron)}</dd>
                            <dt>Population</dt>
                            <dd>{
                                formatNumber(this.props.kingdom.current_population ) + '/' +
                                formatNumber(this.props.kingdom.max_population)
                            }</dd>
                        </dl>
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <div className='grid md:grid-cols-2 gap-4'>
                    <div>
                        <h3>Defence Break Down</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Wall Defence</dt>
                            <dd>{(this.props.kingdom.walls_defence * 100).toFixed(2)}%</dd>
                            <dt>Treasury Defence</dt>
                            <dd>{(this.props.kingdom.treasury_defence * 100).toFixed(2)}%</dd>
                            <dt>Gold Bars Defence</dt>
                            <dd>{(this.props.kingdom.gold_bars_defence * 100).toFixed(2)}%</dd>
                            <dt>Passive Defence</dt>
                            <dd>{(this.props.kingdom.passive_defence * 100).toFixed(2)}%</dd>
                            <dt>Defence Bonus</dt>
                            <dd>{(this.props.kingdom.defence_bonus * 100).toFixed(2)}%</dd>
                            <dt>Total Defence</dt>
                            <dd>{(this.calculateTotalDefence() * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div>
                        <h3>Kingdom Actions</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <div className='grid md:grid-cols-1 gap-4'>
                            <PrimaryOutlineButton button_label={'Change Name'} on_click={this.showChangeName.bind(this)} />
                            <PrimaryOutlineButton button_label={'Buy Population'} on_click={this.showBuyPop.bind(this)} />
                            <SkyOutlineButton button_label={'Manage Gold Bars'} on_click={this.showGoblinBank.bind(this)}  disabled={this.canManageGoldBars()}/>
                            <SkyOutlineButton button_label={'Manage Treasury'} on_click={() => {}} />
                            <DangerOutlineButton button_label={'Abandon Kingdom'} on_click={() => {}} />
                        </div>
                    </div>
                </div>

                {
                    this.state.show_change_name_modal ?
                        <ChangeNameModal
                            name={this.props.kingdom.name}
                            kingdom_id={this.props.kingdom.id}
                            is_open={true}
                            handle_close={this.showChangeName.bind(this)}
                        />
                    : null
                }

                {
                    this.state.show_buy_pop_modal ?
                        <BuyPopulationModal
                            kingdom={this.props.kingdom}
                            is_open={true}
                            handle_close={this.showBuyPop.bind(this)}
                            gold={this.props.character_gold}
                        />
                    : null
                }

                {
                    this.state.show_goblin_bank ?
                        <GoblinBankModal
                            is_open={true}
                            handle_close={this.showGoblinBank.bind(this)}
                            character_gold={this.props.character_gold}
                            gold_bars={this.props.kingdom.gold_bars}
                            kingdom_id={this.props.kingdom.id}
                        />
                    : null
                }
            </Fragment>

        )
    }
}
