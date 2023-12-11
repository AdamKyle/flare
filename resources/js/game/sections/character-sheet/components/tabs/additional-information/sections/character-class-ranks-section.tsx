import React from "react";
import {AdditionalInfoProps} from "../../../types/additional-info-props";
import CharacterClassRanks from "../../../character-class-ranks";
import CharacterClassSpecialtiesModal from "../../../modals/character-class-specialties-modal";

export default class CharacterClassRanksSection extends React.Component<AdditionalInfoProps, any> {


    constructor(props: AdditionalInfoProps) {
        super(props);

        this.state = {
            show_class_specialties_model: false,
        }
    }

    manageClassSpecialtiesModal() {
        this.setState({
            show_class_specialties_model: !this.state.show_class_specialties_model,
        });
    }

    render() {

        if (this.props.character === null) {
            return null;
        }

        return (
            <>

                <CharacterClassRanks character={this.props.character} />

                {
                    this.state.show_class_specialties_model ?
                        <CharacterClassSpecialtiesModal
                            is_open={this.state.show_class_specialties}
                            manage_modal={this.manageClassSpecialtiesModal.bind(this)}
                            title={'Class Specialties'}
                            character={this.props.character}
                            finished_loading={true}
                        />
                        :
                        null
                }
            </>
        );
    }
}
