<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, router, Head } from "@inertiajs/vue3";
import confetti from "canvas-confetti";

defineOptions({ layout: AppLayout });

const props = defineProps({
    habit: Object,
    month: String, // YYYY-MM
    weeks: Array, // Struktur data tetap sama dari backend
});

// --- NAVIGATION ---
const goMonth = (ym) => {
    router.get(
        `/habits/${props.habit.id}`,
        { month: ym },
        { preserveScroll: true, preserveState: true }
    );
};

const prevMonth = () => {
    const [y, m] = props.month.split("-").map(Number);
    // Logic mundur 1 bulan
    const d = new Date(y, m - 2, 1); 
    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    goMonth(ym);
};

const nextMonth = () => {
    const [y, m] = props.month.split("-").map(Number);
    // Logic maju 1 bulan
    const d = new Date(y, m, 1);
    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    goMonth(ym);
};

// --- ACTION ---
const toggleDate = (date, allowed, currentDoneStatus) => {
    if (!allowed) return;
    
    router.patch(
        `/habits/${props.habit.id}/entries/toggle`,
        { date },
        { 
            preserveScroll: true,
            onSuccess: () => {
                // Kalau status berubah jadi DONE, mainkan efek confetti kecil
                if (!currentDoneStatus) {
                    triggerMiniConfetti();
                }
            }
        }
    );
};

// --- VISUALS ---
const triggerMiniConfetti = () => {
    confetti({
        particleCount: 30,
        spread: 50,
        origin: { y: 0.6 },
        colors: ['#34d399', '#ffffff'],
        disableForReducedMotion: true
    });
};

// Helper format bulan (2026-02 -> February 2026)
const formatMonthName = (ym) => {
    const [y, m] = ym.split("-").map(Number);
    const date = new Date(y, m - 1, 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head :title="`Log: ${habit.name}`" />

    <div class="p-4 md:p-8 max-w-4xl mx-auto space-y-6 text-gray-200">
        
        <div class="flex items-center gap-4">
            <Link href="/habits" class="bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white w-10 h-10 flex items-center justify-center rounded-lg transition-colors shadow-md">
                ⬅
            </Link>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">{{ habit.name }}</h1>
                <div class="text-xs text-slate-500 font-mono mt-1">
                    Start: <span class="text-slate-300">{{ habit.start_date }}</span>
                    <span v-if="habit.end_date" class="ml-2">| End: <span class="text-red-400">{{ habit.end_date }}</span></span>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-xl">
            
            <div class="flex justify-between items-center mb-6">
                <button @click="prevMonth" class="p-2 hover:bg-slate-700 rounded-lg text-slate-400 hover:text-white transition-colors">
                    ◀ Prev
                </button>
                <h2 class="text-xl font-bold text-emerald-400 uppercase tracking-widest">
                    {{ formatMonthName(month) }}
                </h2>
                <button @click="nextMonth" class="p-2 hover:bg-slate-700 rounded-lg text-slate-400 hover:text-white transition-colors">
                    Next ▶
                </button>
            </div>

            <div class="w-full">
                <div class="grid grid-cols-7 mb-2 text-center">
                    <div v-for="day in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="day" class="text-xs font-bold text-slate-500 uppercase">
                        {{ day }}
                    </div>
                </div>

                <div class="space-y-1"> <div v-for="(week, wi) in weeks" :key="wi" class="grid grid-cols-7 gap-1 md:gap-2">
                        
                        <div v-for="(cell, ci) in week" :key="ci" class="relative aspect-square">
                            
                            <div v-if="!cell" class="w-full h-full"></div>

                            <button 
                                v-else
                                @click="toggleDate(cell.date, cell.allowed, cell.done)"
                                :disabled="!cell.allowed"
                                class="w-full h-full rounded-lg border flex flex-col items-center justify-center transition-all duration-200 group relative overflow-hidden"
                                :class="{
                                    // DONE STATE (Green & Glowing)
                                    'bg-emerald-600 border-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.4)] text-white': cell.done,
                                    
                                    // ALLOWED BUT NOT DONE (Gray & Hoverable)
                                    'bg-slate-700/50 border-slate-600 hover:bg-slate-600 hover:border-slate-500 text-slate-300': !cell.done && cell.allowed,
                                    
                                    // DISABLED / LOCKED (Dark & Transparent)
                                    'bg-slate-900/30 border-transparent text-slate-600 cursor-not-allowed opacity-50': !cell.allowed
                                }"
                            >
                                <span class="text-sm md:text-lg font-bold z-10">{{ cell.day }}</span>
                                
                                <span v-if="cell.done" class="absolute inset-0 flex items-center justify-center text-4xl opacity-20 transform -rotate-12 select-none">✓</span>

                                <span v-if="!cell.allowed" class="absolute bottom-1 right-1 text-[10px] opacity-50">🔒</span>
                            </button>

                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-center gap-6 text-xs text-slate-400 border-t border-slate-700 pt-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-emerald-600 rounded border border-emerald-500"></div>
                    <span>Completed</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-slate-700 rounded border border-slate-600"></div>
                    <span>Pending</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-slate-900/50 rounded border border-transparent opacity-50"></div>
                    <span>Locked</span>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Aspect Ratio Fallback for older browsers (optional, Tailwind usually handles it) */
.aspect-square {
    aspect-ratio: 1 / 1;
}
</style>