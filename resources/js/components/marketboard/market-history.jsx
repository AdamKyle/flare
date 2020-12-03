import React from 'react';
import { DateTime } from 'luxon';
import { Line } from 'react-chartjs-2';

export default class MarketHistory extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      data: {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
        datasets: [
          {
            label: 'Market History (Last 30 days)',
            fill: false,
            lineTension: 0.1,
            backgroundColor: 'rgba(75,192,192,0.4)',
            borderColor: 'rgba(75,192,192,1)',
            borderCapStyle: 'butt',
            borderDash: [],
            borderDashOffset: 0.0,
            borderJoinStyle: 'miter',
            pointBorderColor: 'rgba(75,192,192,1)',
            pointBackgroundColor: '#fff',
            pointBorderWidth: 1,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: 'rgba(75,192,192,1)',
            pointHoverBorderColor: 'rgba(220,220,220,1)',
            pointHoverBorderWidth: 2,
            pointRadius: 1,
            pointHitRadius: 10,
            data: [65, 59, 80, 81, 56, 55, 40]
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

  fetchMarketHistory() {
    axios.get('/api/market-board/history').then((result) => {
      
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
        <Line data={this.state.data} width={300} height={50} options={{ maintainAspectRatio: true }} />
      </div>
    );
  }
}