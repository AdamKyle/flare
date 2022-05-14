import React, {Fragment} from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import BasicCard from "../../../components/ui/cards/basic-card";
import PopOverContainer from "../../../components/ui/popover/pop-over-container";
import InfoTab from "./tabs/info-tab";
import CharacterTabsProps from "../../../lib/game/character-sheet/types/character-tabs-props";
import CharacterActiveBoons from "./character-active-boons";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import CharacterFactions from "./character-factions";

export default class CharacterTabs extends React.Component<CharacterTabsProps, any> {

    private tabs: {name: string, key: string}[];

    constructor(props: CharacterTabsProps) {
        super(props);

        this.tabs = [{
            key: 'info',
            name: 'Info'
        }, {
            key: 'active-boons',
            name: 'Active Boons',
        }, {
            key: 'factions',
            name: 'Factions'
        }];
    }

    render() {
        return (
            <Fragment>
                <Tabs tabs={this.tabs} full_width={true}>
                    <TabPanel key={'info'}>
                        <InfoTab character={this.props.character} />
                    </TabPanel>
                    <TabPanel key={'active-boons'}>
                        <CharacterActiveBoons character_id={this.props.character?.id} />
                    </TabPanel>
                    <TabPanel key={'factions'}>
                        <CharacterFactions character_id={this.props.character?.id} />
                    </TabPanel>
                </Tabs>
            </Fragment>
        );
    }
}
