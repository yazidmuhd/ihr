<!-- resources/js/Components/App/ChartEnhanced.vue -->
<script setup>
import { ref, onMounted, watch, computed } from "vue";
import {
    Chart,
    BarController,
    PieController,
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";

// Register Chart.js components
Chart.register(
    BarController,
    PieController,
    ArcElement,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
);

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ["bar", "pie"].includes(value),
    },
    labels: {
        type: Array,
        default: () => [],
    },
    values: {
        type: Array,
        default: () => [],
    },
    height: {
        type: Number,
        default: 300,
    },
});

const canvasRef = ref(null);
let chartInstance = null;

// Modern color palettes
const barColors = {
    background: [
        "rgba(16, 185, 129, 0.9)", // Green
        "rgba(245, 158, 11, 0.9)", // Orange
        "rgba(239, 68, 68, 0.9)", // Red
        "rgba(59, 130, 246, 0.9)", // Blue
        "rgba(139, 92, 246, 0.9)", // Purple
        "rgba(236, 72, 153, 0.9)", // Pink
    ],
    border: [
        "rgba(16, 185, 129, 1)",
        "rgba(245, 158, 11, 1)",
        "rgba(239, 68, 68, 1)",
        "rgba(59, 130, 246, 1)",
        "rgba(139, 92, 246, 1)",
        "rgba(236, 72, 153, 1)",
    ],
};

const pieColors = {
    background: [
        "rgba(239, 68, 68, 0.85)", // Red
        "rgba(245, 158, 11, 0.85)", // Yellow
        "rgba(16, 185, 129, 0.85)", // Green
        "rgba(59, 130, 246, 0.85)", // Blue
        "rgba(139, 92, 246, 0.85)", // Purple
        "rgba(236, 72, 153, 0.85)", // Pink
    ],
    border: [
        "rgba(239, 68, 68, 1)",
        "rgba(245, 158, 11, 1)",
        "rgba(16, 185, 129, 1)",
        "rgba(59, 130, 246, 1)",
        "rgba(139, 92, 246, 1)",
        "rgba(236, 72, 153, 1)",
    ],
};

const createChart = () => {
    if (!canvasRef.value) return;

    // Destroy existing chart
    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = canvasRef.value.getContext("2d");

    const isBar = props.type === "bar";
    const colors = isBar ? barColors : pieColors;

    const config = {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: [
                {
                    data: props.values,
                    backgroundColor: colors.background,
                    borderColor: colors.border,
                    borderWidth: isBar ? 2 : 3,
                    borderRadius: isBar ? 12 : 0,
                    hoverBorderWidth: isBar ? 3 : 4,
                    hoverBorderColor: colors.border,
                    hoverOffset: isBar ? 0 : 15,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: !isBar,
                    position: "bottom",
                    labels: {
                        padding: 20,
                        font: {
                            size: 13,
                            family: "'Plus Jakarta Sans', sans-serif",
                            weight: "600",
                        },
                        color: "#0f172a",
                        usePointStyle: true,
                        pointStyle: "circle",
                        boxWidth: 12,
                        boxHeight: 12,
                    },
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: "rgba(15, 23, 42, 0.95)",
                    titleColor: "#f8fafc",
                    bodyColor: "#f8fafc",
                    titleFont: {
                        size: 14,
                        family: "'Plus Jakarta Sans', sans-serif",
                        weight: "700",
                    },
                    bodyFont: {
                        size: 13,
                        family: "'Plus Jakarta Sans', sans-serif",
                        weight: "600",
                    },
                    padding: 12,
                    borderColor: "rgba(6, 182, 212, 0.5)",
                    borderWidth: 2,
                    cornerRadius: 8,
                    displayColors: true,
                    boxWidth: 12,
                    boxHeight: 12,
                    boxPadding: 6,
                    caretSize: 8,
                    callbacks: {
                        label: function (context) {
                            let label = context.label || "";
                            if (label) {
                                label += ": ";
                            }
                            label +=
                                context.parsed.y !== undefined
                                    ? context.parsed.y
                                    : context.parsed;
                            return label;
                        },
                    },
                },
            },
            ...(isBar
                ? {
                      scales: {
                          x: {
                              grid: {
                                  display: false,
                              },
                              ticks: {
                                  color: "#64748b",
                                  font: {
                                      size: 12,
                                      family: "'Plus Jakarta Sans', sans-serif",
                                      weight: "600",
                                  },
                              },
                              border: {
                                  display: false,
                              },
                          },
                          y: {
                              beginAtZero: true,
                              grid: {
                                  color: "rgba(148, 163, 184, 0.1)",
                                  lineWidth: 1,
                              },
                              ticks: {
                                  color: "#64748b",
                                  font: {
                                      size: 12,
                                      family: "'Plus Jakarta Sans', sans-serif",
                                      weight: "600",
                                  },
                                  padding: 10,
                                  precision: 0,
                              },
                              border: {
                                  display: false,
                                  dash: [5, 5],
                              },
                          },
                      },
                      animation: {
                          duration: 1500,
                          easing: "easeInOutQuart",
                          delay: (context) => {
                              return context.dataIndex * 100;
                          },
                      },
                  }
                : {
                      animation: {
                          animateRotate: true,
                          animateScale: true,
                          duration: 1500,
                          easing: "easeInOutQuart",
                      },
                  }),
            interaction: {
                mode: "index",
                intersect: false,
            },
        },
    };

    chartInstance = new Chart(ctx, config);
};

onMounted(() => {
    createChart();
});

watch(
    () => [props.labels, props.values, props.type],
    () => {
        createChart();
    },
    { deep: true },
);
</script>

<template>
    <div class="chart-container" :style="{ height: `${height}px` }">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap");

.chart-container {
    position: relative;
    width: 100%;
}

canvas {
    max-height: 100%;
}
</style>
