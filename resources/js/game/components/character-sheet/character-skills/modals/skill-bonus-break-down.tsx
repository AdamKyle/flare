import React from "react";
import ItemNameColorationText from "../../../items/item-name/item-name-coloration-text";
import HelpDialogue from "../../../ui/dialogue/help-dialogue";

export default class SkillBonusBreakDown extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    renderSections(): JSX.Element[] | [] {
        return this.props.items.map((item: any, index: number) => {
            return (
                <div>
                    <div className="p-4">
                        <div className="flex justify-between items-center mb-2">
                            <span className="font-semibold w-24">Name:</span>
                            <span>
                                <ItemNameColorationText
                                    item={item}
                                    custom_width={false}
                                />
                            </span>
                        </div>

                        <div className="flex justify-between items-center mb-2">
                            <span className="font-semibold w-24">Type:</span>
                            <span>{item.type}</span>
                        </div>

                        {item.type !== "quest" ? (
                            <div className="flex justify-between items-center mb-2">
                                <span className="font-semibold w-24">
                                    Equipped Position:
                                </span>
                                <span>{item.position}</span>
                            </div>
                        ) : null}

                        <div className="flex justify-between items-center mb-2">
                            <span className="font-semibold w-24">Bonus:</span>
                            <span>
                                {this.props.bonus_type === "xp"
                                    ? (item.skill_training_bonus * 100).toFixed(
                                          2,
                                      ) + "%"
                                    : (item.skill_bonus * 100).toFixed(2) + "%"}
                            </span>
                        </div>

                        {index < this.props.items.length - 1 && (
                            <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                        )}
                    </div>
                </div>
            );
        });
    }

    render() {
        const sections = this.renderSections();
        return (
            <HelpDialogue
                is_open={true}
                manage_modal={this.props.manage_modal}
                title={this.props.title}
            >
                <p className="my-4">
                    This is a break down of all the items effecting your{" "}
                    {this.props.bonus_type === "xp"
                        ? "XP Bonus"
                        : "Skill Bonus"}
                    .
                </p>
                {this.props.bonus_type === "xp" ? (
                    <p className={"mb-2"}>
                        These items are responsible for raising the amount of xp
                        you gain per level. There are various enchantments that
                        effect various types of skills.
                    </p>
                ) : (
                    <p className={"mb-2"}>
                        These items are responsible for raising the bonus of the
                        skill, that is - how successful the skill is when it
                        fires. For example a Accuracy skill with a bonus of 50%
                        is better then a Accuracy with a bonus of 5%. There are
                        various enchantments that help raise this bonus.
                    </p>
                )}
                {sections.length > 0 ? (
                    sections
                ) : (
                    <p className={"text-gray-700 dark:text-gray-400 my-6"}>
                        There are no items effecting the bonus of this skill.
                    </p>
                )}
            </HelpDialogue>
        );
    }
}
