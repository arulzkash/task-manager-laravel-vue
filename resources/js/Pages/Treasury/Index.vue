<script setup>
import { Link, useForm, router, Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import confetti from "canvas-confetti";

defineOptions({ layout: AppLayout });

const props = defineProps({
    profile: Object,
    rewards: Array,
    errors: Object,
});

// --- CREATE LOGIC ---
const showCreateForm = ref(false);
const createForm = useForm({
    name: "",
    cost_coin: 100,
});

const submitCreate = () => {
    createForm.post('/treasury/rewards', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
            showToast("🎁 New Item added to shop!");
        },
    });
};

// --- BUY LOGIC (MODAL) ---
const buyingReward = ref(null);
const buyForm = useForm({
    qty: 1,
    note: "",
});

const openBuyModal = (reward) => {
    buyingReward.value = reward;
    buyForm.qty = 1;
    buyForm.note = "";
};

const closeBuyModal = () => {
    buyingReward.value = null;
    buyForm.reset();
};

const totalCost = computed(() => {
    if (!buyingReward.value) return 0;
    return buyingReward.value.cost_coin * buyForm.qty;
});

const canAfford = computed(() => {
    return props.profile.coin_balance >= totalCost.value;
});

const submitBuy = () => {
    if (!buyingReward.value || !canAfford.value) return;

    buyForm.patch(`/treasury/rewards/${buyingReward.value.id}/buy`, {
        preserveScroll: true,
        onSuccess: () => {
            closeBuyModal();
            triggerCoinSound(); // Visual effect only
            showToast(`🛍️ Purchased ${buyForm.qty}x ${buyingReward.value.name}`);
        },
        onError: () => {
            // Handle error saldo kurang dari backend (backup)
            alert("Not enough coins!");
        }
    });
};

// --- EDIT LOGIC (MODAL) ---
const editingReward = ref(null);
const editForm = useForm({ name: "", cost_coin: 0 });

const startEdit = (r) => {
    editingReward.value = r;
    editForm.name = r.name;
    editForm.cost_coin = r.cost_coin;
};

const submitEdit = () => {
    editForm.patch(`/treasury/rewards/${editingReward.value.id}`, {
        preserveScroll: true,
        onSuccess: () => editingReward.value = null,
    });
};

const deleteReward = (r) => {
    if (confirm(`Remove "${r.name}" from shop?`)) {
        router.delete(`/treasury/rewards/${r.id}`, { preserveScroll: true });
    }
};

