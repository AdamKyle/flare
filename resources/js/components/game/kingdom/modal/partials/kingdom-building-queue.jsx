import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import moment from 'moment';
import {CountdownCircleTimer} from 'react-countdown-circle-timer';

export default class KingdomBuildingQueue extends React.Component {

  constructor(props) {
    super(props);

    this.building_queue_config = {
      page_size: 5,
      length_menu: [5, 10, 15],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.building_queue_columns = [
      {
        name: "building-name",
        text: "Building Name",
        sortable: true,
        cell: row => <div data-tag="allowRowEvents">
          <div>{this.fetchBuildingName(row.building_id)}</div>
        </div>,
      },
      {
        key: "to_level",
        text: "Upgrading To Level",
        sortable: true
      },
      {
        name: "completed-at",
        text: "Completed in",
        sortable: true,
        cell: row => <div data-tag="allowRowEvents">
          <div>{this.fetchTime(row.completed_at)}</div>
        </div>,

      },
    ];
  }

  fetchBuildingName(buildingId) {
    return this.props.kingdom.buildings.filter((b) => b.id === buildingId)[0].name
  }

  fetchTime(time) {
    let now = moment();
    let then = moment(time);

    let duration = moment.duration(then.diff(now)).asSeconds();

    const isHours = (duration / 3600) >= 1;

    if (duration > 0) {
      return (
        <>
          <div className="float-left">
            {isHours ?
              <CountdownCircleTimer
                isPlaying={truncate}
                duration={duration}
                initialRemainingTime={duration}
                colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
                size={40}
                strokeWidth={2}
                onComplete={() => [false, 0]}
              >
                {({remainingTime}) => (remainingTime / 3600).toFixed(0)}
              </CountdownCircleTimer>
              :
              <CountdownCircleTimer
                isPlaying={true}
                duration={duration}
                initialRemainingTime={duration}
                colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
                size={40}
                strokeWidth={2}
                onComplete={() => [false, 0]}
              >
                {({remainingTime}) => (remainingTime / 60).toFixed(0)}
              </CountdownCircleTimer>
            }
          </div>
          <div className="float-left mt-2 ml-3">{isHours ? 'Hours' : 'Minutes'}</div>
        </>

      );
    } else {
      return null;
    }
  }

  render() {
    return (
      <div className="mt-3">
        <ReactDatatable
          config={this.building_queue_config}
          records={this.props.kingdom.building_queue}
          columns={this.building_queue_columns}
          onRowClicked={this.props.queueData}
        />
      </div>
    );
  }
}
