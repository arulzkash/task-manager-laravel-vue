<script setup>
import { ref, watch, computed } from "vue";
import { router, Link, Head, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import debounce from "lodash/debounce";
import confetti from "canvas-confetti";

defineOptions({ layout: AppLayout });

const props = defineProps({
    quests: Object,
    filters: Object,
    typeOptions: Array,
});

const page = usePage();

// --- 1. FILTER LOGIC (AUTO RELOAD) ---
const form = ref({
    search: props.filters.search ?? "",
    status: props.filters.status ?? "",
    type: props.filters.type ?? "",
    repeatable: props.filters.repeatable ?? "",
    sort: props.filters.sort ?? "created_at",
    dir: props.filters.dir ?? "desc",
});

// Watcher: Auto-reload saat filter berubah (tunggu 300ms)
watch(
    form,
    debounce(() => {
        router.get("/quests", form.value, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300),
    { deep: true }
);

// --- 2. LEVEL UP LOGIC (Global Watcher) ---
const showLevelUpModal = ref(false);
// Ambil data profile dari Shared Props (Middleware)
const globalProfile = computed(() => page.props.auth.profile);
const previousLevel = ref(globalProfile.value?.level_data?.current_level || 1);

// Pantau perubahan pada Global Profile
watch(globalProfile, (newP) => {
    if (!newP) return;
    const newLevel = newP.level_data.current_level;
    
    // Jika level naik
    if (newLevel > previousLevel.value) {
        showLevelUpModal.value = true;
        triggerLevelUpConfetti();
        previousLevel.value = newLevel;
    }
}, { deep: true });

// --- 3. EDIT LOGIC ---
const editingQuest = ref(null);
const isEditCustomType = ref(false);

const editForm = useForm({
    name: "",
    status: "",
    type: "",
    xp_reward: 0,
    coin_reward: 0,
    due_date: null,
    is_repeatable: false,
});

const startEdit = (q) => {
    editingQuest.value = q;
    editForm.name = q.name;
    editForm.status = q.status;
    editForm.xp_reward = q.xp_reward;
    editForm.coin_reward = q.coin_reward;
    editForm.due_date = q.due_date;
    editForm.is_repeatable = !!q.is_repeatable;

    const standardTypes = ["Daily Grind", "Main Quest", "Side Quest", "Boss Fight", "Learning"];
    const availableOptions = [...new Set([...standardTypes, ...props.typeOptions])];

    if (availableOptions.includes(q.type)) {
        isEditCustomType.value = false;
        editForm.type = q.type;
    } else {
        isEditCustomType.value = true;
        editForm.type = q.type;
    }
};

const handleEditTypeChange = (e) => {
    if (e.target.value === 'Custom') {
        isEditCustomType.value = true;
        editForm.type = ""; 
    } else {
        editForm.type = e.target.value;
    }
};

const cancelEdit = () => {
    editingQuest.value = null;
    editForm.reset();
    isEditCustomType.value = false;
};

const saveEdit = () => {
    if (editForm.is_repeatable) editForm.due_date = null;
    editForm.patch(`/quests/${editingQuest.value.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

// --- 4. COMPLETION LOGIC ---
const completingQuest = ref(null);
const completionForm = useForm({ note: "" });

const openCompleteModal = (q) => {
    completingQuest.value = q;
    completionForm.note = "";
};

const closeCompleteModal = () => {
    completingQuest.value = null;
    completionForm.reset();
};

const submitComplete = () => {
    if (!completingQuest.value) return;
    const rewardXP = completingQuest.value.xp_reward;
    const rewardCoin = completingQuest.value.coin_reward;

    completionForm.patch(`/quests/${completingQuest.value.id}/complete`, {
        preserveScroll: true,
        onSuccess: () => {
            closeCompleteModal();
            triggerConfetti(); // Confetti biasa
            showToast(`✨ +${rewardXP} XP & +${rewardCoin} Gold!`);
        },
    });
};

// --- 5. VISUAL EFFECTS ---
// Confetti Biasa (Complete Quest)
const triggerConfetti = () => {
    confetti({
        particleCount: 150,
        spread: 70,
        origin: { y: 0.6 },
        colors: ['#34d399', '#fcd34d', '#ffffff']
    });
};

// Confetti HEBOH (Level Up)
const triggerLevelUpConfetti = () => {
    const duration = 3000;
    const end = Date.now() + duration;
    (function frame() {
        confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#fbbf24', '#f59e0b', '#ef4444'] });
        confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#3b82f6', '#8b5cf6', '#ec4899'] });
        if (Date.now() < end) requestAnimationFrame(frame);
    }());
};

const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-slate-800 border-l-4 border-emerald-500 text-white px-6 py-4 rounded shadow-2xl z-[100] animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>🎉</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
};

// --- HELPERS ---
const deleteQuest = (q) => {
    if (confirm(`Delete quest "${q.name}"?`)) {
        router.delete(`/quests/${q.id}`, { preserveScroll: true });
    }
};

const canComplete = (q) => {
    if (q.status === "locked") return false;
    if (!q.is_repeatable && q.status === "done") return false;
    return true;
};

const isOverdue = (dateString) => {
    if (!dateString) return false;
    const todayStr = new Date().toISOString().split('T')[0];
    return dateString < todayStr;
};

const statusColors = {
    todo: "bg-slate-700 text-slate-300 border-slate-600",
    in_progress: "bg-indigo-900/50 text-indigo-300 border-indigo-700 animate-pulse",
    done: "bg-emerald-900/50 text-emerald-400 border-emerald-700 opacity-75",
    locked: "bg-red-900/20 text-red-500 border-red-900/50 opacity-60",
};

const formatStatus = (s) => s.replace("_", " ").toUpperCase();
</script>

<template>
    <Head title="Quest Board" />

    <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-6 text-gray-200">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                    <span>📜</span> Quest Board
                </h1>
                <p class="text-slate-400 text-sm mt-1">Select a mission and claim your rewards.</p>
            </div>
            
            <div class="flex items-center gap-2 bg-slate-800 p-1 rounded-lg border border-slate-700">
                <select v-model="form.sort" class="input-dark w-full">
                    <option value="created_at">Date Added</option>
                    <option value="due_date">Due Date</option>
                    <option value="xp_reward">XP Reward</option>
                    <option value="coin_reward">Gold Reward</option>
                </select>
                <button @click="form.dir = form.dir === 'asc' ? 'desc' : 'asc'" class="px-2 py-1 hover:bg-slate-700 rounded text-xs transition-colors">
                    {{ form.dir === 'asc' ? '⬆️' : '⬇️' }}
                </button>
            </div>
        </div>

        <div class="bg-slate-800/80 p-4 rounded-xl border border-slate-700/50 backdrop-blur-sm grid grid-cols-2 md:grid-cols-4 gap-4 shadow-lg">
            <div class="col-span-2 md:col-span-1">
                <input v-model="form.search" placeholder="Search quest..." class="input-dark w-full" />
            </div>

            <div>
                <select v-model="form.status" class="input-dark w-full">
                    <option value="">All Status</option>
                    <option value="todo">To Do</option>
                    <option value="in_progress">In Progress</option>
                    <option value="done">Completed</option>
                    <option value="locked">Locked</option>
                </select>
            </div>

            <div>
                <select v-model="form.type" class="input-dark w-full">
                    <option value="">All Types</option>
                    <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>

            <div>
                <select v-model="form.repeatable" class="input-dark w-full">
                    <option value="">Any Mode</option>
                    <option value="1">Repeatable</option>
                    <option value="0">One-time</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end">
             <Link href="/dashboard" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-lg shadow-lg transition-colors flex items-center gap-2">
                <span>+ Create New Quest</span>
            </Link>
        </div>

        <div v-if="quests.data.length === 0" class="text-center py-20 border-2 border-dashed border-slate-700 rounded-2xl opacity-50">
            <div class="text-5xl mb-4">🕸️</div>
            <h3 class="text-xl font-bold text-slate-400">No Quests Found</h3>
            <p class="text-sm text-slate-500">Try adjusting your filters.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="quest in quests.data" 
                :key="quest.id"
                class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg hover:shadow-indigo-500/20 hover:border-indigo-500/50 transition-all duration-300 group flex flex-col relative overflow-hidden"
                :class="{'opacity-60 grayscale hover:grayscale-0': quest.status === 'done'}"
            >
                <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="{
                    'bg-red-500': quest.type === 'Boss Fight',
                    'bg-yellow-400': quest.type === 'Main Quest',
                    'bg-blue-400': quest.type === 'Side Quest',
                    'bg-emerald-400': quest.type === 'Daily Grind',
                    'bg-slate-500': !['Boss Fight', 'Main Quest', 'Side Quest', 'Daily Grind'].includes(quest.type)
                }"></div>

                <div class="p-5 flex-1 flex flex-col pl-6">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 border border-slate-700 px-2 py-0.5 rounded-full">
                            {{ quest.type }}
                        </span>
                        <span v-if="quest.is_repeatable" class="text-lg text-slate-500" title="Repeatable">↻</span>
                    </div>

                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-white leading-tight mb-2 group-hover:text-indigo-400 transition-colors">
                            {{ quest.name }}
                        </h3>
                        <span 
                            class="text-[10px] px-2 py-0.5 rounded border uppercase font-bold tracking-wider"
                            :class="statusColors[quest.status] || 'bg-slate-700 border-slate-600'"
                        >
                            {{ formatStatus(quest.status) }}
                        </span>
                    </div>

                    <div class="mt-auto pt-4 border-t border-slate-700/50 flex flex-col gap-2">
                        <div class="flex gap-4 text-xs font-mono">
                            <div class="flex items-center gap-1.5 text-indigo-400">
                                <span>✨</span><span class="font-bold">+{{ quest.xp_reward }} XP</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-yellow-500">
                                <span>💰</span><span class="font-bold">+{{ quest.coin_reward }} G</span>
                            </div>
                        </div>

                        <div v-if="quest.due_date" class="text-xs flex items-center gap-1" 
                             :class="isOverdue(quest.due_date) && quest.status !== 'done' ? 'text-red-400 font-bold animate-pulse' : 'text-slate-400'">
                            📅 {{ quest.due_date }}
                            <span v-if="isOverdue(quest.due_date) && quest.status !== 'done'">(OVERDUE)</span>
                        </div>
                        <div v-else class="text-xs text-slate-600 italic">No deadline</div>
                    </div>
                </div>

                <div class="bg-slate-900/50 px-4 py-3 flex justify-between items-center border-t border-slate-700/50">
                    <button 
                        v-if="canComplete(quest)"
                        @click="openCompleteModal(quest)"
                        class="text-xs bg-emerald-900/30 text-emerald-400 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded transition-colors font-bold uppercase"
                    >
                        Complete
                    </button>
                    <span v-else class="text-xs text-slate-600 italic">
                        {{ quest.status === 'done' ? 'Completed' : 'Locked' }}
                    </span>

                    <div class="flex gap-2">
                        <button @click="startEdit(quest)" class="text-slate-400 hover:text-white transition-colors" title="Edit">✏️</button>
                        <button @click="deleteQuest(quest)" class="text-slate-400 hover:text-red-400 transition-colors" title="Delete">🗑️</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="quests.links.length > 3" class="flex justify-center mt-8 gap-2">
            <Component
                v-for="(link, k) in quests.links"
                :key="k"
                :is="link.url ? Link : 'span'"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1 text-sm rounded border transition-colors"
                :class="{
                    'bg-indigo-600 border-indigo-500 text-white': link.active,
                    'bg-slate-800 border-slate-700 text-slate-400 hover:bg-slate-700': !link.active && link.url,
                    'text-slate-600 border-transparent opacity-50': !link.url
                }"
            />
        </div>

        <div v-if="editingQuest" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-slate-800 border border-slate-600 rounded-xl w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="bg-slate-900/50 px-6 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">Edit Quest</h3>
                    <button @click="cancelEdit" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <form @submit.prevent="saveEdit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs uppercase text-slate-400 font-bold mb-1">Quest Name</label>
                        <input v-model="editForm.name" class="input-dark w-full" />
                        <div v-if="editForm.errors.name" class="text-red-400 text-xs mt-1">{{ editForm.errors.name }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase text-slate-400 font-bold mb-1">Status</label>
                            <select v-model="editForm.status" class="input-dark w-full">
                                <option value="todo">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="locked">Locked</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs uppercase text-slate-400 font-bold mb-1">Type</label>
                            <div v-if="!isEditCustomType">
                                <select :value="editForm.type" @change="handleEditTypeChange" class="input-dark w-full">
                                    <option value="Daily Grind">Daily Grind</option>
                                    <option value="Main Quest">Main Quest</option>
                                    <option value="Side Quest">Side Quest</option>
                                    <option value="Boss Fight">Boss Fight</option>
                                    <option value="Learning">Learning</option>
                                    <option value="Custom" class="font-bold text-indigo-400">+ Custom Type...</option>
                                </select>
                            </div>
                            <div v-else class="flex gap-2">
                                <input v-model="editForm.type" placeholder="Type custom..." class="input-dark w-full border-indigo-500" autofocus />
                                <button type="button" @click="isEditCustomType = false; editForm.type = 'Daily Grind'" class="bg-slate-700 px-3 rounded text-slate-300 hover:text-white">✕</button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs uppercase text-slate-400 font-bold mb-1">XP</label>
                            <input type="number" v-model="editForm.xp_reward" class="input-dark w-full" />
                        </div>
                        <div>
                            <label class="block text-xs uppercase text-slate-400 font-bold mb-1">Gold</label>
                            <input type="number" v-model="editForm.coin_reward" class="input-dark w-full" />
                        </div>
                    </div>
                    <div class="bg-slate-900/50 p-3 rounded border border-slate-700/50 flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="editForm.is_repeatable" class="accent-indigo-500 h-4 w-4">
                            <span class="text-sm text-slate-300">Repeatable</span>
                        </label>
                        <div v-if="!editForm.is_repeatable" class="flex-1">
                            <input type="date" v-model="editForm.due_date" class="input-dark w-full text-sm" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-700">
                        <button type="button" @click="cancelEdit" class="px-4 py-2 text-slate-400 hover:text-white transition-colors">Cancel</button>
                        <button type="submit" :disabled="editForm.processing" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-lg font-bold shadow-lg transition-all">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="completingQuest" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-slate-800 border border-emerald-500/50 rounded-xl w-full max-w-md shadow-[0_0_30px_rgba(16,185,129,0.2)] overflow-hidden">
                <div class="bg-emerald-900/20 px-6 py-4 border-b border-emerald-500/30">
                    <h3 class="text-lg font-bold text-emerald-400 flex items-center gap-2"><span>✅</span> Mission Complete!</h3>
                </div>
                <form @submit.prevent="submitComplete" class="p-6 space-y-4">
                    <p class="text-slate-300 text-sm">Completing: <strong class="text-white">{{ completingQuest.name }}</strong></p>
                    <div>
                        <textarea v-model="completionForm.note" rows="3" placeholder="Log your victory..." class="input-dark w-full resize-none" autofocus></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeCompleteModal" class="px-4 py-2 text-slate-400 hover:text-white transition-colors">Cancel</button>
                        <button type="submit" :disabled="completionForm.processing" class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-2 rounded-lg font-bold shadow-lg transition-all flex items-center gap-2"><span>Claim Rewards</span></button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="showLevelUpModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-sm animate-fade-in">
            <div class="text-center relative">
                <div class="absolute inset-0 bg-yellow-500 blur-[100px] opacity-20 rounded-full animate-pulse"></div>
                <h1 class="text-8xl md:text-9xl font-black text-transparent bg-clip-text bg-gradient-to-b from-yellow-300 via-yellow-500 to-yellow-700 drop-shadow-[0_0_25px_rgba(234,179,8,0.8)] animate-bounce-in relative z-10">LEVEL UP!</h1>
                <div class="mt-8 text-white text-4xl font-bold tracking-widest uppercase animate-slide-up relative z-10">
                    You reached Level <span class="text-yellow-400 text-6xl">{{ globalProfile.level_data.current_level }}</span>
                </div>
                <button @click="showLevelUpModal = false" class="mt-12 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-4 px-12 rounded-full text-xl shadow-[0_0_30px_rgba(99,102,241,0.6)] transform hover:scale-105 transition-all duration-300 relative z-10">AWESOME!</button>
            </div>
        </div>

    </div>
</template>

<style scoped>
.input-dark { @apply bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-indigo-500 transition-all placeholder-slate-500; }
.animate-fade-in { animation: fadeIn 0.2s ease-out; }
.animate-bounce-in { animation: bounce-in 0.8s cubic-bezier(0.215, 0.610, 0.355, 1.000) both; }
.animate-slide-up { animation: slide-up 0.8s ease-out 0.5s both; }
@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
@keyframes bounce-in { 0% { transform: scale(0.3); opacity: 0; } 50% { transform: scale(1.05); } 70% { transform: scale(0.9); } 100% { transform: scale(1); opacity: 1; } }
@keyframes slide-up { 0% { transform: translateY(50px); opacity: 0; } 100% { transform: translateY(0); opacity: 1; } }
input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.6; cursor: pointer; }
</style>