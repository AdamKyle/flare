import React from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterReincarnationSection from "./sections/character-reincarnation-section";

export default class CharacterReincarnation extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <CharacterReincarnationSection
                view_port={0}
                character={this.props.character}
                is_open={true}
                manage_modal={() => {}}
                title={''}
                finished_loading={true}
            />
        )
    }
}
