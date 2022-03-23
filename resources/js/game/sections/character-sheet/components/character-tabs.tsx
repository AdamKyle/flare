import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import BasicCard from "../../../components/ui/cards/basic-card";
import PopOverContainer from "../../../components/ui/popover/pop-over-container";
import InfoTab from "./tabs/info-tab";

export default class CharacterTabs extends React.Component<any, any> {

    private tabs: {name: string, key: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'info',
            name: 'Info'
        }, {
            key: 'active-boons',
            name: 'Active Boons',
        }, {
            key: 'automations',
            name: 'Automations'
        }, {
            key: 'factions',
            name: 'Factions'
        }];
    }

    render() {
        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'info'}>
                    <InfoTab />
                </TabPanel>
                <TabPanel key={'active-boons'}>
                    Active Boons
                </TabPanel>
                <TabPanel key={'automations'}>
                    Automations
                </TabPanel>
                <TabPanel key={'factions'}>
                    Factions
                </TabPanel>
            </Tabs>
        );
    }
}
