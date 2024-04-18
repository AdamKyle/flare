import React from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";

export default class CharacterResistances extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <ResistanceInfoSection
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
