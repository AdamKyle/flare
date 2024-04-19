import React from "react";

export default class VoidanceDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div>
                <p className='mt-3'>
                    Devouring Light and Devouring Darkness are considered Void and Devoid. These come from Quest items
                    and Enchantments. Some planes will completely remove your ability to void and devoid enemies.
                </p>
                <p className='my-3'>
                    Voiding (Devouring Light) means none of your or the enemies enchantments can fire. This can
                    completely
                    wreck a player if they get voided by a mid game to late game creature.
                </p>
                <p className='mb-6'>
                    Devoiding (Devouring Darkness) means that you or the enemy have stopped the other from being able to
                    void you.
                    For example if you are devoted, you cannot void the enemy and vice versa.
                </p>
                <dl>
                    <dt>Devouring Light:</dt>
                    <dt>{(this.state.stat_details.devouring_light * 100).toFixed(2)}%</dt>
                    <dt>Devouring Light Res.:</dt>
                    <dt>{(this.state.stat_details.devouring_light_res * 100).toFixed(2)}%</dt>
                    <dt>Devouring Darkness:</dt>
                    <dt>{(this.state.stat_details.devouring_darkness * 100).toFixed(2)}%</dt>
                    <dt>Devouring Darkness Res.:</dt>
                    <dt>{(this.state.stat_details.devouring_darkness_res * 100).toFixed(2)}%</dt>
                </dl>
                <p className='mt-4'>
                    For more information please see <a href='/information/voidance' target='_blank'>Voidance
                    Help <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        );
    }
}
