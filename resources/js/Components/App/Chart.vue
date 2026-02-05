<script setup>
import { ref, onMounted, watch, nextTick } from "vue";

/**
 * Props:
 * - type: "bar" | "pie"
 * - labels: string[]
 * - values: number[]
 * - height: number (px)
 */
const props = defineProps({
    type: { type: String, default: "bar" },
    labels: { type: Array, default: () => [] },
    values: { type: Array, default: () => [] },
    height: { type: Number, default: 260 },
});

const canvas = ref(null);

// simple color palette
function color(i, a = 1) {
    const h = (i * 53) % 360;
    return `hsla(${h} 72% 46% / ${a})`;
}

function dprSize(c, w, h) {
    // crisp lines on retina
    const dpr = window.devicePixelRatio || 1;
    c.width = Math.floor(w * dpr);
    c.height = Math.floor(h * dpr);
    const ctx = c.getContext("2d");
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    return ctx;
}

function drawBar(ctx, w, h, labels, values) {
    ctx.clearRect(0, 0, w, h);

    const pad = 16;
    const axisY = h - 36; // leave room for labels
    const max = Math.max(1, ...values);
    const n = values.length || 1;
    const gap = 10;
    const barW = Math.max(8, (w - pad * 2 - gap * (n - 1)) / n);

    // axis line
    ctx.strokeStyle = "rgba(15,23,42,.25)";
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(pad, axisY + 0.5);
    ctx.lineTo(w - pad, axisY + 0.5);
    ctx.stroke();

    // bars
    for (let i = 0; i < n; i++) {
        const x = pad + i * (barW + gap);
        const val = values[i] || 0;
        const hRatio = val / max;
        const bh = Math.max(4, Math.round((axisY - pad) * hRatio));
        const y = axisY - bh;

        // bar
        const r = 6;
        ctx.fillStyle = color(i);
        // rounded rect
        const right = x + barW;
        ctx.beginPath();
        ctx.moveTo(x, y + r);
        ctx.arcTo(x, y, x + r, y, r);
        ctx.arcTo(right, y, right, y + r, r);
        ctx.lineTo(right, axisY);
        ctx.lineTo(x, axisY);
        ctx.closePath();
        ctx.fill();

        // value
        ctx.fillStyle = "rgba(15,23,42,.75)";
        ctx.font =
            "12px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto";
        ctx.textAlign = "center";
        ctx.fillText(String(val), x + barW / 2, y - 6);

        // label
        ctx.fillStyle = "rgba(15,23,42,.6)";
        const lbl = (labels[i] || "").toString();
        const t = lbl.length > 10 ? lbl.slice(0, 9) + "…" : lbl;
        ctx.fillText(t, x + barW / 2, axisY + 16);
    }
}

function drawPie(ctx, w, h, labels, values) {
    ctx.clearRect(0, 0, w, h);

    const total = values.reduce((s, v) => s + (v || 0), 0) || 1;
    const cx = Math.round(w / 2);
    const cy = Math.round(h / 2);
    const r = Math.min(w, h) / 2 - 12;

    let start = -Math.PI / 2;
    for (let i = 0; i < values.length; i++) {
        const v = values[i] || 0;
        const angle = (v / total) * Math.PI * 2;
        const end = start + angle;

        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, start, end);
        ctx.closePath();
        ctx.fillStyle = color(i, 0.9);
        ctx.fill();

        start = end;
    }

    // simple legend below
    const legendY = h - 10;
    let x = 16;
    ctx.font = "12px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto";
    for (let i = 0; i < labels.length; i++) {
        const sw = 10;
        ctx.fillStyle = color(i, 0.9);
        ctx.fillRect(x, legendY - sw, sw, sw);
        x += sw + 6;

        ctx.fillStyle = "rgba(15,23,42,.7)";
        const txt = `${labels[i] || "—"} (${values[i] ?? 0})`;
        ctx.fillText(txt, x, legendY);
        x += ctx.measureText(txt).width + 14;
        if (x > w - 80) break; // keep it tidy
    }
}

async function render() {
    await nextTick();
    const el = canvas.value;
    if (!el) return;

    const w = el.clientWidth || 320;
    const h = props.height;
    const ctx = dprSize(el, w, h);

    if (props.type === "pie") {
        drawPie(ctx, w, h, props.labels, props.values);
    } else {
        drawBar(ctx, w, h, props.labels, props.values);
    }
}

onMounted(render);
watch(() => [props.type, props.labels, props.values, props.height], render, {
    deep: true,
});
</script>

<template>
    <div class="chart-wrap">
        <canvas ref="canvas" class="block w-full rounded-lg bg-white"></canvas>
    </div>
</template>

<style scoped>
.chart-wrap {
    width: 100%;
    min-height: v-bind(height + "px");
}
</style>
