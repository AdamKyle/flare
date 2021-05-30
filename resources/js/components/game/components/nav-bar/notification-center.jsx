import React from 'react';
import {Dropdown} from 'react-bootstrap';
import {getServerMessage} from '../../helpers/server_message';

export default class NotificationCenter extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      notifications: [],
      notificationCount: 0,
      loading: true,
    };

    this.notifications = Echo.private('update-notifications-' + this.props.userId);
  }

  componentDidMount() {

    axios.get('/api/notifications').then((result) => {
      this.setState({
        notifications: result.data,
        notificationCount: result.data.length,
        loading: false,
      });
    }).catch((err) => {
      console.err(err);
      return getServerMessage('something_went_wrong');
    });

    this.notifications.listen('Game.Core.Events.UpdateNotificationsBroadcastEvent', (event) => {
      this.setState({
        notifications: event.notifications,
        notificationCount: event.notifications.length,
      });
    });
  }

  clearAll(e) {
    e.preventDefault();

    axios.post('/api/notifications/clear').catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          location.reload()
        }
      }
    });
  }

  clearNotification(notification) {
    axios.post('/api/notifications/' + notification.id + '/clear').then((result) => {
      window.location = notification.url;
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          location.reload()
        }
      }
    });
  }

  customDropDown() {
    return React.forwardRef(({children, onClick}, ref) => (
      <a
        href=""
        ref={ref}
        onClick={(e) => {
          e.preventDefault();
          onClick(e);
        }}
      >
        <i className="far fa-comment-alt"></i>
        {this.state.notificationCount !== 0 ?
          <span className="badge badge-success">{this.state.notificationCount}</span> : null}
      </a>
    ));
  }

  customMenu() {
    return React.forwardRef(({children, style, className, 'aria-labelledby': labeledBy}, ref) => {
      return (
        <div
          ref={ref}
          style={style}
          className={className}
          aria-labelledby={labeledBy}
        >
          <div className="actions clearfix">
            <a href="#" onClick={this.clearAll.bind(this)} className="float-right mr-2">Clear</a>
          </div>
          <ul className="list-unstyled">
            {children}
          </ul>
        </div>
      );
    });
  }

  fetchIcon(type) {
    switch (type) {
      case 'adventure':
        return 'ra ra-trail'
      default:
        return '';
    }
  }

  buildNotifications() {
    if (_.isEmpty(this.state.notifications)) {
      return <div className="text-center">All caught up!</div>
    }

    const notifications = [];

    this.state.notifications.forEach((notification) => {
      notifications.push(
        <Dropdown.Item eventKey={notification.id} key={notification.id} data-notification-id={notification.id}
                       onClick={() => {
                         this.clearNotification(notification)
                       }}>
          <div className="notification">
            <div
              className={notification.status === 'success' ? 'success-bar pl-3 clearfix' : 'danger-bar pl-3 clearfix'}>
              <i className={this.fetchIcon(notification.type) + ' float-left mt-2 pr-2'}/>
              <p className="float-right">{notification.message}</p>
            </div>
          </div>
        </Dropdown.Item>
      )
    });

    return notifications;
  }

  render() {
    if (this.state.loading) {
      return null;
    }

    return (
      <div className="notification-center">
        <Dropdown>
          <Dropdown.Toggle as={this.customDropDown()} variant="success" id="dropdown-custom-components">
            Dropdown Button
          </Dropdown.Toggle>

          <Dropdown.Menu as={this.customMenu()} className="notification-center">
            {this.buildNotifications()}
          </Dropdown.Menu>
        </Dropdown>
      </div>
    )
  }
}
