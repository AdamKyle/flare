import React, {ReactNode} from "react";
import QuestItemProps from "../types/quest-item-props";
import clsx from "clsx";

export default class QuestItem extends React.Component<QuestItemProps, {}> {

    constructor(props: QuestItemProps) {
        super(props);
    }

    shouldRenderColumns(): boolean {
        return this.props.item.skill_name !== null && (
            this.props.item.devouring_light > 0 ||
            this.props.item.devouring_darkness > 0
        );
    }



    renderXpBonus(): ReactNode {

        if (this.props.item.xp_bonus <= 0) {
            return
        }

        return (
            <div>
                <h4 className='text-sky-600 dark:text-sky-300'>XP Bonus</h4>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Xp Bonus</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>{(this.props.item.xp_bonus * 100).toFixed(2)}%</dd>
                    <dt>Ignores Caps?</dt>
                    <dd>{this.props.item.ignores_caps ? 'Yes' : 'No'}</dd>
                </dl>
            </div>
        )
    }

    renderDevouringBonus(): ReactNode {

        if (!(
            this.props.item.devouring_light > 0 ||
            this.props.item.devouring_darkness > 0
        )) {
            return;
        }

        return (
            <div>
                <h4 className='text-sky-600 dark:text-sky-300'>Devouring Chance</h4>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Devouring Light</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>{(this.props.item.devouring_light * 100).toFixed(2)}%</dd>
                    <dt>Devouring Darkness</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>{(this.props.item.devouring_darkness * 100).toFixed(2)}%</dd>
                </dl>
            </div>
        )
    }

    renderSkillModifierSection() {

        if (this.props.item.skill_name === null) {
            return;
        }

        return (
            <div>
                <h4 className='text-sky-600 dark:text-sky-300'>Skill Modifiers</h4>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    <dt>Effects Skill</dt>
                    <dd>{this.props.item.skill_name}</dd>
                    <dt>Skill Bonus</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>{(this.props.item.skill_bonus * 100).toFixed(2)}%</dd>
                    <dt>Skill XP Bonus</dt>
                    <dd className={'text-green-700 dark:text-green-500'}>{(this.props.item.skill_training_bonus * 100).toFixed(2)}%</dd>
                </dl>
            </div>
        );

    }

    renderColumns(): ReactNode {

        if (this.props.item.skill_name === null) {
            return;
        }

        return (
            <div>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 '></div>
                <div className="grid md:grid-cols-2 gap-2">
                    <div>
                        {this.renderXpBonus()}
                        {
                            this.props.item.xp_bonus > 0 && (
                                this.props.item.devouring_light > 0 ||
                                this.props.item.devouring_darkness > 0
                            ) ?
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                : null
                        }
                        {this.renderDevouringBonus()}
                    </div>
                    <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden'></div>
                    <div>
                        {this.renderSkillModifierSection()}
                    </div>
                </div>
            </div>
        );
    }

    renderSingleItem() {
        if (this.props.item.skill_name === null && !(
            this.props.item.devouring_light > 0 ||
            this.props.item.devouring_darkness > 0
        ) && this.props.item.xp_bonus <= 0) {
            return;
        }

        return (
            <>
                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 '></div>
                <div className={clsx('mb-4', {
                    'hidden':  this.props.item.xp_bonus <= 0
                })}>
                    {this.renderXpBonus()}
                </div>
                <div className={clsx('mb-4', {
                    'hidden': !(
                        this.props.item.devouring_light > 0 ||
                        this.props.item.devouring_darkness > 0
                    )
                })}>
                    {this.renderDevouringBonus()}
                </div>
                <div className={clsx('mb-4', {
                    'hidden': this.props.item.skill_name === null
                })}>
                    {this.renderSkillModifierSection()}
                </div>
            </>
        )
    }

    render() {
        return (
            <div className='max-h-[400px] overflow-y-auto'>
            <div className='mb-4 mt-4 text-sky-700 dark:text-sky-300'
                     dangerouslySetInnerHTML={{__html: this.props.item.description}}/>
                {
                    this.shouldRenderColumns() ?
                        this.renderColumns()
                        :
                        this.renderSingleItem()
                }
            </div>
        )
    }
}
