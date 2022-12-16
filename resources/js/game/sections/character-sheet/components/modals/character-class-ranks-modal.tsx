import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import CharacterClassRanks from "../character-class-ranks";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";

export default class CharacterClassRanksModal extends React.Component<AdditionalInfoModalProps, any> {


    constructor(props: AdditionalInfoModalProps) {
        super(props);
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
            >
                <InfoAlert additional_css={'my-4'}>
                    <p>
                        Class Ranks are essentially a way for you to level classes to unlock new ones. You can also switch classes
                    here. All classes are leveled by you killing monsters, either exploration or manually. <a href="/information/class-ranks" target="_blank">
                    learn more about Class Ranks <i className="fas fa-external-link-alt"></i></a>
                    </p>
                    <p className='my-2'>
                        When switching classes, you'll have to refresh the character sheet tab by switching away and back to refresh the skills list to see your new
                        class skill. Your previous skill is hidden till you switch back.
                    </p>
                </InfoAlert>
                <CharacterClassRanks character={this.props.character} />
            </Dialogue>
        );
    }
}
