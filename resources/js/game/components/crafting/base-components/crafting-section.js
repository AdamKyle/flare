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
import Crafting from "../general-crafting/crafting";
import Enchanting from "../enchanting/enchanting";
import Alchemy from "../alchemy/alchemy";
import WorkBench from "../../npc-actions/work-bench/work-bench";
import Trinketry from "../trinketry/trinketry";
import QueenOfHearts from "../../npc-actions/queen-of-hearts/queen-of-hearts";
import GemCrafting from "../gem-crafting/gem-crafting";
import SeerCamp from "../../npc-actions/seer-camp/seer-camp";
import LabyrinthOracle from "../../npc-actions/labyrinth-oracle/labyrinth-oracle";
var CraftingSection = (function (_super) {
    __extends(CraftingSection, _super);
    function CraftingSection(props) {
        return _super.call(this, props) || this;
    }
    CraftingSection.prototype.renderCraftingSection = function () {
        switch (this.props.type) {
            case "craft":
                return React.createElement(Crafting, {
                    is_small: this.props.is_small,
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                    fame_tasks: this.props.fame_tasks,
                    user_id: this.props.user_id,
                });
            case "enchant":
                return React.createElement(Enchanting, {
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                    user_id: this.props.user_id,
                });
            case "alchemy":
                return React.createElement(Alchemy, {
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                });
            case "workbench":
                return React.createElement(WorkBench, {
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                });
            case "trinketry":
                return React.createElement(Trinketry, {
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                });
            case "queen":
                return React.createElement(QueenOfHearts, {
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                    user_id: this.props.user_id,
                });
            case "gem-crafting":
                return React.createElement(GemCrafting, {
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                });
            case "seer-camp":
                return React.createElement(SeerCamp, {
                    character_id: this.props.character_id,
                    leave_seer_camp: this.props.remove_crafting,
                });
            case "labyrinth-oracle":
                return React.createElement(LabyrinthOracle, {
                    character_id: this.props.character_id,
                    remove_crafting: this.props.remove_crafting,
                    cannot_craft: this.props.cannot_craft,
                    user_id: this.props.user_id,
                });
            default:
                return null;
        }
    };
    CraftingSection.prototype.render = function () {
        return this.renderCraftingSection();
    };
    return CraftingSection;
})(React.Component);
export default CraftingSection;
//# sourceMappingURL=crafting-section.js.map
