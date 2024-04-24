import React from "react";

type UserId = { user_id: number };

export default class RefreshComponent extends React.Component<UserId, {}> {
    private refreshListener: any;

    constructor(props: UserId) {
        super(props);

        // @ts-ignore
        this.refreshListener = Echo.private(
            "refresh-listener-" + this.props.user_id,
        );
    }

    componentDidMount() {
        this.refreshListener.listen(
            "Admin.Events.RefreshUserScreenEvent",
            (event: any) => {
                location.reload();
            },
        );
    }

    render() {
        return null;
    }
}
