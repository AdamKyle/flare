import React from "react";
import BasicCard from "../../ui/cards/basic-card";

export default class SmallCouncil extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {};
    }

    closeSmallCouncil() {}

    render() {
        return (
            <BasicCard>
                <div className="text-right cursor-pointer text-red-500">
                    <button onClick={this.closeSmallCouncil.bind(this)}>
                        <i className="fas fa-minus-circle"></i>
                    </button>
                </div>
                Content here ...
            </BasicCard>
        );
    }
}
