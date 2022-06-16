import React from 'react';

export default class RefreshComponent extends React.Component<any, any> {

    private refreshListener: any;

    constructor(props: any) {
        super(props);

        // @ts-ignore
        this.refreshListener = Echo.private('refresh-listener-' + this.props.user_id);
    }

    componentDidMount() {
        this.refreshListener.listen('Admin.Events.RefreshUserScreenEvent', (event: any) => {
            location.reload();
        });
    }

    render() {
        return null;
    }
}
