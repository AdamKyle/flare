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
import React, { Fragment } from "react";
import HelpDialogue from "../../../../components/ui/dialogue/help-dialogue";
var QuestHelpModal = (function (_super) {
    __extends(QuestHelpModal, _super);
    function QuestHelpModal(props) {
        return _super.call(this, props) || this;
    }
    QuestHelpModal.prototype.buildTitle = function () {
        switch (this.props.type) {
            case "gold_dust":
                return "How to get: Gold Dust";
            case "shards":
                return "How to get: Shards";
            case "copper_coins":
                return "How to get: Copper Coins";
            case "item_requirement":
                return "How to get: " + this.props.quest.item.name;
            case "secondary_item_requirement":
                return "How to get: " + this.props.quest.secondary_item.name;
            case "required_quest":
                return (
                    "Quest to complete: " + this.props.quest.required_quest.name
                );
            case "reincarnation_times":
                return "Times to reincarnate";
            case "faction_map":
                return "Faction Map Requirements";
            case "fame_requirements":
                return "Fame Requirements";
            default:
                return null;
        }
    };
    QuestHelpModal.prototype.buildGoldDustHelp = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "Gold dust is gained only by disenchanting items and daily through the Gold Dust lottery system. Players are encouraged to level crafting and enchanting while exploration is running or manually fighting as you can then disenchant the items for Gold Dust.",
            ),
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "Players are also encouraged to turn on Auto Disenchanting in their settings after level 20. This will also then auto disenchant drops from manual fights, adventures and exploration - as you level the disenchanting skill, you will also level the enchanting skill at half the XP.",
            ),
        );
    };
    QuestHelpModal.prototype.buildShardHelp = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "Shards drop from Celestial Entities, which can be conjured for a cost in Gold and Gold Dust, or through Hunting them on Wednesdays when they ave a 80% chance to spawn by you just moving. Some quests also reward shards.",
            ),
            React.createElement(
                "p",
                null,
                "Players use shards and Gold Dust in Alchemy to create powerful potions for short term boons or to create Holy Oils for late game gear upgrades.",
            ),
        );
    };
    QuestHelpModal.prototype.buildCopperCoins = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "mt-2 mb-4 text-gray-700 dark:text-gray-200" },
                "You must have access to Purgatory, from there you will kill any creature to get Copper Coins. Players should first complete the quest line to access Purgatory Smith Work Bench in the Purgatory Smiths House in Purgatory. This will allow you to make Holy Items, through Alchemy. These items are required to make it further down the Purgatory monster list.",
            ),
        );
    };
    QuestHelpModal.prototype.buildFactionMapRequirements = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "There are two aspects to this requirement: The Faction and the level of the faction.",
            ),
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                React.createElement(
                    "strong",
                    null,
                    "Plane Faction Name (Map to fight on)",
                ),
                " refers to the map you need to be on to raise the second part:",
                " ",
                React.createElement("strong", null, "Level required"),
                ". For example, if you need to be on Surface and get it to level three then you would kill creatures while on Surface to raise the Faction level.",
            ),
            React.createElement(
                "p",
                { className: "my-2" },
                React.createElement(
                    "a",
                    { href: "/information/factions", target: "_blank" },
                    "Learn more about Factions here",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    QuestHelpModal.prototype.buildFameRequirements = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "There are three aspects to fame requirements: The Npc to assist. The Map the Npc lives on and the fame level to have with that npc.",
            ),
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "Players must have a faction at level 5 for the map that the npc lives on in order to assist the npc with their tasks and thus raise their fame to the required level. Once a player has this they just have to Pledge to that faction and then assist the NPC in order for their actions to count.",
            ),
            React.createElement(
                "p",
                { className: "my-2 text-gray-700 dark:text-gray-200" },
                "By assisting an NPC with their tasks, players will raise their fame with that NPC and gain items and currencies.",
            ),
            React.createElement(
                "p",
                { className: "my-2" },
                React.createElement(
                    "a",
                    { href: "/information/faction-loyalty", target: "_blank" },
                    "Learn more about Faction Loyalty here",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    QuestHelpModal.prototype.buildRequiredQuestHelp = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-4 text-gray-700 dark:text-gray-200" },
                "You need to complete a quest before you can hand this one in. The quest you are looking for can be in the same tree as this quest. Take a look below to see what plane the quest is on, the name of the quest and if its a raid specific quest, which means it only appears when that raid is running.",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Quest Name:"),
                React.createElement(
                    "dd",
                    null,
                    this.props.quest.required_quest.name,
                ),
                React.createElement("dt", null, "Plane:"),
                React.createElement(
                    "dd",
                    null,
                    React.createElement(
                        "a",
                        {
                            href:
                                "/information/map/" +
                                this.props.quest.required_quest.npc.game_map.id,
                            target: "_blank",
                        },
                        this.props.quest.required_quest.npc.game_map.name,
                        " ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                ),
                React.createElement("dt", null, "Is Raid Quest:"),
                React.createElement(
                    "dd",
                    null,
                    this.props.quest.required_quest.raid_id !== null
                        ? "Yes"
                        : "No",
                ),
                this.props.quest.required_quest.raid_id !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement("dt", null, "Raid Name:"),
                          React.createElement(
                              "dd",
                              null,
                              React.createElement(
                                  "strong",
                                  null,
                                  this.props.quest.required_quest.raid.name,
                              ),
                              " ",
                              "Check your event calendar in the side bar to see when this raid is going to take place again!",
                          ),
                      )
                    : null,
            ),
        );
    };
    QuestHelpModal.prototype.buildReincarnationHelp = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "p",
                { className: "mt-2 mb-4 text-gray-700 dark:text-gray-200" },
                "This quest requires you to head to your Character Sheet (tab) and click the Reincarnate button. This will set your character to level one, keeping all skills, current stats and so on. Only your level will be reset back to one, allowing your character to grow stronger.",
            ),
            React.createElement(
                "p",
                { className: "mb-4 text-gray-700 dark:text-gray-200" },
                "While you can reincarnate at anytime, a word of caution: If you reincarnate too early, you will have less stats then someone who waits till they are level 5000. A character may only reincarnate 100 times. The choice is yours if you reincarnate now, or wait.",
            ),
            React.createElement(
                "p",
                { className: "text-gray-700 dark:text-gray-200" },
                "Learn more about",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/reincarnation", target: "_blank" },
                    "Reincarnation",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    QuestHelpModal.prototype.buildContent = function () {
        switch (this.props.type) {
            case "gold_dust":
                return this.buildGoldDustHelp();
            case "shards":
                return this.buildShardHelp();
            case "copper_coins":
                return this.buildCopperCoins();
            case "item_requirement":
                return React.createElement(
                    "p",
                    { className: "my-2 text-gray-700 dark:text-gray-200" },
                    this.props.item_requirements(this.props.quest.item),
                );
            case "secondary_item_requirement":
                return React.createElement(
                    "p",
                    { className: "my-2 text-gray-700 dark:text-gray-200" },
                    this.props.item_requirements(
                        this.props.quest.secondary_item,
                    ),
                );
            case "required_quest":
                return this.buildRequiredQuestHelp();
            case "reincarnation_times":
                return this.buildReincarnationHelp();
            case "faction_map":
                return this.buildFactionMapRequirements();
            case "fame_requirements":
                return this.buildFameRequirements();
            default:
                return null;
        }
    };
    QuestHelpModal.prototype.render = function () {
        return React.createElement(
            HelpDialogue,
            {
                is_open: true,
                manage_modal: this.props.manage_modal,
                title: this.buildTitle(),
            },
            this.buildContent(),
        );
    };
    return QuestHelpModal;
})(React.Component);
export default QuestHelpModal;
//# sourceMappingURL=quest-help-modal.js.map
