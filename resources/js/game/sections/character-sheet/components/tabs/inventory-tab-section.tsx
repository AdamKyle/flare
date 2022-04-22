import React, {Fragment} from "react";
import DropDown from "../../../../components/ui/drop-down/drop-down";
import InventoryTable from "./inventory-tabs/inventory-table";
import UsableItemsTable from "./inventory-tabs/usable-items-table";
import PopOverContainer from "../../../../components/ui/popover/pop-over-container";
import InventoryDetails from "../../../../lib/game/character-sheet/types/inventory/inventory-details";
import InventoryActionConfirmationModal from "../modals/inventory-action-confirmation-modal";
import {isEqual} from "lodash";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import InventoryTabSectionProps from "../../../../lib/game/character-sheet/types/tabs/inventory-tab-section-props";
import InventoryTabSectionState from "../../../../lib/game/character-sheet/types/tabs/inventory-tab-section-state";
import clsx from "clsx";
import UsableItemsDetails from "../../../../lib/game/character-sheet/types/inventory/usable-items-details";

export default class InventoryTabSection extends React.Component<InventoryTabSectionProps, InventoryTabSectionState> {

    constructor(props: InventoryTabSectionProps) {
        super(props);

        this.state = {
            table: 'Inventory',
            data: this.props.inventory,
            usable_items: this.props.usable_items,
            show_destroy_all: false,
            show_disenchant_all: false,
            show_sell_all: false,
            show_destroy_all_alchemy: false,
            success_message: null,
            search_string: '',
        }
    }

    componentDidUpdate(prevProps: Readonly<any>, prevState: Readonly<any>, snapshot?: any) {
        if (!isEqual(this.state.data, this.props.inventory) && this.state.search_string.length === 0) {
            this.setState({
                data: this.props.inventory
            });
        }

        if (!isEqual(this.state.usable_items, this.props.usable_items) && this.state.search_string.length === 0) {
            this.setState({
                usable_items: this.props.usable_items
            });
        }
    }

    setSuccessMessage(message: string) {
        this.setState({
            success_message: message,
        })
    }

    switchTable(type: string) {
        this.setState({
            table: type
        });
    }

    search(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;

        if (this.state.table === 'Inventory') {
            this.setState({
                data: this.props.inventory.filter((item: InventoryDetails) => {
                    return item.item_name.includes(value) || item.type.includes(value);
                }),
                search_string: value,
            });
        } else {
            this.setState({
                usable_items: this.props.usable_items.filter((item: UsableItemsDetails) => {
                    return item.item_name.includes(value) || item.description.includes(value);
                }),
                search_string: value,
            });
        }
    }

    manageDisenchantAll() {
        this.setState({
            show_disenchant_all: !this.state.show_disenchant_all,
        })
    }

    manageDestroyAll() {
        this.setState({
            show_destroy_all: !this.state.show_destroy_all,
        })
    }

    manageDestroyAllAlchemy() {
        this.setState({
            show_destroy_all_alchemy: !this.state.show_destroy_all_alchemy,
        })
    }

    manageSellAll() {
        this.setState({
            show_sell_all: !this.state.show_sell_all,
        })
    }

    closeSuccess(){
        this.setState({
            success_message: null
        })
    }

    createActionsDropDown() {
        if (this.state.table === 'Inventory') {
            return [
                {
                    name: 'Destroy All',
                    icon_class: 'far fa-trash-alt',
                    on_click: () => this.manageDestroyAll()
                },
                {
                    name: 'Disenchant All',
                    icon_class: 'ra ra-fire',
                    on_click: () => this.manageDisenchantAll()
                },
                {
                    name: 'Sell All',
                    icon_class: 'far fa-money-bill-alt',
                    on_click: () => this.manageSellAll()
                },
            ]
        }

        return [
            {
                name: 'Use many',
                icon_class: 'ra ra-bottle-vapors',
                on_click: () => this.manageDestroyAll()
            },
            {
                name: 'Destroy All',
                icon_class: 'far fa-trash-alt',
                on_click: () => this.manageDestroyAllAlchemy()
            },
        ]
    }

    isDropDownHidden() {
        if (this.state.table === 'Inventory') {
            return this.state.data.length === 0;
        } else {
            return this.props.usable_items.length === 0;
        }
    }

