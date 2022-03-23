import React from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import CharacterTabs from "./components/character-tabs";
import InfoAlert from "../../components/ui/alerts/simple-alerts/info-alert";
import CharacterSkillsTabs from "./components/character-skills-tabs";
import CharacterInventoryTabs from "./components/character-inventory-tabs";

export default class CharacterSheet extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return(
            <div>
                <div className='grid md:grid-cols-2 gap-2'>
                    <BasicCard additionalClasses={'md:max-h-[225px]'}>
                        <CharacterTabs />
                    </BasicCard>
                    <BasicCard additionalClasses={'md:max-h-[225px]'}>
                        <div className='grid md:grid-cols-2 gap-2'>
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
                            <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                            <div>
                                <dl>
                                    <dt>Inventory Max:</dt>
                                    <dd>75</dd>
                                    <dt>Inventory Count:</dt>
                                    <dd>50</dd>
                                </dl>
                                <div className='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                                <dl>
                                    <dt>Damage Stat:</dt>
                                    <dd>Str</dd>
                                    <dt>To Hit:</dt>
                                    <dd>Accuracy, Dex</dd>
                                </dl>
                            </div>
                        </div>
                        <InfoAlert additional_css={'my-5'}>
                            <strong>Inventory Count</strong> does not apply to your sets, equipped items or quest items. It only counts the amount of usable item slots and inventory slots you have used.<br />
                            <strong>Damage Stat</strong> refers to the stat you use to do damage with, the higher the stat the more damage.<br />
                            <strong>To Hit</strong> refers to both the skill and stat in which is used to determine if you can hit anything or not.<br />
                        </InfoAlert>
                    </BasicCard>
                </div>
                <div className='mt-2 grid md:grid-cols-2 gap-2'>
                    <BasicCard>
                        <CharacterSkillsTabs />
                    </BasicCard>
                    <BasicCard>
                        <CharacterInventoryTabs />
                    </BasicCard>
                </div>
            </div>
        );
    }
}
