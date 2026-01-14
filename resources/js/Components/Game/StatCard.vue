<script setup>
defineProps({
    label: String, // e.g., "Gold Coins"
    value: [Number, String], // e.g., 1250
    icon: String, // Optional emoji or icon class
    colorClass: String, // e.g., "text-yellow-400"
});
</script>

<template>
    <div
        class="bg-slate-800 p-4 rounded-xl border border-slate-700 hover:border-slate-500 transition-colors flex flex-col items-center justify-center min-w-[100px]"
    >
        <div class="text-xs text-slate-400 uppercase tracking-widest mb-1">
            {{ label }}
        </div>

        <div
            class="text-2xl font-bold flex items-center gap-2"
            :class="colorClass"
        >
            <span v-if="icon" class="text-xl filter drop-shadow-md">{{
                icon
            }}</span>
            {{ value }}
        </div>
    </div>
</template>
