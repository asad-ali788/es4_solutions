function renderChart(chartData, elementId) {
    const categories = chartData.map(item => item.period);
    const spendData = chartData.map(item => item.spend);
    const salesData = chartData.map(item => item.sales);

    const options = {
        chart: {
            type: 'bar',
            height: 300
        },
        series: [
            { name: 'Spend ($)', data: spendData },
            { name: 'Sales ($)', data: salesData }
        ],
        xaxis: {
            categories: categories,
            labels: { rotate: -12 },
        },
        yaxis: [{
            // title: { text: 'Amount ($)' },
            labels: {
                formatter: (val) => {
                    return val / 1000 + 'K'
                }
            }
        }],
        dataLabels: {
            enabled: false,
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        }, plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 5,
                borderRadiusApplication: 'end'
            },
        },
        legend: { position: 'bottom' },
        colors: ['#008ffb', '#00e396'],
        tooltip: {
            shared: true, intersect: false,
            y: {
                formatter: (val) => val // show exact number in tooltip
            }
        },
    };

    const chart = new ApexCharts(document.querySelector(elementId), options);
    chart.render();
}
