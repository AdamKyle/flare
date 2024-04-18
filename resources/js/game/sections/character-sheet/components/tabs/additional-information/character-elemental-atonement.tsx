import React, {ReactNode} from "react";
import AdditionalInfoSection from "./sections/additional-info-section";
import ResistanceInfoSection from "./sections/resistance-info-section";
import CharacterClassRanksSection from "./sections/character-class-ranks-section";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import CharacterElementalAtonementSection from "./sections/character-elemental-atonement-section";

export default class CharacterElementalAtonement extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className="relative">
                <CharacterElementalAtonementSection
                    character={this.props.character}
                    is_open={true}
                    manage_modal={() => {}}
                    title={''}
                    finished_loading={true}
                />
            </div>
        )
    }
}
