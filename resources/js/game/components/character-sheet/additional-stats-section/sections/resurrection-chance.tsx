import React from "react";

export default class ResurrectionChance extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        if (this.props.character === null) {
            return null;
        }

        return (
            <div>
                <dl>
                    <dt>Resurrection Chance</dt>
                    <dd>
                        {(
                            this.props.character.resurrection_chance * 100
                        ).toFixed(2)}
                        %
                    </dd>
                </dl>
            </div>
        );
    }
}
