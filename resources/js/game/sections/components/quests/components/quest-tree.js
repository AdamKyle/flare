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
import { Tree, TreeNode } from "react-organizational-chart";
import QuestNode from "./quest-node";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
var QuestTree = (function (_super) {
    __extends(QuestTree, _super);
    function QuestTree(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            tabs: [
                {
                    key: "quest-chain",
                    name: "Quest Chain",
                },
                {
                    key: "one-off-quests",
                    name: "One Off Quests",
                },
            ],
        };
        _this.invalid_planes = ["Purgatory"];
        return _this;
    }
    QuestTree.prototype.componentDidMount = function () {
        var planeRaidQuest = this.fetchParentRaidQuestChain();
        if (this.props.raid_quests.length > 0 && planeRaidQuest !== null) {
            var tabs = JSON.parse(JSON.stringify(this.state.tabs));
            tabs.push({
                key: "raid-quests",
                name: "Raid Quests",
            });
            this.setState({
                tabs: tabs,
            });
        }
    };
    QuestTree.prototype.componentDidUpdate = function () {
        var tabIndex = this.state.tabs.findIndex(function (tab) {
            return tab.key === "raid-quests";
        });
        if (this.fetchParentRaidQuestChain() === null && tabIndex !== -1) {
            var tabs = JSON.parse(JSON.stringify(this.state.tabs));
            tabs.splice(tabIndex, 1);
            this.setState({
                tabs: tabs,
            });
        } else if (
            this.fetchParentRaidQuestChain() !== null &&
            tabIndex === -1
        ) {
            var tabs = JSON.parse(JSON.stringify(this.state.tabs));
            tabs.push({
                key: "raid-quests",
                name: "Raid Quests",
            });
            this.setState({
                tabs: tabs,
            });
        }
    };
    QuestTree.prototype.renderQuestTree = function (parentQuest) {
        var _this = this;
        if (parentQuest == null) {
            return null;
        }
        return parentQuest.child_quests.map(function (quest) {
            return React.createElement(
                TreeNode,
                {
                    label: React.createElement(QuestNode, {
                        quest: quest,
                        character_id: _this.props.character_id,
                        completed_quests: _this.props.completed_quests,
                        update_quests: _this.props.update_quests,
                    }),
                },
                _this.renderQuestTree(quest),
            );
        });
    };
    QuestTree.prototype.fetchParentQuestChain = function () {
        var plane = this.fetchPlane();
        var questChain = this.props.quests.filter(function (quest) {
            return (
                quest.child_quests.length > 0 &&
                quest.belongs_to_map_name === plane
            );
        });
        if (questChain.length > 0) {
            return questChain[0];
        }
        return null;
    };
    QuestTree.prototype.fetchParentRaidQuestChain = function () {
        var plane = this.fetchPlane();
        var questChain = this.props.raid_quests.filter(function (quest) {
            return (
                quest.child_quests.length > 0 &&
                quest.belongs_to_map_name === plane
            );
        });
        if (questChain.length > 0) {
            return questChain[0];
        }
        return null;
    };
    QuestTree.prototype.fetchSingleQuests = function () {
        var plane = this.fetchPlane();
        var quests = this.props.quests.filter(function (quest) {
            return (
                quest.child_quests.length === 0 &&
                quest.belongs_to_map_name === plane
            );
        });
        if (quests.length > 0) {
            return quests;
        }
        return [];
    };
    QuestTree.prototype.fetchPlane = function () {
        var plane = this.props.plane;
        if (this.invalid_planes.indexOf(plane) !== -1) {
            plane = "Surface";
        }
        return plane;
    };
    QuestTree.prototype.renderSingleQuests = function () {
        var _this = this;
        return this.fetchSingleQuests().map(function (quest) {
            return React.createElement(
                Tree,
                {
                    lineWidth: "2px",
                    lineColor: "#0ea5e9",
                    lineBorderRadius: "10px",
                    label: React.createElement(QuestNode, {
                        quest: quest,
                        character_id: _this.props.character_id,
                        completed_quests: _this.props.completed_quests,
                        update_quests: _this.props.update_quests,
                    }),
                },
                _this.renderQuestTree(quest),
            );
        });
    };
    QuestTree.prototype.render = function () {
        return React.createElement(
            Tabs,
            { tabs: this.state.tabs },
            React.createElement(
                TabPanel,
                { key: "quest-chain" },
                React.createElement(
                    Tree,
                    {
                        lineWidth: "2px",
                        lineColor: "#0ea5e9",
                        lineBorderRadius: "10px",
                        label: React.createElement(QuestNode, {
                            quest: this.fetchParentQuestChain(),
                            character_id: this.props.character_id,
                            completed_quests: this.props.completed_quests,
                            update_quests: this.props.update_quests,
                        }),
                    },
                    this.renderQuestTree(this.fetchParentQuestChain()),
                ),
            ),
            React.createElement(
                TabPanel,
                { key: "one-off-quests" },
                this.renderSingleQuests(),
            ),
            this.props.raid_quests.length > 0 &&
                this.fetchParentRaidQuestChain() !== null
                ? React.createElement(
                      TabPanel,
                      { key: "one-off-quests" },
                      React.createElement(
                          Tree,
                          {
                              lineWidth: "2px",
                              lineColor: "#0ea5e9",
                              lineBorderRadius: "10px",
                              label: React.createElement(QuestNode, {
                                  quest: this.fetchParentRaidQuestChain(),
                                  character_id: this.props.character_id,
                                  completed_quests: this.props.completed_quests,
                                  update_quests: this.props.update_quests,
                              }),
                          },
                          this.renderQuestTree(
                              this.fetchParentRaidQuestChain(),
                          ),
                      ),
                  )
                : null,
        );
    };
    return QuestTree;
})(React.Component);
export default QuestTree;
//# sourceMappingURL=quest-tree.js.map
