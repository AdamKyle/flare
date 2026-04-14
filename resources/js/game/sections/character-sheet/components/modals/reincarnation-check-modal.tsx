import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ReincarnateCheckModelProps from "../../../../lib/game/character-sheet/types/modal/reincarnate-check-model-props";

export default class ReincarnationCheckModal extends React.Component<
    ReincarnateCheckModelProps,
    any
> {
    constructor(props: ReincarnateCheckModelProps) {
        super(props);
    }

    render() {
        return (
            <Dialogue
                is_open={true}
                handle_close={this.props.manage_modal}
                title={"Are you sure?"}
                secondary_actions={{
                    secondary_button_disabled: false,
                    secondary_button_label: "Yes I am sure",
                    handle_action: this.props.handle_reincarnate,
                }}
            >
                <div className="my-4">
                    <p className="mb-4">
                        <strong>This action cannot be undone!</strong>
                    </p>
                    <p className="mb-4">
                        Are you sure you want to reincarnate? Here's what will
                        happen:
                    </p>
                    <p className="mb-4">
                        When{" "}
                        <a href="/information/reincarnation" target="_blank">
                            Reincarnating{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>{" "}
                        Your character will be reset to level 1, you will carry
                        over 5% of your base unmodded stats at the time you
                        choose to reincarnate which can only be level 5,000.
                    </p>
                    <p className="mb-4">
                        You can reincarnate as many times as you would like
                        until your base stats reach 9,999,999. As you
                        reincarnate your base stats will increase by 5% each
                        time making you slightly more powerful each time you
                        reincarnate. Add onto this your{" "}
                        <a href="/information/gear-progression" target="_blank">
                            gear <i className="fas fa-external-link-alt"></i>
                        </a>{" "}
                        ,{" "}
                        <a href="/information/holy-items" target="_blank">
                            holy oils{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>{" "}
                        ,{" "}
                        <a href="/information/enchanting" target="_blank">
                            enchantments{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>{" "}
                        and so on and your character will be able to take on
                        stronger more powerful creatures.
                    </p>
                </div>
            </Dialogue>
        );
    }
}
