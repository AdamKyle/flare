import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import InventoryTable from "./tabs/inventory-tabs/inventory-table";
import DropDown from "../../../components/ui/drop-down/drop-down";
import UsableItemsTable from "./tabs/inventory-tabs/usable-items-table";
import EquippedTable from "./tabs/inventory-tabs/equipped-table";
import SetsTable from "./tabs/inventory-tabs/sets-table";
import QuestItemsTable from "./tabs/inventory-tabs/quest-items-table";
import {watchForDarkModeInventoryChange} from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import CharacterInventoryTabsState from "../../../lib/game/character-sheet/types/character-inventory-tabs-state";
import Inventory from "resources/js/game/lib/game/character-sheet/types/inventory/inventory";
import InventoryTabSection from "./tabs/inventory-tab-section";

export default class CharacterInventoryTabs extends React.Component<any, CharacterInventoryTabsState> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'inventory',
            name: 'Inventory'
        }, {
            key: 'equipped',
            name: 'Equipped',
        }, {
            key: 'sets',
            name: 'Sets'
        }, {
            key: 'quest',
            name: 'Quest items'
        }];

        this.state = {
            table: 'Inventory',
            dark_tables: false,
            loading: true,
            inventory: null,
        }
    }

    componentDidMount() {
        watchForDarkModeInventoryChange(this);

        (new Ajax()).setRoute('character/'+this.props.character_id+'/inventory').doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                loading: false,
                inventory: result.data,
            });
        }, (error: AxiosError) => {
            console.log(error);
        })
    }

    switchTable(type: string) {
        this.setState({
            table: type,
        });
    }

    render() {
        if (this.state.loading || this.state.inventory === null) {
            return <ComponentLoading />
        }

        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'inventory'}>
                    <InventoryTabSection dark_tables={this.state.dark_tables} character_id={this.props.character_id} inventory={this.state.inventory.inventory} usable_items={this.state.inventory.usable_items} is_dead={this.props.is_dead} />
                </TabPanel>
                <TabPanel key={'equipped'}>
                    <EquippedTable dark_table={this.state.dark_tables} equipped_items={this.state.inventory.equipped} is_dead={this.props.is_dead} />
                </TabPanel>
                <TabPanel key={'sets'}>
                    <SetsTable dark_table={this.state.dark_tables} sets={this.state.inventory.sets} is_dead={this.props.is_dead} />
                </TabPanel>
                <TabPanel key={'quest'}>
                    <QuestItemsTable dark_table={this.state.dark_tables} quest_items={this.state.inventory.quest_items} is_dead={this.props.is_dead} />
                </TabPanel>
            </Tabs>
        )
    }
}
