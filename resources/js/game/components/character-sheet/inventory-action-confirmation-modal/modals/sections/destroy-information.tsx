import React from "react";

export default class DestroyInformation extends React.Component<{}, {}> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <>
                <p>
                    Are you sure? You will destroy all items in your inventory.
                    Quest Items, Gems and Alchemy Items will be untouched as
                    will anything in Sets or currently equipped.{" "}
                    <strong>You cannot undo this action</strong>.
                </p>
            </>
        );
    }
}
