import React from "react";
import {AdditionalInfoProps} from "../../../types/additional-info-props";
import CharacterClassRanks from "../../../character-class-ranks";
import CharacterClassRankSpecialtiesSection from "./character-class-rank-specialties-section";
import Tabs from "../../../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../../../components/ui/tabs/tab-panel";

export default class CharacterClassRanksSection extends React.Component<AdditionalInfoProps, {}> {

    private tabs: {key: string, name: string}[];

    constructor(props: AdditionalInfoProps) {
        super(props);

        this.tabs = [{
            key: 'class-ranks',
            name: 'Class Ranks'
        }, {
            key: 'class-masteries',
            name: 'Class Masteries',
        }];
    }

    whenTabUpdates(index: number) {

        if (!this.props.when_tab_changes) {
            return;
        }

        this.props.when_tab_changes(index, this.tabs);
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        return (
            <Tabs tabs={this.tabs} full_width={true} listen_for_change={this.whenTabUpdates.bind(this)} >
                <TabPanel key={'class-ranks'}>
                    <CharacterClassRanks character={this.props.character} />
                </TabPanel>
                <TabPanel key={'class-masteries'}>
                    <CharacterClassRankSpecialtiesSection
                        is_open={true}
                        manage_modal={() => {}}
                        title={''}
                        character={this.props.character}
                        finished_loading={true}
                    />
                </TabPanel>
            </Tabs>
        );
    }
}
