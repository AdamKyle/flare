import React from "react";
import Tabs from "../../../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../../../components/ui/tabs/tab-panel";
import AdditionalInformation from "../additional-information";
import CharacterResistances from "../character-resistances";
import CharacterReincarnation from "../character-reincarnation";
import CharacterClassRanks from "../character-class-ranks";

export default class CharacterAdditionalStatsSection extends React.Component<any, any> {

    private tabs: {key: string, name: string}[];

    constructor(props: any) {
        super(props);

        this.tabs = [{
            key: 'core-info',
            name: 'Core Information'
        },{
            key: 'resistance',
            name: 'Resistances',
        }, {
            key: 'reincarnation',
            name: 'Reincarnation'
        }, {
            key: 'class-ranks',
            name: 'Class Ranks',
        }];

        this.state = {
            additional_info_tab_text: 'stats'
        }
    }

    render() {
        return (
            <Tabs tabs={this.tabs} full_width={true}>
                <TabPanel key={'core-info'}>
                    <AdditionalInformation character={this.props.character} />
                </TabPanel>
                <TabPanel key={'resistance'}>
                    <CharacterResistances character={this.props.character} />
                </TabPanel>
                <TabPanel key={'reincarnation'}>
                    <CharacterReincarnation character={this.props.character} />
                </TabPanel>
                <TabPanel key={'class-ranks'}>
                    <CharacterClassRanks character={this.props.character} />
                </TabPanel>
            </Tabs>
        );
    }
}
