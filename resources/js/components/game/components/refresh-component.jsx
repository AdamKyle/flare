import React from 'react';

export default class RefreshComponent extends React.Component {

    constructor(props) {
        super(props);
        
        this.refreshListener  = Echo.private('refresh-listener-' + this.props.userId);
    }

    componentDidMount() {
        this.refreshListener.listen('Admin.Events.BannedUserEvent', (event) => {
            location.reload();
        });
    }

    render() {
        return null;
    }
} 