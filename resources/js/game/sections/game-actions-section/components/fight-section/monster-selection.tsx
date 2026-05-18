import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import MonsterSelectionProps from "./types/monster-selection-props";

export default class MonsterSelection extends React.Component<
    MonsterSelectionProps,
    {}
> {
    constructor(props: MonsterSelectionProps) {
        super(props);
    }

    render() {
        return (
            <div className="mx-auto w-full md:w-2/3">
                <div className="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-2 items-start">
                    <div>
                        <Select
                            onChange={this.props.set_monster_to_fight}
                            options={this.props.monsters}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={this.props.default_monster}
                        />
                    </div>

                    <div className="text-center mt-4 lg:mt-0 lg:text-left">
                        <PrimaryButton
                            button_label={"Attack"}
                            on_click={this.props.attack}
                            disabled={this.props.is_attack_disabled}
                        />

                        {typeof this.props.close_monster_section !==
                        "undefined" ? (
                            <DangerButton
                                button_label={"Close"}
                                on_click={this.props.close_monster_section}
                                additional_css={"ml-4"}
                            />
                        ) : null}
                    </div>
                </div>
            </div>
        );
    }
}
