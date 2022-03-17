import React, {Fragment} from "react";
import Ajax from "../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComponentLoading from "../../components/ui/loading/component-loading";
import DropDown from "../../components/ui/drop-down/drop-down";
import DangerButton from "../../components/ui/buttons/danger-button";
import FightSection from "./components/fight-section";
import CraftingSection from "./components/crafting-section";
import AttackButton from "../../components/ui/buttons/attack-button";

export default class Actions extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            character: {},
            monsters: [],
        }
    }

    componentDidMount() {
        (new Ajax()).setRoute('actions/' + this.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            this.setState({
                character: result.data.character,
                monsters: result.data.monsters,
                loading: false,
            })
        }, (error: AxiosError) => {

        });
    }

    openCrafting() {
    }

    attackKingdom() {
    }

    render() {
        return (
            <div className='px-4'>
                {
                    this.state.loading ?
                        <ComponentLoading />
                    :
                        <div className='grid md:grid-cols-4'>
                            <div className='md:col-start-1 md:col-span-1'>
                                <DropDown menu_items={[
                                    {
                                        name: 'Craft',
                                        icon_class: 'ra ra-hammer',
                                        on_click: this.openCrafting.bind(this)
                                    },
                                    {
                                        name: 'Enchant',
                                        icon_class: 'ra ra-burning-embers',
                                        on_click: this.openCrafting.bind(this)
                                    },
                                    {
                                        name: 'Alchemy',
                                        icon_class: 'ra ra-potion',
                                        on_click: this.openCrafting.bind(this)
                                    },
                                    {
                                        name: 'Work Bench',
                                        icon_class: 'ra ra-anvil',
                                        on_click: this.openCrafting.bind(this)
                                    }
                                ]} button_title={'Craft/Enchant'} />
                                <DangerButton button_label={'Attack Kingdom'} on_click={this.attackKingdom.bind(this)} />
                            </div>
                            <div className='border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden'></div>
                            <div className='md:col-start-2 md:col-span-2'>
                                <FightSection character={this.state.character} monsters={this.state.monsters} />
                                <CraftingSection />
                                <div className='my-4 text-xs text-center lg:text-left lg:pl-16'>
                                    <AttackButton />
                                    <AttackButton />
                                    <AttackButton />
                                    <AttackButton />
                                    <AttackButton />
                                </div>
                                <div className='font-italic text-center lg:pr-36'>
                                    Enemy Message
                                </div>
                            </div>
                        </div>
                }
            </div>
        )
    }
}
