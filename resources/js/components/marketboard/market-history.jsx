import React from 'react';
import ReactDOM from 'react-dom';
import { Line } from 'react-chartjs-2';

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
      }
    }

    this.update = Echo.join('update-market');
  }

  componentDidMount() {
    this.fetchMarketHistory();

    this.update.listen('Game.Core.Events.UpdateMarketBoardBroadcastEvent', (event) => {
      this.fetchMarketHistory();
    });
  }

  componentDidUpdate(prevProps) {
    if (this.props.type !== prevProps.type) {
      this.fetchMarketHistory();
    }
  }

  fetchMarketHistory() {
    axios.get('/api/market-board/history', {
      params: {
        type: this.props.type,
      }
    }).then((result) => {
      
      let dataset = {...this.state.data};

      dataset.labels           = result.data.labels;
      dataset.datasets[0].data = result.data.data;

      this.setState({
        data: dataset,
        loading: false,
      });

    }).catch((error) => {
      console.error(error);
    });
  }

  render() {

    if (this.state.loading) {
      return (<div className="mb-4 text-center">Please wait...</div>);
    }

    return (
      <div className="mb-4">
        <h6>Market History</h6>
        <Line 
          data={this.state.data} 
          width={300} 
          height={50} 
          options={{ maintainAspectRatio: true, scales: {xAxes: [{ticks: {display: false}}]} }} 
        />
      </div>
    );
  }
}

const marketHistory = document.getElementById('admin-market-history');

if (marketHistory !== null) {
  ReactDOM.render(
    <MarketHistory />,
    marketHistory
  );
}