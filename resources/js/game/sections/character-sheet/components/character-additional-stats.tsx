import React from "react";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import AdditionalInfoSection from "./additional-info-section";
import AdditionalInformation from "./tabs/additional-information/additional-information";
import CharacterResistances from "./tabs/additional-information/character-resistances";

export default class CharacterAdditionalStats extends React.Component<any, any> {

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
            key: 'factions',
            name: 'Factions'
        }, {
            key: 'mercenaries',
            name: 'Mercenaries'
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
            </Tabs>
        );
    }
}
