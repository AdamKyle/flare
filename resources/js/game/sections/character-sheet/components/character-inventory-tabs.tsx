import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import EquippedTable from "./tabs/inventory-tabs/equipped-table";
import SetsTable from "./tabs/inventory-tabs/sets-table";
import QuestItemsTable from "./tabs/inventory-tabs/quest-items-table";
import { watchForDarkModeInventoryChange } from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import CharacterInventoryTabsState from "../../../lib/game/character-sheet/types/character-inventory-tabs-state";
import InventoryTabSection from "./tabs/inventory-tab-section";
import InventoryDetails from "../../../lib/game/character-sheet/types/inventory/inventory-details";
import CharacterInventoryTabsProps from "../../../lib/game/character-sheet/types/character-inventory-tabs-props";
import ItemSkillManagement from "./item-skill-management/item-skill-management";
import ItemSkill from "./item-skill-management/types/deffinitions/item-skill";
import ItemSkillProgression from "./item-skill-management/types/deffinitions/item-skill-progression";

export default class CharacterInventoryTabs extends React.Component<
    CharacterInventoryTabsProps,
    CharacterInventoryTabsState
> {
    private tabs: { name: string; key: string }[];

    private updateInventoryListener: any;

    constructor(props: CharacterInventoryTabsProps) {
        super(props);

        this.tabs = [
            {
                key: "inventory",
                name: "Inventory",
            },
            {
                key: "equipped",
                name: "Equipped",
            },
            {
                key: "sets",
                name: "Sets",
            },
            {
                key: "quest",
                name: "Quest items",
            },
        ];

        this.state = {
            table: "Inventory",
            dark_tables: false,
            loading: true,
            inventory: null,
            disable_tabs: false,
            item_skill_data: null,
        };

        // @ts-ignore
        this.updateInventoryListener = Echo.private(
            "update-inventory-" + this.props.user_id
        );
    }

    componentDidMount() {
        watchForDarkModeInventoryChange(this);

        if (this.props.finished_loading) {
            new Ajax()
                .setRoute("character/" + this.props.character_id + "/inventory")
                .doAjaxCall(
                    "get",
                    (result: AxiosResponse) => {
                        this.setState({
                            loading: false,
                            inventory: result.data,
                        });
                    },
                    (error: AxiosError) => {
                        console.error(error);
                    }
                );
        }

        // @ts-ignore
        this.updateInventoryListener.listen(
            "Game.Core.Events.CharacterInventoryUpdateBroadCastEvent",
            (event: any) => {
                if (this.state.inventory !== null) {
                    const inventoryState = JSON.parse(
                        JSON.stringify(this.state.inventory)
                    );
                    
                    inventoryState[event.type] = event.inventory;

                    this.setState(
                        {
                            inventory: inventoryState,
                        },
                        () => {
                            this.updateItemSkillData();
                        }
                    );
                }
            }
        );
    }

    switchTable(type: string) {
        this.setState({
            table: type,
        });
    }

    updateInventory(inventory: { [key: string]: InventoryDetails[] }) {
        let stateInventory = JSON.parse(JSON.stringify(this.state.inventory));

        const keys = Object.keys(inventory);

        console.log("updateInventory", stateInventory, keys, inventory);

        for (let i = 0; i < keys.length; i++) {
            stateInventory[keys[i]] = inventory[keys[i]];
        }

        this.setState({
            inventory: stateInventory,
        });
    }

    manageDisableTabs() {
        this.setState(
            {
                disable_tabs: !this.state.disable_tabs,
            },
            () => {
                if (typeof this.props.update_disable_tabs !== "undefined") {
                    this.props.update_disable_tabs();
                }
            }
        );
    }

    updateItemSkillData() {
        if (this.state.item_skill_data === null) {
            return;
        }

        if (this.state.inventory === null) {
            return;
        }

        const equippedSlot = this.state.inventory.equipped.find(
            (slot: InventoryDetails) => {
                return slot.slot_id === this.state.item_skill_data?.slot_id;
            }
        );

        if (typeof equippedSlot !== "undefined") {
            return this.manageItemSkills(
                equippedSlot.slot_id,
                equippedSlot.item_skills,
                equippedSlot.item_skill_progressions
            );
        }
    }

    manageItemSkills(
        slotId: number,
        itemSkills: ItemSkill[],
        itemSkillProgressions: ItemSkillProgression[]
    ) {
        this.setState({
            item_skill_data: {
                slot_id: slotId,
                item_skills: itemSkills,
                item_skill_progressions: itemSkillProgressions,
            },
        });
    }

    closeItemSkillTree() {
        this.setState({
            item_skill_data: null,
        });
    }

    render() {
        if (this.state.loading || this.state.inventory === null) {
            return (
                <div className="my-4">
                    <ComponentLoading />
                </div>
            );
        }

        if (this.state.item_skill_data !== null) {
            return (
                <div className="my-4">
                    <ItemSkillManagement
                        slot_id={this.state.item_skill_data.slot_id}
                        skill_data={this.state.item_skill_data.item_skills}
                        skill_progression_data={
                            this.state.item_skill_data.item_skill_progressions
                        }
                        close_skill_tree={this.closeItemSkillTree.bind(this)}
                        character_id={this.props.character_id}
                    />
                </div>
            );
        }

        return (
            <Tabs
                tabs={this.tabs}
                full_width={true}
                disabled={this.state.disable_tabs}
            >
                <TabPanel key={"inventory"}>
                    <InventoryTabSection
                        dark_tables={this.state.dark_tables}
                        character_id={this.props.character_id}
                        inventory={this.state.inventory.inventory}
                        usable_items={this.state.inventory.usable_items}
                        is_dead={this.props.is_dead}
                        update_inventory={this.updateInventory.bind(this)}
                        usable_sets={this.state.inventory.usable_sets}
                        is_automation_running={this.props.is_automation_running}
                        user_id={this.props.user_id}
                        manage_skills={this.manageItemSkills.bind(this)}
                    />
                </TabPanel>
                <TabPanel key={"equipped"}>
                    <EquippedTable
                        dark_tables={this.state.dark_tables}
                        equipped_items={this.state.inventory.equipped}
                        is_dead={this.props.is_dead}
                        sets={this.state.inventory.sets}
                        character_id={this.props.character_id}
                        is_set_equipped={this.state.inventory.set_is_equipped}
                        update_inventory={this.updateInventory.bind(this)}
                        is_automation_running={this.props.is_automation_running}
                        disable_tabs={this.manageDisableTabs.bind(this)}
                        manage_skills={this.manageItemSkills.bind(this)}
                    />
                </TabPanel>
                <TabPanel key={"sets"}>
                    <SetsTable
                        dark_tables={this.state.dark_tables}
                        sets={this.state.inventory.sets}
                        is_dead={this.props.is_dead}
                        character_id={this.props.character_id}
                        savable_sets={this.state.inventory.savable_sets}
                        update_inventory={this.updateInventory.bind(this)}
                        set_name_equipped={
                            this.state.inventory.set_name_equipped
                        }
                        is_automation_running={this.props.is_automation_running}
                        disable_tabs={this.manageDisableTabs.bind(this)}
                        manage_skills={this.manageItemSkills.bind(this)}
                    />
                </TabPanel>
                <TabPanel key={"quest"}>
                    <QuestItemsTable
                        dark_table={this.state.dark_tables}
                        quest_items={this.state.inventory.quest_items}
                        is_dead={this.props.is_dead}
                        character_id={this.props.character_id}
                    />
                </TabPanel>
            </Tabs>
        );
    }
}
