import React from "react";
import HelpDialogue from "../../../../components/ui/dialogue/help-dialogue";

export default class SpecialLocationHelpModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <HelpDialogue is_open={true}
                          manage_modal={this.props.manage_modal}
                          title={'Speical Locations'}>
                <p className='my-2'>
                    This is a special location which contains the same monsters you have been fighting but they are much stronger here.
                    Players will want to have <a href={"/information/voidance"} target='_blank'>Devouring Darkness and Light <i className="fas fa-external-link-alt"></i></a> <a href={"/information/quest-items"} target='_blank'>Quest items <i className="fas fa-external-link-alt"></i> </a>
                    Which you can get from completing various: <a href={"/information/quests"} target='_blank'>Quests <i className="fas fa-external-link-alt"></i></a> with in the game.
                </p>
                <p>
                    These places offer specific quest items that drop at a 1/1,000,000 chance with your looting skill bonus capped at 45%. You can read more about
                    special locations and see their drops by reading: <a href={"/information/special-locations"} target='_blank'>Special Locations <i className="fas fa-external-link-alt"></i></a>.
                </p>
            </HelpDialogue>
        );
    }
}
