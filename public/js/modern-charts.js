/**
 * MODERN APEXCHARTS CONFIGURATION
 * Configuración mejorada para gráficas del dashboard SGP
 */

// Paleta de Colores Corporativos
const modernColors = {
    primary: '#1e2b58',
    accent: '#F59E0B',
    success: '#10B981',
    warning: '#F59E0B',
    danger: '#EF4444',
    info: '#3B82F6',
    purple: '#8B5CF6',
    pink: '#EC4899'
};

/**
 * Configuración Base para Gráficas de Barras
 */
function getModernBarChartConfig(categories, seriesData, title = '') {
    return {
        series: [{
            name: seriesData.name || 'Valor',
            data: seriesData.data
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: false
            },
            fontFamily: 'Plus Jakarta Sans, Inter, sans-serif',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                borderRadiusApplication: 'end',
                columnWidth: '60%',
                distributed: true,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        colors: [
            modernColors.accent,
            modernColors.info,
            modernColors.success,
            modernColors.purple,
            modernColors.pink,
            modernColors.warning
        ],
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px',
                    fontWeight: 500
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                },
                formatter: function (val) {
                    return Math.floor(val);
                }
            }
        },
        grid: {
            show: false
        },
        tooltip: {
            theme: 'light',
            style: {
                fontSize: '13px',
                fontFamily: 'Plus Jakarta Sans, Inter, sans-serif'
            },
            y: {
                formatter: function (val) {
                    return val;
                }
            }
        },
        legend: {
            show: false
        },
        title: {
            text: title,
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 600,
                color: '#1F2937'
            }
        }
    };
}

/**
 * Configuración para Gráficas Radiales (KPI)
 */
function getModernRadialChartConfig(value, label, color = modernColors.accent) {
    return {
        series: [value],
        chart: {
            height: 120,
            type: 'radialBar'
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    size: '65%'
                },
                track: {
                    background: '#F3F4F6',
                    strokeWidth: '100%'
                },
                dataLabels: {
                    name: {
                        show: false
                    },
                    value: {
                        fontSize: '20px',
                        fontWeight: 700,
                        color: '#1F2937',
                        offsetY: 8,
                        formatter: function (val) {
                            return Math.floor(val) + '%';
                        }
                    }
                }
            }
        },
        colors: [color],
        stroke: {
            lineCap: 'round'
        }
    };
}

/**
 * Configuración para Gráficas de Línea
 */
function getModernLineChartConfig(categories, seriesData, title = '') {
    return {
        series: seriesData,
        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: false
            },
            fontFamily: 'Plus Jakarta Sans, Inter, sans-serif',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: [modernColors.accent, modernColors.info, modernColors.success],
        dataLabels: {
            enabled: false
        },
        markers: {
            size: 0,
            hover: {
                size: 6
            }
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                }
            },
            axisBorder: {
                show: false
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#6B7280',
                    fontSize: '13px'
                }
            }
        },
        grid: {
            show: false
        },
        tooltip: {
            theme: 'light'
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '13px',
            fontWeight: 500
        },
        title: {
            text: title,
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 600,
                color: '#1F2937'
            }
        }
    };
}

/**
 * Configuración para Gráficas de Dona
 */
function getModernDonutChartConfig(series, labels, title = '') {
    return {
        series: series,
        chart: {
            type: 'donut',
            height: 350,
            fontFamily: 'Plus Jakarta Sans, Inter, sans-serif'
        },
        labels: labels,
        colors: [
            modernColors.accent,
            modernColors.info,
            modernColors.success,
            modernColors.purple,
            modernColors.pink
        ],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '14px',
                            fontWeight: 500
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            position: 'bottom',
            fontSize: '13px'
        },
        title: {
            text: title,
            align: 'left',
            style: {
                fontSize: '18px',
                fontWeight: 600,
                color: '#1F2937'
            }
        }
    };
}

// Exportar para uso global
window.getModernBarChartConfig = getModernBarChartConfig;
window.getModernRadialChartConfig = getModernRadialChartConfig;
window.getModernLineChartConfig = getModernLineChartConfig;
window.getModernDonutChartConfig = getModernDonutChartConfig;
window.modernColors = modernColors;
