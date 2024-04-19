import React from "react";

export default class AmbushAndCounterDetails extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div>
                <p className='my-3'>
                    Ambush and Counter chance come from trinkets, which raise your chance to ambush an enemy before the
                    fight begins or to
                    counter an enemies attack.
                </p>
                <p className='mb-6'>
                    Ambush and Counter resistance also come from trinkets and increase your chance to resist late game
                    creatures ability to
                    ambush you before the battle starts or counter your attacks when you attack.
                </p>
                <dl>
                    <dt>Ambush Chance</dt>
                    <dd>{(this.state.stat_details.ambush_chance * 100).toFixed(2)}%</dd>
                    <dt>Ambush Resistance</dt>
                    <dd>{(this.state.stat_details.ambush_resistance_chance * 100).toFixed(2)}%</dd>
                    <dt>Counter Chance</dt>
                    <dd>{(this.state.stat_details.counter_chance * 100).toFixed(2)}%</dd>
                    <dt>Counter Resistance</dt>
                    <dd>{(this.state.stat_details.counter_resistance_chance * 100).toFixed(2)}%</dd>
                </dl>
                <p className='mt-4'>
                    For more information please see <a href='/information/ambush-and-counter' target='_blank'>Ambush
                    and Counter Help <i
                        className="fas fa-external-link-alt"></i></a>
                </p>
            </div>
        );
    }
}