// --- VISUALS ---
const showToast = (message) => {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-slate-800 border-l-4 border-yellow-500 text-white px-6 py-4 rounded shadow-2xl z-[100] animate-bounce font-bold flex items-center gap-2';
    toast.innerHTML = `<span>💰</span> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};

// Simulasi efek suara/visual
const triggerCoinSound = () => {
    // Bisa tambah audio di sini nanti
    confetti({
        particleCount: 50,
        spread: 40,
        origin: { y: 0.6 },
        colors: ['#fbbf24', '#f59e0b'] // Gold colors
    });
};
</script>

<template>
    <Head title="Treasury" />

    <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-8 text-gray-200">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
                    <span>🛒</span> The Treasury
                </h1>
                <p class="text-slate-400 text-sm mt-1">Exchange your hard-earned gold for rewards.</p>
            </div>
            
            <div class="bg-slate-800 border border-yellow-500/30 rounded-xl px-6 py-3 flex items-center gap-3 shadow-[0_0_20px_rgba(234,179,8,0.2)]">
                <span class="text-3xl">🪙</span>
                <div>
                    <div class="text-xs text-slate-400 uppercase tracking-widest font-bold">Current Balance</div>
                    <div class="text-2xl font-black text-yellow-400 font-mono">{{ profile?.coin_balance ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div v-if="showCreateForm" class="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-lg animate-fade-in">
            <h3 class="text-lg font-bold text-white mb-4">Stock New Item</h3>
            <form @submit.prevent="submitCreate" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1 w-full">
                    <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Item Name</label>
                    <input v-model="createForm.name" placeholder="e.g. 1 Hour Gaming" class="input-dark w-full" autofocus />
                    <div v-if="createForm.errors.name" class="text-red-400 text-xs mt-1">{{ createForm.errors.name }}</div>
                </div>
                <div class="w-full md:w-32">
                    <label class="text-xs font-bold text-slate-500 uppercase mb-1 block">Price</label>
                    <input type="number" v-model="createForm.cost_coin" class="input-dark w-full" />
                </div>
                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" :disabled="createForm.processing" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 px-6 rounded-lg transition-all">
                        Add Item
                    </button>
                    <button type="button" @click="showCreateForm = false" class="px-4 py-2.5 bg-slate-700 hover:bg-slate-600 rounded-lg text-slate-300">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <div v-else class="flex justify-end">
            <button @click="showCreateForm = true" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-600 rounded-lg text-sm text-slate-300 transition-colors flex items-center gap-2">
                <span>+ Stock New Item</span>
            </button>
        </div>

        <div v-if="rewards.length === 0" class="text-center py-16 opacity-50">
            <div class="text-5xl mb-4">🕸️</div>
            <h3 class="text-xl font-bold text-slate-400">Shop is Empty</h3>
            <p class="text-sm text-slate-500">Stock some rewards to motivate yourself.</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div 
                v-for="reward in rewards" 
                :key="reward.id"
                class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg hover:border-yellow-500/50 hover:shadow-[0_0_20px_rgba(234,179,8,0.1)] transition-all group relative overflow-hidden flex flex-col"
            >
                <div class="h-32 bg-slate-900/50 flex items-center justify-center text-6xl group-hover:scale-110 transition-transform duration-500">
                    🎁
                </div>

                <div class="p-5 flex-1 flex flex-col">
                    <h3 class="text-lg font-bold text-white mb-1 group-hover:text-yellow-400 transition-colors">{{ reward.name }}</h3>
                    
                    <div class="mt-auto pt-4 flex items-center justify-between">
                        <div class="text-yellow-500 font-mono font-bold text-xl flex items-center gap-1">
                            <span>🪙</span> {{ reward.cost_coin }}
                        </div>
                        
                        <button 
                            @click="openBuyModal(reward)"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider shadow-lg active:scale-95 transition-all"
                        >
                            Buy
                        </button>
                    </div>
                </div>

                <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="startEdit(reward)" class="p-1.5 bg-slate-700/80 hover:bg-indigo-600 rounded text-slate-300 hover:text-white" title="Edit">✏️</button>
                    <button @click="deleteReward(reward)" class="p-1.5 bg-slate-700/80 hover:bg-red-600 rounded text-slate-300 hover:text-white" title="Delete">🗑️</button>
                </div>
            </div>
        </div>

        <div v-if="buyingReward" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-slate-800 border border-yellow-500/30 rounded-xl w-full max-w-md shadow-[0_0_40px_rgba(234,179,8,0.1)] overflow-hidden">
                <div class="bg-slate-900/50 px-6 py-4 border-b border-slate-700">
                    <h3 class="text-lg font-bold text-white">Purchase Item</h3>
                </div>
                
                <form @submit.prevent="submitBuy" class="p-6 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-slate-700 rounded-lg flex items-center justify-center text-3xl">🎁</div>
                        <div>
                            <div class="text-slate-400 text-xs uppercase font-bold">Buying</div>
                            <div class="text-xl font-bold text-white">{{ buyingReward.name }}</div>
                            <div class="text-yellow-500 font-mono text-sm">Price: {{ buyingReward.cost_coin }} coins</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase text-slate-500 font-bold mb-1">Quantity</label>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="buyForm.qty = Math.max(1, buyForm.qty - 1)" class="w-10 h-10 bg-slate-700 rounded hover:bg-slate-600 text-white font-bold">-</button>
                            <input type="number" v-model="buyForm.qty" class="input-dark text-center font-bold text-xl w-20" min="1" />
                            <button type="button" @click="buyForm.qty++" class="w-10 h-10 bg-slate-700 rounded hover:bg-slate-600 text-white font-bold">+</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase text-slate-500 font-bold mb-1">Usage Note (Optional)</label>
                        <textarea v-model="buyForm.note" rows="2" placeholder="e.g. 'Watched Favorite Streamer...'" class="input-dark w-full resize-none"></textarea>
                    </div>

                    <div class="bg-slate-900/50 p-4 rounded-lg flex justify-between items-center border border-slate-700">
                        <div>
                            <div class="text-xs text-slate-500">Total Cost</div>
                            <div class="text-xl font-bold font-mono" :class="canAfford ? 'text-white' : 'text-red-400'">
                                🪙 {{ totalCost }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-500">Your Balance</div>
                            <div class="text-sm font-bold text-yellow-500">{{ profile.coin_balance }}</div>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="closeBuyModal" class="px-4 py-2 text-slate-400 hover:text-white transition-colors">Cancel</button>
                        <button 
                            type="submit" 
                            :disabled="!canAfford || buyForm.processing"
                            class="bg-yellow-600 hover:bg-yellow-500 text-white px-6 py-2 rounded-lg font-bold shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <span v-if="canAfford">Confirm Purchase</span>
                            <span v-else>Insufficient Funds</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="editingReward" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-slate-800 border border-slate-600 rounded-xl w-full max-w-sm shadow-2xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Edit Reward</h3>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Name</label>
                        <input v-model="editForm.name" class="input-dark w-full" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cost</label>
                        <input type="number" v-model="editForm.cost_coin" class="input-dark w-full" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="editingReward = null" class="text-slate-400 hover:text-white">Cancel</button>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded font-bold">Save</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>

<style scoped>
.input-dark {
    @apply bg-slate-900 border border-slate-700 text-slate-200 rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-yellow-500 transition-all placeholder-slate-500;
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>