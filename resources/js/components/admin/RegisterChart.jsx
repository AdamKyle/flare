import React from 'react';
import ReactDOM from 'react-dom';
import {Line} from 'react-chartjs-2';

export default class SignInChart extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      data: {
        datasets: [
          {
            label: 'Registered',
            fill: false,
            lineTension: 0.1,
            backgroundColor: 'rgb(63,146,12)',
            borderColor: 'rgb(63,146,12)',
            borderCapStyle: 'butt',
            borderDash: [],
            borderDashOffset: 0.0,
            borderJoinStyle: 'miter',
            pointBorderColor: 'rgb(63,146,12)',
            pointBackgroundColor: '#fff',
            pointBorderWidth: 10,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: 'rgb(63,146,12)',
            pointHoverBorderColor: 'rgb(63,146,12)',
            pointHoverBorderWidth: 2,
            pointRadius: 1,
            pointHitRadius: 10,
          }
        ]
      }
    }

    this.update = Echo.private('update-admin-site-statistics-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/admin/site-statistics').then((result) => {
      let dataset = {...this.state.data};

      dataset.labels = result.data.registered.labels;
      dataset.datasets[0].data = result.data.registered.data;

      this.setState({
        data: dataset,
        loading: false,
      });
    }).catch((error) => {
      console.error(error);
    });

    this.update.listen('Flare.Events.UpdateSiteStatisticsChart', (event) => {
      let dataset = {...this.state.data};

      dataset.labels = event.registered.labels;
      dataset.datasets[0].data = event.registered.data;

      this.setState({
        data: dataset,
        loading: false,
      });
    });
  }

  componentDidUpdate(prevProps) {
    if (this.props.type !== prevProps.type) {
      this.fetchMarketHistory();
    }
  }

  render() {

    if (this.state.loading) {
      return (<div className="mb-4 text-center">Please wait...</div>);
    }

    return (
      <div className="mb-4">
        <h6>Registration Stats</h6>
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
