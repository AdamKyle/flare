import React from "react";
import HelpDialogue from "../../../../../components/ui/dialogue/help-dialogue";

export default class SkillHelpModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <HelpDialogue is_open={true}
                          manage_modal={this.props.manage_modal}
                          title='Sacrifice XP'>
                <p className='my-2 text-gray-700 dark:text-gray-200'>
                    By setting this to a percentage, any XP gained from monsters, Adventures or Exploration
                    will be reduced <strong>BEFORE</strong> additional modifiers and quest items are applied. This amount will then be
                    applied to the skill XP.
                </p>
                <p className='my-2 text-gray-700 dark:text-gray-200'>
                    As you level these skills the XP will go up by 100 per skill level, that is at level 1, you need 100XP, but at level 10, you need 1000.
                </p>
                <p className='my-2 text-gray-700 dark:text-gray-200'>
                    From level 1-10, you will get a base of 25XP + what you choose to sacrifice, at level 10 to 99, you get 100 XP. Level 100 - 499 you get 500XP and finally level 500-999 you get 750XP.
                </p>
            </HelpDialogue>
        );
    }
}
