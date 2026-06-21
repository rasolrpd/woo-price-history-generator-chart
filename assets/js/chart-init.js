document.addEventListener("DOMContentLoaded", function () {

    const ctx = document.getElementById('priceHistoryChart');
    if (!ctx) return;

    const formatPrice = (price) => {
        return new Intl.NumberFormat('fa-IR').format(price);
    };

    const data  = window.priceHistoryData  || {};
    const style = window.priceHistoryStyle || {};

    const themes = {

        default: {
            lineColor: '#2271b1',
            bgColor: 'rgba(34,113,177,0.1)',
            gridColor: '#e5e5e5',
            lineWidth: 3
        },

        dark: {
            lineColor: '#4cc9f0',
            bgColor: 'rgba(76,201,240,0.15)',
            gridColor: '#444',
            lineWidth: 3
        },

        minimal: {
            lineColor: '#111111',
            bgColor: 'rgba(0,0,0,0.05)',
            gridColor: '#dddddd',
            lineWidth: 2
        }

    };

    const selectedTheme = style.theme || 'default';
    const baseTheme = themes[selectedTheme] || themes.default;

    const lineColor = style.lineColor || baseTheme.lineColor;
    const bgColor   = style.bgColor   || baseTheme.bgColor;
    const gridColor = style.gridColor || baseTheme.gridColor;
    const lineWidth = style.lineWidth || baseTheme.lineWidth;

    new Chart(ctx, {

        type: 'line',

        data: {
            labels: data.labels,
            datasets: [{
                label: 'قیمت محصول',
                data: data.prices,

                borderColor: lineColor,
                backgroundColor: bgColor,
                borderWidth: lineWidth,

                fill: true,
                tension: 0.4,

                pointRadius: 5,
                pointHoverRadius: 8
            }]
        },

        options:{

            responsive:true,
            maintainAspectRatio:false,

            /* حذف فاصله‌های داخلی چارت */
            layout:{
                padding:{
                    top:0,
                    bottom:0,
                    left:0,
                    right:0
                }
            },

            font:{
                family:'EstedadChart'
            },

            scales: {

                y: {
                    grid:{
                        color:gridColor
                    },
                    ticks: {
                        callback: function(value){
                            return formatPrice(value);
                        },
                        font:{ family:'EstedadChart' }
                    }
                },

                x: {
                    
                    grid:{
                        color:gridColor
                    },
                    ticks:{
                        font:{ family:'EstedadChart' }
                    }
                }

            },

            plugins: {

                legend:{ display:false },

                tooltip:{
                    rtl:true,
                    bodyFont:{ family:'EstedadChart' },
                    titleFont:{ family:'EstedadChart' },
                    callbacks:{
                        label:function(context){
                            let price = formatPrice(context.parsed.y);
                            return "قیمت: " + price + " ریال ";
                        }
                    }
                }

            }

        }

    });

});
