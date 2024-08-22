import React from "react";
import HelpDialogue from "../../../../../components/ui/dialogue/help-dialogue";

export default class SkillHelpModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <HelpDialogue
                is_open={true}
                manage_modal={this.props.manage_modal}
                title="Sacrifice XP"
            >
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    By setting this to a percentage, any XP gained from monsters
                    or Exploration will be reduced <strong>BEFORE</strong>{" "}
                    additional modifiers and quest items are applied. This
                    amount will then be applied to the skill XP.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    For example, if you set this at 10% - recommended for new
                    players - and then kill a monster giving you 100 XP, you
                    will get 90 XP + all modifiers and bonuses.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    As you advance in the game and get more established by being
                    able to kill harder creatures for more xp, you will be in a
                    better position to sacrifice more of your XP to level the
                    skill faster.
                </p>
                <p className="my-2 text-gray-700 dark:text-gray-200">
                    There are various Enchantments that can help level skills as
                    well. For example,{" "}
                    <a
                        href="/information/enchanting?table-filters[types]=7"
                        target="_blank"
                    >
                        <i className="fas fa-external-link-alt"></i> here's the
                        Accuracy enchantments, scroll down after clicking to see
                        them.
                    </a>
                </p>
            </HelpDialogue>
        );
    }
}