    render() {
        return (
            <Fragment>
                {
                    this.state.success_message !== null ?
                        <SuccessAlert close_alert={this.closeSuccess.bind(this)} additional_css={'mt-4 mb-4'}>
                            {this.state.success_message}
                        </SuccessAlert>
                    : null
                }

                <div className='flex items-center'>
                    <div>
                        <DropDown menu_items={[
                            {
                                name: 'Inventory',
                                icon_class: 'fas fa-shopping-bag',
                                on_click: () => this.switchTable('Inventory')
                            },
                            {
                                name: 'Usable',
                                icon_class: 'ra ra-bubbling-potion',
                                on_click: () => this.switchTable('Usable')
                            },
                        ]} button_title={'Type'} selected_name={this.state.table} disabled={this.props.is_dead} />
                    </div>
                    <div className={clsx('ml-2', {'hidden': this.isDropDownHidden()})}>
                        <DropDown menu_items={this.createActionsDropDown()} button_title={'Actions'} selected_name={this.state.table} disabled={this.props.is_dead} />
                    </div>
                    <div className='ml-4 md:ml-0 my-4 md:my-0 md:absolute md:right-0'>
                        <div className='flex items-center'>
                            <div>
                                <input type='text' name='search' className='form-control' onChange={this.search.bind(this)} placeholder={'Search'}/>
                            </div>
                            <div className='mt-2'>
                                <PopOverContainer icon={'fas fa-info-circle'} icon_label={'Help'} additional_css={'left-[14px] md:left-0'} make_small={true} >
                                    <h3>Searching</h3>
                                    <p className='my-2'>
                                       This will only search within the current selected type. To find other items, change types and search.
                                    </p>
                                </PopOverContainer>
                            </div>
                        </div>
                    </div>
                </div>

                {
                    this.state.table === 'Inventory'  ?
                        <InventoryTable dark_table={this.props.dark_tables} character_id={this.props.character_id} inventory={this.state.data} is_dead={this.props.is_dead} update_inventory={this.props.update_inventory} usable_sets={this.props.usable_sets} set_success_message={this.setSuccessMessage.bind(this)}/>
                        :
                        <UsableItemsTable dark_table={this.props.dark_tables} character_id={this.props.character_id} usable_items={this.state.usable_items} is_dead={this.props.is_dead} update_inventory={this.props.update_inventory} set_success_message={this.setSuccessMessage.bind(this)}/>
                }

                {
                    this.state.show_destroy_all ?
                        <InventoryActionConfirmationModal
                            is_open={this.state.show_destroy_all}
                            manage_modal={this.manageDestroyAll.bind(this)}
                            title={'Destroy all'}
                            url={'character/'+this.props.character_id+'/inventory/destroy-all'}
                            update_inventory={this.props.update_inventory}
                            set_success_message={this.setSuccessMessage.bind(this)}
                        >
                            <p>
                                Are you sure you want to do this? This action will destroy all items in your inventory. You cannot undo this action.
                            </p>
                            <p className='mt-2'>
                                Make sure you move any items you want to a set or equip the items you want, before destroying all.
                            </p>
                            <p className='mt-2'>
                                It is advised that players do not destroy enchanted items (names with *'s) or uniques (green items), but instead sell them on the market or <a href={'/information/skill-information'} target='_blank'>disenchant <i className="fas fa-external-link-alt"></i></a> them to
                                make <a href={'/information/currencies'} target='_blank'>Gold Dust <i className="fas fa-external-link-alt"></i></a>.
                            </p>
                        </InventoryActionConfirmationModal>
                    : null
                }

                {
                    this.state.show_destroy_all_alchemy ?
                        <InventoryActionConfirmationModal
                            is_open={this.state.show_destroy_all_alchemy}
                            manage_modal={this.manageDestroyAllAlchemy.bind(this)}
                            title={'Destroy all Alchemy Items'}
                            url={'character/'+this.props.character_id+'/inventory/destroy-all-alchemy-items'}
                            update_inventory={this.props.update_inventory}
                            set_success_message={this.setSuccessMessage.bind(this)}
                        >
                            <p>
                                Are you sure you want to do this? This action will destroy all (Alchemy) items in your inventory. You cannot undo this action.
                            </p>
                        </InventoryActionConfirmationModal>
                        : null
                }

                {
                    this.state.show_disenchant_all ?
                        <InventoryActionConfirmationModal
                            is_open={this.state.show_disenchant_all}
                            manage_modal={this.manageDisenchantAll.bind(this)}
                            title={'Disenchant all'}
                            url={'character/'+this.props.character_id+'/inventory/disenchant-all'}
                            update_inventory={this.props.update_inventory}
                            set_success_message={this.setSuccessMessage.bind(this)}
                        >
                            <p>
                                Are you sure you want to do this? This action will disenchant all items in your inventory. You cannot undo this action.
                            </p>
                            <p className='mt-2'>
                                When you disenchant items you will get some <a href={'/information/currencies'} target='_blank'>Gold Dust <i className="fas fa-external-link-alt"></i></a> and
                                experience towards <a href={'/information/skill-information'} target='_blank'>Disenchanting <i className="fas fa-external-link-alt"></i></a> and half XP towards Enchanting.
                            </p>
                            <p className='mt-2'>
                                Tip for crafters/enchanters: Equip a set that's full enchanting when doing your mass disenchanting, because the XP you get,
                                while only half, can be boosted. For new players, you should be crafting and enchanting and then disenchanting or selling your equipment
                                on the market, if it is not viable for you.
                            </p>
                        </InventoryActionConfirmationModal>
                    : null
                }

                {
                    this.state.show_sell_all ?
                        <InventoryActionConfirmationModal
                            is_open={this.state.show_sell_all}
                            manage_modal={this.manageSellAll.bind(this)}
                            title={'Sell all'}
                            url={'character/'+this.props.character_id+'/inventory/sell-all'}
                            update_inventory={this.props.update_inventory}
                            set_success_message={this.setSuccessMessage.bind(this)}
                        >
                            <p>
                                Are you sure? You are about to sell all items in your inventory (this does not effect Alchemy, Quest items, Sets or Equipped items). This action cannot be undone.
                            </p>
                            <p className='mt-2'>
                                <strong>Note</strong>: This will not take into account prices for Holy Items and Uniques.
                                In those cases you only get the base item cost, even in the case of holy items, if there are affixes attached.
                                These items are best sold on the market to make your gold invested and time invested worth it.
                            </p>
                            <p className='mt-2'>
                                Finally, if the either affix cost attached the item is greater then 2 Billion gold, you will only get 2 billion gold for that affix.
                                For example, on a 10 gold item that has two 12 billion enchants on it, you would only make 4,000,000,010 gold before taxes.
                            </p>
                        </InventoryActionConfirmationModal>
                        : null
                }
            </Fragment>
        )
    }
}
