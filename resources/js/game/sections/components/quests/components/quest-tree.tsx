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

    constructor(props: QuestTreeProps) {
        super(props);

        this.tabs = [{
            key: 'quest-chain',
            name: 'Quest Chain'
        }, {
            key: 'one-off-quests',
            name: 'One Off Quests',
        }];
    }

    renderQuestTree(parentQuest: QuestDetails | ChildQuestDetails | null) {

        if (parentQuest == null) {
            return null;
        }

        return parentQuest.child_quests.map((quest) => {
            return (
                <TreeNode label={<QuestNode quest={quest} character_id={this.props.character_id} />}>
                    {this.renderQuestTree(quest)}
                </TreeNode>
            )
        });
    }

    fetchParentQuestChain(): QuestDetails | null {
        const questChain = this.props.quests.filter((quest) => quest.child_quests.length > 0);

        if (questChain.length > 0) {
            return questChain[0];
        }

        return null
    }

    fetchSingleQuests(): QuestDetails[] | [] {
        const quests = this.props.quests.filter((quest) => quest.child_quests.length === 0);

        if (quests.length > 0) {
            return quests;
        }

        return []
    }

    renderSingleQuests() {
        return this.fetchSingleQuests().map((quest) => {
            return (
                <Tree
                    lineWidth={'2px'}
                    lineColor={'#0ea5e9'}
                    lineBorderRadius={'10px'}
                    label={<QuestNode quest={quest} character_id={this.props.character_id} />}
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
                        label={<QuestNode quest={this.fetchParentQuestChain()} character_id={this.props.character_id}/>}
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
