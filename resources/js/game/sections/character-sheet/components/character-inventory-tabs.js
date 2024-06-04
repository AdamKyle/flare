var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import EquippedTable from "./tabs/inventory-tabs/equipped-table";
import SetsTable from "./tabs/inventory-tabs/sets-table";
import QuestItemsTable from "./tabs/inventory-tabs/quest-items-table";
import { watchForDarkModeInventoryChange } from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import InventoryTabSection from "./tabs/inventory-tab-section";
import ItemSkillManagement from "./item-skill-management/item-skill-management";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
var CharacterInventoryTabs = (function (_super) {
    __extends(CharacterInventoryTabs, _super);
    function CharacterInventoryTabs(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
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
        _this.state = {
            table: "Inventory",
            dark_tables: false,
            loading: true,
            inventory: null,
            disable_tabs: false,
            item_skill_data: null,
        };
        _this.updateInventoryListener = Echo.private(
            "update-inventory-" + _this.props.user_id,
        );
        return _this;
    }
    CharacterInventoryTabs.prototype.componentDidMount = function () {
        var _this = this;
        watchForDarkModeInventoryChange(this);
        if (this.props.finished_loading) {
            new Ajax()
                .setRoute("character/" + this.props.character_id + "/inventory")
                .doAjaxCall(
                    "get",
                    function (result) {
                        _this.setState({
                            loading: false,
                            inventory: result.data,
                        });
                    },
                    function (error) {
                        console.error(error);
                    },
                );
        }
        this.updateInventoryListener.listen(
            "Game.CharacterInventory.Events.CharacterInventoryUpdateBroadCastEvent",
            function (event) {
                if (_this.state.inventory !== null) {
                    var inventoryState = JSON.parse(
                        JSON.stringify(_this.state.inventory),
                    );
                    inventoryState[event.type] = event.inventory;
                    _this.setState(
                        {
                            inventory: inventoryState,
                        },
                        function () {
                            _this.updateItemSkillData();
                        },
                    );
                }
            },
        );
    };
    CharacterInventoryTabs.prototype.updateInventory = function (inventory) {
        var stateInventory = JSON.parse(JSON.stringify(this.state.inventory));
        var keys = Object.keys(inventory);
        for (var i = 0; i < keys.length; i++) {
            stateInventory[keys[i]] = inventory[keys[i]];
        }
        this.setState({
            inventory: stateInventory,
        });
    };
    CharacterInventoryTabs.prototype.manageDisableTabs = function () {
        var _this = this;
        this.setState(
            {
                disable_tabs: !this.state.disable_tabs,
            },
            function () {
                if (typeof _this.props.update_disable_tabs !== "undefined") {
                    _this.props.update_disable_tabs();
                }
            },
        );
    };
    CharacterInventoryTabs.prototype.updateItemSkillData = function () {
        var _this = this;
        if (this.state.item_skill_data === null) {
            return;
        }
        if (this.state.inventory === null) {
            return;
        }
        var equippedSlot = this.state.inventory.equipped.find(function (slot) {
            var _a;
            return (
                slot.slot_id ===
                ((_a = _this.state.item_skill_data) === null || _a === void 0
                    ? void 0
                    : _a.slot_id)
            );
        });
        if (typeof equippedSlot !== "undefined") {
            return this.manageItemSkills(
                equippedSlot.slot_id,
                equippedSlot.item_skills,
                equippedSlot.item_skill_progressions,
            );
        }
    };
    CharacterInventoryTabs.prototype.manageItemSkills = function (
        slotId,
        itemSkills,
        itemSkillProgressions,
    ) {
        this.setState({
            item_skill_data: {
                slot_id: slotId,
                item_skills: itemSkills,
                item_skill_progressions: itemSkillProgressions,
            },
        });
    };
    CharacterInventoryTabs.prototype.closeItemSkillTree = function () {
        this.setState({
            item_skill_data: null,
        });
    };
    CharacterInventoryTabs.prototype.render = function () {
        if (this.state.loading || this.state.inventory === null) {
            return React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(LoadingProgressBar, null),
            );
        }
        if (this.state.item_skill_data !== null) {
            return React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(ItemSkillManagement, {
                    slot_id: this.state.item_skill_data.slot_id,
                    skill_data: this.state.item_skill_data.item_skills,
                    skill_progression_data:
                        this.state.item_skill_data.item_skill_progressions,
                    close_skill_tree: this.closeItemSkillTree.bind(this),
                    character_id: this.props.character_id,
                }),
            );
        }
        return React.createElement(
            Tabs,
            {
                tabs: this.tabs,
                full_width: true,
                disabled: this.state.disable_tabs,
            },
            React.createElement(
                TabPanel,
                { key: "inventory" },
                React.createElement(InventoryTabSection, {
                    dark_tables: this.state.dark_tables,
                    character_id: this.props.character_id,
                    inventory: this.state.inventory.inventory,
                    usable_items: this.state.inventory.usable_items,
                    is_dead: this.props.is_dead,
                    update_inventory: this.updateInventory.bind(this),
                    usable_sets: this.state.inventory.usable_sets,
                    is_automation_running: this.props.is_automation_running,
                    user_id: this.props.user_id,
                    manage_skills: this.manageItemSkills.bind(this),
                    view_port: this.props.view_port,
                }),
            ),
            React.createElement(
                TabPanel,
                { key: "equipped" },
                React.createElement(EquippedTable, {
                    dark_tables: this.state.dark_tables,
                    equipped_items: this.state.inventory.equipped,
                    is_dead: this.props.is_dead,
                    sets: this.state.inventory.sets,
                    character_id: this.props.character_id,
                    is_set_equipped: this.state.inventory.set_is_equipped,
                    update_inventory: this.updateInventory.bind(this),
                    is_automation_running: this.props.is_automation_running,
                    disable_tabs: this.manageDisableTabs.bind(this),
                    manage_skills: this.manageItemSkills.bind(this),
                    view_port: this.props.view_port,
                }),
            ),
            React.createElement(
                TabPanel,
                { key: "sets" },
                React.createElement(SetsTable, {
                    dark_tables: this.state.dark_tables,
                    sets: this.state.inventory.sets,
                    is_dead: this.props.is_dead,
                    character_id: this.props.character_id,
                    savable_sets: this.state.inventory.savable_sets,
                    update_inventory: this.updateInventory.bind(this),
                    set_name_equipped: this.state.inventory.set_name_equipped,
                    is_automation_running: this.props.is_automation_running,
                    disable_tabs: this.manageDisableTabs.bind(this),
                    manage_skills: this.manageItemSkills.bind(this),
                    view_port: this.props.view_port,
                }),
            ),
            React.createElement(
                TabPanel,
                { key: "quest" },
                React.createElement(QuestItemsTable, {
                    dark_table: this.state.dark_tables,
                    quest_items: this.state.inventory.quest_items,
                    is_dead: this.props.is_dead,
                    character_id: this.props.character_id,
                    view_port: this.props.view_port,
                }),
            ),
        );
    };
    return CharacterInventoryTabs;
})(React.Component);
export default CharacterInventoryTabs;
//# sourceMappingURL=character-inventory-tabs.js.map
