import React from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import CharacterTabs from "./components/character-tabs";
import CharacterSkillsTabs from "./components/character-skills-tabs";
import CharacterInventoryTabs from "./components/character-inventory-tabs";

export default class CharacterSheet extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return(
            <div>
                <div className='flex flex-col lg:flex-row w-full gap-2'>
                    <BasicCard additionalClasses={'overflow-x-auto lg:w-1/2'}>
                        <CharacterTabs />
                    </BasicCard>
                    <BasicCard additionalClasses={'overflow-x-auto lg:w-1/2'}>
                        <div className='grid lg:grid-cols-2 gap-2'>
                            <div>
                                <dl>
                                    <dt>Gold:</dt>
                                    <dd>2,000,000,000,000</dd>
                                    <dt>Gold Dust:</dt>
                                    <dd>2,000,000,000</dd>
                                    <dt>Shards:</dt>
                                    <dd>2,000,000,000</dd>
                                    <dt>Copper Coins:</dt>
                                    <dd>100,000</dd>
                                </dl>
                            </div>
                            <div className='border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div>
                                <dl>
                                    <dt>Inventory Max:</dt>
                                    <dd>75</dd>
                                    <dt>Inventory Count:</dt>
                                    <dd>50</dd>
                                </dl>
                                <div className='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                <dl>
                                    <dt>Damage Stat:</dt>
                                    <dd>Str</dd>
                                    <dt>To Hit:</dt>
                                    <dd>Accuracy, Dex</dd>
                                </dl>
                            </div>
                        </div>
                    </BasicCard>
                </div>
                <div className='flex flex-col lg:flex-row gap-2 w-full mt-2'>
                    <BasicCard additionalClasses={'overflow-x-auto lg:w-1/2 lg:h-fit'}>
                        <CharacterSkillsTabs character_id={this.props.character_id}/>
                    </BasicCard>
                    <BasicCard additionalClasses={'overflow-x-auto lg:w-1/2 lg:h-fit'}>
                        <CharacterInventoryTabs character_id={this.props.character_id}/>
                    </BasicCard>
                </div>
            </div>
        );
    }
}
