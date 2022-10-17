import React from "react";

export default class InventoryQuestItemDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }


    render() {
        console.log(this.props.item);
        return (
            <div className='max-h-[400px] overflow-y-auto'>
                <div className='mb-4 mt-4 text-sky-700 dark:text-sky-300' dangerouslySetInnerHTML={{__html: this.props.item.description}} />
                <div className='grid md:grid-cols-3 gap-3 mb-4'>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-300'>Skill Modifiers</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Effects Skill</dt>
                            <dd>{this.props.item.skill_name !== null ? this.props.item.skill_name : 'N/A'}</dd>
                            <dt>Skill Bonus</dt>
                            <dd>{(this.props.item.skill_bonus * 100).toFixed(2)}%</dd>
                            <dt>Skill XP Bonus</dt>
                            <dd>{(this.props.item.skill_training_bonus * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div className='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-300'>Devouring Chance</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Devouring Light</dt>
                            <dd>{(this.props.item.devouring_light * 100).toFixed(2)}%</dd>
                            <dt>Devouring Darkness</dt>
                            <dd>{(this.props.item.devouring_darkness * 100).toFixed(2)}%</dd>
                        </dl>
                    </div>
                    <div className='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div>
                        <h4 className='text-sky-600 dark:text-sky-300'>XP Bonus</h4>
                        <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            <dt>Xp Bonus</dt>
                            <dd>{(this.props.item.xp_bonus * 100).toFixed(2)}%</dd>
                            <dt>Ignores Caps?</dt>
                            <dd>{this.props.item.ignores_caps ? 'Yes' : 'No'}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        )
    }
}
