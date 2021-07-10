import React from 'react';
import {Line} from 'react-chartjs-2';

export default class SiteAccessedAllTime extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      data: {
        datasets: [
          {
            label: this.props.label,
            fill: false,
            lineTension: 0.1,
            backgroundColor: 'rgb(24,103,196)',
            borderColor: 'rgb(24,103,196)',
            borderCapStyle: 'butt',
            borderDash: [],
            borderDashOffset: 0.0,
            borderJoinStyle: 'miter',
            pointBorderColor: 'rgb(24,103,196)',
            pointBackgroundColor: '#fff',
            pointBorderWidth: 10,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: 'rgb(24,103,196)',
            pointHoverBorderColor: 'rgb(24,103,196)',
            pointHoverBorderWidth: 2,
            pointRadius: 1,
            pointHitRadius: 10,
          }
        ]
      }
    }
  }

  componentDidMount() {
    axios.get(this.props.apiUrl).then((result) => {
      let dataset = {...this.state.data};

      dataset.labels = result.data.stats.labels;
      dataset.datasets[0].data = result.data.stats.data;

      this.setState({
        data: dataset,
        loading: false,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          this.props.openTimeOutModal()
        }
      }
    });
  }

  render() {

    if (this.state.loading) {
      return (<div className="mb-4 text-center">Please wait...</div>);
    }

    return (
      <div className="mb-4">
        <h6>{this.props.title}</h6>
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
