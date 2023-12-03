import React from "react";
import {Tree, TreeNode} from "react-organizational-chart";
import QuestTreeProps from "../../../map/types/map/quests/quest-tree-props";
import QuestNode from "./quest-node";
import {QuestDetails} from "../../../map/types/quest-details";
import {ChildQuestDetails} from "../../../map/types/child-quest-details";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";

export  default class QuestTree extends React.Component<QuestTreeProps, {tabs: {key: string, name: string}[]}> {

    private invalid_planes: string[];

    constructor(props: QuestTreeProps) {
        super(props);

        this.state = {
            tabs: [{
                key: 'quest-chain',
                name: 'Quest Chain'
            }, {
                key: 'one-off-quests',
                name: 'One Off Quests',
            }],
        }

        this.invalid_planes = ['Purgatory']
    }

    componentDidMount(): void {
        const planeRaidQuest = this.fetchParentRaidQuestChain();

        if (this.props.raid_quests.length > 0 && planeRaidQuest !== null) {

            const tabs = JSON.parse(JSON.stringify(this.state.tabs));

            tabs.push({
                key: 'raid-quests',
                name: 'Raid Quests',
            });

            this.setState({
                tabs: tabs,
            });
        }
    }

    componentDidUpdate() {
        const tabIndex = this.state.tabs.findIndex((tab) => tab.key === 'raid-quests');
        
        if (this.fetchParentRaidQuestChain() === null && tabIndex !== -1) {
            const tabs = JSON.parse(JSON.stringify(this.state.tabs));

            tabs.splice(tabIndex, 1);

            this.setState({
                tabs: tabs,
            });
        } else if (this.fetchParentRaidQuestChain() !== null && tabIndex === -1) {
            const tabs = JSON.parse(JSON.stringify(this.state.tabs));

            tabs.push({
                key: 'raid-quests',
                name: 'Raid Quests',
            });

            this.setState({
                tabs: tabs,
            });
        }
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

    fetchParentRaidQuestChain(): QuestDetails | null {
        const plane = this.fetchPlane();

        const questChain = this.props.raid_quests.filter((quest) => quest.child_quests.length > 0 && quest.belongs_to_map_name === plane);

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
            <Tabs tabs={this.state.tabs}>
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
                {
                    this.props.raid_quests.length > 0 && this.fetchParentRaidQuestChain() !== null ?
                        <TabPanel key={'one-off-quests'}>
                            <Tree
                                lineWidth={'2px'}
                                lineColor={'#0ea5e9'}
                                lineBorderRadius={'10px'}
                                label={<QuestNode quest={this.fetchParentRaidQuestChain()} character_id={this.props.character_id} completed_quests={this.props.completed_quests} update_quests={this.props.update_quests}/>}
                            >
                                {this.renderQuestTree(this.fetchParentRaidQuestChain())}
                            </Tree>
                        </TabPanel>
                    : null
                }
            </Tabs>
        );
    }
}
