import React from "react";
import {Tree, TreeNode} from "react-organizational-chart";
import QuestTreeProps from "../../../../lib/game/types/map/quests/quest-tree-props";
import QuestNode from "./quest-node";
import {QuestDetails} from "../../../../lib/game/map/types/quest-details";
import {ChildQuestDetails} from "../../../../lib/game/map/types/child-quest-details";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";

export  default class QuestTree extends React.Component<QuestTreeProps, any> {

    private tabs: {key: string, name: string}[];

    private invalid_planes: string[];

    constructor(props: QuestTreeProps) {
        super(props);

        this.tabs = [{
            key: 'quest-chain',
            name: 'Quest Chain'
        }, {
            key: 'one-off-quests',
            name: 'One Off Quests',
        }];

        this.invalid_planes = ['Dungeon', 'Shadow Plane', 'Purgatory']
    }

    renderQuestTree(parentQuest: QuestDetails | ChildQuestDetails | null) {

        if (parentQuest == null) {
            return null;
        }

        return parentQuest.child_quests.map((quest) => {
            return (
                <TreeNode label={<QuestNode quest={quest} character_id={this.props.character_id} completed_quests={this.props.completed_quests} update_quests={this.props.update_quests}/>}>
                    {this.renderQuestTree(quest)}
                </TreeNode>
            )
        });
    }

    fetchParentQuestChain(): QuestDetails | null {

        const plane = this.fetchPlane();

        const questChain = this.props.quests.filter((quest) => quest.child_quests.length > 0 && quest.belongs_to_map_name === plane);

        if (questChain.length > 0) {
            return questChain[0];
        }

        return null
    }

    fetchSingleQuests(): QuestDetails[] | [] {
        const plane = this.fetchPlane();

        const quests = this.props.quests.filter((quest) => quest.child_quests.length === 0 && quest.belongs_to_map_name === plane);

        if (quests.length > 0) {
            return quests;
        }

        return []
    }

    fetchPlane() {
        let plane = this.props.plane;

        if (this.invalid_planes.indexOf(plane) !== -1) {
            plane = 'Surface'
        }

        return plane;
    }

    renderSingleQuests() {
        return this.fetchSingleQuests().map((quest) => {
            return (
                <Tree
                    lineWidth={'2px'}
                    lineColor={'#0ea5e9'}
                    lineBorderRadius={'10px'}
                    label={<QuestNode quest={quest} character_id={this.props.character_id} completed_quests={this.props.completed_quests} update_quests={this.props.update_quests}/>}
                >
                    {this.renderQuestTree(quest)}
                </Tree>
            );
        });
    }

    render() {
        return(
            <Tabs tabs={this.tabs}>
                <TabPanel key={'quest-chain'}>
                    <Tree
                        lineWidth={'2px'}
                        lineColor={'#0ea5e9'}
                        lineBorderRadius={'10px'}
                        label={<QuestNode quest={this.fetchParentQuestChain()} character_id={this.props.character_id} completed_quests={this.props.completed_quests} update_quests={this.props.update_quests}/>}
                    >
                        {this.renderQuestTree(this.fetchParentQuestChain())}
                    </Tree>
                </TabPanel>
                <TabPanel key={'one-off-quests'}>
                    {this.renderSingleQuests()}

                </TabPanel>
            </Tabs>
        );
    }
}
