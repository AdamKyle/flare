import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../lib/game/format-number";
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
                    Class Ranks are essentially a way for you to level classes to unlock new ones. You can also switch classes
                    here. All classes are leveled by you killing monsters, either exploration or manually. <a href="/information/class-ranks" target="_blank">
                    learn more about Class Ranks <i className="fas fa-external-link-alt"></i></a>
                </InfoAlert>
                <CharacterClassRanks character={this.props.character} />
            </Dialogue>
        );
    }
}
