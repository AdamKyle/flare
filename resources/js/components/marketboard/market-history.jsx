import React from 'react';
import ReactDOM from 'react-dom';
import {Line} from 'react-chartjs-2';
import {Dropdown} from "react-bootstrap";

export default class MarketHistory extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      data: {
        datasets: [
          {
            label: 'Sold For',
            fill: false,
            lineTension: 0.1,
            backgroundColor: 'rgba(13, 55, 99)',
            borderColor: 'rgba(13, 55, 99)',
            borderCapStyle: 'butt',
            borderDash: [],
            borderDashOffset: 0.0,
            borderJoinStyle: 'miter',
            pointBorderColor: 'rgba(13, 55, 99)',
            pointBackgroundColor: '#fff',
            pointBorderWidth: 10,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: 'rgba(13, 55, 99)',
            pointHoverBorderColor: 'rgba(13, 55, 99)',
            pointHoverBorderWidth: 2,
            pointRadius: 1,
            pointHitRadius: 10,
          }
        ]
      },
      dropDown: [
        {type: 'reset', name: 'Reset Filter'},
        {type: '24 hours', name: '24 Hours'},
        {type: '1 week', name: 'Week'},
        {type: '1 month', name: 'Month'},
      ]
    }

    this.update = Echo.join('update-market');
  }

  componentDidMount() {

    this.fetchMarketHistory();

    this.update.listen('Game.Core.Events.UpdateMarketBoardBroadcastEvent', (event) => {
      this.fetchMarketHistory();
    });
  }

  componentWillUnmount() {
    Echo.leave('update-market');
  }

  componentDidUpdate(prevProps) {
    if (this.props.type !== prevProps.type) {
      this.fetchMarketHistory();
    }
  }

  fetchMarketHistory(when) {
    axios.get('/api/market-board/history', {
      params: {
        type: this.props.type,
        when: typeof when !== 'undefined' ? when : null,
      }
    }).then((result) => {

      let dataset = {...this.state.data};

      dataset.labels = result.data.labels;
      dataset.datasets[0].data = result.data.data;

      this.setState({
        data: dataset,
        loading: false,
      });

    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return window.location = '/game';
        }
      }
    });
  }

  changeWhen(event) {
    this.fetchMarketHistory(event.target.dataset.when);
  }

  buildDropDownOptions() {
    return this.state.dropDown.map((button) => {
      return <Dropdown.Item onClick={this.changeWhen.bind(this)} data-when={button.type}
                            key={"button-" + button.type}>{button.name}</Dropdown.Item>
    })
  }

  render() {

    if (this.state.loading) {
      return (<div className="mb-4 text-center">Please wait...</div>);
    }

    return (
      <div className="mb-4 clearfix">
        <h6 className="float-left">Market History</h6>
        <Dropdown size="sm" className="float-right">
          <Dropdown.Toggle variant="primary" id="dropdown-basic">
            Show history for last?
          </Dropdown.Toggle>

          <Dropdown.Menu>
            {this.buildDropDownOptions()}
          </Dropdown.Menu>
        </Dropdown>
        <Line
          data={this.state.data}
          width={300}
          height={75}
          options={{maintainAspectRatio: true, scales: {xAxes: [{ticks: {display: false}}]}}}
        />
      </div>
    );
  }
}

const marketHistory = document.getElementById('admin-market-history');

if (marketHistory !== null) {
  ReactDOM.render(
    <MarketHistory/>,
    marketHistory
  );
}
