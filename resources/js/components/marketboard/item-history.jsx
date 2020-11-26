import React    from 'react';
import ReactDOM from 'react-dom';
import { Line } from 'react-chartjs-2';

export default class ItemHistory extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      data: {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
        datasets: [
          {
            label: 'My First dataset',
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
  }

  render() {
    return (
      <div className="mb-4">
        <h6>Market History</h6>
        <Line data={this.state.data} width={300} height={150} options={{ maintainAspectRatio: true }} />
      </div>
    );
  }
}

const itemHistory = document.getElementById('market-history');
const character = document.head.querySelector('meta[name="character"]');

if (itemHistory !== null) {
  ReactDOM.render(
    <ItemHistory characterId={character.content} item={document.querySelector('#market-history').getAttribute('item-id')} />,
    itemHistory
  );
}