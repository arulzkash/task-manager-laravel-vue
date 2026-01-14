<script setup>
import { computed } from "vue";

const props = defineProps({
    current: Number,
    max: Number,
    percent: Number,
});

// Computed property for the width style
const widthStyle = computed(() => {
    // Clamp the percentage between 0 and 100 to prevent overflow visual bugs
    const safePercent = Math.min(Math.max(props.percent, 0), 100);
    return { width: `${safePercent}%` };
});
</script>

<template>
    <div class="w-full">
        <div
            class="w-full h-6 bg-slate-800 rounded-full border border-slate-700 shadow-inner overflow-hidden relative"
        >
            <div
                class="h-full bg-gradient-to-r from-blue-600 to-cyan-400 transition-all duration-700 ease-out shadow-[0_0_10px_rgba(34,211,238,0.7)]"
                :style="widthStyle"
            >
                <div class="w-full h-[50%] bg-white opacity-20"></div>
            </div>
        </div>

        <div
            class="flex justify-between items-center mt-1 text-xs font-mono tracking-wider text-slate-400"
        >
            <span>LVL UP PROGRESS</span>
            <span>
                <span class="text-cyan-400 font-bold">{{ current }}</span>
                <span class="opacity-50">/</span>
                {{ max }} XP
            </span>
        </div>
    </div>
</template>
