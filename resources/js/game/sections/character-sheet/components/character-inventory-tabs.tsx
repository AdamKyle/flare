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

export default class CharacterInventoryTabs extends React.Component<any, any> {

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
        }
    }

    componentDidMount() {
        watchForDarkModeInventoryChange(this);
    }

    switchTable(type: string) {
        this.setState({
            table: type,
        });
    }

    render() {
        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'inventory'}>
                    <DropDown menu_items={[
                        {
                            name: 'Inventory',
                            icon_class: 'fas fa-shopping-bag',
                            on_click: () => this.switchTable('Inventory')
                        },
                        {
                            name: 'Usable',
                            icon_class: 'ra  ra-bubbling-potion',
                            on_click: () => this.switchTable('Usable')
                        },
                    ]} button_title={'Type'} selected_name={this.state.table} />

                    {
                        this.state.table === 'Inventory' ?
                            <InventoryTable dark_table={this.state.dark_tables} />
                        :
                            <UsableItemsTable dark_table={this.state.dark_tables} />
                    }

                </TabPanel>
                <TabPanel key={'equipped'}>
                    <EquippedTable dark_table={this.state.dark_tables} />
                </TabPanel>
                <TabPanel key={'sets'}>
                    <SetsTable dark_table={this.state.dark_tables} />
                </TabPanel>
                <TabPanel key={'quest'}>
                    <QuestItemsTable dark_table={this.state.dark_tables} />
                </TabPanel>
            </Tabs>
        )
    }
}
