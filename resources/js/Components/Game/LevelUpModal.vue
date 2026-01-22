<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    currentLevel: {
        type: Number,
        default: 1,
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div
        v-if="modelValue"
        class="animate-fade-in fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm"
    >
        <div class="relative text-center">
            <div class="absolute inset-0 animate-pulse rounded-full bg-yellow-500 opacity-20 blur-[100px]"></div>
            <h1
                class="animate-bounce-in relative z-10 bg-gradient-to-b from-yellow-300 via-yellow-500 to-yellow-700 bg-clip-text text-8xl font-black text-transparent drop-shadow-[0_0_25px_rgba(234,179,8,0.8)] md:text-9xl"
            >
                LEVEL UP!
            </h1>
            <div
                class="animate-slide-up relative z-10 mt-8 text-4xl font-bold uppercase tracking-widest text-white"
            >
                You reached Level
                <span class="text-6xl text-yellow-400">{{ currentLevel }}</span>
            </div>
            <button
                @click="emit('update:modelValue', false)"
                class="relative z-10 mt-12 transform rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 px-12 py-4 text-xl font-bold text-white shadow-[0_0_30px_rgba(99,102,241,0.6)] transition-all duration-300 hover:scale-105 hover:from-indigo-500 hover:to-purple-500"
            >
                AWESOME!
            </button>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
.animate-bounce-in {
    animation: bounce-in 0.8s cubic-bezier(0.215, 0.61, 0.355, 1) both;
}
.animate-slide-up {
    animation: slide-up 0.8s ease-out 0.5s both;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes bounce-in {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
@keyframes slide-up {
    0% {
        transform: translateY(50px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
