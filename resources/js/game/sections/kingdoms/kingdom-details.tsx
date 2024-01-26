import React, {Fragment} from "react";
import KingdomDetailsProps from "../../lib/game/kingdoms/types/kingdom-details-props";
import {formatNumber} from "../../lib/game/format-number";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";
import SkyOutlineButton from "../../components/ui/buttons/sky-outline-button";
import ChangeNameModal from "./modals/change-name-modal";
import BuyPopulationModal from "./modals/buy-population-modal";
import BuildingDetails from "./buildings/deffinitions/building-details";
import GoblinBankModal from "./modals/goblin-bank-modal";
import AbandonKingdomModal from "./modals/abadnon-kingdom-modal";
import ManageTreasuryModal from "./modals/manage-treasury-modal";
import KingdomDetailsState from "../../lib/game/kingdoms/types/kingdom-details-state";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import CallForReinforcements from "./modals/call-for-reinforcements ";
import SmelterModal from "./modals/smelter-modal";
import SpecialtyActionsHelpModal from "./modals/specialty-actions-help-modal";

export default class KingdomDetails extends React.Component<KingdomDetailsProps, KingdomDetailsState> {
    constructor(props: KingdomDetailsProps) {
        super(props);

        this.state = {
            goblin_bank_building: null,
            show_change_name_modal: false,
            show_buy_pop_modal: false,
            show_goblin_bank: false,
            show_abandon_kingdom: false,
            show_manage_treasury: false,
            show_call_for_reinforcements: false,
            show_smelter: false,
            show_specialty_help: false,
        }
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
        const building = this.props.kingdom.buildings.filter((building: BuildingDetails) => building.name === 'Goblin Coin Bank')[0];

        this.setState({
            show_goblin_bank: !this.state.show_goblin_bank,
            goblin_bank_building: building,
        });
    }

    showAbandonKingdom() {
        this.setState({
            show_abandon_kingdom: !this.state.show_abandon_kingdom
        })
    }

    showManageTreasury() {
        this.setState({
            show_manage_treasury: !this.state.show_manage_treasury
        })
    }

    showCallForReinforcements() {
        this.setState({
            show_call_for_reinforcements: !this.state.show_call_for_reinforcements
        })
    }

    showSmelter() {
        this.setState({
            show_smelter: !this.state.show_smelter
        });
    }

    abandonedKingdom() {
        this.props.close_details();
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

    showSpecialtyHelpModal() {
        this.setState({
            show_specialty_help: !this.state.show_specialty_help,
        });
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
                                (this.props.kingdom.current_morale * 100).toFixed(2) + '/100%'
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
                            <dt>Steel</dt>
                            <dd>{formatNumber(this.props.kingdom.current_steel) + '/' + formatNumber(this.props.kingdom.max_steel)}</dd>
                            <dt>Population</dt>
                            <dd>{
                                formatNumber(this.props.kingdom.current_population ) + '/' +
                                formatNumber(this.props.kingdom.max_population)
                            }</dd>
                        </dl>
                    </div>
                </div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <div className='grid md:grid-cols-3 gap-2'>
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
                            <dt>Total Defence Bonus</dt>
                            <dd>{(this.props.kingdom.defence_bonus * 100).toFixed(2)}%</dd>
                        </dl>

                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3>Item Resistance</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <dl>
                            <dt>Item Resistance</dt>
                            <dd>{(this.props.kingdom.item_resistance_bonus * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div>
                        <h3>Kingdom Actions</h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <div className='grid md:grid-cols-1 gap-4'>
                            <PrimaryOutlineButton button_label={'Change Name'} on_click={this.showChangeName.bind(this)} />
                            <SuccessOutlineButton button_label={'Call for Reinforcements'} on_click={this.showCallForReinforcements.bind(this)} />
                            <PrimaryOutlineButton button_label={'Buy Population'} on_click={this.showBuyPop.bind(this)} />
                            <SkyOutlineButton button_label={'Manage Treasury'} on_click={this.showManageTreasury.bind(this)} />
                            <DangerOutlineButton button_label={'Abandon Kingdom'} on_click={this.showAbandonKingdom.bind(this)} />
                        </div>
                    </div>
                    <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                    <div>
                        <h3>
                            Specialty Actions <button onClick={this.showSpecialtyHelpModal.bind(this)}><i className="fas fa-info-circle text-blue-500 dark:text-blue-400"></i></button>
                        </h3>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <div className='grid md:grid-cols-1 gap-4'>
                            <PrimaryOutlineButton button_label={
                                this.props.kingdom.smelting_time_left > 0 ?
                                    <Fragment>
                                        <i className='far fa-clock text-yellow-700 dark:text-yellow-500 mr-2' /> Smelter
                                    </Fragment>
                                :
                                    'Smelter'
                            } on_click={this.showSmelter.bind(this)} disabled={!this.props.kingdom.can_access_smelter}/>
                            <SkyOutlineButton button_label={'Manage Gold Bars'} on_click={this.showGoblinBank.bind(this)}  disabled={this.canManageGoldBars()}/>
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
                    this.state.show_goblin_bank && this.state.goblin_bank_building !== null ?
                        <GoblinBankModal
                            is_open={true}
                            handle_close={this.showGoblinBank.bind(this)}
                            character_gold={this.props.character_gold}
                            gold_bars={this.props.kingdom.gold_bars}
                            kingdom_id={this.props.kingdom.id}
                            goblin_bank={this.state.goblin_bank_building}
                        />
                    : null
                }

                {
                    this.state.show_abandon_kingdom ?
                        <AbandonKingdomModal is_open={true}
                                             handle_close={this.showAbandonKingdom.bind(this)}
                                             handle_kingdom_close={this.props.close_details}
                                             kingdom_id={this.props.kingdom.id}
                        />
                    : null
                }

                {
                    this.state.show_manage_treasury ?
                        <ManageTreasuryModal
                            is_open={true}
                            handle_close={this.showManageTreasury.bind(this)}
                            character_gold={this.props.character_gold}
                            treasury={this.props.kingdom.treasury}
                            morale={this.props.kingdom.current_morale}
                            kingdom_id={this.props.kingdom.id}
                            character_id={this.props.kingdom.character_id}
                        />
                    : null
                }

                {
                    this.state.show_call_for_reinforcements ?
                        <CallForReinforcements
                            is_open={true}
                            kingdom_id={this.props.kingdom.id}
                            handle_close={this.showCallForReinforcements.bind(this)}
                            character_id={this.props.kingdom.character_id}
                        />
                    : null
                }

                {
                    this.state.show_smelter ?
                        <SmelterModal
                            is_open={true}
                            kingdom_id={this.props.kingdom.id}
                            max_steel={this.props.kingdom.max_steel}
                            iron={this.props.kingdom.current_iron}
                            handle_close={this.showSmelter.bind(this)}
                            character_id={this.props.kingdom.character_id}
                            smelting_time_reduction={this.props.kingdom.smelting_time_reduction}
                            smelting_time_left={this.props.kingdom.smelting_time_left}
                            smelting_completed_at={this.props.kingdom.smelting_completed_at}
                            smelting_amount={this.props.kingdom.smelting_amount}
                        />
                        : null
                }

                {
                    this.state.show_specialty_help ?
                        <SpecialtyActionsHelpModal
                            is_open={true}
                            handle_close={this.showSpecialtyHelpModal.bind(this)}
                        />
                    : null
                }
            </Fragment>

        )
    }
}
