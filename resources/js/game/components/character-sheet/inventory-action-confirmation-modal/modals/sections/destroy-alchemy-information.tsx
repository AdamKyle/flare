import React from "react";

export default class DestroyAlchemyInformation extends React.Component<{}, {}> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <>
                <p>
                    Are you sure you want to do this? This action will destroy
                    all (Alchemy) items in your inventory?
                    <strong>You cannot undo this action</strong>.
                </p>
            </>
        );
    }
}
