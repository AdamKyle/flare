import React, {ReactNode} from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterClassRanksSection from "./sections/character-class-ranks-section";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";

export default class CharacterClassRanks extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            character_class_rank_tab_text: 'class-ranks'
        }
    }

    render() {
        return (
            <CharacterClassRanksSection
                view_port={0}
                character={this.props.character}
                is_open={true}
                manage_modal={() => {}}
                title={''}
                finished_loading={true}
                when_tab_changes={() => {}}
            />
        )
    }
}
