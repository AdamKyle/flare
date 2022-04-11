import React from "react";

export default class Trinketry extends React.Component<any, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            selected_item: null,
            craftable_items: [],
        }
    }

    render() {
        return (
            <p>Content Here</p>
        );
    }
}
