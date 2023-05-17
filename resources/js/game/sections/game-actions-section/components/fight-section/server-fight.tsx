import React from "react";
import ServerFightProps from "./types/server-fight-props";
import AttackButton from "../../../../components/ui/buttons/attack-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import HealthMeters from "../health-meters";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import clsx from "clsx";

export default class ServerFight extends React.Component<ServerFightProps, {}> {

    constructor(props: any) {
        super(props);
    }

    attackButtonDisabled() {
        return this.props.monster_health <= 0 || this.props.character_health <= 0 || this.props.is_dead || !this.props.can_attack || this.props.monster_id === 0
    }

    render() {
        return (
            <div className='relative'>
                    <div className={clsx('mt-4 mb-4 text-xs text-center', {
                        'hidden': this.attackButtonDisabled()
                    })}>
                        <AttackButton additional_css={'btn-attack'} icon_class={'ra ra-sword'} on_click={() => this.props.attack('attack')} disabled={this.attackButtonDisabled() || this.props.preforming_action}/>
                        <AttackButton additional_css={'btn-cast'} icon_class={'ra ra-burning-book'} on_click={() => this.props.attack('cast')} disabled={this.attackButtonDisabled() || this.props.preforming_action}/>
                        <AttackButton additional_css={'btn-cast-attack'} icon_class={'ra ra-lightning-sword'} on_click={() => this.props.attack('cast_and_attack')} disabled={this.attackButtonDisabled() || this.props.preforming_action}/>
                        <AttackButton additional_css={'btn-attack-cast'} icon_class={'ra ra-lightning-sword'} on_click={() => this.props.attack('attack_and_cast')} disabled={this.attackButtonDisabled() || this.props.preforming_action}/>
                        <AttackButton additional_css={'btn-defend'} icon_class={'ra ra-round-shield'} on_click={() => this.props.attack('defend')} disabled={this.attackButtonDisabled() || this.props.preforming_action}/>
                        <a href='/information/combat' target='_blank' className='ml-2'>Help <i
                            className="fas fa-external-link-alt"></i></a>
                    </div>
                    <div className={clsx('mt-1 text-xs text-center ml-[-50px]', { 'hidden': this.attackButtonDisabled() })}>
                        <span className={'w-10 mr-4 ml-4'}>Atk</span>
                        <span className={'w-10 ml-6'}>Cast</span>
                        <span className={'w-10 ml-4'}>Cast & Atk</span>
                        <span className={'w-10 ml-2'}>Atk & Cast</span>
                        <span className={'w-10 ml-2'}>Defend</span>
                    </div>
                    {
                        this.props.monster_max_health > 0 ?
                            <div className={clsx('mb-4 max-w-md m-auto', {
                                'mt-4': this.attackButtonDisabled()
                            })}>
                                <HealthMeters is_enemy={true} name={this.props.monster_name} current_health={this.props.monster_health} max_health={this.props.monster_max_health} />
                                <HealthMeters is_enemy={false} name={this.props.character_name} current_health={this.props.character_health} max_health={this.props.character_max_health} />
                            </div>
                            : null
                    }
                    {
                        this.props.preforming_action ?
                            <div className='w-1/2 ml-auto mr-auto'>
                                <LoadingProgressBar />
                            </div>
                        : null
                    }
                    <div className='italic text-center mb-4'>
                        {this.props.children}
                    </div>
                    <div className='text-center'>
                        {
                            typeof this.props.manage_server_fight !== 'undefined' ?
                                <DangerButton button_label={'Leave Fight'} on_click={this.props.manage_server_fight} additional_css={'mr-4'} disabled={this.props.is_dead}/>
                            : null
                        }
                        
                        {
                            this.props.is_dead ?
                                <PrimaryButton button_label={'Revive'} on_click={this.props.revive.bind(this)} disabled={!this.props.can_attack}/>
                            : null
                        }
                    </div>
                </div>
        );
    }
}