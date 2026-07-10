import React from "react";

export default class DestroyAllFromSetInformation extends React.Component<
    {},
    {}
> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <>
                <p>
                    Are you sure? You will destroy all items in your Crafted
                    Items Set, including trinkets.{" "}
                    <strong>You cannot undo this action</strong>.
                </p>
            </>
        );
    }
}
