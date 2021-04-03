import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import moment from 'moment';
import {CountdownCircleTimer} from 'react-countdown-circle-timer';
import {truncate} from 'lodash';

export default class UnitRecruitmentQueue extends React.Component {

  constructor(props) {
    super(props);

    this.unit_queue_config = {
      page_size: 25,
      length_menu: [25],
      show_filter: true,
      show_pagination: true,
      pagination: 'advance',
    }

    this.unit_queue_columns = [
      {
        name: "unit-name",
        text: "Unit Name",
        sortable: true,
        cell: row => <div data-tag="allowRowEvents">
          <div>{this.fetchUnitName(row.game_unit_id)}</div>
        </div>,
      },
      {
        key: "amount",
        text: "Recruiting Amount",
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

  fetchUnitName(unitId) {
    return this.props.kingdom.recruitable_units.filter((ru) => ru.id === unitId)[0].name;
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
          config={this.unit_queue_config}
          records={this.props.kingdom.unit_queue}
          columns={this.unit_queue_columns}
          onRowClicked={this.props.queueData}
        />
      </div>
    );
  }
}
