import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";

export default class CharacterSkillsTabs extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'skills',
            name: 'Skills'
        }, {
            key: 'kingdom-passives',
            name: 'Kingdom Passives',
        }, {
            key: 'crafting',
            name: 'Crafting Skills'
        }, {
            key: 'misc',
            name: 'Misc Skills'
        }];
    }

    render() {
        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'skills'}>Skills</TabPanel>
                <TabPanel key={'kingdom-passives'}>Kingdom Passives</TabPanel>
                <TabPanel key={'crafting'}>Crafting Skills</TabPanel>
                <TabPanel key={'misc'}>Misc Skills</TabPanel>
            </Tabs>
        )
    }

}
