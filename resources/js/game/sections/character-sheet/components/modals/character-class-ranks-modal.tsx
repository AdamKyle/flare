import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoProps} from "../types/additional-info-props";
import CharacterClassRanks from "../character-class-ranks";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import CharacterClassSpecialtiesModal from "./character-class-specialties-modal";

export default class CharacterClassRanksModal extends React.Component<AdditionalInfoProps, any> {


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
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
                      medium_modal={true}
                      secondary_actions={{
                          secondary_button_label: 'Manage Specialties',
                          secondary_button_disabled: false,
                          handle_action: this.manageClassSpecialtiesModal.bind(this)
                      }}
            >
                <InfoAlert additional_css={'my-4'}>
                    <p>
                        Class Ranks are essentially a way for you to level classes to unlock new ones. You can also switch classes
                    here. All classes are leveled by you killing monsters, either exploration or manually. <a href="/information/class-ranks" target="_blank">
                    learn more about Class Ranks <i className="fas fa-external-link-alt"></i></a>
                    </p>
                </InfoAlert>
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
            </Dialogue>
        );
    }
}
