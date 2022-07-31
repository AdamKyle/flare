import React from "react";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";

export default class LocationDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Dialogue is_open={true}
                      handle_close={this.props.handle_close}
                      title={this.props.title + ' (X/Y): ' + this.props.location.x + '/' + this.props.location.y}
            >
            </Dialogue>
        )
    }
}
